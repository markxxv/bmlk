<?php

namespace App\Services\Invoices;

use RuntimeException;

/**
 * Minimal TrueType reader: just enough (cmap format 4, hmtx) to measure text and
 * embed the font in a PDF without any third-party PDF library.
 */
class TtfFont
{
    public readonly string $data;

    public readonly int $unitsPerEm;

    /** @var array<int, int> glyph id => advance width (font units) */
    private array $advances = [];

    /** @var array<int, int> unicode code point => glyph id */
    private array $cmap = [];

    private int $numGlyphs = 0;

    /** @var array<int, int> */
    private array $used = [];

    public function __construct(public readonly string $path)
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("Cannot read font {$path}");
        }
        $this->data = $data;

        $tables = [];
        $count = $this->u16(4);
        for ($i = 0; $i < $count; $i++) {
            $o = 12 + $i * 16;
            $tables[substr($data, $o, 4)] = [$this->u32($o + 8), $this->u32($o + 12)];
        }

        $this->unitsPerEm = $this->u16($tables['head'][0] + 18);
        $this->numGlyphs = $this->u16($tables['maxp'][0] + 4);
        $metrics = $this->u16($tables['hhea'][0] + 34);

        $hmtx = $tables['hmtx'][0];
        $last = 0;
        for ($g = 0; $g < $this->numGlyphs; $g++) {
            if ($g < $metrics) {
                $last = $this->u16($hmtx + $g * 4);
            }
            $this->advances[$g] = $last;
        }

        $this->parseCmap($tables['cmap'][0]);
    }

    private function parseCmap(int $base): void
    {
        $n = $this->u16($base + 2);
        $sub = null;
        for ($i = 0; $i < $n; $i++) {
            $o = $base + 4 + $i * 8;
            $platform = $this->u16($o);
            $enc = $this->u16($o + 2);
            $off = $base + $this->u32($o + 4);
            if ($this->u16($off) === 4 && (($platform === 3 && $enc === 1) || $platform === 0)) {
                $sub = $off;
                break;
            }
        }
        if ($sub === null) {
            throw new RuntimeException('Unsupported font cmap');
        }

        $segX2 = $this->u16($sub + 6);
        $segs = intdiv($segX2, 2);
        $endO = $sub + 14;
        $startO = $endO + $segX2 + 2;
        $deltaO = $startO + $segX2;
        $rangeO = $deltaO + $segX2;
        for ($s = 0; $s < $segs; $s++) {
            $end = $this->u16($endO + $s * 2);
            $start = $this->u16($startO + $s * 2);
            $delta = $this->u16($deltaO + $s * 2);
            $range = $this->u16($rangeO + $s * 2);
            if ($start === 0xFFFF) {
                continue;
            }
            for ($c = $start; $c <= $end; $c++) {
                if ($range === 0) {
                    $g = ($c + $delta) & 0xFFFF;
                } else {
                    $p = $rangeO + $s * 2 + $range + ($c - $start) * 2;
                    $g = $this->u16($p);
                    if ($g !== 0) {
                        $g = ($g + $delta) & 0xFFFF;
                    }
                }
                if ($g !== 0) {
                    $this->cmap[$c] = $g;
                }
            }
        }
    }

    private function u16(int $o): int
    {
        return (ord($this->data[$o]) << 8) | ord($this->data[$o + 1]);
    }

    private function u32(int $o): int
    {
        return ($this->u16($o) << 16) | $this->u16($o + 2);
    }

    /** @return list<int> code points, unsupported ones replaced by "?" */
    public function codePoints(string $text): array
    {
        $out = [];
        foreach (mb_str_split($text) as $ch) {
            $cp = mb_ord($ch, 'UTF-8');
            if ($cp < 32) {
                continue;
            }
            $out[] = isset($this->cmap[$cp]) ? $cp : 63;
        }

        return $out;
    }

    public function width(string $text, float $size): float
    {
        $sum = 0;
        foreach ($this->codePoints($text) as $cp) {
            $sum += $this->advances[$this->cmap[$cp] ?? 0] ?? 0;
        }

        return $sum * $size / $this->unitsPerEm;
    }

    /** Hex string of 2-byte glyph ids for a PDF Identity-H font; records usage. */
    public function hex(string $text): string
    {
        $hex = '';
        foreach ($this->codePoints($text) as $cp) {
            $g = $this->cmap[$cp] ?? 0;
            $this->used[$g] = $cp;
            $hex .= sprintf('%04X', $g);
        }

        return $hex;
    }

    /** @return array<int, int> glyph id => code point, for glyphs written so far */
    public function usedGlyphs(): array
    {
        return $this->used;
    }

    public function advance(int $gid): int
    {
        return $this->advances[$gid] ?? 0;
    }

    public function numGlyphs(): int
    {
        return $this->numGlyphs;
    }
}
