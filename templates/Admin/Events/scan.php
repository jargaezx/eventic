<?php
$this->assign('title', __('Eventos'));
$this->assign('subtitle', __('Escanear'));
$this->assign('eventicPage', '1');
$this->Breadcrumbs->add([
    ['title' => 'Eventos', 'url' => ['controller' => 'Events', 'action' => 'index']],
    ['title' => 'Escanear'],
]);

echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js', ['block' => true]);
$sold = (int)$event->ticket_count;
$attended = (int)$event->ticket_attended_count;
$pending = max(0, $sold - $attended);
$checkin = $sold > 0 ? round(($attended / $sold) * 100, 1) : 0;
?>
<div class="eventic-shell">
    <div class="eventic-pagebar">
        <div>
            <div class="eventic-eyebrow"><?= __('Validación de acceso') ?></div>
            <h1 class="eventic-title"><?= h($event->name) ?></h1>
            <p class="eventic-subtitle"><?= __('Escanea el QR del pase para confirmar asistencia con respuesta inmediata.') ?></p>
        </div>
        <div class="eventic-actions">
            <?= $this->RBAC->link(__('{0} Detalle', $this->FontAwesome->icon('fas', 'arrow-left')), ['action' => 'view', $event->id], ['class' => 'btn btn-outline-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <section class="eventic-scan-shell" data-event-id="<?= h($event->id) ?>">
        <div class="eventic-staff-mini-stats eventic-scan-stats">
            <span><strong id="scan-attended-count"><?= $attended ?></strong><?= __('Accesos') ?></span>
            <span><strong id="scan-pending-count"><?= $pending ?></strong><?= __('Pendientes') ?></span>
            <span><strong id="scan-checkin-rate"><?= $this->Number->toPercentage($checkin, 1) ?></strong><?= __('Check-in') ?></span>
        </div>

        <div class="eventic-scan-panel">
            <div class="eventic-scanner-frame">
                <div class="eventic-scanner-toolbar">
                    <select id="camera-select" class="form-select" aria-label="<?= __('Cámara') ?>" disabled>
                        <option><?= __('Cámara disponible al activar') ?></option>
                    </select>
                    <button id="start-scanner" class="btn btn-primary" type="button"><?= $this->FontAwesome->icon('fas', 'camera') ?> <?= __('Activar cámara') ?></button>
                    <button id="stop-scanner" class="btn btn-outline-secondary" type="button" disabled><?= $this->FontAwesome->icon('fas', 'pause') ?> <?= __('Detener') ?></button>
                </div>
                <div class="eventic-scan-stage">
                    <div id="qr-reader" class="w-100"></div>
                    <div id="scan-result" class="eventic-scan-result is-ready" role="status" aria-live="polite">
                        <div class="eventic-scan-result-icon"><?= $this->FontAwesome->icon('fas', 'qrcode') ?></div>
                        <div>
                            <span id="scan-result-label"><?= __('Listo para escanear') ?></span>
                            <strong id="scan-result-title"><?= __('Apunta la cámara al QR del pase') ?></strong>
                            <p id="scan-result-message"><?= __('El resultado aparecerá sobre la cámara sin perder el encuadre.') ?></p>
                        </div>
                    </div>
                </div>
                <p id="camera-help" class="eventic-camera-help"><?= __('Permite el acceso a la cámara del dispositivo cuando el navegador lo solicite.') ?></p>
            </div>
        </div>

        <div id="scan-ticket-card" class="eventic-ticket-result-card d-none">
            <div><span><?= __('Asistente') ?></span><strong data-ticket-field="name">-</strong></div>
            <div><span><?= __('Correo') ?></span><strong data-ticket-field="email">-</strong></div>
            <div><span><?= __('Folio') ?></span><strong data-ticket-field="folio">-</strong></div>
            <div><span><?= __('Tipo de boleto') ?></span><strong data-ticket-field="ticket_type">-</strong></div>
            <div><span><?= __('Importe') ?></span><strong data-ticket-field="amount">-</strong></div>
            <div><span><?= __('Validado') ?></span><strong data-ticket-field="attended">-</strong></div>
        </div>

        <details class="eventic-manual-scan">
            <summary><?= $this->FontAwesome->icon('fas', 'keyboard') ?> <?= __('Validación manual') ?></summary>
            <form id="manual-scan-form" autocomplete="off">
                <label for="manual-ticket-code"><?= __('Código QR del pase') ?></label>
                <div class="input-group">
                    <input id="manual-ticket-code" class="form-control" inputmode="text" aria-describedby="manual-ticket-help" placeholder="<?= __('Pega el UUID o contenido del QR') ?>">
                    <button class="btn btn-outline-primary" type="submit"><?= $this->FontAwesome->icon('fas', 'check') ?> <?= __('Validar') ?></button>
                </div>
                <small id="manual-ticket-help" class="eventic-field-hint"><?= __('No uses el folio visible. Copia el código del QR o el UUID del pase.') ?></small>
            </form>
        </details>
    </section>
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
var scanAudio = {
    context: null,
    enabled: false
};

if (!html5QrCode) {
    startButton.disabled = true;
    stopButton.disabled = true;
    cameraHelp.textContent = '<?= __('El lector de cámara no está disponible. Puedes validar el pase de forma manual.') ?>';
}

function formatPercentage(value) {
    return value.toFixed(1) + '%';
}

function unlockScanAudio() {
    var AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) {
        return;
    }
    if (!scanAudio.context) {
        scanAudio.context = new AudioContext();
    }
    scanAudio.enabled = true;
    if (scanAudio.context.state === 'suspended') {
        scanAudio.context.resume().catch(function () {});
    }
}

function beep(frequency, start, duration, volume, type) {
    if (!scanAudio.context || !scanAudio.enabled) {
        return;
    }
    var audioContext = scanAudio.context;
    var oscillator = audioContext.createOscillator();
    var gain = audioContext.createGain();
    var startsAt = audioContext.currentTime + start;
    var endsAt = startsAt + duration;

    oscillator.type = type || 'sine';
    oscillator.frequency.setValueAtTime(frequency, startsAt);
    gain.gain.setValueAtTime(0.0001, startsAt);
    gain.gain.exponentialRampToValueAtTime(volume, startsAt + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, endsAt);
    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    oscillator.start(startsAt);
    oscillator.stop(endsAt + 0.03);
}

function playScanSound(state) {
    if (!scanAudio.enabled) {
        return;
    }
    unlockScanAudio();
    if (!scanAudio.context || scanAudio.context.state !== 'running') {
        return;
    }
    if (state === 'valid') {
        beep(1046, 0, 0.075, 0.24, 'sine');
        beep(1318, 0.085, 0.075, 0.22, 'sine');
        beep(1568, 0.17, 0.13, 0.24, 'triangle');
        return;
    }
    if (state === 'duplicate' || state === 'wrong' || state === 'invalid') {
        beep(196, 0, 0.14, 0.3, 'square');
        beep(147, 0.17, 0.2, 0.26, 'square');
    }
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
    playScanSound(state);
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

    var requestController = new AbortController();
    var requestTimeout = window.setTimeout(function () { requestController.abort(); }, 15000);
    fetch(url + '/' + encodeURIComponent(decodedText) + '.json', {
        signal: requestController.signal,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(async function (response) {
        if (!(response.headers.get('content-type') || '').includes('application/json')) {
            throw new Error('No se pudo confirmar el acceso. Vuelve a escanear antes de autorizar la entrada.');
        }
        var json = await response.json();
        if (!response.ok && (response.status >= 500 || response.status === 401 || response.status === 403)) {
            throw new Error(json.message || 'No se pudo confirmar el acceso. Revisa tu sesión y vuelve a escanear.');
        }
        var ticket = json.data || null;
        updateCounters(json.status);
        if (json.status === 'valid') {
            showScanResult('valid', '<?= __('Acceso autorizado') ?>', ticket && ticket.name ? ticket.name : '<?= __('Pase válido') ?>', json.message, ticket);
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
        showScanResult('invalid', '<?= __('Pase no válido') ?>', '<?= __('No autorizar acceso') ?>', json.message || '<?= __('No se pudo validar el pase.') ?>', null);
    })
    .catch(function (error) {
        showScanResult('invalid', '<?= __('Error de lectura') ?>', '<?= __('No se pudo validar') ?>', error.name === 'AbortError' ? 'La consulta tardó demasiado. Vuelve a escanear antes de autorizar la entrada.' : error.message, null);
    })
    .then(function () {
        window.clearTimeout(requestTimeout);
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
        option.textContent = device.label || '<?= __('Cámara') ?> ' + (index + 1);
        cameraSelect.appendChild(option);
    });
    cameraSelect.disabled = devices.length < 2;
}

function startScanner() {
    unlockScanAudio();
    if (!html5QrCode) {
        showScanResult('invalid', '<?= __('Cámara no disponible') ?>', '<?= __('Usa validación manual') ?>', '<?= __('El lector QR no pudo cargarse en este navegador.') ?>', null);
        return;
    }
    showScanResult('loading', '<?= __('Cámara') ?>', '<?= __('Preparando lector') ?>', '<?= __('Acepta el permiso de cámara para iniciar el escaneo.') ?>', null);
    startButton.disabled = true;
    Html5Qrcode.getCameras()
        .then(function (devices) {
            if (!devices || devices.length === 0) {
                throw new Error('<?= __('No se encontraron cámaras disponibles.') ?>');
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
            cameraHelp.textContent = '<?= __('Escáner activo. Mantén el QR dentro del recuadro hasta recibir el resultado.') ?>';
            showScanResult('ready', '<?= __('Escáner activo') ?>', '<?= __('Listo para validar pases') ?>', '<?= __('Cada lectura se bloqueará mientras se confirma el pase.') ?>', null);
        })
        .catch(function (error) {
            scannerRunning = false;
            startButton.disabled = false;
            stopButton.disabled = true;
            cameraSelect.innerHTML = '<option><?= __('Cámara disponible al activar') ?></option>';
            cameraSelect.disabled = true;
            showScanResult('invalid', '<?= __('Cámara no disponible') ?>', '<?= __('Usa validación manual') ?>', error.name === 'AbortError' ? 'La consulta tardó demasiado. Vuelve a escanear antes de autorizar la entrada.' : error.message, null);
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
            cameraHelp.textContent = '<?= __('Cámara detenida. Puedes activarla nuevamente o validar de forma manual.') ?>';
            showScanResult('ready', '<?= __('Escáner detenido') ?>', '<?= __('Cámara pausada') ?>', '<?= __('Activa la cámara cuando estés listo para continuar.') ?>', null);
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
    unlockScanAudio();
    validateTicket(manualInput.value);
    manualInput.value = '';
});

startButton.addEventListener('click', startScanner);
stopButton.addEventListener('click', stopScanner);
<?php $this->Html->scriptEnd(); ?>
