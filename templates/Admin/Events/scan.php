<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Escanear'));
$this->assign('eventicPage', '1');

$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Escanear']
]);
?>
<?php
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js', ['block' => true]);
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Acceso') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Validacion de pases para staff operativo.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="eventic-scan-panel">
                <div id="scan-status" class="alert alert-info" role="status" aria-live="polite">
                    <?= __('Listo para escanear pases.') ?>
                </div>
                <div id="qr-reader" class="w-100"></div>
            </div>
        </div>
    </div>
</div>

<script>
<?php
$this->Html->scriptStart(['block' => true]);
?>
var url = '<?= Cake\Routing\Router::url(['prefix'=>'Api', 'controller'=>'Tickets', 'action'=>'attend', '_full'=>true]) ?>';
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

function onScanSuccess(decodedText, decodedResult) {
    var now = Date.now();
    if (decodedText === lastScan.text && now - lastScan.at < 2500) {
        return;
    }
    lastScan = { text: decodedText, at: now };
    html5QrcodeScanner.pause();
    showScanStatus('info', 'Validando pase...');
    fetch(url + '/' + decodedText + '.json', {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(async response => {
        var json = await response.json();
        showScanStatus(response.ok ? 'success' : 'danger', json.message);
    })
    .catch((error) => {
        showScanStatus('danger', error.message);
    })
    .then((response) => html5QrcodeScanner.resume());
}

var html5QrcodeScanner = new Html5QrcodeScanner(
    "qr-reader", { fps: 10}
);
html5QrcodeScanner.render(onScanSuccess);

<?php
$this->Html->scriptEnd();
?>
</script>
