/**
 * mod_content.js
 * Handles fetching data from existing Datatable JSON endpoint and rendering M3 Cards.
 */

class ContentManager {
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
        this.loadData();
        this.initScrollListener();
        this.initSearchListener();
        this.initPullToRefresh();
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) return;

        this.isLoading = true;
        if (reset) {
            this.start = 0;
            this.container.innerHTML = '';
            this.hasMore = true;
            this.emptyState.style.display = 'none';
        }

        this.loader.style.display = 'flex';

        // Prepare FormData for Datatable compatibility
        const formData = new FormData(document.getElementById('filterForm'));
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

            // Update pagination tracking
            this.start += this.length;
            this.draw++;

            // Check if we reached the end
            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
                this.loader.style.display = 'none';
            }

            if (data.recordsFiltered === 0) {
                this.emptyState.style.display = 'flex';
            }

        } catch (error) {
            console.error('Data Load Error:', error);
            // Show error toast
        } finally {
            this.isLoading = false;
            // Hide loader if not more data, usually kept if loading, but here we hide it after batch
            if (!this.hasMore) this.loader.style.display = 'none';
        }
    }

    renderCards(items) {
        if (!items || items.length === 0) return;

        const fragment = document.createDocumentFragment();

        items.forEach(item => {
            // Mapping DT columns to Card fields
            // Assuming strict column order from list.twig:
            // 0: Checkbox
            // 1: ID
            // 2: Title (usually contains HTML link)
            // 3: Image (contains <img> tag)
            // 4: Lang
            // 5: Date
            // 6: Actions

            // Need to parse HTML strings since DT sends rendered HTML
            const id = this.stripHtml(item[1]);
            const titleHtml = item[2];
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]);

            // Extract pure text title and link if possible
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = titleHtml;
            const titleText = tempDiv.textContent || tempDiv.innerText;

            tempDiv.innerHTML = imgHtml;
            const imgSrc = tempDiv.querySelector('img') ? tempDiv.querySelector('img').src : '';

            const card = document.createElement('div');
            card.className = 'content-card';
            card.onclick = () => this.openActionSheet(id, titleText); // Context menu

            const imageStyle = imgSrc ? `background-image: url('${imgSrc}')` : `background-color: #eee`;
            const imageHtml = imgSrc
                ? `<div class="card-image" style="${imageStyle}"></div>`
                : `<div class="card-image"><i class="bi bi-card-image" style="font-size:24px; color:#999;"></i></div>`;

            card.innerHTML = `
                ${imageHtml}
                <div class="card-content">
                    <h3 class="card-title">${titleText}</h3>
                    <div class="card-meta">
                        <span class="meta-item"><i class="bi bi-calendar3"></i> ${date}</span>
                        <span class="meta-item"><i class="bi bi-translate"></i> ${lang}</span>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="icon-btn-small" onclick="event.stopPropagation(); window.location.href='${MODULE_URL}edit/${id}'">
                        <i class="bi bi-pencil-fill" style="font-size:16px;"></i>
                    </button>
                </div>
            `;
            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    stripHtml(html) {
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    initScrollListener() {
        const scrollFrame = document.querySelector('.app-scroll-frame');
        scrollFrame.addEventListener('scroll', () => {
            if (scrollFrame.scrollTop + scrollFrame.clientHeight >= scrollFrame.scrollHeight - 100) {
                this.loadData();
            }
        });
    }

    initSearchListener() {
        const searchInput = document.getElementById('searchInput');
        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                document.getElementById('filterSearch').value = e.target.value;
                this.loadData(true);
            }, 500);
        });
    }

    initPullToRefresh() {
        // Simple PTR visual logic handled in script.js (global), 
        // we just need to ensure loadData(true) is called when triggered.
        // We can listen to a custom event if we implemented one, 
        // or just rely on the global script calling a refresh function if we expose it.
        window.refreshContent = () => this.loadData(true);
    }

    openActionSheet(id, title) {
        // Populate and open the context menu sheet
        document.getElementById('contextMenuTitle').textContent = title;
        // Bind actions
        // set global currentId or pass it
        window.currentContentId = id;
        openContextMenu();
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    new ContentManager();
});

// Helper for Context Menu Actions (Global scope for onclicks)
function handleContextAction(action) {
    const id = window.currentContentId;
    if (!id) return;

    switch (action) {
        case 'edit':
            window.location.href = `${MODULE_URL}edit/${id}`;
            break;
        case 'delete':
            if (confirm('Silmek istediğinize emin misiniz?')) {
                // Call delete ajax
            }
            break;
        case 'gallery':
            window.location.href = `${SITE_URL}mod_gallery/list/mod_content/${id}`;
            break;
    }
    closeContextMenu();
}
