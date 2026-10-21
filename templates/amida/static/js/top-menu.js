/* Top Menu JavaScript - Amida Theme */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize mobile overlay
    const overlay = document.querySelector('.mobile-overlay');
    if (overlay) {
        overlay.style.pointerEvents = 'none';
    }

    // --- Search Box Controller (Modern Press: / shortcut & clear button) ---
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

    // --- Mobile Offcanvas Drawer Controller ---
    const mobileDrawer = document.getElementById('amidaMobileMenu');
    const drawerOpenBtn = document.getElementById('mobileDrawerOpenBtn') || document.querySelector('.btn-header-toggler');
    const drawerCloseBtn = document.getElementById('mobileDrawerCloseBtn') || document.querySelector('.mobile-menu-close');

    function openMobileDrawer() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.add('show', 'is-open');
        mobileDrawer.setAttribute('aria-hidden', 'false');
        if (overlay) {
            overlay.classList.add('show');
            overlay.style.pointerEvents = 'auto';
        }
        document.body.style.overflow = 'hidden';
        drawerOpenBtn?.setAttribute('aria-expanded', 'true');
    }

    function closeMobileDrawer() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.remove('show', 'is-open');
        mobileDrawer.setAttribute('aria-hidden', 'true');
        if (overlay) {
            overlay.classList.remove('show');
            overlay.style.pointerEvents = 'none';
        }
        document.body.style.overflow = '';
        drawerOpenBtn?.setAttribute('aria-expanded', 'false');
    }

    function toggleMobileDrawer(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const isOpen = mobileDrawer?.classList.contains('show') || mobileDrawer?.classList.contains('is-open');
        if (isOpen) {
            closeMobileDrawer();
        } else {
            openMobileDrawer();
        }
    }

    window.openMobileMenu = openMobileDrawer;
    window.closeMobileMenu = closeMobileDrawer;
    window.toggleMobileMenu = toggleMobileDrawer;

    drawerOpenBtn?.addEventListener('click', toggleMobileDrawer);

    drawerCloseBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeMobileDrawer();
    });

    if (overlay) {
        overlay.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileDrawer();
        });
    }

    // Close mobile menu when navigating on mobile
    document.querySelectorAll('#amidaMobileMenu .nav-link').forEach(link => {
        link.addEventListener('click', function () {
            if (!link.classList.contains('dropdown-toggle')) {
                setTimeout(closeMobileDrawer, 180);
            }
        });
    });

    // Close mobile menu when modal trigger or auth button is clicked inside drawer
    document.querySelectorAll('#amidaMobileMenu [data-bs-toggle="modal"], #amidaMobileMenu .btn-header-login, #amidaMobileMenu .btn-header-register').forEach(btn => {
        btn.addEventListener('click', function () {
            closeMobileDrawer();
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
        const swipeThreshold = 50;
        const verticalThreshold = 75;

        const diffX = touchEndX - touchStartX;
        const diffY = touchEndY - touchStartY;

        if (Math.abs(diffX) > swipeThreshold && Math.abs(diffY) < verticalThreshold) {
            const isOpen = mobileDrawer?.classList.contains('show') || mobileDrawer?.classList.contains('is-open');
            // Swipe right to close drawer
            if (diffX > 0 && isOpen) {
                closeMobileDrawer();
            }
            // Swipe left from right edge to open drawer
            else if (diffX < 0 && !isOpen && touchStartX > window.innerWidth - 45) {
                openMobileDrawer();
            }
        }
    }

    // --- Keyboard Accessibility ---
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileDrawer();
            const openDropdowns = document.querySelectorAll('#amidaStackedHeader .dropdown-menu.show, #amidaMobileMenu .dropdown-menu.show, #amidaMainHeader .dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
                const btn = dropdown.previousElementSibling;
                btn?.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // --- High-Performance Headroom Scroll Physics ---
    const navbar = document.querySelector('#amidaStackedHeader') || document.querySelector('#amidaMainHeader');
    if (navbar) {
        const navbarCollapse = document.querySelector('.navbar-collapse');
        let lastScrollTop = 0;
        let ticking = false;
        let upScrollDistance = 0;
        let downScrollDistance = 0;

        const safeZone = 140; // Pixels from top where navbar remains anchored
        const downThreshold = 12; // Minimum downward travel required to trigger hide
        const upThreshold = 16; // Intentional upward travel required to reveal (prevents rapid toggle)

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    const scrollTop = Math.max(0, window.pageYOffset || document.documentElement.scrollTop);
                    const isMobileOpen = Boolean(
                        mobileDrawer?.classList.contains('show') ||
                        mobileDrawer?.classList.contains('is-open') ||
                        navbarCollapse?.classList.contains('show')
                    );
                    const hasOpenDropdown = Boolean(navbar.querySelector('.dropdown-menu.show'));
                    const diff = scrollTop - lastScrollTop;

                    if (diff > 0) {
                        // Scrolling DOWN
                        downScrollDistance += diff;
                        upScrollDistance = 0;

                        if (!isMobileOpen && !hasOpenDropdown && scrollTop > safeZone) {
                            if (downScrollDistance >= downThreshold) {
                                navbar.classList.add('navbar-hidden');
                            }
                        }
                    } else if (diff < 0) {
                        // Scrolling UP
                        upScrollDistance += Math.abs(diff);
                        downScrollDistance = 0;

                        if (upScrollDistance >= upThreshold || scrollTop <= safeZone) {
                            navbar.classList.remove('navbar-hidden');
                        }
                    }

                    // Scrolled state handling with zero layout bounce
                    if (scrollTop <= 20) {
                        navbar.classList.remove('navbar-hidden');
                        navbar.classList.remove('is-scrolled');
                        navbar.classList.remove('scrolled');
                        upScrollDistance = 0;
                        downScrollDistance = 0;
                    } else if (scrollTop > safeZone) {
                        navbar.classList.add('is-scrolled');
                        navbar.classList.add('scrolled');
                    }

                    lastScrollTop = scrollTop;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }
});
