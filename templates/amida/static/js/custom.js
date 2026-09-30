/**
 * Public Theme Custom JavaScript
 * Modern JavaScript Functions Compatible with Bootstrap 5.3
 */

// Global state container
window.AKM = {
    swiperInstances: {},
    lightbox: null,
    initialized: false
};

// Document Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeAKM();
});

/**
 * Initialize AKM Core
 */
function initializeAKM() {
    if (AKM.initialized) return;

    console.log('Rojname initializing...');

    // Custom sliders
    initializeCustomSliders();

    // Lightbox
    initializeLightbox();

    // Additional features
    initializeOtherFeatures();

    AKM.initialized = true;
    console.log('Rojname initialized successfully!');
}

/**
 * Initialize Custom Sliders
 */
function initializeCustomSliders() {
    // AKM Slider - modernized with Swiper
    const akmSliders = document.querySelectorAll('.akmslider');
    akmSliders.forEach(function(slider, index) {
        const swiperInstance = new Swiper(slider, {
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            }
        });

        AKM.swiperInstances['akmslider_' + index] = swiperInstance;
    });

    // Product Carousel
    const productCarousels = document.querySelectorAll('.product-carousel');
    productCarousels.forEach(function(carousel, index) {
        const swiperInstance = new Swiper(carousel, {
            slidesPerView: 4,
            spaceBetween: 20,
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                1000: { slidesPerView: 4 },
                900: { slidesPerView: 3 },
                600: { slidesPerView: 2 },
                400: { slidesPerView: 1 }
            }
        });

        AKM.swiperInstances['product_' + index] = swiperInstance;
    });
}

/**
 * Initialize GLightbox
 */
function initializeLightbox() {
    AKM.lightbox = GLightbox({
        selector: '[data-bs-toggle="lightbox"]',
        openEffect: 'zoom',
        closeEffect: 'fade',
        touchNavigation: true,
        loop: true,
        autoplayVideo: true,
        onOpen: function() {
            console.log('Lightbox opened');
        },
        onClose: function() {
            console.log('Lightbox closed');
        }
    });
}

/**
 * Initialize Other Features
 */
function initializeOtherFeatures() {
    // Lazy loading with Intersection Observer
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            // Skip empty or invalid href attributes
            if (!href || href === '#') {
                return;
            }
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Modern AKM Slider Class
 */
class AKMSlider {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            loop: true,
            autoplay: true,
            delay: 3000,
            navigation: true,
            pagination: true,
            ...options
        };
        this.init();
    }

    init() {
        const swiperConfig = {
            loop: this.options.loop,
            autoplay: this.options.autoplay ? {
                delay: this.options.delay,
                disableOnInteraction: false,
            } : false,
            pagination: this.options.pagination ? {
                el: this.element.querySelector('.swiper-pagination'),
                clickable: true,
            } : false,
            navigation: this.options.navigation ? {
                nextEl: this.element.querySelector('.swiper-button-next'),
                prevEl: this.element.querySelector('.swiper-button-prev'),
            } : false,
        };

        this.swiper = new Swiper(this.element, swiperConfig);
        return this.swiper;
    }

    destroy() {
        if (this.swiper) {
            this.swiper.destroy();
        }
    }
}

/**
 * Swiper initialization utility
 */
function makeSwiper(name, options = {}) {
    const container = document.querySelector(`#${name}`);
    if (!container) {
        console.warn(`Swiper container #${name} not found`);
        return false;
    }

    const defaultOptions = {
        slidesPerView: 5,
        spaceBetween: 20,
        loop: true,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            1000: { slidesPerView: 5 },
            900: { slidesPerView: 4 },
            600: { slidesPerView: 3 },
            400: { slidesPerView: 2 }
        }
    };

    const swiperInstance = new Swiper(container, { ...defaultOptions, ...options });
    AKM.swiperInstances[name] = swiperInstance;
    return swiperInstance;
}

/**
 * SweetAlert2 notification helpers
 */
function showSuccessAlert(title, message) {
    Swal.fire({
        title: title,
        text: message,
        icon: 'success',
        confirmButtonText: 'OK',
        confirmButtonColor: '#28a745'
    });
}

function showErrorAlert(title, message) {
    Swal.fire({
        title: title,
        text: message,
        icon: 'error',
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc3545'
    });
}

function showConfirmAlert(title, message, confirmCallback) {
    Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && confirmCallback) {
            confirmCallback();
        }
    });
}

/**
 * Initialize Date Pickers
 */
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('.datepicker');
    dateInputs.forEach(function(input) {
        flatpickr(input, {
            locale: "en",
            dateFormat: "d.m.Y",
            minDate: "today",
            allowInput: true
        });
    });
}

/**
 * Initialize Google Maps
 */
function initializeGoogleMaps() {
    if (typeof google !== 'undefined' && document.getElementById('map')) {
        const mapOptions = {
            zoom: 15,
            center: { lat: 37.9144, lng: 40.2306 },
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        };

        const map = new google.maps.Map(document.getElementById('map'), mapOptions);

        const marker = new google.maps.Marker({
            position: { lat: 37.9144, lng: 40.2306 },
            map: map,
            title: 'Rojname'
        });

        return map;
    }
    return null;
}

/**
 * Responsive Menu Toggle
 */
function initializeResponsiveMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });

        // Close menu on outside click
        document.addEventListener('click', function(event) {
            if (!navbarToggler.contains(event.target) && !navbarCollapse.contains(event.target)) {
                navbarCollapse.classList.remove('show');
            }
        });
    }
}

/**
 * Interactive Month Popover Listener
 */
function initializeMonthPopovers() {
    let activePopover = null;

    function closeActive() {
        if (activePopover) {
            activePopover.remove();
            activePopover = null;
        }
    }

    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.month-popover');
        if (!trigger) {
            closeActive();
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if (activePopover && activePopover._trigger === trigger) {
            closeActive();
            return;
        }

        closeActive();

        const ku = trigger.dataset.monthKu || trigger.textContent.trim();
        const en = trigger.dataset.monthEn || '';
        const tr = trigger.dataset.monthTr || '';
        const ar = trigger.dataset.monthAr || '';

        const pop = document.createElement('div');
        pop.className = 'month-popover-box';
        pop._trigger = trigger;

        pop.innerHTML = `
            <div class="month-row"><span class="month-lang">Kurdî</span><span class="month-val">${ku}</span></div>
            ${en ? `<div class="month-row"><span class="month-lang">English</span><span class="month-val">${en}</span></div>` : ''}
            ${tr ? `<div class="month-row"><span class="month-lang">Türkçe</span><span class="month-val">${tr}</span></div>` : ''}
            ${ar ? `<div class="month-row"><span class="month-lang">العربية</span><span class="month-val" dir="rtl">${ar}</span></div>` : ''}
        `;

        document.body.appendChild(pop);

        const rect = trigger.getBoundingClientRect();
        const popRect = pop.getBoundingClientRect();

        let top = rect.bottom + window.scrollY + 6;
        let left = rect.left + window.scrollX + (rect.width / 2) - (popRect.width / 2);

        if (left < 10) left = 10;
        if (left + popRect.width > window.innerWidth - 10) {
            left = window.innerWidth - popRect.width - 10;
        }

        pop.style.top = `${top}px`;
        pop.style.left = `${left}px`;

        activePopover = pop;
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeActive();
    });
}

// Export global methods
window.AKM = {
    ...window.AKM,
    showSuccessAlert,
    showErrorAlert,
    showConfirmAlert,
    initializeDatePickers,
    initializeGoogleMaps,
    initializeResponsiveMenu,
    initializeMonthPopovers
};

// Initialize after load
window.addEventListener('load', function() {
    initializeDatePickers();
    initializeResponsiveMenu();
    initializeGoogleMaps();
    initializeMonthPopovers();
});