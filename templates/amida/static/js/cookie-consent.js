(function () {
    var consentKey = 'cookie_consent_accepted';
    var banner = document.getElementById('cookieConsentBanner');
    var acceptButton = document.getElementById('acceptCookies');

    if (!banner || !acceptButton) {
        return;
    }

    function getStoredConsent() {
        try {
            if (window.localStorage && window.localStorage.getItem(consentKey) === 'true') {
                return true;
            }
        } catch (error) {
        }

        try {
            if (window.jQuery && typeof window.jQuery.cookie === 'function') {
                return window.jQuery.cookie(consentKey) === 'true';
            }
        } catch (error) {
        }

        var match = document.cookie.match(new RegExp('(^|;\\s*)' + consentKey + '=true'));
        if (match) {
            return true;
        }

        return false;
    }

    function persistConsent() {
        try {
            if (window.localStorage) {
                window.localStorage.setItem(consentKey, 'true');
            }
        } catch (error) {
        }

        try {
            if (window.jQuery && typeof window.jQuery.cookie === 'function') {
                window.jQuery.cookie(consentKey, 'true', { expires: 365, path: '/' });
            } else {
                document.cookie = consentKey + '=true; path=/; max-age=31536000; SameSite=Lax';
            }
        } catch (error) {
            console.warn('Cookie writing failed:', error);
        }
    }

    function hideBanner() {
        banner.classList.remove('is-visible');
        banner.setAttribute('aria-hidden', 'true');
        setTimeout(function () {
            banner.classList.add('d-none');
        }, 400);
    }

    if (getStoredConsent()) {
        banner.classList.add('d-none');
        return;
    }

    banner.classList.remove('d-none');
    setTimeout(function () {
        banner.classList.add('is-visible');
        banner.setAttribute('aria-hidden', 'false');
    }, 150);

    acceptButton.addEventListener('click', function () {
        persistConsent();
        hideBanner();
    });
}());
