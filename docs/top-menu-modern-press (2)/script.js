/**
 * Stacked Top Menu — Prototype 4: Modern Press (Amed Bülten Live Parity)
 * Mirrors templates/amida/static/js/top-menu.js & login-modal.js behavior
 * Includes Super Polished Search Interactions (/ shortcut, has-value state, clear button)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.querySelector('.mobile-overlay');
    const html = document.documentElement;
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');

    // 1. Super Polished Search Box Controller
    const searchWrapper = document.getElementById('stackedSearchWrapper');
    const searchInput = document.getElementById('stackedSearchInput');
    const searchClearBtn = document.getElementById('stackedSearchClear');

    function syncSearchValueState() {
      if (!searchWrapper || !searchInput) return;
      if (searchInput.value.trim().length > 0) {
        searchWrapper.classList.add('has-value');
      } else {
        searchWrapper.classList.remove('has-value');
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', syncSearchValueState);
      syncSearchValueState();
    }

    if (searchClearBtn && searchInput) {
      searchClearBtn.addEventListener('click', function () {
        searchInput.value = '';
        syncSearchValueState();
        searchInput.focus();
      });
    }

    // Global "/" keyboard shortcut to focus search (unless typing in an input/textarea)
    document.addEventListener('keydown', function (e) {
      if (e.key === '/' && searchInput) {
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if (activeTag !== 'input' && activeTag !== 'textarea' && !document.activeElement?.isContentEditable) {
          e.preventDefault();
          searchInput.focus();
          searchInput.select();
        }
      } else if (e.key === 'Escape' && document.activeElement === searchInput) {
        searchInput.blur();
      }
    });

    // 2. Dark Mode Controller (data-bs-theme="light|dark")
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

    // 3. Mobile Offcanvas Drawer Controller
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

    // 6. Guest vs Member State Switcher
    const toggleAuthBtn = document.getElementById('toggleAuthStateBtn');
    const guestDesktop = document.getElementById('guestAuthDesktop');
    const userDesktop = document.getElementByById('userProfileDesktop');
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
