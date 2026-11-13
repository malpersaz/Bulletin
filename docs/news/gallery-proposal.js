document.addEventListener('DOMContentLoaded', () => {
    const mainImg = document.getElementById('gallery-main-img');
    const captionEl = document.getElementById('gallery-caption');
    const counterEl = document.getElementById('gallery-counter');
    const thumbs = Array.from(document.querySelectorAll('.thumb-item'));
    const prevBtn = document.querySelector('.gallery-prev');
    const nextBtn = document.querySelector('.gallery-next');
    const zoomBtn = document.querySelector('.gallery-zoom-btn');

    const lightbox = document.getElementById('gallery-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxClose = document.querySelector('.lightbox-close');
    const lightboxPrev = document.querySelector('.lightbox-prev');
    const lightboxNext = document.querySelector('.lightbox-next');

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
        captionEl.textContent = caption;
        counterEl.textContent = `${currentIndex + 1} / ${total}`;

        thumbs.forEach((btn, i) => {
            const isActive = i === currentIndex;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

        if (!lightbox.hidden) {
            lightboxImg.src = src;
            lightboxImg.alt = caption;
            lightboxCaption.textContent = caption;
        }
    }

    function openLightbox() {
        const activeThumb = thumbs[currentIndex];
        lightboxImg.src = activeThumb.dataset.src;
        lightboxImg.alt = activeThumb.dataset.caption || '';
        lightboxCaption.textContent = activeThumb.dataset.caption || '';
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.hidden = true;
        document.body.style.overflow = '';
    }

    thumbs.forEach((btn, index) => {
        btn.addEventListener('click', () => updateGallery(index));
    });

    prevBtn.addEventListener('click', () => updateGallery(currentIndex - 1));
    nextBtn.addEventListener('click', () => updateGallery(currentIndex + 1));

    mainImg.addEventListener('click', openLightbox);
    zoomBtn.addEventListener('click', openLightbox);
    lightboxClose.addEventListener('click', closeLightbox);
    lightboxPrev.addEventListener('click', () => updateGallery(currentIndex - 1));
    lightboxNext.addEventListener('click', () => updateGallery(currentIndex + 1));

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', (e) => {
        if (lightbox.hidden) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') updateGallery(currentIndex - 1);
        if (e.key === 'ArrowRight') updateGallery(currentIndex + 1);
    });
});
