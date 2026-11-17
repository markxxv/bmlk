<?php

namespace App\Services\Invoices;

/**
 * Tiny dependency-free PDF writer: A4 pages, text in embedded TrueType fonts,
 * lines, filled rectangles and one embedded RGB+alpha image (the logo).
 */
class PdfWriter
{
    /** @var list<string> page content streams */
    private array $pages = [];

    private string $buf = '';

    /** @var array<string, TtfFont> */
    private array $fonts = [];

    private ?array $image = null;

    private bool $imageUsed = false;

    public function __construct(
        public readonly float $width = 595.28,
        public readonly float $height = 841.89,
        private string $title = '',
    ) {}

    public function addFont(string $key, TtfFont $font): void
    {
        $this->fonts[$key] = $font;
    }

    /** @param string $rgbZ zlib-compressed raw RGB, $maskZ zlib-compressed 8-bit alpha */
    public function setImage(string $rgbZ, string $maskZ, int $w, int $h): void
    {
        $this->image = compact('rgbZ', 'maskZ', 'w', 'h');
    }

    public function text(float $x, float $y, string $text, string $font, float $size, array $rgb = [0, 0, 0]): void
    {
        $hex = $this->fonts[$font]->hex($text);
        if ($hex === '') {
            return;
        }
        $this->buf .= sprintf("BT %s rg /%s %s Tf %s %s Td <%s> Tj ET\n",
            $this->rgb($rgb), $font, $this->n($size), $this->n($x), $this->n($y), $hex);
    }

    public function textWidth(string $text, string $font, float $size): float
    {
        return $this->fonts[$font]->width($text, $size);
    }

    public function line(float $x1, float $x2, float $y, float $w, array $rgb): void
    {
        $this->buf .= sprintf("%s RG %s w %s %s m %s %s l S\n",
            $this->rgb($rgb), $this->n($w), $this->n($x1), $this->n($y), $this->n($x2), $this->n($y));
    }

    public function rect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->buf .= sprintf("%s rg %s %s %s %s re f\n",
            $this->rgb($rgb), $this->n($x), $this->n($y), $this->n($w), $this->n($h));
    }

    public function image(float $x, float $y, float $w, float $h): void
    {
        $this->imageUsed = true;
        $this->buf .= sprintf("q %s 0 0 %s %s %s cm /Im1 Do Q\n", $this->n($w), $this->n($h), $this->n($x), $this->n($y));
    }

    public function newPage(): void
    {
        $this->pages[] = $this->buf;
        $this->buf = '';
    }

    public function output(): string
    {
        if ($this->buf !== '' || $this->pages === []) {
            $this->newPage();
        }

        /** @var array<int, string> $objs */
        $objs = [];
        $add = function (string $body) use (&$objs): int {
            $objs[] = $body;

            return count($objs);
        };
        $stream = fn (string $dict, string $data): string => "<< {$dict} /Length ".strlen($data)." >>\nstream\n{$data}\nendstream";

        $catalogId = $add('');
        $pagesId = $add('');

        // Fonts
        $fontRefs = [];
        foreach ($this->fonts as $key => $font) {
            $file = $add($stream('/Filter /FlateDecode /Length1 '.strlen($font->data), gzcompress($font->data, 9)));
            $name = 'AAAAAA+'.preg_replace('/[^A-Za-z0-9]/', '', $key);
            $desc = $add("<< /Type /FontDescriptor /FontName /{$name} /Flags 32 /FontBBox [-600 -300 1300 1100] "
                ."/ItalicAngle 0 /Ascent 1069 /Descent -293 /CapHeight 714 /StemV 80 /FontFile2 {$file} 0 R >>");

            $scale = 1000 / $font->unitsPerEm;
            $gids = array_keys($font->usedGlyphs());
            sort($gids);
            $w = '';
            foreach ($gids as $g) {
                $w .= sprintf('%d [%d] ', $g, round($font->advance($g) * $scale));
            }
            $cid = $add("<< /Type /Font /Subtype /CIDFontType2 /BaseFont /{$name} "
                .'/CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> '
                ."/FontDescriptor {$desc} 0 R /CIDToGIDMap /Identity /DW 600 /W [{$w}] >>");

            $map = '';
            foreach ($font->usedGlyphs() as $g => $cp) {
                $map .= sprintf("<%04X> <%04X>\n", $g, $cp);
            }
            $cmap = "/CIDInit /ProcSet findresource begin 12 dict begin begincmap\n"
                ."/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n"
                ."/CMapName /Adobe-Identity-UCS def /CMapType 2 def\n1 begincodespacerange <0000> <FFFF> endcodespacerange\n"
                .count($font->usedGlyphs())." beginbfchar\n{$map}endbfchar\nendcmap CMapName currentdict /CMap defineresource pop end end";
            $uni = $add($stream('', $cmap));

            $fontRefs[$key] = $add("<< /Type /Font /Subtype /Type0 /BaseFont /{$name} /Encoding /Identity-H "
                ."/DescendantFonts [{$cid} 0 R] /ToUnicode {$uni} 0 R >>");
        }

        $xobj = '';
        if ($this->image && $this->imageUsed) {
            $i = $this->image;
            $mask = $add($stream("/Type /XObject /Subtype /Image /Width {$i['w']} /Height {$i['h']} "
                .'/ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode', $i['maskZ']));
            $img = $add($stream("/Type /XObject /Subtype /Image /Width {$i['w']} /Height {$i['h']} "
                ."/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /SMask {$mask} 0 R", $i['rgbZ']));
            $xobj = "/XObject << /Im1 {$img} 0 R >>";
        }

        $fontDict = '';
        foreach ($fontRefs as $key => $ref) {
            $fontDict .= "/{$key} {$ref} 0 R ";
        }
        $resources = "<< /Font << {$fontDict}>> {$xobj} >>";

        $kids = [];
        foreach ($this->pages as $content) {
            $c = $add($stream('/Filter /FlateDecode', gzcompress($content, 9)));
            $kids[] = $add("<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$this->n($this->width)} {$this->n($this->height)}] "
                ."/Resources {$resources} /Contents {$c} 0 R >>").' 0 R';
        }

        $objs[$catalogId - 1] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        $objs[$pagesId - 1] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        $info = $add('<< /Title <FEFF'.bin2hex(mb_convert_encoding($this->title, 'UTF-16BE', 'UTF-8')).'> /Producer (BMLK) >>');

        $out = "%PDF-1.5\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $i => $body) {
            $offsets[$i + 1] = strlen($out);
            $out .= ($i + 1)." 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $out .= sprintf("%010d 00000 n \n", $off);
        }
        $out .= 'trailer << /Size '.(count($objs) + 1)." /Root {$catalogId} 0 R /Info {$info} 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $out;
    }

    private function rgb(array $c): string
    {
        return $this->n($c[0]).' '.$this->n($c[1]).' '.$this->n($c[2]);
    }

    private function n(float|int $v): string
    {
        return rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.') ?: '0';
    }
}
