<?php
declare(strict_types=1);

use App\Service\EventCoverRenderer;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\TestCase;

class EventCoverRendererTest extends TestCase
{
    public function testUploadedFileEntityUsesPersistedCoverBeforeStringConversion(): void
    {
        $coverPath = TMP . 'event-cover-existing-' . bin2hex(random_bytes(8)) . '.png';
        file_put_contents($coverPath, 'cover');

        $uploadPath = TMP . 'event-cover-upload-' . bin2hex(random_bytes(8)) . '.png';
        file_put_contents($uploadPath, 'upload');

        $event = new Entity([
            'id' => '3ce77e77-5ed5-4e23-87d2-2193ca6a9bb5',
            'cover' => new UploadedFile($uploadPath, filesize($uploadPath), UPLOAD_ERR_OK, 'cover.png', 'image/png'),
            'cover_dir' => '',
        ]);
        $persisted = new Entity([
            'id' => $event->id,
            'cover' => basename($coverPath),
            'cover_dir' => 'tmp' . DS,
        ]);

        $eventsTable = $this->createMock(Table::class);
        $eventsTable->expects($this->once())
            ->method('get')
            ->with($event->id)
            ->willReturn($persisted);
        $eventsTable->expects($this->never())->method('updateAll');

        try {
            (new EventCoverRenderer())->ensure($event, $eventsTable);
            $this->assertSame($persisted->cover, $event->cover);
            $this->assertSame($persisted->cover_dir, $event->cover_dir);
        } finally {
            @unlink($coverPath);
            @unlink($uploadPath);
        }
    }
}
