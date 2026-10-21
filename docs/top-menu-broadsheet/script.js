/**
 * Stacked Top Menu — Prototype 3: Broadsheet Edition (Amed Bülten Live Parity)
 * Mirrors templates/amida/static/js/top-menu.js & login-modal.js behavior
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.querySelector('.mobile-overlay');
    const html = document.documentElement;
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');

    // 1. Dark Mode Controller (data-bs-theme="light|dark")
    function getThemeIcons() {
      return [
        darkModeToggle ? darkModeToggle.querySelector('i') : null,
        darkModeToggleMobile ? darkModeToggleMobile.querySelector('i') : null
      ].filter(Boolean);
    }

    function applyTheme(theme) {
      html.setAttribute('data-bs-theme', theme);
      const icons = getThemeIcons();
      icons.forEach(function (icon) {
        if (theme === 'dark') {
          icon.classList.replace('bi-moon-stars', 'bi-sun');
        } else {
          icon.classList.replace('bi-sun', 'bi-moon-stars');
        }
      });
    }

    function toggleDarkMode() {
      const isDark = html.getAttribute('data-bs-theme') === 'dark';
      applyTheme(isDark ? 'light' : 'dark');
    }

    if (darkModeToggle) darkModeToggle.addEventListener('click', toggleDarkMode);
    if (darkModeToggleMobile) darkModeToggleMobile.addEventListener('click', toggleDarkMode);

    // 2. Mobile Offcanvas Drawer Controller
    const mobileDrawer = document.getElementById('amidaMobileMenu');
    const drawerOpenBtn = document.getElementById('mobileDrawerOpenBtn');
    const drawerCloseBtn = document.getElementById('mobileDrawerCloseBtn');

    function openMobileDrawer() {
      if (!mobileDrawer) return;
      mobileDrawer.classList.add('show', 'is-open');
      mobileDrawer.setAttribute('aria-hidden', 'false');
      if (overlay) overlay.classList.add('show');
      document.body.style.overflow = 'hidden';
      if (drawerOpenBtn) drawerOpenBtn.setAttribute('aria-expanded', 'true');
    }

    function closeMobileDrawer() {
      if (!mobileDrawer) return;
      mobileDrawer.classList.remove('show', 'is-open');
      mobileDrawer.setAttribute('aria-hidden', 'true');
      if (overlay) overlay.classList.remove('show');
      document.body.style.overflow = '';
      if (drawerOpenBtn) drawerOpenBtn.setAttribute('aria-expanded', 'false');
    }

    if (drawerOpenBtn) {
      drawerOpenBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const isOpen = mobileDrawer && mobileDrawer.classList.contains('show');
        if (isOpen) {
          closeMobileDrawer();
        } else {
          openMobileDrawer();
        }
      });
    }

    if (drawerCloseBtn) {
      drawerCloseBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeMobileDrawer();
      });
    }

    if (overlay) {
      overlay.addEventListener('click', closeMobileDrawer);
    }

    // 3. Headroom Scroll Physics (matching top-menu.js)
    const navbar = document.getElementById('amidaStackedHeader');
    if (navbar) {
      let lastScrollTop = 0;
      const safeZone = 120;

      window.addEventListener('scroll', function () {
        const scrollTop = Math.max(0, window.pageYOffset || document.documentElement.scrollTop);
        if (scrollTop > 20) {
          navbar.classList.add('is-scrolled');
        } else {
          navbar.classList.remove('is-scrolled', 'navbar-hidden');
        }

        if (scrollTop > safeZone && scrollTop > lastScrollTop + 12) {
          navbar.classList.add('navbar-hidden');
        } else if (scrollTop < lastScrollTop - 14 || scrollTop <= safeZone) {
          navbar.classList.remove('navbar-hidden');
        }
        lastScrollTop = scrollTop;
      }, { passive: true });
    }

    // 4. Two-Step Login Modal Flow
    const loginStep1 = document.getElementById('loginStep1');
    const loginStep2 = document.getElementById('loginStep2');
    const loginUsername = document.getElementById('loginUsername');
    const loginPassword = document.getElementById('loginPassword');
    const displayUsername = document.getElementById('displayUsername');
    const btnNextStep = document.getElementById('btnNextStep');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (btnNextStep && loginStep1 && loginStep2 && loginUsername) {
      btnNextStep.addEventListener('click', function () {
        const val = loginUsername.value.trim();
        if (!val) {
          loginUsername.focus();
          return;
        }
        if (displayUsername) displayUsername.textContent = '@' + val;
        loginStep1.classList.add('d-none');
        loginStep2.classList.remove('d-none');
        if (loginPassword) loginPassword.focus();
      });
    }

    if (btnBackToStep1 && loginStep1 && loginStep2) {
      btnBackToStep1.addEventListener('click', function () {
        loginStep2.classList.add('d-none');
        loginStep1.classList.remove('d-none');
        if (loginUsername) loginUsername.focus();
      });
    }

    if (togglePassword && loginPassword && togglePasswordIcon) {
      togglePassword.addEventListener('click', function () {
        const isPwd = loginPassword.getAttribute('type') === 'password';
        loginPassword.setAttribute('type', isPwd ? 'text' : 'password');
        togglePasswordIcon.className = isPwd ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    }

    // 5. Guest vs Member State Switcher
    const toggleAuthBtn = document.getElementById('toggleAuthStateBtn');
    const guestDesktop = document.getElementById('guestAuthDesktop');
    const userDesktop = document.getElementById('userProfileDesktop');
    const guestMobile = document.getElementById('guestAuthMobile');
    const userMobile = document.getElementById('userProfileMobile');

    if (toggleAuthBtn && guestDesktop && userDesktop) {
      const guestLabel = toggleAuthBtn.getAttribute('data-label-guest') || 'Guest View';
      const memberLabel = toggleAuthBtn.getAttribute('data-label-member') || 'Member View';

      toggleAuthBtn.addEventListener('click', function () {
        const isGuest = !guestDesktop.classList.contains('d-none');
        if (isGuest) {
          guestDesktop.classList.add('d-none');
          guestDesktop.classList.remove('d-lg-flex');
          userDesktop.classList.remove('d-none');
          if (guestMobile) guestMobile.classList.add('d-none');
          if (userMobile) userMobile.classList.remove('d-none');
          toggleAuthBtn.textContent = memberLabel;
        } else {
          guestDesktop.classList.remove('d-none');
          guestDesktop.classList.add('d-lg-flex');
          userDesktop.classList.add('d-none');
          if (guestMobile) guestMobile.classList.remove('d-none');
          if (userMobile) userMobile.classList.add('d-none');
          toggleAuthBtn.textContent = guestLabel;
        }
      });
    }
  });
})();
