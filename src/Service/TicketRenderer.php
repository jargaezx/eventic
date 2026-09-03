<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Event;
use App\Model\Entity\Ticket;
use Cake\Core\Exception\CakeException;
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
    public function renderPreview(Event $event): string
    {
        return $this->compose($event, 'PREVIEW')->toPng()->toDataUri();
    }

    public function renderTicket(Event $event, Ticket $ticket): string
    {
        $image = $this->compose($event, $ticket->id, $ticket);
        $path = WWW_ROOT . "files/tickets/{$ticket->id}.png";
        $image->save($path);

        return $path;
    }

    private function compose(Event $event, string $qrContent, ?Ticket $ticket = null)
    {
        $configuration = $event->ticket_configuration;
        if (!$configuration || !$configuration->ticket || !$configuration->ticket_dir) {
            throw new CakeException(__('El evento no tiene configurada una plantilla de boleto.'));
        }

        $templatePath = ROOT . DS . $configuration->ticket_dir . $configuration->ticket;
        if (!is_file($templatePath)) {
            throw new CakeException(__('No se encontro la plantilla de boleto configurada.'));
        }

        $writer = new PngWriter();
        $qrCode = QrCode::create($qrContent)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize((int)$configuration->qr_size)
            ->setMargin(1)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $manager = new ImageManager(new Driver());
        $image = $manager->read($templatePath);

        $this->applyTicketBranding($image, $event, $ticket);
        $image->place(
            $writer->write($qrCode)->getString(),
            'top-left',
            (int)$configuration->x,
            (int)$configuration->y
        );

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
            $rectangle->width($width)->height(92);
            $rectangle->background($primary);
        });
        $image->drawRectangle(0, $height - 18, function ($rectangle) use ($width, $accent) {
            $rectangle->width($width)->height(18);
            $rectangle->background($accent);
        });

        if (!$font) {
            return;
        }

        $image->text((string)$event->name, 36, 38, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(26)->color('ffffff');
        });
        $image->text(__('Pase digital'), 36, 72, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(15)->color('eaf2ff');
        });

        if ($ticket) {
            $image->text((string)$ticket->name, 36, 134, function ($fontStyle) use ($font) {
                $fontStyle->file($font)->size(24)->color('111827');
            });
            $image->text(__('Folio {0}', str_pad((string)$ticket->folio, 5, '0', STR_PAD_LEFT)), 36, 168, function ($fontStyle) use ($font) {
                $fontStyle->file($font)->size(16)->color('475467');
            });
        } else {
            $image->text(__('Vista previa'), 36, 134, function ($fontStyle) use ($font) {
                $fontStyle->file($font)->size(24)->color('111827');
            });
        }

        $image->text((string)$event->event_date, 36, $height - 44, function ($fontStyle) use ($font) {
            $fontStyle->file($font)->size(15)->color('475467');
        });
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
