/* Top Menu JavaScript - Extracted from top_menu.twig */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize mobile overlay to prevent interaction issues
    const overlay = document.querySelector('.mobile-overlay');
    if (overlay) {
        overlay.style.pointerEvents = 'none';
    }

    // Dark Mode Toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');
    const html = document.documentElement;
    const icon = darkModeToggle?.querySelector('i');
    const mobileIcon = darkModeToggleMobile?.querySelector('i');

    // Check for saved theme preference or default to light mode
    let currentTheme = 'light';
    try {
        currentTheme = localStorage.getItem('theme') || 'light';
    } catch (e) {
        // Silently handle localStorage access errors (e.g., in private browsing mode)
        // Using default theme
    }

    if (currentTheme === 'dark') {
        html.setAttribute('data-bs-theme', 'dark');
        icon?.classList.replace('bi-moon-stars', 'bi-sun');
        mobileIcon?.classList.replace('bi-moon-stars', 'bi-sun');
    }

    // Dark mode toggle functionality for desktop
    darkModeToggle?.addEventListener('click', function () {
        toggleDarkMode();
    });

    // Dark mode toggle functionality for mobile
    darkModeToggleMobile?.addEventListener('click', function () {
        toggleDarkMode();
    });

    // Unified dark mode toggle function
    function toggleDarkMode() {
        const isDark = html.getAttribute('data-bs-theme') === 'dark';

        if (isDark) {
            html.setAttribute('data-bs-theme', 'light');
            icon?.classList.replace('bi-sun', 'bi-moon-stars');
            mobileIcon?.classList.replace('bi-sun', 'bi-moon-stars');
            try {
                localStorage.setItem('theme', 'light');
            } catch (e) {
                // Silently handle localStorage access errors
            }
        } else {
            html.setAttribute('data-bs-theme', 'dark');
            icon?.classList.replace('bi-moon-stars', 'bi-sun');
            mobileIcon?.classList.replace('bi-moon-stars', 'bi-sun');
            try {
                localStorage.setItem('theme', 'dark');
            } catch (e) {
                // Silently handle localStorage access errors
            }
        }
    }

    // Enhanced keyboard navigation
    const navbar = document.getElementById('defaultMainHeader');
    navbar?.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
                const button = dropdown.previousElementSibling;
                button?.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Fix dropdown positioning
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.querySelector('#userDropdown + .dropdown-menu');

    if (userDropdown && userDropdownMenu) {
        userDropdown.addEventListener('click', function () {
            setTimeout(() => {
                if (userDropdownMenu.classList.contains('show')) {
                    userDropdownMenu.style.right = '0px';
                    userDropdownMenu.style.left = 'auto';
                    userDropdownMenu.style.transform = 'translateX(0)';
                }
            }, 10);
        });
    }

    // Mobile language dropdown positioning fix
    const languageDropdown = document.getElementById('languageDropdown');
    const languageDropdownMenu = document.querySelector('#languageDropdown + .dropdown-menu');

    function fixLanguageDropdownMobile() {
        if (languageDropdown && languageDropdownMenu) {
            const isMobile = window.innerWidth <= 991.98;

            if (isMobile && languageDropdownMenu.classList.contains('show')) {
                // Ensure language dropdown stays within mobile menu container
                languageDropdownMenu.style.position = 'absolute';
                languageDropdownMenu.style.top = '100%';
                languageDropdownMenu.style.left = '0';
                languageDropdownMenu.style.right = 'auto';
                languageDropdownMenu.style.width = '200px';
                languageDropdownMenu.style.zIndex = '1000';
            }
        }
    }

    if (languageDropdown) {
        languageDropdown.addEventListener('click', function () {
            setTimeout(fixLanguageDropdownMobile, 10);
        });
    }

    const languageDropdownMobile = document.getElementById('languageDropdownMobile');
    if (languageDropdownMobile) {
        // No special fix needed for dropup in flex footer mostly, but ensuring clicks work
    }

    // Fix on window resize
    window.addEventListener('resize', function () {
        setTimeout(fixLanguageDropdownMobile, 50);
    });

    // Enhanced Mobile Menu Functions
    function closeMobileMenu() {
        const navbarCollapse = document.querySelector('#defaultMobileMenu');
        const navbarToggler = document.querySelector('.navbar-toggler');

        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            navbarToggler?.click();
        }
    }

    function openMobileMenu() {
        const navbarCollapse = document.querySelector('#defaultMobileMenu');
        const overlay = document.querySelector('.mobile-overlay');

        if (navbarCollapse && !navbarCollapse.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
            overlay.style.display = 'block';
            overlay.style.pointerEvents = 'auto';
        }
    }

    function toggleMobileMenuState(isOpen) {
        const overlay = document.querySelector('.mobile-overlay');
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

    // Close mobile menu when clicking on overlay
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileMenu();
        });
    }

    // Touch gesture support for mobile
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
        const verticalThreshold = 100; // Limit vertical movement to avoid interfering with scroll

        const diffX = touchEndX - touchStartX;
        const diffY = touchEndY - touchStartY;

        // Check if movement is primarily horizontal
        if (Math.abs(diffX) > swipeThreshold && Math.abs(diffY) < verticalThreshold) {
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('#defaultMobileMenu');

            // Swipe RIGHT to CLOSE menu (since menu is on the right side)
            if (diffX > 0 && navbarCollapse.classList.contains('show')) {
                closeMobileMenu();
            }
            // Swipe LEFT to OPEN menu (pull from right edge)
            else if (diffX < 0 && !navbarCollapse.classList.contains('show')) {
                // Only open if swipe started from the right edge (optional refinement)
                if (touchStartX > window.innerWidth - 50) {
                    navbarToggler?.click();
                }
            }
        }
    }

    // Enhanced keyboard navigation for mobile
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });

    // Monitor mobile menu toggle state
    const navbarCollapse = document.querySelector('#defaultMobileMenu');

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

    // Close mobile menu when clicking on nav links
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 991.98) {
                setTimeout(closeMobileMenu, 200);
            }
        });
    });


});

// Global function for closing mobile menu (accessible from other scripts)
window.closeMobileMenu = function () {
    const navbarCollapse = document.querySelector('#defaultMobileMenu');
    const navbarToggler = document.querySelector('.navbar-toggler');

    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
        navbarToggler?.click();
    }
};

// Scroll Effects
document.addEventListener('DOMContentLoaded', function () {
    let lastScrollTop = 0;
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', function () {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                navbar.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                navbar.style.transform = 'translateY(0)';
            }

            if (scrollTop > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            lastScrollTop = scrollTop;
        });
    }
});
