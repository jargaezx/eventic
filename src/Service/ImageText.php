<?php
declare(strict_types=1);

namespace App\Service;

use Endroid\QrCode\Label\Font\NotoSans;

/** Shared, portable typography for generated passes and event covers. */
class ImageText
{
    public static function fontPath(): string
    {
        return (new NotoSans())->getPath();
    }

    public static function width(string $text, int $size, string $font): int
    {
        // Intervention Image 3's GD FontProcessor converts pixel sizes by 0.76.
        $box = imageftbbox($size * 0.76, 0, $font, $text);

        return (int)ceil(max($box[0], $box[2], $box[4], $box[6]) - min($box[0], $box[2], $box[4], $box[6]));
    }

    public static function fit(string $text, int $width, int $size, int $maxLines, string $font): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        $minimum = max(12, (int)floor($size * 0.7));
        do {
            $lines = self::wrap($text, $width, $size, $font);
            if (count($lines) <= $maxLines || $size <= $minimum) {
                break;
            }
            $size--;
        } while (true);
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = rtrim($lines[$maxLines - 1]);
            while ($last !== '' && self::width($last . '…', $size, $font) > $width) {
                $last = mb_substr($last, 0, -1);
            }
            $lines[$maxLines - 1] = rtrim($last) . '…';
        }

        return ['lines' => $lines, 'size' => $size];
    }

    private static function wrap(string $text, int $width, int $size, string $font): array
    {
        $lines = [];
        $line = '';
        foreach (explode(' ', $text) as $word) {
            $candidate = ltrim($line . ' ' . $word);
            if (self::width($candidate, $size, $font) <= $width) {
                $line = $candidate;
                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }
            preg_match_all('/\X/u', $word, $characters);
            foreach ($characters[0] as $character) {
                if ($line !== '' && self::width($line . $character, $size, $font) > $width) {
                    $lines[] = $line;
                    $line = '';
                }
                $line .= $character;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }
}
