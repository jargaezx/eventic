<?php
$this->assign('title', __('Escaner'));
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js', ['block' => true]);
?>
<section class="eventic-staff-hero">
    <div class="eventic-eyebrow"><?= __('Validacion de acceso') ?></div>
    <h1><?= h($event->name) ?></h1>
    <p><?= __('Escanea el QR del pase para confirmar asistencia.') ?></p>
</section>

<div class="eventic-scan-panel">
    <div id="scan-status" class="alert alert-info" role="status" aria-live="polite">
        <?= __('Listo para escanear pases.') ?>
    </div>
    <div id="qr-reader" class="w-100"></div>
</div>

<div class="eventic-actions mt-3">
    <?= $this->Html->link(__('{0} Resumen', $this->FontAwesome->icon('fas', 'chart-bar')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-primary w-100', 'escape' => false]) ?>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
var url = '<?= Cake\Routing\Router::url(['prefix' => 'Api', 'controller' => 'Tickets', 'action' => 'attend', '_full' => true]) ?>';
var eventId = '<?= h($event->id) ?>';
var lastScan = { text: null, at: 0 };
var statusBox = document.getElementById('scan-status');

function showScanStatus(type, message) {
    statusBox.className = 'alert alert-' + type;
    statusBox.textContent = message;
    if (type === 'success' && navigator.vibrate) {
        navigator.vibrate(80);
    }
}

function onScanSuccess(decodedText) {
    var now = Date.now();
    if (decodedText === lastScan.text && now - lastScan.at < 2500) {
        return;
    }
    lastScan = { text: decodedText, at: now };
    html5QrcodeScanner.pause();
    showScanStatus('info', 'Validando pase...');
    fetch(url + '/' + decodedText + '.json', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(async function (response) {
        var json = await response.json();
        showScanStatus(response.ok ? 'success' : 'danger', json.message);
    })
    .catch(function (error) {
        showScanStatus('danger', error.message);
    })
    .then(function () {
        html5QrcodeScanner.resume();
    });
}

var html5QrcodeScanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: { width: 260, height: 260 } });
html5QrcodeScanner.render(onScanSuccess);
<?php $this->Html->scriptEnd(); ?>
