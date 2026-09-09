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

    public function renderEditorTemplate(Event $event): string
    {
        $manager = new ImageManager(new Driver());
        $image = $this->baseImage($manager, $event);

        if (!$this->templatePath($event)) {
            $this->applyTicketBranding($image, $event);
        }

        return $image->toPng()->toDataUri();
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
        $usesCustomTemplate = (bool)$this->templatePath($event);

        $writer = new PngWriter();
        $configuredQrSize = (int)($configuration->qr_size ?? 0);
        $qrSize = $configuredQrSize >= 180 ? min(280, $configuredQrSize) : 240;
        $qrCode = QrCode::create($qrContent)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize($qrSize)
            ->setMargin(2)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        if (!$usesCustomTemplate) {
            $this->applyTicketBranding($image, $event, $ticket);
        }

        $qrImage = $manager->read($writer->write($qrCode)->getString());
        $defaultQrX = 930 + (int)round((300 - $qrSize) / 2);
        $defaultQrY = 210 + (int)round((300 - $qrSize) / 2);
        $configuredQrX = isset($configuration->x) ? (int)$configuration->x : null;
        $configuredQrY = isset($configuration->y) ? (int)$configuration->y : null;
        $hasValidConfiguredPosition = $configuredQrX !== null
            && $configuredQrY !== null
            && $configuredQrX > 0
            && $configuredQrX <= (self::CANVAS_WIDTH - $qrSize)
            && $configuredQrY > 0
            && $configuredQrY <= (self::CANVAS_HEIGHT - $qrSize);
        $qrX = $hasValidConfiguredPosition ? $configuredQrX : $defaultQrX;
        $qrY = $hasValidConfiguredPosition ? $configuredQrY : $defaultQrY;

        $image->place($qrImage, 'top-left', $qrX, $qrY);

        return $image;
    }

    private function baseImage(ImageManager $manager, Event $event)
    {
        $templatePath = $this->templatePath($event);
        if ($templatePath) {
            return $manager->read($templatePath)->resize(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        }

        $image = $manager->create(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        $image->fill('f8fafc');

        return $image;
    }

    private function templatePath(Event $event): ?string
    {
        $configuration = $event->ticket_configuration;
        if (!$configuration || !$configuration->ticket || !$configuration->ticket_dir) {
            return null;
        }

        $templatePath = ROOT . DS . $configuration->ticket_dir . $configuration->ticket;

        return is_file($templatePath) ? $templatePath : null;
    }

    private function applyTicketBranding($image, Event $event, ?Ticket $ticket = null): void
    {
        $width = $image->width();
        $height = $image->height();
        $primary = $this->cleanHexColor((string)($event->primary_color ?: '#76132c'), '76132c');
        $primaryDark = '4d0d1f';
        $ink = '17202a';
        $muted = '687385';
        $accent = $this->cleanHexColor((string)($event->accent_color ?: '#c99a3f'), 'c99a3f');
        $font = $this->fontPath();
        $eventDate = $this->formatEventDate($event);
        $eventTime = $this->formatEventTime($event);
        $location = trim((string)($event->location ?? '')) ?: __('Ubicacion por confirmar');

        $image->drawRectangle(0, 0, function ($rectangle) use ($width) {
            $rectangle->width($width)->height(720);
            $rectangle->background('f5f7fa');
        });
        $image->drawRectangle(0, 0, function ($rectangle) use ($height, $primary) {
            $rectangle->width(360)->height($height);
            $rectangle->background($primary);
        });
        $image->drawRectangle(24, 24, function ($rectangle) use ($primaryDark) {
            $rectangle->width(312)->height(672);
            $rectangle->background($primaryDark);
        });
        $image->drawRectangle(48, 54, function ($rectangle) {
            $rectangle->width(86)->height(10);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(48, 74, function ($rectangle) use ($accent) {
            $rectangle->width(58)->height(10);
            $rectangle->background($accent);
        });
        $image->drawRectangle(408, 48, function ($rectangle) {
            $rectangle->width(780)->height(86);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(408, 166, function ($rectangle) {
            $rectangle->width(420)->height(376);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(880, 166, function ($rectangle) {
            $rectangle->width(348)->height(376);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(930, 210, function ($rectangle) {
            $rectangle->width(300)->height(300);
            $rectangle->background('f8fafc');
        });
        $image->drawRectangle(408, 590, function ($rectangle) use ($accent) {
            $rectangle->width(820)->height(8);
            $rectangle->background($accent);
        });
        $image->drawRectangle(408, 616, function ($rectangle) {
            $rectangle->width(250)->height(82);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(690, 616, function ($rectangle) {
            $rectangle->width(190)->height(82);
            $rectangle->background('ffffff');
        });
        $image->drawRectangle(912, 616, function ($rectangle) {
            $rectangle->width(316)->height(82);
            $rectangle->background('ffffff');
        });

        if (!$font) {
            return;
        }

        $image->text('EventIC', 48, 136, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(30)->color('ffffff');
        });
        $image->text(__('Pase digital'), 48, 180, function ($fontStyle) use ($font, $accent) {
            $fontStyle->file($font)->size(20)->color($accent);
        });
        $this->writeWrapped($image, (string)$event->name, 48, 438, 250, 42, 'ffffff', $font, 3);
        $image->text(__('Acceso validado por QR'), 48, 626, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(18)->color('d6d3d1');
        });

        $this->writeWrapped($image, (string)$event->name, 440, 78, 690, 34, $ink, $font, 1);
        $image->text(__('Entrada digital'), 440, 124, function ($fontStyle) use ($font, $accent) {
            $fontStyle->file($font)->size(18)->color($accent);
        });

        if ($ticket) {
            $folio = str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT);
            $ticketType = trim((string)($ticket->ticket_type_name ?? '')) ?: __('Entrada digital');
            $image->text(__('Folio'), 444, 226, function ($fontStyle) use ($font, $muted) {
                $fontStyle->file($font)->size(18)->color($muted);
            });
            $image->text('#' . $folio, 444, 292, function ($fontStyle) use ($font, $primary) {
                $fontStyle->file($font)->size(66)->color($primary);
            });
            $this->writeWrapped($image, (string)$ticket->name, 444, 382, 340, 32, $ink, $font, 2);
            $this->writeWrapped($image, $ticketType, 444, 472, 340, 24, $primary, $font, 1);
            $this->writeWrapped($image, (string)$ticket->email, 444, 512, 340, 18, $muted, $font, 1);
        } else {
            $image->text(__('Vista previa'), 444, 318, function ($fontStyle) use ($font, $primary) {
                $fontStyle->file($font)->size(48)->color($primary);
            });
            $image->text(__('Datos del asistente'), 444, 380, function ($fontStyle) use ($font, $muted) {
                $fontStyle->file($font)->size(20)->color($muted);
            });
        }

        $image->text(__('Escanea para validar acceso'), 934, 512, function ($fontStyle) use ($font, $muted) {
            $fontStyle->file($font)->size(17)->color($muted);
        });
        $image->text(__('FECHA'), 432, $height - 76, function ($fontStyle) use ($font, $muted) {
            $fontStyle->file($font)->size(14)->color($muted);
        });
        $this->writeWrapped($image, $eventDate, 432, $height - 48, 200, 20, $ink, $font, 1);
        $image->text(__('HORA'), 714, $height - 76, function ($fontStyle) use ($font, $muted) {
            $fontStyle->file($font)->size(14)->color($muted);
        });
        $this->writeWrapped($image, $eventTime, 714, $height - 48, 130, 20, $ink, $font, 1);
        $image->text(__('UBICACION'), 936, $height - 76, function ($fontStyle) use ($font, $muted) {
            $fontStyle->file($font)->size(14)->color($muted);
        });
        $this->writeWrapped($image, $location, 936, $height - 48, 260, 18, $ink, $font, 2);
    }

    private function cleanHexColor(string $value, string $fallback): string
    {
        $value = ltrim(trim($value), '#');

        return preg_match('/^[a-f0-9]{6}$/i', $value) ? $value : $fallback;
    }

    private function formatEventDate(Event $event): string
    {
        if (!$event->event_date) {
            return __('Por confirmar');
        }

        return $event->event_date->i18nFormat('dd/MM/yyyy');
    }

    private function formatEventTime(Event $event): string
    {
        if (!$event->event_date) {
            return __('Por confirmar');
        }

        return $event->event_date->i18nFormat('HH:mm');
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
