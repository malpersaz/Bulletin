/**
 * Navbar JavaScript functionality
 * Handles scroll effects, dropdown animations, language switching and search
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add scroll effect to navbar
    const navbar = document.getElementById('mainNavbarElement');
    let lastScrollY = window.scrollY;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
        lastScrollY = window.scrollY;
    });

    // BOOTSTRAP 5.3.8 NOTU:
    // Dropdown animations artık Bootstrap 5.3.8 ile otomatik olarak sağlanır.
    // show.bs.dropdown, hide.bs.dropdown gibi event'ler mevcuttur.
    // Özel animasyon kodlarına gerek kalmamıştır.

    // Language switcher functionality
    const languageLinks = document.querySelectorAll('[data-lang]');
    languageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.dataset.lang;
            // Implement language switching logic here
            console.log('Switching to language:', lang);
        });
    });

    // Search functionality
    const searchForm = document.querySelector('.navbar-search form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = this.querySelector('input[type="search"]').value;
            // Implement search functionality here
            console.log('Searching for:', searchTerm);
        });
    }
});