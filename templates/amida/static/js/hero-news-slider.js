/**
 * Hero News Slider (Swiper Integration & Fault-Tolerant Engine)
 * Amed Framework - Public Theme (Amida)
 */
(function () {
    'use strict';

    var retryCount = 0;
    var maxRetries = 50;

    function handleBrokenSlideImage(img) {
        var parent = img.closest('.hero-slide__image');
        if (parent) {
            parent.classList.add('has-failed-image');
            img.classList.add('is-hidden');
        }
    }

    function initHeroSlider() {
        var sliderEl = document.querySelector('.hero-news-swiper');
        if (!sliderEl) {
            return;
        }

        if (sliderEl.swiper) {
            return;
        }

        if (typeof Swiper === 'undefined') {
            if (retryCount < maxRetries) {
                retryCount++;
                setTimeout(initHeroSlider, 100);
            }
            return;
        }

        var sliderContainer = sliderEl.closest('.hero-news-slider');
        var slides = sliderEl.querySelectorAll('.swiper-slide');
        var slideCount = slides.length;
        var isMultiple = slideCount > 1;

        if (!isMultiple && sliderContainer) {
            sliderContainer.classList.add('is-single-slide');
        }

        var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function updateCounter(swiper) {
            if (!sliderContainer) {
                return;
            }
            var currentEl = sliderContainer.querySelector('.hero-slider__counter-current');
            var totalEl = sliderContainer.querySelector('.hero-slider__counter-total');
            if (!currentEl || !totalEl) {
                return;
            }

            var current = (typeof swiper.realIndex !== 'undefined' ? swiper.realIndex : swiper.activeIndex) + 1;
            var total = slideCount;

            var currentStr = current < 10 ? '0' + current : String(current);
            var totalStr = total < 10 ? '0' + total : String(total);

            currentEl.textContent = currentStr;
            totalEl.textContent = totalStr;
        }

        var swiperConfig = {
            loop: isMultiple,
            speed: 650,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            autoplay: (isMultiple && !prefersReducedMotion) ? {
                delay: 6000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            } : false,
            watchSlidesProgress: true,
            allowTouchMove: isMultiple,
            keyboard: {
                enabled: true,
                onlyInViewport: true
            },
            navigation: {
                nextEl: '.hero-news-swiper-button-next',
                prevEl: '.hero-news-swiper-button-prev'
            },
            pagination: {
                el: '.hero-news-swiper-pagination',
                clickable: true
            },
            on: {
                init: function () {
                    updateCounter(this);
                },
                slideChange: function () {
                    updateCounter(this);
                    if (sliderContainer) {
                        sliderContainer.style.setProperty('--hero-autoplay-progress', '0%');
                    }
                },
                autoplayTimeLeft: function (swiper, timeLeft, progress) {
                    if (!sliderContainer || prefersReducedMotion) {
                        return;
                    }
                    var elapsed = Math.min(100, Math.max(0, (1 - progress) * 100));
                    sliderContainer.style.setProperty('--hero-autoplay-progress', elapsed.toFixed(1) + '%');
                }
            }
        };

        var swiperInstance = new Swiper(sliderEl, swiperConfig);

        // Click delegation on container for controls
        if (sliderContainer) {
            sliderContainer.addEventListener('click', function (e) {
                var nextBtn = e.target.closest('.hero-news-swiper-button-next');
                if (nextBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    swiperInstance.slideNext();
                    return;
                }

                var prevBtn = e.target.closest('.hero-news-swiper-button-prev');
                if (prevBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    swiperInstance.slidePrev();
                    return;
                }
            });
        }

        // Defensive broken slide image fallback
        var slideImages = sliderEl.querySelectorAll('.hero-slide__image > img, .hero-slide__image .hero-slide__cover-img, .hero-slide__image .hero-slide__backdrop-img');
        slideImages.forEach(function (img) {
            if (img.classList.contains('hero-slide__placeholder-symbol')) {
                return;
            }
            img.addEventListener('error', function () {
                handleBrokenSlideImage(this);
            });
        });

        // Resilient author avatar fallback: show initials if image fails or 404s
        var avatarImages = sliderEl.querySelectorAll('.hero-slide__avatar img');
        avatarImages.forEach(function (img) {
            function fallbackToInitials() {
                img.classList.add('is-hidden');
                var avatarBox = img.closest('.hero-slide__avatar');
                if (avatarBox) {
                    var initials = avatarBox.querySelector('.hero-slide__avatar-initials');
                    if (initials) {
                        initials.classList.add('is-visible');
                    }
                }
            }

            if (img.complete && (img.naturalWidth === 0 || img.naturalHeight === 0)) {
                fallbackToInitials();
            } else {
                img.addEventListener('error', fallbackToInitials);
            }
        });

        // Pause autoplay when browser tab is inactive
        document.addEventListener('visibilitychange', function () {
            if (!swiperInstance.autoplay) {
                return;
            }
            if (document.hidden) {
                swiperInstance.autoplay.stop();
            } else if (isMultiple && !prefersReducedMotion) {
                swiperInstance.autoplay.start();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            requestAnimationFrame(initHeroSlider);
        });
    } else {
        requestAnimationFrame(initHeroSlider);
    }
})();
