/**
 * mod_slider.js
 * Handles fetching slider data from other modules and managing dashboard card toggles.
 */

class SliderManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');

        this.isLoading = false;
        this.hasMore = true;

        this.start = 0;
        this.length = 10;
        this.draw = 1;

        this.init();
    }

    init() {
        this.createLoadMoreButton();
        this.loadData();
        this.initPullToRefresh();
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined';
        this.loadMoreBtn.style.cssText = 'display:none; margin: 20px auto; width: fit-content; cursor:pointer;';

        let btnText = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.load_more) ? CONTENT_LANG.load_more : 'Load More';
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;

        if (this.container && this.loader) {
            this.container.parentNode.insertBefore(this.loadMoreBtn, this.loader);
        }

        this.loadMoreBtn.onclick = () => {
            this.loadData();
        };
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) return;
        this.isLoading = true;

        if (this.loadMoreBtn) {
            this.loadMoreBtn.disabled = true;
            this.loadMoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right:8px;"></span>...`;
        }

        if (reset) {
            this.start = 0;
            if (this.container) this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            if (this.emptyState) this.emptyState.style.display = 'none';
            if (this.loader) this.loader.style.display = 'flex';
            if (this.loadMoreBtn) this.loadMoreBtn.style.display = 'none';
        } else {
            if (this.loader) this.loader.style.display = 'flex';
        }

        const filterLengthInput = document.getElementById('filterLength');
        if (filterLengthInput && filterLengthInput.value) {
            this.length = parseInt(filterLengthInput.value) || 10;
        }

        const filterForm = document.getElementById('filterForm');
        let formData = new FormData();
        if (filterForm) {
            formData = new FormData(filterForm);
        }
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(DATA_URL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            this.renderCards(data.data);

            this.start += this.length;
            this.draw++;

            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
            }

            if (data.recordsFiltered === 0 && this.emptyState) {
                this.emptyState.style.display = 'flex';
                const descEl = document.getElementById('empty-state-desc');
                if (descEl) {
                    descEl.innerHTML = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.empty_no_data) ? CONTENT_LANG.empty_no_data : 'No data yet.';
                }
            }

        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.style.display = 'none';

            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = (this.hasMore && this.container && this.container.innerHTML !== '') ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
                let btnText = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.load_more) ? CONTENT_LANG.load_more : 'Load More';
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0 || !this.container) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            const compoundId = this.stripHtml(item[1]);
            const titleText = this.stripHtml(item[2]);
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]);
            const badgeText = this.stripHtml(item[6]); 

            // Extract src from img tag
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            const card = document.createElement('div');
            card.className = 'content-card';
            card.dataset.id = compoundId;

            // Thumbnail background style
            const thumbStyle = imgSrc && !imgSrc.includes('slider_no_image') 
                ? `background-image: url('${imgSrc}');` 
                : `background-color: var(--md-sys-color-surface-container-high, #f0f0f0); display:flex; align-items:center; justify-content:center;`;

            card.innerHTML = `
                <!-- Thumbnail -->
                <div class="card-thumbnail" style="${thumbStyle}">
                    ${!imgSrc || imgSrc.includes('slider_no_image') ? '<i class="bi bi-image" style="font-size: 24px; color: #999;"></i>' : ''}
                </div>
                
                <!-- Card Body -->
                <div class="card-body-col">
                    <!-- Top Row -->
                    <div class="card-header-row">
                        <span class="module-badge">${badgeText}</span>
                        <div class="switch-container">
                            <span class="switch-label">${CONTENT_LANG.unpublish}</span>
                            <label class="m3-switch">
                                <input type="checkbox" checked onchange="window.sliderManager.toggleItemStatus('${compoundId}', this)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="card-title">${titleText}</h3>
                    
                    <!-- Meta Row -->
                    <div class="card-meta-row">
                        <span class="meta-item"><i class="bi bi-translate"></i> ${lang}</span>
                        <span class="meta-item"><i class="bi bi-calendar3"></i> ${date}</span>
                    </div>
                </div>
            `;

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    stripHtml(html) {
        if (!html) return "";
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    initPullToRefresh() {
        window.refreshContent = () => this.loadData(true);
    }

    async toggleItemStatus(compoundId, checkboxEl) {
        const checked = checkboxEl.checked;
        const labelEl = checkboxEl.closest('.switch-container').querySelector('.switch-label');
        const cardEl = checkboxEl.closest('.content-card');

        // Update label text immediately
        labelEl.textContent = checked ? CONTENT_LANG.unpublish : CONTENT_LANG.publish;

        if (!checked) {
            // Unpublish (Remove from slider)
            const formData = new FormData();
            formData.append('query[]', compoundId);

            try {
                const response = await fetch(MODULE_URL + 'unpublish', {
                    method: 'POST',
                    body: formData
                });
                const msg = await response.text();

                if (msg.trim() === 'Oke') {
                    // Smooth exit animation
                    cardEl.classList.add('fade-out');
                    
                    // Wait for transition to finish before removing from DOM
                    cardEl.addEventListener('transitionend', () => {
                        cardEl.remove();
                        // If no cards left, refresh or show empty state
                        if (this.container.querySelectorAll('.content-card').length === 0) {
                            this.loadData(true);
                        }
                    }, { once: true });
                    
                    // Vibrate if supported
                    if (window.navigator && window.navigator.vibrate) {
                        window.navigator.vibrate(10);
                    }
                } else {
                    // Revert state
                    checkboxEl.checked = true;
                    labelEl.textContent = CONTENT_LANG.unpublish;
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(msg, 'error');
                    }
                }
            } catch (error) {
                console.error('Unpublish Error:', error);
                // Revert state
                checkboxEl.checked = true;
                labelEl.textContent = CONTENT_LANG.unpublish;
                if (typeof showSnackbar === 'function') {
                    showSnackbar(CONTENT_LANG.error_general, 'error');
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.sliderManager = new SliderManager();
});
