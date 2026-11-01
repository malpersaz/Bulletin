/**
 * Amed Framework - Cookie Preferences Manager
 * Standard: Super Polish / Impeccable Vanilla JS
 */

(function () {
  'use strict';

  var COOKIE_NAME = 'cookie_consent';
  var STORAGE_KEY = 'af_cookie_preferences';
  var COOKIE_EXPIRE_DAYS = 180; // 6 months recommended for GDPR

  function getStoredConsent() {
    // 1. Try cookie
    var match = document.cookie.match(new RegExp('(^|;\\s*)' + COOKIE_NAME + '=([^;]*)'));
    if (match && match[2]) {
      try {
        var decoded = decodeURIComponent(match[2]);
        return JSON.parse(decoded);
      } catch (e) {
        // Fallback below
      }
    }

    // 2. Try localStorage fallback
    try {
      var local = localStorage.getItem(STORAGE_KEY);
      if (local) {
        return JSON.parse(local);
      }
    } catch (e) {
      // LocalStorage blocked
    }

    // Default: Necessary always true, others false until opted-in
    return {
      necessary: true,
      functional: true,
      analytics: false,
      marketing: false,
      timestamp: null
    };
  }

  function setConsentCookie(consent) {
    var consentJson = JSON.stringify(consent);
    var date = new Date();
    date.setTime(date.getTime() + (COOKIE_EXPIRE_DAYS * 24 * 60 * 60 * 1000));
    var expires = '; expires=' + date.toUTCString();

    var isSecure = window.location.protocol === 'https:';
    var cookieString = COOKIE_NAME + '=' + encodeURIComponent(consentJson) + expires + '; path=/; SameSite=Lax' + (isSecure ? '; Secure' : '');
    document.cookie = cookieString;

    try {
      localStorage.setItem(STORAGE_KEY, consentJson);
    } catch (e) {
      // Storage unavailable
    }

    // Dispatch global custom event for any tracking script listeners
    window.dispatchEvent(new CustomEvent('cookie_consent_updated', { detail: consent }));
  }

  function showToast(message) {
    var toast = document.getElementById('cookie-save-toast');
    if (!toast) return;

    if (message) {
      var textEl = toast.querySelector('.cookie-toast-text');
      if (textEl) textEl.textContent = message;
    }

    toast.classList.add('show');
    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(function () {
      toast.classList.remove('show');
    }, 4000);
  }

  function syncUIFromConsent() {
    var consent = getStoredConsent();

    var functionalInput = document.getElementById('cookie-toggle-functional');
    var analyticsInput = document.getElementById('cookie-toggle-analytics');
    var marketingInput = document.getElementById('cookie-toggle-marketing');

    if (functionalInput) functionalInput.checked = !!consent.functional;
    if (analyticsInput) analyticsInput.checked = !!consent.analytics;
    if (marketingInput) marketingInput.checked = !!consent.marketing;
  }

  function saveCurrentPreferences() {
    var functionalInput = document.getElementById('cookie-toggle-functional');
    var analyticsInput = document.getElementById('cookie-toggle-analytics');
    var marketingInput = document.getElementById('cookie-toggle-marketing');

    var consent = {
      necessary: true,
      functional: functionalInput ? functionalInput.checked : true,
      analytics: analyticsInput ? analyticsInput.checked : false,
      marketing: marketingInput ? marketingInput.checked : false,
      timestamp: new Date().toISOString()
    };

    setConsentCookie(consent);
    showToast();
  }

  function acceptAll() {
    var functionalInput = document.getElementById('cookie-toggle-functional');
    var analyticsInput = document.getElementById('cookie-toggle-analytics');
    var marketingInput = document.getElementById('cookie-toggle-marketing');

    if (functionalInput) functionalInput.checked = true;
    if (analyticsInput) analyticsInput.checked = true;
    if (marketingInput) marketingInput.checked = true;

    saveCurrentPreferences();
  }

  function rejectOptional() {
    var functionalInput = document.getElementById('cookie-toggle-functional');
    var analyticsInput = document.getElementById('cookie-toggle-analytics');
    var marketingInput = document.getElementById('cookie-toggle-marketing');

    if (functionalInput) functionalInput.checked = false;
    if (analyticsInput) analyticsInput.checked = false;
    if (marketingInput) marketingInput.checked = false;

    saveCurrentPreferences();
  }

  document.addEventListener('DOMContentLoaded', function () {
    syncUIFromConsent();

    var btnSave = document.getElementById('btn-save-cookie-pref');
    var btnAcceptAll = document.getElementById('btn-cookie-accept-all');
    var btnAcceptAllTop = document.getElementById('btn-cookie-accept-all-top');
    var btnRejectOpt = document.getElementById('btn-cookie-reject-opt');
    var btnRejectOptTop = document.getElementById('btn-cookie-reject-opt-top');

    if (btnSave) {
      btnSave.addEventListener('click', saveCurrentPreferences);
    }
    if (btnAcceptAll) {
      btnAcceptAll.addEventListener('click', acceptAll);
    }
    if (btnAcceptAllTop) {
      btnAcceptAllTop.addEventListener('click', acceptAll);
    }
    if (btnRejectOpt) {
      btnRejectOpt.addEventListener('click', rejectOptional);
    }
    if (btnRejectOptTop) {
      btnRejectOptTop.addEventListener('click', rejectOptional);
    }
  });

  // Expose helper API globally for developer/modal integration
  window.AFCookieConsent = {
    get: getStoredConsent,
    set: setConsentCookie,
    acceptAll: acceptAll,
    rejectOptional: rejectOptional
  };
})();
