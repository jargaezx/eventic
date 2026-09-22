<?php
declare(strict_types=1);

use App\Model\Entity\Event;
use App\Model\Entity\Ticket;
use App\Service\ImageText;
use App\Service\TicketRenderer;
use Cake\ORM\Entity;
use PHPUnit\Framework\TestCase;

class TicketRendererTest extends TestCase
{
    public function testTextFitsByActualFontWidthIncludingUnbrokenNames(): void
    {
        $font = ImageText::fontPath();
        $this->assertFileExists($font);
        foreach (['María José de los Ángeles Hernández González', str_repeat('W', 120), 'correo.muy.largo.sin.espacios@universidad.example'] as $text) {
            $layout = ImageText::fit($text, 220, 32, 2, $font);
            $this->assertLessThanOrEqual(2, count($layout['lines']));
            foreach ($layout['lines'] as $line) {
                $this->assertLessThanOrEqual(220, ImageText::width($line, $layout['size'], $font));
            }
        }
    }

    public function testQrDimensionsAndWhiteQuietZone(): void
    {
        foreach ([180, 240, 280] as $size) {
            $bytes = (new TicketRenderer())->renderQr('a3a60c41-83cb-4ff5-9f4e-b02b88a39d85', $size);
            $image = imagecreatefromstring($bytes);
            $this->assertSame($size, imagesx($image));
            $this->assertSame($size, imagesy($image));
            for ($i = 0; $i < $size; $i++) {
                $this->assertSame(0xffffff, imagecolorat($image, $i, 31) & 0xffffff);
                $this->assertSame(0xffffff, imagecolorat($image, 31, $i) & 0xffffff);
            }
            imagedestroy($image);
        }
    }

    public function testSquareTemplateIsContainedWithoutStretching(): void
    {
        $templatePath = TMP . 'ticket-template-' . bin2hex(random_bytes(8)) . '.png';
        $outputPath = TMP . 'ticket-result-' . bin2hex(random_bytes(8)) . '.png';
        $source = imagecreatetruecolor(400, 400);
        imagefill($source, 0, 0, imagecolorallocate($source, 0, 180, 220));
        imagepng($source, $templatePath);
        imagedestroy($source);
        $event = new Event([
            'name' => 'Prueba de proporciones',
            'ticket_configuration' => new Entity([
                'ticket_dir' => 'tmp' . DS,
                'ticket' => basename($templatePath),
                'qr_size' => 280, 'x' => 1279, 'y' => 719,
            ]),
        ]);
        try {
            (new TicketRenderer())->renderTicketToPath($event, new Ticket(['id' => 'a3a60c41-83cb-4ff5-9f4e-b02b88a39d85']), $outputPath);
            $image = imagecreatefrompng($outputPath);
            $this->assertSame(1280, imagesx($image));
            $this->assertSame(720, imagesy($image));
            $this->assertSame(0xffffff, imagecolorat($image, 100, 100) & 0xffffff);
            $this->assertSame(0x00b4dc, imagecolorat($image, 640, 360) & 0xffffff);
            $this->assertSame(0xffffff, imagecolorat($image, 1180, 100) & 0xffffff);
            imagedestroy($image);
        } finally {
            unlink($templatePath);
            unlink($outputPath);
        }
    }
}
