/* Top Menu JavaScript - Amida Theme (Rojname) */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize mobile overlay
    const overlay = document.querySelector('.mobile-overlay');
    if (overlay) {
        overlay.style.pointerEvents = 'none';
    }

    // --- Dark Mode Controller ---
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');
    const html = document.documentElement;

    function getThemeIcons() {
        return [
            darkModeToggle?.querySelector('i'),
            darkModeToggleMobile?.querySelector('i')
        ].filter(Boolean);
    }

    function applyTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        const icons = getThemeIcons();
        icons.forEach(icon => {
            if (theme === 'dark') {
                icon.classList.replace('bi-moon-stars', 'bi-sun');
            } else {
                icon.classList.replace('bi-sun', 'bi-moon-stars');
            }
        });
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
    }

    let savedTheme = null;
    try {
        savedTheme = localStorage.getItem('theme');
    } catch (e) {
        // Handle localStorage restrictions in private browsing
    }

    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        applyTheme('dark');
    } else {
        applyTheme('light');
    }

    function toggleDarkMode() {
        const isDark = html.getAttribute('data-bs-theme') === 'dark';
        const nextTheme = isDark ? 'light' : 'dark';
        applyTheme(nextTheme);
        try {
            localStorage.setItem('theme', nextTheme);
        } catch (e) {}
    }

    darkModeToggle?.addEventListener('click', toggleDarkMode);
    darkModeToggleMobile?.addEventListener('click', toggleDarkMode);

    // Sync with OS theme changes if user has not set explicit preference
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
        let hasStoredTheme = false;
        try {
            hasStoredTheme = Boolean(localStorage.getItem('theme'));
        } catch (err) {}

        if (!hasStoredTheme) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });

    // --- Mobile Drawer & Overlay Management ---
    const navbarCollapse = document.querySelector('#amidaMobileMenu');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const drawerCloseBtn = document.querySelector('.mobile-menu-close');

    function closeMobileMenu() {
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                const instance = bootstrap.Collapse.getInstance(navbarCollapse);
                if (instance) {
                    instance.hide();
                    return;
                }
            }
            navbarToggler?.click();
        }
    }

    window.closeMobileMenu = closeMobileMenu;

    drawerCloseBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeMobileMenu();
    });

    function toggleMobileMenuState(isOpen) {
        if (!overlay) return;
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            overlay.classList.add('show');
            overlay.style.display = 'block';
            overlay.style.pointerEvents = 'auto';
        } else {
            document.body.style.overflow = '';
            overlay.classList.remove('show');
            overlay.style.display = 'none';
            overlay.style.pointerEvents = 'none';
        }
    }

    if (overlay) {
        overlay.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileMenu();
        });
    }

    if (navbarCollapse) {
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isOpen = navbarCollapse.classList.contains('show');
                    toggleMobileMenuState(isOpen);
                }
            });
        });

        observer.observe(navbarCollapse, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Close mobile menu when navigating on mobile
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 991.98 && !link.classList.contains('dropdown-toggle')) {
                setTimeout(closeMobileMenu, 200);
            }
        });
    });

    // --- Touch Swipe Gesture Support ---
    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartY = 0;
    let touchEndY = 0;

    document.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 60;
        const verticalThreshold = 80;

        const diffX = touchEndX - touchStartX;
        const diffY = touchEndY - touchStartY;

        if (Math.abs(diffX) > swipeThreshold && Math.abs(diffY) < verticalThreshold) {
            // Swipe right to close drawer
            if (diffX > 0 && navbarCollapse?.classList.contains('show')) {
                closeMobileMenu();
            }
            // Swipe left from right edge to open drawer
            else if (diffX < 0 && navbarCollapse && !navbarCollapse.classList.contains('show')) {
                if (touchStartX > window.innerWidth - 45) {
                    navbarToggler?.click();
                }
            }
        }
    }

    // --- Keyboard Accessibility ---
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
            const openDropdowns = document.querySelectorAll('#amidaMainHeader .dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
                const btn = dropdown.previousElementSibling;
                btn?.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // --- High-Performance Scroll Effects ---
    const navbar = document.querySelector('#amidaMainHeader');
    if (navbar) {
        let lastScrollTop = 0;
        let ticking = false;

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const isMobileOpen = navbarCollapse?.classList.contains('show');
                    const hasOpenDropdown = Boolean(navbar.querySelector('.dropdown-menu.show'));

                    if (!isMobileOpen && !hasOpenDropdown) {
                        if (scrollTop > lastScrollTop && scrollTop > 120) {
                            navbar.classList.add('navbar-hidden');
                        } else {
                            navbar.classList.remove('navbar-hidden');
                        }
                    } else {
                        navbar.classList.remove('navbar-hidden');
                    }

                    if (scrollTop > 40) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }

                    lastScrollTop = Math.max(0, scrollTop);
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }
});
