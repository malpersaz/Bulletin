document.addEventListener('DOMContentLoaded', () => {
    const galleryRoot = document.querySelector('.news-gallery');
    if (!galleryRoot) return;

    const mainImg = document.getElementById('news-gallery-main-img');
    const captionEl = document.getElementById('news-gallery-caption');
    const captionBar = document.getElementById('news-gallery-caption-bar');
    const counterEl = document.getElementById('news-gallery-counter');
    const thumbs = Array.from(galleryRoot.querySelectorAll('.thumb-item'));
    const prevBtn = galleryRoot.querySelector('.gallery-prev');
    const nextBtn = galleryRoot.querySelector('.gallery-next');
    const zoomBtn = galleryRoot.querySelector('.gallery-zoom-btn');

    const lightbox = document.getElementById('news-gallery-lightbox');
    const lightboxImg = document.getElementById('news-lightbox-img');
    const lightboxCaption = document.getElementById('news-lightbox-caption');
    const lightboxClose = lightbox ? lightbox.querySelector('.lightbox-close') : null;
    const lightboxPrev = lightbox ? lightbox.querySelector('.lightbox-prev') : null;
    const lightboxNext = lightbox ? lightbox.querySelector('.lightbox-next') : null;

    if (!mainImg || thumbs.length === 0) return;

    let currentIndex = 0;
    const total = thumbs.length;

    function updateGallery(index) {
        currentIndex = (index + total) % total;
        const activeThumb = thumbs[currentIndex];
        const src = activeThumb.dataset.src;
        const caption = activeThumb.dataset.caption || '';

        mainImg.src = src;
        mainImg.alt = caption;

        if (captionEl && captionBar) {
            captionEl.textContent = caption;
            captionBar.hidden = caption.trim() === '';
        }

        if (counterEl) {
            counterEl.textContent = `${currentIndex + 1} / ${total}`;
        }

        thumbs.forEach((btn, i) => {
            const isActive = i === currentIndex;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

        if (lightbox && !lightbox.hidden) {
            lightboxImg.src = src;
            lightboxImg.alt = caption;
            lightboxCaption.textContent = caption;
        }
    }

    function openLightbox() {
        if (!lightbox) return;
        const activeThumb = thumbs[currentIndex];
        const caption = activeThumb.dataset.caption || '';
        lightboxImg.src = activeThumb.dataset.src;
        lightboxImg.alt = caption;
        lightboxCaption.textContent = caption;
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.hidden = true;
        document.body.style.overflow = '';
    }

    thumbs.forEach((btn, index) => {
        btn.addEventListener('click', () => updateGallery(index));
    });

    if (prevBtn) prevBtn.addEventListener('click', () => updateGallery(currentIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => updateGallery(currentIndex + 1));

    mainImg.addEventListener('click', openLightbox);
    if (zoomBtn) zoomBtn.addEventListener('click', openLightbox);
    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxPrev) lightboxPrev.addEventListener('click', () => updateGallery(currentIndex - 1));
    if (lightboxNext) lightboxNext.addEventListener('click', () => updateGallery(currentIndex + 1));

    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (!lightbox || lightbox.hidden) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') updateGallery(currentIndex - 1);
        if (e.key === 'ArrowRight') updateGallery(currentIndex + 1);
    });
});
