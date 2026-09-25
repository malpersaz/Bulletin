/**
 * mod_seo Admin Interactions - Mobile First
 */
document.addEventListener('DOMContentLoaded', function() {
    // 1. Search Filter for SEO URL list
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.seo-url-card');
            cards.forEach(function(card) {
                const title = card.getAttribute('data-name') || '';
                const path = card.getAttribute('data-path') || '';
                if (title.includes(term) || path.includes(term)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 2. Declarative URL Navigation Buttons
    document.querySelectorAll('[data-url]').forEach(function(el) {
        el.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            if (url) {
                window.location.href = url;
            }
        });
    });

    // 3. Refresh button handler
    const refreshBtn = document.getElementById('btnRefresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }

    // 4. Google Settings Form Change Detection
    const bucketInput = document.getElementById('bucket_name');
    const geminiInput = document.getElementById('gemini_api_key');
    const fileInput = document.getElementById('credentials_file');
    const saveBtn = document.querySelector('.m3-btn-half-primary');

    if (bucketInput && geminiInput && fileInput && saveBtn) {
        const initialBucket = bucketInput.value.trim();
        const initialGemini = geminiInput.value.trim();

        function checkChanges() {
            const bucketChanged = bucketInput.value.trim() !== initialBucket;
            const geminiChanged = geminiInput.value.trim() !== initialGemini;
            const fileSelected = fileInput.files.length > 0;

            if (bucketChanged || geminiChanged || fileSelected) {
                saveBtn.removeAttribute('disabled');
                saveBtn.classList.remove('is-disabled');
            } else {
                saveBtn.setAttribute('disabled', 'true');
                saveBtn.classList.add('is-disabled');
            }
        }

        bucketInput.addEventListener('input', checkChanges);
        geminiInput.addEventListener('input', checkChanges);
        fileInput.addEventListener('change', checkChanges);

        checkChanges();
    }
});

window.clearSearch = function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    const cards = document.querySelectorAll('.seo-url-card');
    cards.forEach(function(card) {
        card.style.display = 'flex';
    });
};
