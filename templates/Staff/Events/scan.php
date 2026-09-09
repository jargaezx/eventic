<?php
$this->assign('title', __('Escaner'));
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js', ['block' => true]);
$sold = (int)$event->ticket_count;
$attended = (int)$event->ticket_attended_count;
$pending = max(0, $sold - $attended);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
?>
<section class="eventic-staff-hero">
    <div class="eventic-eyebrow"><?= __('Validacion de acceso') ?></div>
    <h1><?= h($event->name) ?></h1>
    <p><?= __('Escanea el QR del pase para confirmar asistencia con respuesta inmediata.') ?></p>
</section>

<section class="eventic-scan-shell" data-event-id="<?= h($event->id) ?>">
    <div id="scan-result" class="eventic-scan-result is-ready" role="status" aria-live="polite">
        <div class="eventic-scan-result-icon"><?= $this->FontAwesome->icon('fas', 'qrcode') ?></div>
        <div>
            <span id="scan-result-label"><?= __('Listo para escanear') ?></span>
            <strong id="scan-result-title"><?= __('Apunta la camara al QR del pase') ?></strong>
            <p id="scan-result-message"><?= __('El sistema validara el pase y mostrara el resultado aqui.') ?></p>
        </div>
    </div>

    <div class="eventic-staff-mini-stats eventic-scan-stats">
        <span><strong id="scan-attended-count"><?= $attended ?></strong><?= __('Accesos') ?></span>
        <span><strong id="scan-pending-count"><?= $pending ?></strong><?= __('Pendientes') ?></span>
        <span><strong id="scan-checkin-rate"><?= $this->Number->toPercentage($checkin, 1) ?></strong><?= __('Check-in') ?></span>
    </div>

    <div class="eventic-scan-panel">
        <div class="eventic-scanner-frame">
            <div class="eventic-scanner-toolbar">
                <select id="camera-select" class="form-select" aria-label="<?= __('Camara') ?>" disabled>
                    <option><?= __('Camara disponible al activar') ?></option>
                </select>
                <button id="start-scanner" class="btn btn-primary" type="button"><?= $this->FontAwesome->icon('fas', 'camera') ?> <?= __('Activar camara') ?></button>
                <button id="stop-scanner" class="btn btn-outline-secondary" type="button" disabled><?= $this->FontAwesome->icon('fas', 'pause') ?> <?= __('Detener') ?></button>
            </div>
            <div id="qr-reader" class="w-100"></div>
            <p id="camera-help" class="eventic-camera-help"><?= __('Permite el acceso a la camara del dispositivo cuando el navegador lo solicite.') ?></p>
        </div>
    </div>

    <div id="scan-ticket-card" class="eventic-ticket-result-card d-none">
        <div>
            <span><?= __('Asistente') ?></span>
            <strong data-ticket-field="name">-</strong>
        </div>
        <div>
            <span><?= __('Correo') ?></span>
            <strong data-ticket-field="email">-</strong>
        </div>
        <div>
            <span><?= __('Folio') ?></span>
            <strong data-ticket-field="folio">-</strong>
        </div>
        <div>
            <span><?= __('Tipo') ?></span>
            <strong data-ticket-field="ticket_type">-</strong>
        </div>
        <div>
            <span><?= __('Tarifa') ?></span>
            <strong data-ticket-field="ticket_rate">-</strong>
        </div>
        <div>
            <span><?= __('Importe') ?></span>
            <strong data-ticket-field="amount">-</strong>
        </div>
        <div>
            <span><?= __('Validado') ?></span>
            <strong data-ticket-field="attended">-</strong>
        </div>
    </div>

    <form id="manual-scan-form" class="eventic-manual-scan" autocomplete="off">
        <label for="manual-ticket-code"><?= __('Validacion manual') ?></label>
        <div class="input-group">
            <input id="manual-ticket-code" class="form-control" inputmode="text" placeholder="<?= __('Pega o captura el codigo del pase') ?>">
            <button class="btn btn-outline-primary" type="submit"><?= $this->FontAwesome->icon('fas', 'check') ?> <?= __('Validar') ?></button>
        </div>
    </form>
</section>

<div class="eventic-actions mt-3">
    <?= $this->Html->link(__('{0} Resumen', $this->FontAwesome->icon('fas', 'chart-bar')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-primary w-100', 'escape' => false]) ?>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
var url = '<?= Cake\Routing\Router::url(['prefix' => 'Api', 'controller' => 'Tickets', 'action' => 'attend', '_full' => true]) ?>';
var eventId = '<?= h($event->id) ?>';
var scanState = {
    locked: false,
    lastText: null,
    lastAt: 0,
    attended: <?= $attended ?>,
    pending: <?= $pending ?>,
    checkinBase: <?= max(1, $sold) ?>,
};
var resultBox = document.getElementById('scan-result');
var resultLabel = document.getElementById('scan-result-label');
var resultTitle = document.getElementById('scan-result-title');
var resultMessage = document.getElementById('scan-result-message');
var ticketCard = document.getElementById('scan-ticket-card');
var manualForm = document.getElementById('manual-scan-form');
var manualInput = document.getElementById('manual-ticket-code');
var attendedCount = document.getElementById('scan-attended-count');
var pendingCount = document.getElementById('scan-pending-count');
var checkinRate = document.getElementById('scan-checkin-rate');
var cameraSelect = document.getElementById('camera-select');
var startButton = document.getElementById('start-scanner');
var stopButton = document.getElementById('stop-scanner');
var cameraHelp = document.getElementById('camera-help');
var html5QrCode = typeof Html5Qrcode !== 'undefined' ? new Html5Qrcode('qr-reader') : null;
var scannerRunning = false;

if (!html5QrCode) {
    startButton.disabled = true;
    stopButton.disabled = true;
    cameraHelp.textContent = '<?= __('El lector de camara no esta disponible. Puedes validar el pase de forma manual.') ?>';
}

function formatPercentage(value) {
    return value.toFixed(1) + '%';
}

function setTicketDetails(ticket) {
    if (!ticket) {
        ticketCard.classList.add('d-none');
        return;
    }
    ticketCard.classList.remove('d-none');
    ticketCard.querySelector('[data-ticket-field="name"]').textContent = ticket.name || '-';
    ticketCard.querySelector('[data-ticket-field="email"]').textContent = ticket.email || '-';
    ticketCard.querySelector('[data-ticket-field="folio"]').textContent = ticket.folio || '-';
    ticketCard.querySelector('[data-ticket-field="ticket_type"]').textContent = ticket.ticket_type || '-';
    ticketCard.querySelector('[data-ticket-field="ticket_rate"]').textContent = ticket.ticket_rate || '-';
    ticketCard.querySelector('[data-ticket-field="amount"]').textContent = ticket.price !== undefined
        ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: ticket.currency || 'MXN' }).format(ticket.price)
        : '-';
    ticketCard.querySelector('[data-ticket-field="attended"]').textContent = ticket.attended || '-';
}

function updateCounters(status) {
    if (status !== 'valid') {
        return;
    }
    scanState.attended += 1;
    scanState.pending = Math.max(0, scanState.pending - 1);
    attendedCount.textContent = scanState.attended;
    pendingCount.textContent = scanState.pending;
    checkinRate.textContent = formatPercentage((scanState.attended / scanState.checkinBase) * 100);
}

function showScanResult(state, label, title, message, ticket) {
    resultBox.className = 'eventic-scan-result is-' + state;
    resultLabel.textContent = label;
    resultTitle.textContent = title;
    resultMessage.textContent = message;
    setTicketDetails(ticket);
    if (navigator.vibrate) {
        navigator.vibrate(state === 'valid' ? [90, 30, 90] : [180]);
    }
}

function normalizeScanText(value) {
    value = String(value || '').trim();
    var match = value.match(/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/);
    return match ? match[0].toLowerCase() : value;
}

function pauseScanner() {
    if (!scannerRunning || !html5QrCode) {
        return;
    }
    try {
        html5QrCode.pause(true);
    } catch (error) {}
}

function resumeScanner() {
    if (!scannerRunning || !html5QrCode) {
        return;
    }
    try {
        html5QrCode.resume();
    } catch (error) {}
}

function validateTicket(decodedText) {
    decodedText = normalizeScanText(decodedText);
    var now = Date.now();
    if (!decodedText || scanState.locked) {
        return;
    }
    if (decodedText === scanState.lastText && now - scanState.lastAt < 6500) {
        showScanResult('hold', '<?= __('Lectura repetida') ?>', '<?= __('QR detectado recientemente') ?>', '<?= __('Espera unos segundos antes de volver a validar el mismo pase.') ?>', null);
        return;
    }

    scanState.locked = true;
    scanState.lastText = decodedText;
    scanState.lastAt = now;
    pauseScanner();
    showScanResult('loading', '<?= __('Validando') ?>', '<?= __('Consultando pase') ?>', '<?= __('Mantente en esta pantalla hasta ver el resultado.') ?>', null);

    fetch(url + '/' + decodedText + '.json', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(async function (response) {
        var json = await response.json();
        var ticket = json.data || null;
        updateCounters(json.status);
        if (json.status === 'valid') {
            showScanResult('valid', '<?= __('Acceso autorizado') ?>', ticket && ticket.name ? ticket.name : '<?= __('Pase valido') ?>', json.message, ticket);
            return;
        }
        if (json.status === 'duplicate') {
            showScanResult('duplicate', '<?= __('Pase ya utilizado') ?>', ticket && ticket.name ? ticket.name : '<?= __('Acceso duplicado') ?>', json.message, ticket);
            return;
        }
        if (json.status === 'wrong_event') {
            showScanResult('wrong', '<?= __('Evento incorrecto') ?>', ticket && ticket.event ? ticket.event : '<?= __('Otro evento') ?>', json.message, ticket);
            return;
        }
        showScanResult('invalid', '<?= __('Pase no valido') ?>', '<?= __('No autorizar acceso') ?>', json.message || '<?= __('No se pudo validar el pase.') ?>', null);
    })
    .catch(function (error) {
        showScanResult('invalid', '<?= __('Error de lectura') ?>', '<?= __('No se pudo validar') ?>', error.message, null);
    })
    .then(function () {
        window.setTimeout(function () {
            scanState.locked = false;
            resumeScanner();
        }, 1200);
    });
}

function onScanSuccess(decodedText) {
    validateTicket(decodedText);
}

function populateCameras(devices) {
    cameraSelect.innerHTML = '';
    devices.forEach(function (device, index) {
        var option = document.createElement('option');
        option.value = device.id;
        option.textContent = device.label || '<?= __('Camara') ?> ' + (index + 1);
        cameraSelect.appendChild(option);
    });
    cameraSelect.disabled = devices.length < 2;
}

function startScanner() {
    if (!html5QrCode) {
        showScanResult('invalid', '<?= __('Camara no disponible') ?>', '<?= __('Usa validacion manual') ?>', '<?= __('El lector QR no pudo cargarse en este navegador.') ?>', null);
        return;
    }
    showScanResult('loading', '<?= __('Camara') ?>', '<?= __('Preparando lector') ?>', '<?= __('Acepta el permiso de camara para iniciar el escaneo.') ?>', null);
    startButton.disabled = true;
    Html5Qrcode.getCameras()
        .then(function (devices) {
            if (!devices || devices.length === 0) {
                throw new Error('<?= __('No se encontraron camaras disponibles.') ?>');
            }
            populateCameras(devices);
            var cameraId = cameraSelect.value || devices[0].id;
            return html5QrCode.start(
                cameraId,
                {
                    fps: 8,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        var size = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.72);
                        size = Math.max(220, Math.min(size, 320));
                        return { width: size, height: size };
                    },
                    aspectRatio: 1,
                },
                onScanSuccess
            );
        })
        .then(function () {
            scannerRunning = true;
            startButton.disabled = true;
            stopButton.disabled = false;
            cameraSelect.disabled = true;
            cameraHelp.textContent = '<?= __('Escaner activo. Mantén el QR dentro del recuadro hasta recibir el resultado.') ?>';
            showScanResult('ready', '<?= __('Escaner activo') ?>', '<?= __('Listo para validar pases') ?>', '<?= __('Cada lectura se bloqueara mientras se confirma el pase.') ?>', null);
        })
        .catch(function (error) {
            scannerRunning = false;
            startButton.disabled = false;
            stopButton.disabled = true;
            cameraSelect.innerHTML = '<option><?= __('Camara disponible al activar') ?></option>';
            cameraSelect.disabled = true;
            showScanResult('invalid', '<?= __('Camara no disponible') ?>', '<?= __('Usa validacion manual') ?>', error.message, null);
        });
}

function stopScanner() {
    if (!scannerRunning || !html5QrCode) {
        return;
    }
    html5QrCode.stop()
        .then(function () {
            scannerRunning = false;
            startButton.disabled = false;
            stopButton.disabled = true;
            cameraSelect.disabled = cameraSelect.options.length < 2;
            cameraHelp.textContent = '<?= __('Camara detenida. Puedes activarla nuevamente o validar de forma manual.') ?>';
            showScanResult('ready', '<?= __('Escaner detenido') ?>', '<?= __('Camara pausada') ?>', '<?= __('Activa la camara cuando estes listo para continuar.') ?>', null);
        })
        .catch(function () {
            scannerRunning = false;
            startButton.disabled = false;
            stopButton.disabled = true;
            cameraSelect.disabled = cameraSelect.options.length < 2;
        });
}

manualForm.addEventListener('submit', function (event) {
    event.preventDefault();
    validateTicket(manualInput.value);
    manualInput.value = '';
});

startButton.addEventListener('click', startScanner);
stopButton.addEventListener('click', stopScanner);
<?php $this->Html->scriptEnd(); ?>
