/**
 * Hero News Slider (Zero-Reflow Native Cross-Fade Engine)
 * Amed Framework - Public Theme (Amida)
 */
(function () {
    'use strict';

    function handleBrokenSlideImage(img) {
        var parent = img.closest('.hero-slide__image');
        if (parent) {
            parent.classList.add('has-failed-image');
            img.classList.add('is-hidden');
        }
    }

    function hydrateSlideMedia(slide) {
        if (!slide || slide.dataset.hydrated === '1') {
            return;
        }
        slide.dataset.hydrated = '1';

        var sources = slide.querySelectorAll('source[data-srcset]');
        for (var i = 0; i < sources.length; i++) {
            var srcSetVal = sources[i].getAttribute('data-srcset');
            if (srcSetVal) {
                sources[i].setAttribute('srcset', srcSetVal);
                sources[i].removeAttribute('data-srcset');
            }
        }

        var lazyImgs = slide.querySelectorAll('img[data-src]');
        for (var j = 0; j < lazyImgs.length; j++) {
            var srcVal = lazyImgs[j].getAttribute('data-src');
            if (srcVal) {
                lazyImgs[j].setAttribute('src', srcVal);
                lazyImgs[j].removeAttribute('data-src');
            }
        }
    }

    function initHeroSlider() {
        var sliderEl = document.querySelector('.hero-news-swiper');
        if (!sliderEl || sliderEl.dataset.initialized === '1') {
            return;
        }
        sliderEl.dataset.initialized = '1';

        var sliderContainer = sliderEl.closest('.hero-news-slider');
        if (sliderContainer) {
            sliderContainer.classList.add('is-initialized');
        }

        var slides = Array.prototype.slice.call(sliderEl.querySelectorAll('.swiper-slide'));
        var slideCount = slides.length;
        if (slideCount === 0) {
            return;
        }

        var isMultiple = slideCount > 1;
        if (!isMultiple && sliderContainer) {
            sliderContainer.classList.add('is-single-slide');
        }

        var prefersReducedMotion = Boolean(
            window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
        );

        var currentIndex = 0;
        var autoplayDelay = 6000;
        var autoplayTimer = null;
        var isPaused = false;

        var currentEl = sliderContainer ? sliderContainer.querySelector('.hero-slider__counter-current') : null;
        var totalEl = sliderContainer ? sliderContainer.querySelector('.hero-slider__counter-total') : null;
        var paginationEl = sliderContainer ? sliderContainer.querySelector('.hero-news-swiper-pagination') : null;
        var bullets = [];

        if (paginationEl && isMultiple) {
            var existingBullets = paginationEl.querySelectorAll('.swiper-pagination-bullet');
            if (existingBullets.length === slideCount) {
                bullets = Array.prototype.slice.call(existingBullets);
            } else {
                paginationEl.innerHTML = '';
                var fragment = document.createDocumentFragment();
                for (var i = 0; i < slideCount; i++) {
                    var bullet = document.createElement('button');
                    bullet.type = 'button';
                    bullet.className = 'swiper-pagination-bullet' + (i === 0 ? ' swiper-pagination-bullet-active' : '');
                    bullet.setAttribute('aria-label', 'Slide ' + (i + 1));
                    bullet.dataset.slideTo = String(i);
                    fragment.appendChild(bullet);
                    bullets.push(bullet);
                }
                paginationEl.appendChild(fragment);
            }
        }

        function updateCounter(idx) {
            if (!currentEl || !totalEl) {
                return;
            }
            var currentNum = idx + 1;
            currentEl.textContent = currentNum < 10 ? '0' + currentNum : String(currentNum);
            totalEl.textContent = slideCount < 10 ? '0' + slideCount : String(slideCount);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearTimeout(autoplayTimer);
                autoplayTimer = null;
            }
            if (sliderContainer) {
                sliderContainer.classList.remove('is-autoplay-running');
            }
        }

        function scheduleNextSlide(delayMs) {
            stopAutoplay();
            if (!isMultiple || prefersReducedMotion || isPaused || document.hidden) {
                return;
            }
            var delay = typeof delayMs === 'number' ? delayMs : autoplayDelay;
            if (sliderContainer) {
                sliderContainer.classList.remove('is-autoplay-paused');
                sliderContainer.classList.add('is-autoplay-running');
            }
            autoplayTimer = setTimeout(function () {
                goToSlide(currentIndex + 1);
            }, delay);
        }

        function goToSlide(targetIdx) {
            if (!isMultiple) {
                return;
            }
            var nextIdx = ((targetIdx % slideCount) + slideCount) % slideCount;
            if (nextIdx === currentIndex) {
                return;
            }

            hydrateSlideMedia(slides[nextIdx]);
            hydrateSlideMedia(slides[(nextIdx + 1) % slideCount]);

            slides[currentIndex].classList.remove('swiper-slide-active');
            slides[nextIdx].classList.add('swiper-slide-active');

            if (bullets[currentIndex]) {
                bullets[currentIndex].classList.remove('swiper-pagination-bullet-active');
            }
            if (bullets[nextIdx]) {
                bullets[nextIdx].classList.add('swiper-pagination-bullet-active');
            }

            currentIndex = nextIdx;
            updateCounter(currentIndex);

            if (!isPaused && !document.hidden && !prefersReducedMotion) {
                scheduleNextSlide(autoplayDelay);
            }
        }

        hydrateSlideMedia(slides[0]);
        updateCounter(0);

        if (isMultiple && !prefersReducedMotion) {
            var startAutoplayAfterDelay = function () {
                setTimeout(function () {
                    if (!isPaused && !document.hidden) {
                        scheduleNextSlide(autoplayDelay);
                    }
                }, 8000);
            };

            if (document.readyState === 'complete') {
                startAutoplayAfterDelay();
            } else {
                window.addEventListener('load', startAutoplayAfterDelay, { once: true });
            }
        }

        if (sliderContainer) {
            sliderContainer.addEventListener('mouseenter', function () {
                isPaused = true;
                stopAutoplay();
                sliderContainer.classList.add('is-autoplay-paused');
            }, { passive: true });

            sliderContainer.addEventListener('mouseleave', function () {
                isPaused = false;
                sliderContainer.classList.remove('is-autoplay-paused');
                scheduleNextSlide(autoplayDelay);
            }, { passive: true });

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    stopAutoplay();
                    if (sliderContainer) {
                        sliderContainer.classList.add('is-autoplay-paused');
                    }
                } else if (!isPaused) {
                    if (sliderContainer) {
                        sliderContainer.classList.remove('is-autoplay-paused');
                    }
                    scheduleNextSlide(autoplayDelay);
                }
            });

            sliderContainer.addEventListener('click', function (e) {
                var nextBtn = e.target.closest('.hero-news-swiper-button-next');
                if (nextBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    goToSlide(currentIndex + 1);
                    return;
                }

                var prevBtn = e.target.closest('.hero-news-swiper-button-prev');
                if (prevBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    goToSlide(currentIndex - 1);
                    return;
                }

                var bulletBtn = e.target.closest('.swiper-pagination-bullet');
                if (bulletBtn && typeof bulletBtn.dataset.slideTo !== 'undefined') {
                    e.preventDefault();
                    e.stopPropagation();
                    goToSlide(parseInt(bulletBtn.dataset.slideTo, 10));
                }
            });

            var touchStartX = 0;
            var touchStartY = 0;
            sliderContainer.addEventListener('touchstart', function (e) {
                if (!e.touches || e.touches.length === 0) {
                    return;
                }
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }, { passive: true });

            sliderContainer.addEventListener('touchend', function (e) {
                if (!e.changedTouches || e.changedTouches.length === 0) {
                    return;
                }
                var dx = e.changedTouches[0].clientX - touchStartX;
                var dy = e.changedTouches[0].clientY - touchStartY;
                if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
                    if (dx < 0) {
                        goToSlide(currentIndex + 1);
                    } else {
                        goToSlide(currentIndex - 1);
                    }
                }
            }, { passive: true });
        }

        document.addEventListener('keydown', function (e) {
            if (!isMultiple) {
                return;
            }
            if (e.key === 'ArrowRight') {
                goToSlide(currentIndex + 1);
            } else if (e.key === 'ArrowLeft') {
                goToSlide(currentIndex - 1);
            }
        });

        var slideImages = sliderEl.querySelectorAll('.hero-slide__image > img, .hero-slide__image .hero-slide__cover-img, .hero-slide__image .hero-slide__backdrop-img');
        slideImages.forEach(function (img) {
            if (img.classList.contains('hero-slide__placeholder-symbol')) {
                return;
            }
            img.addEventListener('error', function () {
                handleBrokenSlideImage(this);
            });
        });

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

            img.addEventListener('error', fallbackToInitials);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeroSlider);
    } else {
        initHeroSlider();
    }
})();
