/**
 * Rojname Custom JavaScript
 * Bootstrap 5.3 Uyumlu Modern JavaScript Fonksiyonları
 */

// Global değişkenler
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
 * AKM Başlatma Fonksiyonu
 */
function initializeAKM() {
    if (AKM.initialized) return;

    console.log('Rojname initializing...');

    // Bootstrap 5.3.8 ile auto-initialize edilen bileşenler:
    // - Tooltips (data-bs-toggle="tooltip")
    // - Popovers (data-bs-toggle="popover")
    // - Modals (data-bs-toggle="modal")
    // - Toasts (.toast)
    // - Form validation (.needs-validation)

    // Custom slider'ları başlat
    initializeCustomSliders();

    // GLightbox'ı başlat
    initializeLightbox();

    // Diğer özel fonksiyonlar
    initializeOtherFeatures();

    AKM.initialized = true;
    console.log('Rojname initialized successfully!');
}

/* BOOTSTRAP 5.3.8 NOTU:
 * Aşağıdaki bileşenler artık otomatik olarak başlatılır:
 * - Tooltips: data-bs-toggle="tooltip" attribute ile
 * - Popovers: data-bs-toggle="popover" attribute ile
 * - Modals: data-bs-toggle="modal" attribute ile
 * - Toasts: .toast class ile
 * - Form Validation: .needs-validation class ile
 *
 * Bu fonksiyon Bootstrap 5.3.8 ile gereksizleşmiştir.
 */

/**
 * Custom Slider'ları Başlat
 */
function initializeCustomSliders() {
    // AKM Slider - Swiper ile modernleştirilmiş
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
 * GLightbox Başlat
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

/* BOOTSTRAP 5.3.8 NOTU:
 * Form validation artık Bootstrap 5.3.8 ile otomatik çalışır:
 * - .needs-validation class ile form validasyonu
 * - :valid ve :invalid pseudo-class'ları
 * - .was-validated class'ı otomatik eklenir
 *
 * Bu fonksiyon Bootstrap 5.3.8 ile gereksizleşmiştir.
 */

/**
 * Diğer Özel Özellikleri Başlat
 */
function initializeOtherFeatures() {
    // Lazy loading için Intersection Observer
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
 * Modern AKM Slider Sınıfı
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
 * Owl Carousel'dan Swiper'e Geçiş Fonksiyonu
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
 * SweetAlert2 İçin Yardımcı Fonksiyonlar
 */
function showSuccessAlert(title, message) {
    Swal.fire({
        title: title,
        text: message,
        icon: 'success',
        confirmButtonText: 'Tamam',
        confirmButtonColor: '#28a745'
    });
}

function showErrorAlert(title, message) {
    Swal.fire({
        title: title,
        text: message,
        icon: 'error',
        confirmButtonText: 'Tamam',
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
        confirmButtonText: 'Evet',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed && confirmCallback) {
            confirmCallback();
        }
    });
}

/**
 * Flatpickr Date Picker Başlatma
 */
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('.datepicker');
    dateInputs.forEach(function(input) {
        flatpickr(input, {
            locale: "tr",
            dateFormat: "d.m.Y",
            minDate: "today",
            allowInput: true
        });
    });
}

/**
 * Google Maps Entegrasyonu (Bootstrap 5 ile uyumlu)
 */
function initializeGoogleMaps() {
    if (typeof google !== 'undefined' && document.getElementById('map')) {
        const mapOptions = {
            zoom: 15,
            center: { lat: 41.0082, lng: 28.9784 }, // İstanbul koordinatları
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        };

        const map = new google.maps.Map(document.getElementById('map'), mapOptions);

        // Özel marker ekle
        const marker = new google.maps.Marker({
            position: { lat: 41.0082, lng: 28.9784 },
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

        // Menu dışına tıklayınca kapat
        document.addEventListener('click', function(event) {
            if (!navbarToggler.contains(event.target) && !navbarCollapse.contains(event.target)) {
                navbarCollapse.classList.remove('show');
            }
        });
    }
}

// Global fonksiyonları export et
window.AKM = {
    ...window.AKM,
    AKMSlider,
    makeSwiper,
    showSuccessAlert,
    showErrorAlert,
    showConfirmAlert,
    initializeDatePickers,
    initializeGoogleMaps,
    initializeResponsiveMenu
};

// Sayfa yüklendiğinde diğer başlatma fonksiyonlarını çağır
window.addEventListener('load', function() {
    initializeLightbox();
    initializeDatePickers();
    initializeResponsiveMenu();
    initializeGoogleMaps();
});