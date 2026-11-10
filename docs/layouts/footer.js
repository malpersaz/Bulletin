/**
 * Amed Bulletin — Editorial Footer Interactive Controller
 * docs/layouts/footer.js
 * Strict Code Separation: 100% external JS, zero inline events.
 */

document.addEventListener('DOMContentLoaded', () => {
  const stage = document.getElementById('previewStage');
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeIcon = document.getElementById('themeIcon');
  const themeLabel = document.getElementById('themeLabel');

  const btnViewDesktop = document.getElementById('btnViewDesktop');
  const btnViewTablet = document.getElementById('btnViewTablet');
  const btnViewMobile = document.getElementById('btnViewMobile');

  const btnCompareNew = document.getElementById('btnCompareNew');
  const btnCompareOld = document.getElementById('btnCompareOld');
  const newFooter = document.getElementById('proposedFooter');
  const oldFooter = document.getElementById('oldFooter');

  const backToTopBtn = document.getElementById('backToTopBtn');

  // Theme toggle
  let isDark = false;
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      isDark = !isDark;
      if (isDark) {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        document.body.classList.add('dark-mode');
        themeIcon.className = 'bi bi-sun-fill';
        themeLabel.textContent = 'Ronî (Light)';
      } else {
        document.documentElement.setAttribute('data-bs-theme', 'light');
        document.body.classList.remove('dark-mode');
        themeIcon.className = 'bi bi-moon-stars-fill';
        themeLabel.textContent = 'Tarî (Dark)';
      }
    });
  }

  // Viewport mode switcher
  const viewButtons = [btnViewDesktop, btnViewTablet, btnViewMobile];
  function setViewport(mode) {
    viewButtons.forEach(btn => btn?.classList.remove('active'));
    if (!stage) return;

    stage.classList.remove('view-tablet', 'view-mobile');
    if (mode === 'tablet') {
      stage.classList.add('view-tablet');
      btnViewTablet?.classList.add('active');
    } else if (mode === 'mobile') {
      stage.classList.add('view-mobile');
      btnViewMobile?.classList.add('active');
    } else {
      btnViewDesktop?.classList.add('active');
    }
  }

  btnViewDesktop?.addEventListener('click', () => setViewport('desktop'));
  btnViewTablet?.addEventListener('click', () => setViewport('tablet'));
  btnViewMobile?.addEventListener('click', () => setViewport('mobile'));

  // Compare New vs Old
  btnCompareNew?.addEventListener('click', () => {
    btnCompareNew.classList.add('active');
    btnCompareOld?.classList.remove('active');
    if (newFooter) newFooter.classList.remove('is-hidden');
    if (oldFooter) oldFooter.classList.add('is-hidden');
  });

  btnCompareOld?.addEventListener('click', () => {
    btnCompareOld.classList.add('active');
    btnCompareNew?.classList.remove('active');
    if (newFooter) newFooter.classList.add('is-hidden');
    if (oldFooter) oldFooter.classList.remove('is-hidden');
  });

  // Smooth scroll back to top
  backToTopBtn?.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
});
