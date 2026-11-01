/**
 * Legal Information Interactive Actions (Clipboard Copy, Print & Toast)
 * templates/default/static/js/legal-info.js
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // 1. Toast Notification Helper
  function showLegalToast(message) {
    let toast = document.getElementById('legalToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'legalToast';
      toast.className = 'legal-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="bi bi-check2-circle text-success fs-5"></i><span>' + message + '</span>';
    toast.classList.add('show');

    if (toast.dismissTimeout) {
      clearTimeout(toast.dismissTimeout);
    }

    toast.dismissTimeout = setTimeout(function () {
      toast.classList.remove('show');
    }, 2400);
  }

  // 2. Clipboard Copy Handler
  var copyButtons = document.querySelectorAll('.legal-copy-btn');
  copyButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var textToCopy = btn.getAttribute('data-copy-text');
      if (!textToCopy) {
        var valEl = btn.closest('.legal-field-row')?.querySelector('.legal-field-value');
        if (valEl) {
          textToCopy = valEl.innerText.trim();
        }
      }

      if (!textToCopy) {
        return;
      }

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textToCopy).then(function () {
          handleSuccess(btn);
        }).catch(function () {
          fallbackCopy(textToCopy, btn);
        });
      } else {
        fallbackCopy(textToCopy, btn);
      }
    });
  });

  function fallbackCopy(text, btn) {
    var tempInput = document.createElement('textarea');
    tempInput.value = text;
    tempInput.setAttribute('readonly', '');
    tempInput.style.position = 'absolute';
    tempInput.style.left = '-9999px';
    document.body.appendChild(tempInput);
    tempInput.select();
    try {
      document.execCommand('copy');
      handleSuccess(btn);
    } catch (err) {
      console.error('Could not copy text: ', err);
    }
    document.body.removeChild(tempInput);
  }

  function handleSuccess(btn) {
    var originalIcon = btn.innerHTML;
    var copiedText = btn.getAttribute('data-copied-label') || 'Copied!';

    btn.classList.add('copied');
    btn.innerHTML = '<i class="bi bi-check2"></i>';
    btn.setAttribute('aria-label', copiedText);

    showLegalToast(copiedText);

    setTimeout(function () {
      btn.classList.remove('copied');
      btn.innerHTML = originalIcon;
    }, 2000);
  }

  // 3. Print Official Record Action
  var printButtons = document.querySelectorAll('.legal-print-btn');
  printButtons.forEach(function (pBtn) {
    pBtn.addEventListener('click', function (e) {
      e.preventDefault();
      window.print();
    });
  });

  // 4. Share / Copy Link Action
  var shareButtons = document.querySelectorAll('.legal-share-btn');
  shareButtons.forEach(function (sBtn) {
    sBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var pageUrl = window.location.href;
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(pageUrl).then(function () {
          var copiedText = sBtn.getAttribute('data-copied-label') || 'Copied!';
          showLegalToast(copiedText);
        });
      }
    });
  });
});
