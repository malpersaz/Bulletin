/**
 * AKM Business - World-Class Fullscreen Lightbox Engine (UI/UX)
 * Inspired by Airbnb, Apple, PhotoSwipe 5 & Fancybox 5.
 */
(function () {
  'use strict';

  class AFLightbox {
    constructor() {
      this.items = [];
      this.currentIndex = 0;
      this.isOpen = false;
      this.isZoomed = false;
      this.overlay = null;
      this.stage = null;
      this.img = null;
      this.titleEl = null;
      this.counterEl = null;
      this.thumbsEl = null;
      this.toastEl = null;
      this.touchStartX = 0;
      this.touchStartY = 0;

      this.init();
    }

    init() {
      document.addEventListener('click', (e) => {
        const link = e.target.closest('a.glightbox, a[data-bs-toggle="lightbox"]');
        if (link) {
          e.preventDefault();
          this.openGalleryFromLink(link);
        }
      });

      document.addEventListener('keydown', (e) => this.handleKeyDown(e));
    }

    getI18n() {
      const lang = document.documentElement.lang || 'en';
      const dict = {
        tr: { close: 'Kapat (Esc)', download: 'İndir', share: 'Bağlantıyı Kopyala', fullscreen: 'Tam Ekran', zoom: 'Yakınlaştır', copied: 'Bağlantı Kopyalandı!' },
        ku: { close: 'Girtin (Esc)', download: 'Daxîne', share: 'Girêdanê Kopî Biki', fullscreen: 'Ekrana Tijî', zoom: 'Mezin Bike', copied: 'Girêdan Hate Kopîkirin!' },
        en: { close: 'Close (Esc)', download: 'Download', share: 'Copy Link', fullscreen: 'Fullscreen', zoom: 'Zoom', copied: 'Link Copied!' },
        de: { close: 'Schließen (Esc)', download: 'Herunterladen', share: 'Link Kopieren', fullscreen: 'Vollbild', zoom: 'Vergrößern', copied: 'Link Kopiert!' },
        ar: { close: 'إغلاق (Esc)', download: 'تحميل', share: 'نسخ الرابط', fullscreen: 'ملء الشاشة', zoom: 'تكبير', copied: 'تم نسخ الرابط!' },
        ckb: { close: 'داخستن (Esc)', download: 'داگرتن', share: 'کۆپیکردنی لینک', fullscreen: 'تەواوی پەردەکە', zoom: 'زووم', copied: 'لینک کۆپی کرا!' },
        fr: { close: 'Fermer (Esc)', download: 'Télécharger', share: 'Copier le lien', fullscreen: 'Plein écran', zoom: 'Zoom', copied: 'Lien copié !' },
        sv: { close: 'Stäng (Esc)', download: 'Ladda ner', share: 'Kopiera länk', fullscreen: 'Helskärm', zoom: 'Zooma', copied: 'Länk kopierad!' },
        zza: { close: 'Biki Girtî (Esc)', download: 'Banse', share: 'Gire Gêni', fullscreen: 'Perde Pîl', zoom: 'Pîl Biki', copied: 'Gire Gêriya!' }
      };
      return dict[lang] || dict.en;
    }

    buildOverlay() {
      if (this.overlay) return;

      const i18n = this.getI18n();
      const div = document.createElement('div');
      div.id = 'af-lightbox-overlay';
      div.innerHTML = `
        <div class="af-lightbox-header">
          <div class="af-lightbox-info">
            <span class="af-lightbox-counter" id="af-lb-counter">1 / 1</span>
            <span class="af-lightbox-title" id="af-lb-title"></span>
          </div>
          <div class="af-lightbox-toolbar">
            <button type="button" class="af-lightbox-btn" id="af-lb-zoom" title="${i18n.zoom}">
              <i class="bi bi-search"></i><span>${i18n.zoom}</span>
            </button>
            <a href="#" download class="af-lightbox-btn" id="af-lb-download" title="${i18n.download}" target="_blank" rel="noopener">
              <i class="bi bi-download"></i><span>${i18n.download}</span>
            </a>
            <button type="button" class="af-lightbox-btn" id="af-lb-share" title="${i18n.share}">
              <i class="bi bi-share"></i><span>${i18n.share}</span>
            </button>
            <button type="button" class="af-lightbox-btn" id="af-lb-fullscreen" title="${i18n.fullscreen}">
              <i class="bi bi-fullscreen"></i><span>${i18n.fullscreen}</span>
            </button>
            <button type="button" class="af-lightbox-btn af-lightbox-btn-close" id="af-lb-close" title="${i18n.close}">
              <i class="bi bi-x-lg"></i><span>${i18n.close}</span>
            </button>
          </div>
        </div>

        <div class="af-lightbox-toast" id="af-lb-toast">${i18n.copied}</div>

        <div class="af-lightbox-viewport" id="af-lb-viewport">
          <button type="button" class="af-lightbox-nav af-lightbox-prev" id="af-lb-prev" aria-label="Previous">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="af-lightbox-stage" id="af-lb-stage">
            <img id="af-lb-img" src="" alt="">
          </div>

          <button type="button" class="af-lightbox-nav af-lightbox-next" id="af-lb-next" aria-label="Next">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>

        <div class="af-lightbox-footer">
          <div class="af-lightbox-thumbs" id="af-lb-thumbs"></div>
        </div>
      `;

      document.body.appendChild(div);
      this.overlay = div;
      this.stage = div.querySelector('#af-lb-stage');
      this.img = div.querySelector('#af-lb-img');
      this.titleEl = div.querySelector('#af-lb-title');
      this.counterEl = div.querySelector('#af-lb-counter');
      this.thumbsEl = div.querySelector('#af-lb-thumbs');
      this.toastEl = div.querySelector('#af-lb-toast');

      // Event Bindings
      div.querySelector('#af-lb-close').addEventListener('click', () => this.close());
      div.querySelector('#af-lb-prev').addEventListener('click', () => this.prev());
      div.querySelector('#af-lb-next').addEventListener('click', () => this.next());
      div.querySelector('#af-lb-zoom').addEventListener('click', () => this.toggleZoom());
      div.querySelector('#af-lb-fullscreen').addEventListener('click', () => this.toggleFullscreen());
      div.querySelector('#af-lb-share').addEventListener('click', () => this.shareCurrent());

      // Backdrop dismissal click (outside image)
      div.querySelector('#af-lb-viewport').addEventListener('click', (e) => {
        if (e.target.id === 'af-lb-viewport' || e.target.id === 'af-lb-stage') {
          this.close();
        }
      });

      // Double click image to zoom
      this.img.addEventListener('dblclick', () => this.toggleZoom());

      // Mobile Touch Gestures
      div.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
          this.touchStartX = e.touches[0].clientX;
          this.touchStartY = e.touches[0].clientY;
        }
      }, { passive: true });

      div.addEventListener('touchend', (e) => {
        if (e.changedTouches.length === 1) {
          const deltaX = e.changedTouches[0].clientX - this.touchStartX;
          const deltaY = e.changedTouches[0].clientY - this.touchStartY;

          if (Math.abs(deltaX) > 50 && Math.abs(deltaY) < 60) {
            if (deltaX < 0) this.next();
            else this.prev();
          } else if (deltaY > 90 && Math.abs(deltaX) < 60) {
            this.close();
          }
        }
      }, { passive: true });
    }

    openGalleryFromLink(activeLink) {
      const galleryName = activeLink.getAttribute('data-gallery');
      let links = [];

      if (galleryName) {
        links = Array.from(document.querySelectorAll(`a[data-gallery="${galleryName}"]`));
      } else {
        const container = activeLink.closest('.bento-gallery-section, section, article, div');
        if (container) {
          links = Array.from(container.querySelectorAll('a.glightbox, a[data-bs-toggle="lightbox"]'));
        } else {
          links = [activeLink];
        }
      }

      this.items = links.map((a) => ({
        url: a.getAttribute('href'),
        title: a.getAttribute('data-title') || a.getAttribute('aria-label') || a.getAttribute('title') || '',
        thumb: a.querySelector('img') ? a.querySelector('img').getAttribute('src') : a.getAttribute('href')
      })).filter((item) => item.url && item.url !== '#');

      if (!this.items.length) return;

      const activeUrl = activeLink.getAttribute('href');
      this.currentIndex = this.items.findIndex((item) => item.url === activeUrl);
      if (this.currentIndex === -1) this.currentIndex = 0;

      this.buildOverlay();
      this.renderThumbs();
      this.show(this.currentIndex);
      this.open();
    }

    open() {
      this.isOpen = true;
      this.overlay.classList.add('is-active');
      document.body.style.overflow = 'hidden';
    }

    close() {
      if (!this.isOpen) return;
      this.isOpen = false;
      this.overlay.classList.remove('is-active');
      document.body.style.overflow = '';
      if (this.isZoomed) this.resetZoom();
      if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
      }
    }

    show(index) {
      if (index < 0) index = this.items.length - 1;
      if (index >= this.items.length) index = 0;
      this.currentIndex = index;

      const item = this.items[index];
      this.img.src = item.url;
      this.img.alt = item.title;
      this.titleEl.textContent = item.title;
      this.counterEl.textContent = `${index + 1} / ${this.items.length}`;

      const downloadBtn = this.overlay.querySelector('#af-lb-download');
      downloadBtn.href = item.url;

      if (this.isZoomed) this.resetZoom();

      // Highlight active thumb
      const thumbs = this.thumbsEl.querySelectorAll('.af-lightbox-thumb');
      thumbs.forEach((t, idx) => {
        if (idx === index) {
          t.classList.add('is-active');
          t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
          t.classList.remove('is-active');
        }
      });
    }

    next() {
      this.show(this.currentIndex + 1);
    }

    prev() {
      this.show(this.currentIndex - 1);
    }

    renderThumbs() {
      this.thumbsEl.innerHTML = '';
      this.items.forEach((item, idx) => {
        const thumbDiv = document.createElement('div');
        thumbDiv.className = `af-lightbox-thumb ${idx === this.currentIndex ? 'is-active' : ''}`;
        thumbDiv.innerHTML = `<img src="${item.thumb}" alt="${item.title}">`;
        thumbDiv.addEventListener('click', () => this.show(idx));
        this.thumbsEl.appendChild(thumbDiv);
      });
    }

    toggleZoom() {
      this.isZoomed = !this.isZoomed;
      if (this.isZoomed) {
        this.stage.classList.add('is-zoomed');
      } else {
        this.resetZoom();
      }
    }

    resetZoom() {
      this.isZoomed = false;
      this.stage.classList.remove('is-zoomed');
    }

    toggleFullscreen() {
      if (!document.fullscreenElement) {
        this.overlay.requestFullscreen().catch(() => {});
      } else {
        document.exitFullscreen().catch(() => {});
      }
    }

    shareCurrent() {
      const item = this.items[this.currentIndex];
      if (item && item.url) {
        const fullUrl = item.url.startsWith('http') ? item.url : window.location.origin + item.url;
        navigator.clipboard.writeText(fullUrl).then(() => {
          this.toastEl.classList.add('is-visible');
          setTimeout(() => this.toastEl.classList.remove('is-visible'), 2200);
        }).catch(() => {});
      }
    }

    handleKeyDown(e) {
      if (!this.isOpen) return;
      if (e.key === 'Escape') {
        this.close();
      } else if (e.key === 'ArrowRight') {
        this.next();
      } else if (e.key === 'ArrowLeft') {
        this.prev();
      } else if (e.key === 'f' || e.key === 'F') {
        this.toggleFullscreen();
      } else if (e.key === 'z' || e.key === 'Z') {
        this.toggleZoom();
      }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.AF_Lightbox_Engine = new AFLightbox();
  });
})();
