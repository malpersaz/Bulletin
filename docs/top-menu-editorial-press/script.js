/**
 * Stacked Top Menu — Navy & Forest Prototype
 * Interactive behavior for Mobile Offcanvas Drawer, Dark Mode, Two-Step Login Modal, and Session Preview Toggle.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const header = document.getElementById('amidaStackedHeader');
    const mobileDrawer = document.getElementById('amidaMobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileOpenBtn = document.getElementById('mobileDrawerOpenBtn');
    const mobileCloseBtn = document.getElementById('mobileDrawerCloseBtn');

    const darkModeBtnDesktop = document.getElementById('darkModeToggle');
    const darkModeBtnMobile = document.getElementById('darkModeToggleMobile');

    const loginStep1 = document.getElementById('loginStep1');
    const loginStep2 = document.getElementById('loginStep2');
    const loginUsername = document.getElementById('loginUsername');
    const loginPassword = document.getElementById('loginPassword');
    const displayUsername = document.getElementById('displayUsername');
    const btnNextStep = document.getElementById('btnNextStep');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    const toggleAuthStateBtn = document.getElementById('toggleAuthStateDemo');
    const guestBlockDesktop = document.getElementById('guestAuthDesktop');
    const userBlockDesktop = document.getElementById('userProfileDesktop');
    const guestBlockMobile = document.getElementById('guestAuthMobile');
    const userBlockMobile = document.getElementById('userProfileMobile');

    // 1. Sticky Scroll Elevation
    function handleScroll() {
      if (!header) return;
      if (window.scrollY > 12) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();

    // 2. Mobile Drawer Open / Close
    function openMobileDrawer() {
      if (!mobileDrawer || !mobileOverlay) return;
      mobileDrawer.classList.add('is-open');
      mobileDrawer.setAttribute('aria-hidden', 'false');
      mobileOverlay.classList.add('is-visible');
      if (mobileOpenBtn) mobileOpenBtn.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeMobileDrawer() {
      if (!mobileDrawer || !mobileOverlay) return;
      mobileDrawer.classList.remove('is-open');
      mobileDrawer.setAttribute('aria-hidden', 'true');
      mobileOverlay.classList.remove('is-visible');
      if (mobileOpenBtn) mobileOpenBtn.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    if (mobileOpenBtn) {
      mobileOpenBtn.addEventListener('click', openMobileDrawer);
    }
    if (mobileCloseBtn) {
      mobileCloseBtn.addEventListener('click', closeMobileDrawer);
    }
    if (mobileOverlay) {
      mobileOverlay.addEventListener('click', closeMobileDrawer);
    }

    // 3. Dark Mode Toggle
    function toggleDarkMode() {
      const root = document.documentElement;
      const isDark = root.getAttribute('data-theme') === 'dark';
      const nextTheme = isDark ? 'light' : 'dark';
      root.setAttribute('data-theme', nextTheme);

      const icons = document.querySelectorAll('.js-theme-icon');
      icons.forEach(function (icon) {
        icon.className = nextTheme === 'dark' ? 'bi bi-sun js-theme-icon' : 'bi bi-moon-stars js-theme-icon';
      });
    }

    if (darkModeBtnDesktop) {
      darkModeBtnDesktop.addEventListener('click', toggleDarkMode);
    }
    if (darkModeBtnMobile) {
      darkModeBtnMobile.addEventListener('click', toggleDarkMode);
    }

    // 4. Two-Step Login Modal Flow
    if (btnNextStep && loginStep1 && loginStep2 && loginUsername) {
      btnNextStep.addEventListener('click', function () {
        const val = loginUsername.value.trim();
        if (!val) {
          loginUsername.focus();
          return;
        }
        if (displayUsername) {
          displayUsername.textContent = '@' + val;
        }
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

    if (togglePasswordBtn && loginPassword && togglePasswordIcon) {
      togglePasswordBtn.addEventListener('click', function () {
        const isPwd = loginPassword.getAttribute('type') === 'password';
        loginPassword.setAttribute('type', isPwd ? 'text' : 'password');
        togglePasswordIcon.className = isPwd ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    }

    // 5. Demo Switcher: Guest vs Logged-in User View
    if (toggleAuthStateBtn && guestBlockDesktop && userBlockDesktop) {
      const labelGuest = toggleAuthStateBtn.getAttribute('data-label-guest') || 'Guest View';
      const labelMember = toggleAuthStateBtn.getAttribute('data-label-member') || 'Member View';

      toggleAuthStateBtn.addEventListener('click', function () {
        const isCurrentlyGuest = !guestBlockDesktop.classList.contains('d-none');
        if (isCurrentlyGuest) {
          guestBlockDesktop.classList.add('d-none');
          guestBlockDesktop.classList.remove('d-lg-flex');
          userBlockDesktop.classList.remove('d-none');

          if (guestBlockMobile) guestBlockMobile.classList.add('d-none');
          if (userBlockMobile) userBlockMobile.classList.remove('d-none');
          toggleAuthStateBtn.textContent = labelMember;
        } else {
          guestBlockDesktop.classList.remove('d-none');
          guestBlockDesktop.classList.add('d-lg-flex');
          userBlockDesktop.classList.add('d-none');

          if (guestBlockMobile) guestBlockMobile.classList.remove('d-none');
          if (userBlockMobile) userBlockMobile.classList.add('d-none');
          toggleAuthStateBtn.textContent = labelGuest;
        }
      });
    }
  });
})();
