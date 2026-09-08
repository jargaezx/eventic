(function () {
  'use strict';

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }
    callback();
  }

  function enhanceAlerts() {
    document.querySelectorAll('.eventic-alert').forEach(function (alert) {
      alert.classList.add('is-visible');
      var close = alert.querySelector('button');
      if (close) {
        close.addEventListener('click', function () {
          alert.classList.remove('is-visible');
          alert.classList.add('is-dismissed');
        });
      }
      if (!alert.classList.contains('error') && !alert.classList.contains('warning')) {
        window.setTimeout(function () {
          alert.classList.remove('is-visible');
          alert.classList.add('is-dismissed');
        }, 5600);
      }
    });
  }

  function enhanceConfirmations() {
    document.querySelectorAll('form').forEach(function (form) {
      var message = form.getAttribute('data-confirm-message');
      if (!message) {
        return;
      }
      form.addEventListener('submit', function (event) {
        if (!window.confirm(message)) {
          event.preventDefault();
        }
      });
    });
  }

  function enhanceButtons() {
    document.querySelectorAll('.btn, .eventic-icon-button, .eventic-icon-btn').forEach(function (button) {
      button.addEventListener('pointerdown', function () {
        button.classList.add('is-pressing');
      });
      button.addEventListener('pointerup', function () {
        button.classList.remove('is-pressing');
      });
      button.addEventListener('pointerleave', function () {
        button.classList.remove('is-pressing');
      });
    });
  }

  ready(function () {
    enhanceAlerts();
    enhanceConfirmations();
    enhanceButtons();
  });
})();
