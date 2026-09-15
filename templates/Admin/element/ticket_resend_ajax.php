<?php $this->Html->scriptStart(['block' => true]); ?>
(function () {
    function setFeedback(form, type, message) {
        var feedback = form.querySelector('[data-resend-feedback]');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.setAttribute('data-resend-feedback', '');
            feedback.setAttribute('aria-live', 'polite');
            form.appendChild(feedback);
        }
        feedback.className = 'eventic-inline-feedback is-' + type;
        feedback.hidden = false;
        feedback.textContent = message;
    }

    function updateDelivery(form, ticket) {
        if (!ticket) {
            return;
        }
        var scope = form.closest('[data-ticket-row]') || document;
        var deliveryDate = scope.querySelector('[data-ticket-last-emailed]');
        var attempts = scope.querySelector('[data-ticket-email-attempts]');
        var emailDisplay = scope.querySelector('[data-ticket-email-display]');
        var deliveryCell = scope.querySelector('[data-ticket-delivery-cell]');
        var emailInput = form.querySelector('input[type="email"]');
        if (emailInput && ticket.email) {
            emailInput.value = ticket.email;
        }
        if (emailDisplay && ticket.email) {
            emailDisplay.textContent = ticket.email;
        }
        if (deliveryDate && ticket.last_emailed) {
            deliveryDate.textContent = ticket.last_emailed;
        } else if (deliveryCell && ticket.last_emailed) {
            deliveryCell.innerHTML = '<span class="eventic-ticket-delivery"><i class="fas fa-paper-plane" aria-hidden="true"></i> <span data-ticket-last-emailed></span></span> <small data-ticket-email-attempts></small>';
            deliveryCell.querySelector('[data-ticket-last-emailed]').textContent = ticket.last_emailed;
            deliveryCell.querySelector('[data-ticket-email-attempts]').textContent = ticket.attempts_label || '';
        }
        if (attempts && ticket.attempts_label) {
            attempts.textContent = attempts.tagName === 'SMALL' ? ticket.attempts_label : String(ticket.email_attempt_count || 0);
        }
    }

    document.querySelectorAll('form.eventic-ticket-resend, form.eventic-ticket-detail-resend').forEach(function (form) {
        form.addEventListener('input', function () {
            var feedback = form.querySelector('[data-resend-feedback]');
            if (feedback) {
                feedback.hidden = true;
            }
        });
        form.addEventListener('invalid', function () {
            setFeedback(form, 'error', '<?= __('Ingresa un correo electrónico válido para reenviar el pase.') ?>');
        }, true);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!form.reportValidity()) {
                setFeedback(form, 'error', '<?= __('Ingresa un correo electrónico válido para reenviar el pase.') ?>');
                return;
            }

            var button = form.querySelector('button[type="submit"], button:not([type])');
            var original = button ? button.innerHTML : '';
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> <?= __('Enviando') ?>';
            }
            setFeedback(form, 'loading', '<?= __('Enviando pase...') ?>');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            })
                .then(async function (response) {
                    var payload = await response.json().catch(function () {
                        return {};
                    });
                    if (!response.ok || payload.ok === false) {
                        throw new Error(payload.message || '<?= __('No fue posible reenviar el pase.') ?>');
                    }
                    updateDelivery(form, payload.ticket);
                    setFeedback(form, 'success', payload.message || '<?= __('Pase reenviado correctamente.') ?>');
                })
                .catch(function (error) {
                    setFeedback(form, 'error', error.message || '<?= __('No fue posible reenviar el pase.') ?>');
                })
                .finally(function () {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = original;
                    }
                });
        });
    });
})();
<?php $this->Html->scriptEnd(); ?>
