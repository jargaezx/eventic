<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Pase'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Pase']
]);
$savedQrSize = (int)($ticketConfiguration->qr_size ?? 0);
$qrSize = $savedQrSize >= 180 ? min(280, $savedQrSize) : 240;
$savedQrX = (int)($ticketConfiguration->x ?? 0);
$savedQrY = (int)($ticketConfiguration->y ?? 0);
$qrX = $savedQrX > 0 ? $savedQrX : 930 + (int)round((300 - $qrSize) / 2);
$qrY = $savedQrY > 0 ? $savedQrY : 210 + (int)round((300 - $qrSize) / 2);
?>
<?php
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js', ['block' => true]);
echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css', ['block' => true]);
?>

<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Pase digital') ?></div>
            <h1 class="eventic-title"><?= __('Ubicacion del QR') ?></h1>
            <p class="eventic-subtitle"><?= __('Define el area donde se imprimira el codigo QR en la plantilla del pase.') ?></p>
        </div>
    </div>

    <div class="eventic-card mb-4">
        <?php
        echo $this->Form->create($ticketConfiguration);
        echo $this->Form->hidden('x', ['id' => 'x']);
        echo $this->Form->hidden('y', ['id' => 'y']);
        echo $this->Form->hidden('qr_size', ['id' => 'qr_size']);
        echo $this->Form->button(__('{0} Guardar pase', $this->FontAwesome->icon('fas', 'save')), ['class' => 'btn btn-primary w-100', 'escapeTitle' => false]);
        echo $this->Form->end();
        ?>
    </div>

    <div class="eventic-card">
        <img src="<?= h($editorTemplate) ?>" id="ticket" alt="<?= __('Plantilla del pase') ?>" class="img-fluid rounded">
    </div>
</div>

<?php echo $this->Html->scriptStart(['block' => true]); ?>

    window.addEventListener('DOMContentLoaded', function() {

        var ticket = document.querySelector('#ticket');
        var x = document.querySelector('#x');
        var y = document.querySelector('#y');
        var qr_size = document.querySelector('#qr_size');

        var cropper = new Cropper(ticket, {
            aspectRatio: 1,
            zoomable: false,
            ready: function(event) {
                cropper.zoomTo(1);
                cropper.setData({
                    x: <?= $qrX ?>,
                    y: <?= $qrY ?>,
                    width: <?= $qrSize ?>
                });
            },

            crop: function(event) {
                var data = cropper.getData();
                x.value = Math.round(data.x);
                y.value = Math.round(data.y);
                qr_size.value = Math.round(data.width);
            },
        });
    });
<?php echo $this->Html->scriptEnd(); ?>
