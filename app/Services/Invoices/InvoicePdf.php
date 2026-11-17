<?php

namespace App\Services\Invoices;

/**
 * Draws one invoice / credit note. Coordinates are measured from the reference PDFs (A4, points).
 * Amounts in the data array are integer cents.
 */
class InvoicePdf
{
    private const SELLER = 'ELENA SMIRNOVA E.I.';

    private const SELLER_ADDRESS = ['31 Av du Beau Site', '77 400 Lagny – sur – Marne'];

    private const FOOTER = 'TEL: +33 7 44 83 81 87 / EMAIL: lenanailsmd@gmail.com';

    private const X1 = 56.693;

    private const X2 = 345.829;

    private const X3 = 442.208;

    private const X4 = 538.587;

    private const BOTTOM = 105;

    private const BLACK = [0, 0, 0];

    private const GREY = [0.8, 0.8, 0.8];

    private static ?TtfFont $regular = null;

    private static ?TtfFont $bold = null;

    private PdfWriter $pdf;

    public static function render(array $inv): string
    {
        return (new self)->draw($inv);
    }

    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    private function draw(array $inv): string
    {
        $dir = resource_path('invoice');
        // Fonts are parsed once per process; glyph usage is tracked per document, so clone state is reset here.
        self::$regular = new TtfFont($dir.'/OpenSans-Regular.ttf');
        self::$bold = new TtfFont($dir.'/OpenSans-Bold.ttf');

        $pdf = $this->pdf = new PdfWriter(title: $inv['title'].' '.$inv['number']);
        $pdf->addFont('OS', self::$regular);
        $pdf->addFont('OSB', self::$bold);
        $pdf->setImage(file_get_contents($dir.'/logo_rgb.z'), file_get_contents($dir.'/logo_mask.z'), 1106, 200);

        $pdf->image(57.443, 760.780, 287.637, 52.014);
        $pdf->text(346.579, 802.007, self::SELLER, 'OSB', 9);
        $pdf->text(346.579, 788.523, self::SELLER_ADDRESS[0], 'OS', 9);
        $pdf->text(346.579, 775.039, self::SELLER_ADDRESS[1], 'OS', 9);
        $pdf->text(56.693, 715.227, $inv['title'], 'OSB', 16);

        $left = [];
        foreach ($inv['address'] as $line) {
            array_push($left, ...$this->wrap($line, 'OS', 9, 287));
        }
        $y = 686.820;
        foreach ($left as $line) {
            $pdf->text(57.443, $y, $line, 'OS', 9);
            $y -= 13.4838;
        }

        $info = [
            [$inv['label'].' Number:', $inv['number']],
            [$inv['label'].' Date:', $inv['date']],
            ['Order Number:', $inv['order_number']],
            ['Order Date:', $inv['order_date']],
            ['Payment Method:', $inv['payment']],
        ];
        $y = 686.070;
        foreach ($info as [$label, $value]) {
            $pdf->text(347.329, $y, $label, 'OS', 9);
            $pdf->text(442.958, $y, $value, 'OS', 9);
            $y -= 14.98375;
        }

        $top = 593.216 - max(0.0, count($left) * 13.4838 - 5 * 14.98375);
        $yg = $this->tableHeader($top);

        foreach ($inv['items'] as [$name, $qty, $price]) {
            $nameLines = $this->wrap($name, 'OS', 9, 275);
            $rowH = 26.984 + 13.483 * (count($nameLines) - 1);
            if ($yg - $rowH < self::BOTTOM) {
                $yg = $this->newPage(true);
            }
            $base = $yg - 14.537;
            $yg -= $rowH;
            foreach ($nameLines as $k => $line) {
                $pdf->text(60.068, $base - 13.483 * $k, $line, 'OS', 9);
            }
            $pdf->text(349.204, $base, (string) $qty, 'OS', 9);
            $pdf->text(445.583, $base, self::money($price), 'OS', 9);
            $this->cells($yg, 0.75, self::GREY, [[self::X1, self::X2], [self::X2, self::X3], [self::X3, self::X4]]);
        }

        if ($yg - 78 < self::BOTTOM) {
            $yg = $this->newPage(false);
        }

        $pdf->text(349.204, $yg - 14.537, 'Subtotal', 'OSB', 9);
        $pdf->text(445.583, $yg - 14.537, self::money($inv['subtotal']), 'OS', 9);
        $yg -= 20.984;

        if ($inv['shipping'] !== null) {
            $this->cells($yg, 0.75, self::GREY, [[self::X2, self::X3], [self::X3, self::X4]]);
            $pdf->text(349.204, $yg - 14.537, 'Shipping', 'OSB', 9);
            $amount = self::money($inv['shipping']);
            $pdf->text(445.583, $yg - 14.537, $amount, 'OS', 9);
            $x = 445.583 + $pdf->textWidth($amount, 'OS', 9) + $pdf->textWidth(' ', 'OS', 9);
            $yy = $yg - 14.537;
            $rows = 1;
            foreach (explode(' ', 'via '.$inv['ship_label']) as $word) {
                $ww = $pdf->textWidth($word, 'OS', 8);
                if ($x > 445.584 && $x + $ww > 535.209) {
                    $x = 445.583;
                    $yy -= 13.483;
                    $rows++;
                }
                $pdf->text($x, $yy, $word, 'OS', 8);
                $x += $ww + $pdf->textWidth(' ', 'OS', 8);
            }
            $yg -= 21.359 + 13.483 * ($rows - 1);
        }

        $this->cells($yg, 1.5, self::BLACK, [[self::X2, self::X3], [self::X3, self::X4]]);
        $this->cells($yg - 21.734, 1.5, self::BLACK, [[self::X2, self::X3], [self::X3, self::X4]]);
        $pdf->text(349.204, $yg - 14.912, 'Total', 'OSB', 9);
        $pdf->text(445.583, $yg - 14.912, self::money($inv['total']), 'OSB', 9);

        $this->footer();

        return $pdf->output();
    }

    private function cells(float $y, float $w, array $rgb, array $spans): void
    {
        foreach ($spans as [$a, $b]) {
            $this->pdf->line($a, $b, $y, $w, $rgb);
        }
    }

    private function tableHeader(float $top): float
    {
        foreach ([[self::X1, self::X2], [self::X2, self::X3], [self::X3, self::X4]] as [$a, $b]) {
            $this->pdf->rect($a, $top - 20.608, $b - $a, 20.234, self::BLACK);
            $this->pdf->line($a, $b, $top, 0.75, self::BLACK);
            $this->pdf->line($a, $b, $top - 20.983, 0.75, self::BLACK);
        }
        $white = [1, 1, 1];
        $this->pdf->text(60.068, $top - 14.537, 'Product', 'OSB', 9, $white);
        $this->pdf->text(349.204, $top - 14.537, 'Quantity', 'OSB', 9, $white);
        $this->pdf->text(445.583, $top - 14.537, 'Price', 'OSB', 9, $white);

        return $top - 20.983;
    }

    private function newPage(bool $withHeader): float
    {
        $this->footer();
        $this->pdf->newPage();

        return $withHeader ? $this->tableHeader(785.0) : 785.0;
    }

    private function footer(): void
    {
        $this->pdf->line(self::X1, self::X4, 90.850, 0.283, [0.502, 0.502, 0.502]);
        $w = $this->pdf->textWidth(self::FOOTER, 'OS', 9);
        $this->pdf->text(($this->pdf->width - $w) / 2, 74.252, self::FOOTER, 'OS', 9);
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, float $size, float $max): array
    {
        $lines = [];
        $cur = '';
        foreach (explode(' ', $text) as $word) {
            $try = $cur === '' ? $word : $cur.' '.$word;
            if ($cur !== '' && $this->pdf->textWidth($try, $font, $size) > $max) {
                $lines[] = $cur;
                $cur = $word;
            } else {
                $cur = $try;
            }
        }
        $lines[] = $cur;

        return $lines;
    }
}
