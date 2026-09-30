/**
 * Hero News Slider (Swiper Integration)
 * Amed Framework - Public Theme (Amida)
 */
(function () {
    'use strict';

    var retryCount = 0;
    var maxRetries = 50;

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

        var slides = sliderEl.querySelectorAll('.swiper-slide');
        var slideCount = slides.length;
        var isMultiple = slideCount > 1;

        if (!isMultiple) {
            var sliderContainer = sliderEl.closest('.hero-news-slider');
            if (sliderContainer) {
                sliderContainer.classList.add('is-single-slide');
            }
        }

        var swiperConfig = {
            loop: isMultiple,
            speed: 700,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            autoplay: isMultiple ? {
                delay: 5000,
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
            }
        };

        var swiperInstance = new Swiper(sliderEl, swiperConfig);

        // Event delegation for next/prev controls across all slides
        sliderEl.addEventListener('click', function (e) {
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

        // Pause autoplay when browser tab is inactive
        document.addEventListener('visibilitychange', function () {
            if (!swiperInstance.autoplay) {
                return;
            }
            if (document.hidden) {
                swiperInstance.autoplay.stop();
            } else if (isMultiple) {
                swiperInstance.autoplay.start();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeroSlider);
    } else {
        initHeroSlider();
    }
})();
