<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Event;
use App\Model\Entity\Ticket;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class TicketRenderer
{
    private const CANVAS_WIDTH = 1280;
    private const CANVAS_HEIGHT = 720;

    public function renderPreview(Event $event): string
    {
        return $this->compose($event, 'PREVIEW')->toPng()->toDataUri();
    }

    public function renderTicket(Event $event, Ticket $ticket): string
    {
        $image = $this->compose($event, $ticket->id, $ticket);
        $directory = WWW_ROOT . 'files' . DS . 'tickets' . DS;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory . $ticket->id . '.png';
        $image->save($path);

        return $path;
    }

    private function compose(Event $event, string $qrContent, ?Ticket $ticket = null)
    {
        $configuration = $event->ticket_configuration;
        $manager = new ImageManager(new Driver());
        $image = $this->baseImage($manager, $event);

        $writer = new PngWriter();
        $qrSize = max(180, min(280, (int)($configuration->qr_size ?? 240)));
        $qrCode = QrCode::create($qrContent)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize($qrSize)
            ->setMargin(2)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $this->applyTicketBranding($image, $event, $ticket);
        $qrImage = $manager->read($writer->write($qrCode)->getString());
        $defaultQrX = self::CANVAS_WIDTH - $qrSize - 88;
        $defaultQrY = 264;
        $configuredQrX = isset($configuration->x) ? (int)$configuration->x : null;
        $configuredQrY = isset($configuration->y) ? (int)$configuration->y : null;
        $hasValidConfiguredPosition = $configuredQrX !== null
            && $configuredQrY !== null
            && $configuredQrX >= 0
            && $configuredQrX <= (self::CANVAS_WIDTH - $qrSize)
            && $configuredQrY >= 0
            && $configuredQrY <= (self::CANVAS_HEIGHT - $qrSize);
        $qrX = $hasValidConfiguredPosition ? $configuredQrX : $defaultQrX;
        $qrY = $hasValidConfiguredPosition ? $configuredQrY : $defaultQrY;

        $image->place($qrImage, 'top-left', $qrX, $qrY);

        return $image;
    }

    private function baseImage(ImageManager $manager, Event $event)
    {
        $configuration = $event->ticket_configuration;
        if ($configuration && $configuration->ticket && $configuration->ticket_dir) {
            $templatePath = ROOT . DS . $configuration->ticket_dir . $configuration->ticket;
            if (is_file($templatePath)) {
                return $manager->read($templatePath)->resize(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
            }
        }

        $image = $manager->create(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        $image->fill('f8fafc');

        return $image;
    }

    private function applyTicketBranding($image, Event $event, ?Ticket $ticket = null): void
    {
        $width = $image->width();
        $height = $image->height();
        $primary = ltrim((string)($event->primary_color ?: '1c63f2'), '#');
        $accent = ltrim((string)($event->accent_color ?: '0ea5a4'), '#');
        $font = $this->fontPath();

        $image->drawRectangle(0, 0, function ($rectangle) use ($width, $primary) {
            $rectangle->width($width)->height(164);
            $rectangle->background($primary);
        });
        $image->drawRectangle(0, $height - 34, function ($rectangle) use ($width, $accent) {
            $rectangle->width($width)->height(34);
            $rectangle->background($accent);
        });
        $image->drawRectangle(58, 218, function ($rectangle) {
            $rectangle->width(760)->height(384);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(874, 218, function ($rectangle) {
            $rectangle->width(346)->height(384);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(902, 246, function ($rectangle) {
            $rectangle->width(290)->height(290);
            $rectangle->background('f8fafc');
        });

        if (!$font) {
            return;
        }

        $this->writeWrapped($image, (string)$event->name, 58, 56, 760, 42, 'ffffff', $font, 2);
        $image->text(__('Pase digital'), 60, 124, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(20)->color('dbeafe');
        });

        if ($ticket) {
            $folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
            $image->text(__('Folio'), 96, 280, function ($fontStyle) use ($font) {
                $fontStyle->file($font)->size(18)->color('64748b');
            });
            $image->text($folio, 96, 342, function ($fontStyle) use ($font, $primary) {
                $fontStyle->file($font)->size(58)->color($primary);
            });
            $this->writeWrapped($image, (string)$ticket->name, 96, 418, 650, 32, '111827', $font, 2);
            $this->writeWrapped($image, (string)$ticket->email, 96, 506, 650, 22, '475569', $font, 1);
        } else {
            $image->text(__('Vista previa'), 96, 342, function ($fontStyle) use ($font, $primary) {
                $fontStyle->file($font)->size(48)->color($primary);
            });
        }

        $image->text(__('Escanea para validar acceso'), 920, 574, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(18)->color('475569');
        });
        $image->text((string)$event->event_date, 60, $height - 68, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(20)->color('ffffff');
        });
    }

    private function writeWrapped($image, string $text, int $x, int $y, int $maxWidth, int $size, string $color, string $font, int $maxLines): void
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';
        $maxChars = max(16, (int)floor($maxWidth / max(8, $size * 0.56)));

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if (mb_strlen($candidate) > $maxChars && $line !== '') {
                $lines[] = $line;
                $line = $word;
                continue;
            }
            $line = $candidate;
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        $lines = array_slice($lines, 0, $maxLines);
        foreach ($lines as $index => $wrappedLine) {
            $image->text($wrappedLine, $x, $y + ($index * (int)round($size * 1.25)), function ($fontStyle) use ($font, $size, $color) {
                $fontStyle->file($font)->size($size)->color($color);
            });
        }
    }

    private function fontPath(): ?string
    {
        $paths = [
            'C:\\Windows\\Fonts\\arial.ttf',
            WWW_ROOT . 'assets' . DS . 'fonts' . DS . 'MaterialIcons-Regular.ttf',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
