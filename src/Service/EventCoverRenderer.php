<?php
declare(strict_types=1);

namespace App\Service;

use App\Utility\EventDefaults;
use Cake\ORM\Table;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class EventCoverRenderer
{
    public function ensure($event, Table $eventsTable): void
    {
        $cover = (string)($event->cover ?? '');
        $coverDir = (string)($event->cover_dir ?? '');
        $hasCoverFile = $cover !== ''
            && $coverDir !== ''
            && is_file(ROOT . DS . $coverDir . $cover);

        if ($hasCoverFile) {
            return;
        }

        $relativeDir = 'webroot/files/Events/cover/generated/' . $event->id . '/';
        $absoluteDir = ROOT . DS . str_replace('/', DS, $relativeDir);
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $filename = 'eventic-cover.png';
        $this->render($event, $absoluteDir . $filename, 1600, 900, false);
        $this->render($event, $absoluteDir . 'card-' . $filename, 960, 420, true);
        $this->render($event, $absoluteDir . 'thumbnail-' . $filename, 80, 80, true);

        $eventsTable->updateAll([
            'cover' => $filename,
            'cover_dir' => $relativeDir,
        ], ['id' => $event->id]);

        $event->cover = $filename;
        $event->cover_dir = $relativeDir;
    }

    private function render($event, string $path, int $width, int $height, bool $compact): void
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->create($width, $height);
        $primary = $this->imageHex((string)($event->primary_color ?: EventDefaults::PRIMARY_COLOR), '76132c');
        $accent = $this->imageHex((string)($event->accent_color ?: EventDefaults::ACCENT_COLOR), 'c99a3f');
        $deep = '4d0d1f';
        $paper = 'ffffff';
        $muted = 'e5e9ef';

        $image->fill('f5f7fa');
        $image->drawRectangle(0, 0, function ($rectangle) use ($width, $height, $primary) {
            $rectangle->width($width)->height($height);
            $rectangle->background($primary);
        });
        $image->drawRectangle(0, 0, function ($rectangle) use ($width, $height, $deep) {
            $rectangle->width((int)round($width * .56))->height($height);
            $rectangle->background($deep);
        });
        $image->drawRectangle((int)round($width * .58), 0, function ($rectangle) use ($width, $height, $paper) {
            $rectangle->width((int)round($width * .42))->height($height);
            $rectangle->background($paper);
        });

        $stripeHeight = max(6, (int)round($height * .015));
        $image->drawRectangle((int)round($width * .08), (int)round($height * .74), function ($rectangle) use ($width, $stripeHeight, $accent) {
            $rectangle->width((int)round($width * .72))->height($stripeHeight);
            $rectangle->background($accent);
        });

        for ($i = 0; $i < 12; $i++) {
            $x = (int)round($width * .62) + ($i * max(12, (int)round($width * .025)));
            $y = (int)round($height * .18) + (($i % 5) * max(12, (int)round($height * .07)));
            $size = max(4, (int)round($width * .01));
            $image->drawRectangle($x, $y, function ($rectangle) use ($size, $accent) {
                $rectangle->width($size)->height($size);
                $rectangle->background($accent);
            });
        }

        $font = $this->fontPath();
        if (!$font) {
            $image->save($path);

            return;
        }

        $brandSize = max(14, (int)round($height * .045));
        $titleSize = $compact ? max(22, (int)round($height * .105)) : max(38, (int)round($height * .09));
        $subtitleSize = max(12, (int)round($height * .03));
        $left = max(24, (int)round($width * .07));
        $top = max(24, (int)round($height * .12));

        $image->text('EventIC', $left, $top, function ($fontStyle) use ($font, $brandSize, $accent) {
            $fontStyle->file($font)->size($brandSize)->color($accent);
        });
        $image->text(__('Pase digital y control de acceso'), $left, $top + (int)round($brandSize * 1.45), function ($fontStyle) use ($font, $subtitleSize, $muted) {
            $fontStyle->file($font)->size($subtitleSize)->color($muted);
        });
        $this->writeText($image, (string)$event->name, $left, (int)round($height * .38), (int)round($width * .48), $titleSize, $paper, $font, $compact ? 2 : 3);

        if (!$compact) {
            $details = [
                $event->event_date ? $event->event_date->format('d/m/Y H:i') : __('Fecha por confirmar'),
                trim((string)($event->location ?? '')) ?: __('Ubicación por confirmar'),
            ];
            $this->writeText($image, implode('  /  ', $details), $left, (int)round($height * .80), (int)round($width * .7), $subtitleSize, $paper, $font, 2);
        }

        $image->save($path);
    }

    private function writeText($image, string $text, int $x, int $y, int $maxWidth, int $size, string $color, string $font, int $maxLines): void
    {
        $layout = ImageText::fit($text, $maxWidth, $size, $maxLines, $font);
        $size = $layout['size'];
        foreach ($layout['lines'] as $index => $line) {
            $image->text($line, $x, $y + ($index * (int)round($size * 1.18)), function ($fontStyle) use ($font, $size, $color) {
                $fontStyle->file($font)->size($size)->color($color);
            });
        }
    }

    private function imageHex(string $value, string $fallback): string
    {
        $value = ltrim(trim($value), '#');

        return preg_match('/^[a-f0-9]{6}$/i', $value) ? $value : $fallback;
    }

    private function fontPath(): string
    {
        return ImageText::fontPath();
    }
}
