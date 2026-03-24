// Mobile Interaction Scripts for M3 Prototype

// Bottom Sheet / Filter Logic
function openSheet() {
    const sheet = document.getElementById('sheet-menu');
    const overlay = document.getElementById('sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.add('active');
        overlay.classList.add('active');
    }
}

function closeSheet() {
    const sheet = document.getElementById('sheet-menu');
    const overlay = document.getElementById('sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.classList.remove('active');
    }
}

// Navigation Drawer Logic
function toggleDrawer() {
    const drawer = document.getElementById('navDrawer');
    const scrim = document.getElementById('drawerScrim');
    if (drawer && scrim) {
        drawer.classList.toggle('active');
        scrim.classList.toggle('active');
    }
}

function toggleGroup(header) {
    const group = header.parentElement;
    group.classList.toggle('expanded');
}


// Scroll Effect for Top Bar
const topAppBar = document.querySelector('.top-app-bar');
const scrollContent = document.querySelector('.mobile-wrapper'); // Since we scroll within wrapper in simulator

if (scrollContent) {
    scrollContent.addEventListener('scroll', () => {
        if (scrollContent.scrollTop > 10) {
            topAppBar.style.backgroundColor = '#F3EDF7'; // Surface Container slightly darker
            topAppBar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        } else {
            topAppBar.style.backgroundColor = 'var(--md-sys-color-surface-container)';
            topAppBar.style.boxShadow = 'none';
        }
    });
} else {
    // Fallback for real mobile logic
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            topAppBar.style.backgroundColor = '#F3EDF7';
            topAppBar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        } else {
            topAppBar.style.backgroundColor = 'var(--md-sys-color-surface-container)';
            topAppBar.style.boxShadow = 'none';
        }
    });
}

// Selection Mode Simulation (Long Press)
const cards = document.querySelectorAll('.m3-card');
let pressTimer;

cards.forEach(card => {
    // Mouse Interaction
    card.addEventListener('mousedown', () => {
        pressTimer = window.setTimeout(() => toggleSelectionMode(), 800);
    });
    card.addEventListener('mouseup', () => clearTimeout(pressTimer));

    // Touch Interaction
    card.addEventListener('touchstart', () => {
        pressTimer = window.setTimeout(() => toggleSelectionMode(), 800);
    });
    card.addEventListener('touchend', () => clearTimeout(pressTimer));
});

function toggleSelectionMode() {
    document.body.classList.toggle('selection-mode');

    // Titreşim efekti (sadece görsel)
    if (document.body.classList.contains('selection-mode')) {
        navigator.vibrate?.(50); // Eğer cihaz destekliyorsa
    }
}


// Speed Dial Logic
function toggleFabMenu() {
    const container = document.getElementById('fabContainer');
    if (container) {
        container.classList.toggle('active');
        // Titreşim
        navigator.vibrate?.(10);
    }
}

// Close FAB when clicking outside
document.addEventListener('click', (e) => {
    const container = document.getElementById('fabContainer');
    // Eğer tıklanan yer container değilse ve container aktifse kapat
    if (container && container.classList.contains('active')) {
        if (!container.contains(e.target)) {
            container.classList.remove('active');
        }
    }
});

// CONTEXT MENU LOGIC for Cards
let activeContextItem = { id: null, title: '', status: false };

function openContextMenu(id, title, isPublished) {
    activeContextItem = { id, title, status: isPublished };

    // UI Updates
    const sheet = document.getElementById('context-menu-sheet');
    const overlay = document.getElementById('context-menu-overlay');
    const titleEl = document.getElementById('contextMenuTitle');
    const statusAction = document.getElementById('contextActionStatus');

    if (titleEl) titleEl.innerText = title;

    // Toggle Status Text based on current state
    if (statusAction) {
        if (isPublished) {
            statusAction.querySelector('.nav-item-label').innerText = 'Yayından Kaldır';
            statusAction.querySelector('.nav-item-icon').className = 'bi bi-eye-slash nav-item-icon';
        } else {
            statusAction.querySelector('.nav-item-label').innerText = 'Yayınla';
            statusAction.querySelector('.nav-item-icon').className = 'bi bi-check-circle nav-item-icon';
        }
    }

    if (sheet && overlay) {
        sheet.classList.add('active');
        overlay.classList.add('active');
        // Vibrate for feedback
        navigator.vibrate?.(10);

        // Hide FAB
        const fab = document.getElementById('fabContainer');
        if (fab) fab.classList.add('hidden');
    }
}

function closeContextMenu() {
    const sheet = document.getElementById('context-menu-sheet');
    const overlay = document.getElementById('context-menu-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.classList.remove('active');

        // Restore FAB
        const fab = document.getElementById('fabContainer');
        if (fab) fab.classList.remove('hidden');
    }
}

function handleContextAction(action) {
    closeContextMenu();

    // Simulate Action Delay
    setTimeout(() => {
        switch (action) {
            case 'edit':
                alert(`DÜZENLEME MODU:\nID: ${activeContextItem.id}\nBaşlık: ${activeContextItem.title}`);
                break;
            case 'gallery':
                alert(`GALERİ YÖNETİMİ:\nID: ${activeContextItem.id}\nBaşlık: ${activeContextItem.title}\nİçerik için resim galerisi açılıyor.`);
                break;
            case 'files':
                alert(`DOSYA YÖNETİMİ:\nID: ${activeContextItem.id}\nBaşlık: ${activeContextItem.title}\nİçerik için dosya yükleme ekranı açılıyor.`);
                break;
            case 'status':
                const newStatus = !activeContextItem.status ? 'Yayınlandı' : 'Yayından Kaldırıldı';
                alert(`DURUM GÜNCELLENDİ:\n${newStatus}`);
                // In real app, we would update the DOM element here
                break;
            case 'delete':
                if (confirm('Bu içeriği silmek istediğinize emin misiniz?')) {
                    alert('İçerik silindi!');
                    // Remove from DOM logic would go here
                }
                break;
        }
    }, 200);
}

// Close Context Menu when clicking overlay (handled by HTML onclick, but added here for safety)
// document.addEventListener('click', ...) logic handles specific containers, but overlays usually cover all.


// Hamburger Menu Binding (after DOM load)
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.querySelector('.icon-btn');
    if (menuBtn) {
        // Remove old listeners just in case
        const newBtn = menuBtn.cloneNode(true);
        menuBtn.parentNode.replaceChild(newBtn, menuBtn);
        newBtn.addEventListener('click', toggleDrawer);
    }
});

// WIZARD LOGIC
let wizardCurrentStep = 1;
const wizardTotalSteps = 8;
const wizardData = {
    lang: 'TR',
    category: null,
    title: '',
    date: '',
    intro: '',
    content: '',
    image: null,
    published: true
};

function openWizard() {
    const wizard = document.getElementById('contentWizard');
    if (wizard) {
        wizard.classList.add('active');
        // Reset to step 1
        wizardCurrentStep = 1;
        updateWizardUI();
        // Close other menus (FAB)
        const fab = document.getElementById('fabContainer');
        if (fab) {
            fab.classList.remove('active');
            fab.style.display = 'none';
        }
    }
}

function closeWizard() {
    const wizard = document.getElementById('contentWizard');
    if (wizard) wizard.classList.remove('active');

    // Restore FAB
    const fab = document.getElementById('fabContainer');
    if (fab) fab.style.display = 'flex';
}

function updateWizardUI() {
    // Hide all steps
    document.querySelectorAll('.wizard-step').forEach(step => {
        step.classList.remove('active');
    });
    // Show current
    const currentStepEl = document.getElementById('wizardStep' + wizardCurrentStep);
    if (currentStepEl) currentStepEl.classList.add('active');

    // Update Header
    const counterEl = document.getElementById('wizardStepCounter');
    if (counterEl) counterEl.innerText = `${wizardCurrentStep}/${wizardTotalSteps}`;

    // Dynamic Title Update
    const titleEl = document.getElementById('wizardStepTitle');
    if (titleEl) {
        const stepTitles = [
            'Dil Seçimi', // Step 1
            'Kategori Seçimi', // Step 2
            'Başlık Girin', // Step 3
            'Yayın Tarihi', // Step 4
            'Giriş Metni', // Step 5
            'İçerik Detayı', // Step 6
            'Görsel Seçimi', // Step 7
            'Özet ve Yayınla' // Step 8
        ];
        titleEl.innerText = stepTitles[wizardCurrentStep - 1] || 'İçerik Ekle';
    }

    // Update Progress Bar
    const progress = (wizardCurrentStep / wizardTotalSteps) * 100;
    const progressFill = document.getElementById('wizardProgressFill');
    if (progressFill) progressFill.style.width = `${progress}%`;

    // Update Buttons
    const btnPrev = document.getElementById('btnWizardPrev');
    const btnNext = document.getElementById('btnWizardNext');
    const btnFinish = document.getElementById('btnWizardFinish');

    if (btnPrev) btnPrev.disabled = (wizardCurrentStep === 1);

    if (wizardCurrentStep === wizardTotalSteps) {
        if (btnNext) btnNext.style.display = 'none';
        if (btnFinish) btnFinish.style.display = 'block';
        updateSummary();
    } else {
        if (btnNext) btnNext.style.display = 'block';
        if (btnFinish) btnFinish.style.display = 'none';
    }
}

function nextWizardStep() {
    if (!validateWizardStep(wizardCurrentStep)) return;

    if (wizardCurrentStep < wizardTotalSteps) {
        wizardCurrentStep++;
        updateWizardUI();
    }
}

function prevWizardStep() {
    if (wizardCurrentStep > 1) {
        wizardCurrentStep--;
        updateWizardUI();
    }
}

function validateWizardStep(step) {
    if (step === 3) {
        const titleInput = document.getElementById('wizardTitleInput');
        if (titleInput) {
            const title = titleInput.value;
            if (title.length < 2) {
                const msg = document.getElementById('titleValidationMsg');
                if (msg) msg.style.display = 'block';
                titleInput.focus();
                return false;
            }
        }
    }
    return true;
}

// Step 1: Language
function selectWizardLang(el, lang) {
    document.querySelectorAll('.selection-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    wizardData.lang = lang;
}

// Step 2: Category
function toggleWizardCategorySheet() {
    const sheet = document.getElementById('wizard-cat-sheet');
    const overlay = document.getElementById('wizard-cat-overlay');
    if (sheet && overlay) {
        if (sheet.classList.contains('active')) {
            sheet.classList.remove('active');
            overlay.style.display = 'none';
        } else {
            sheet.classList.add('active');
            overlay.style.display = 'block';
        }
    }
}

function selectWizardCategory(name, id) {
    wizardData.category = { id, name };
    const label = document.getElementById('wizardSelectedCategory');
    if (label) {
        label.innerText = name;
        label.style.color = 'var(--md-sys-color-on-surface)';
    }
    toggleWizardCategorySheet();
}

// Step 7: Image Preview
function previewWizardImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('wizardImgPreview');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            const label = document.querySelector('.image-upload-area p');
            if (label) label.innerText = "Görsel Değiştir";
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function updateSummary() {
    const langSum = document.getElementById('summaryLang');
    if (langSum) langSum.innerText = wizardData.lang;

    const catSum = document.getElementById('summaryCategory');
    if (catSum) catSum.innerText = wizardData.category ? wizardData.category.name : 'Seçilmedi';
}

function finishWizard() {
    alert('İçerik başarıyla oluşturuldu! (Simülasyon)');
    closeWizard();
}

// Add Listeners dynamically if needed
document.addEventListener('DOMContentLoaded', () => {
    // Validation Listeners
    const titleInput = document.getElementById('wizardTitleInput');
    if (titleInput) {
        titleInput.addEventListener('input', function () {
            const len = this.value.length;
            const counter = document.getElementById('titleCharCounter');
            if (counter) counter.innerText = `${len} / 40`;

            if (len >= 2) {
                const msg = document.getElementById('titleValidationMsg');
                if (msg) msg.style.display = 'none';
            }
            wizardData.title = this.value;
        });
    }

    const introInput = document.getElementById('wizardIntroInput');
    if (introInput) {
        introInput.addEventListener('input', function () {
            const len = this.value.length;
            const counter = document.getElementById('introCharCounter');
            if (counter) counter.innerText = `${len} / 280`;
            wizardData.intro = this.value;
        });
    }

    // Connect FAB
    const fabBtns = document.querySelectorAll('.fab-mini');
    fabBtns.forEach(btn => {
        if (btn.getAttribute('title') === 'İçerik Ekle') {
            btn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                openWizard();
            };
        }
    });
});

// SEARCH BAR LOGIC
const searchInput = document.getElementById('searchInput');
const clearSearchBtn = document.getElementById('clearSearchBtn');

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        if (e.target.value.length > 0) {
            clearSearchBtn.style.display = 'flex';
        } else {
            clearSearchBtn.style.display = 'none';
        }
    });
}

function clearSearch() {
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
        clearSearchBtn.style.display = 'none';
    }
}

// FILTER LOGIC
const filterState = {
    sort: 'En Yeni',
    limit: '10',
    lang: 'TR', // Default to TR as requested (Single language logic)
    dateStart: '',
    dateEnd: ''
};

function selectChip(el, type) {
    // Parent container içindeki diğer active class'ları kaldır
    const container = el.parentElement;
    container.querySelectorAll('.filter-chip').forEach(chip => {
        chip.classList.remove('active');
    });

    // Seçilen öğeyi aktif yap
    el.classList.add('active');

    // State güncelle
    if (type) {
        filterState[type] = el.innerText;
    }
}

// Language Logic (Advanced - Content Focused / Single Select)
const allLanguages = [
    { code: 'TR', name: 'Türkçe' },
    { code: 'EN', name: 'English' },
    { code: 'KU', name: 'Kurdî' },
    { code: 'AR', name: 'العربية' },
    { code: 'DE', name: 'Deutsch' },
    { code: 'FR', name: 'Français' },
    { code: 'ES', name: 'Español' },
    { code: 'IT', name: 'Italiano' },
    { code: 'RU', name: 'Русский' },
    { code: 'JP', name: '日本語' },
    { code: 'ZH', name: '中文' },
    { code: 'PT', name: 'Português' },
    { code: 'NL', name: 'Nederlands' },
    { code: 'KO', name: '한국어' },
    { code: 'SV', name: 'Svenska' },
    { code: 'DA', name: 'Dansk' },
    { code: 'FI', name: 'Suomi' },
    { code: 'NO', name: 'Norsk' },
    { code: 'PL', name: 'Polski' },
    { code: 'CS', name: 'Čeština' },
    { code: 'EL', name: 'Ελληνικά' },
    { code: 'HE', name: 'עברית' },
    { code: 'HI', name: 'हिन्दी' },
    { code: 'TH', name: 'ไทย' },
    { code: 'VI', name: 'Tiếng Việt' },
    { code: 'ID', name: 'Bahasa Indonesia' },
    //... (List continues)
];

// Add dummy languages to reach ~50
for (let i = 1; i <= 50; i++) {
    allLanguages.push({ code: `L${i}`, name: `Language ${i}` });
}

// Single Select Logic: String or null, not array
let tempSelectedLang = 'TR';

function openLangSheet() {
    const sheet = document.getElementById('lang-sheet');
    const overlay = document.getElementById('lang-sheet-overlay');

    // Sync logic: Load current filterState
    // filterState.lang is now a single string
    tempSelectedLang = filterState.lang;

    renderLangList();

    if (sheet && overlay) {
        sheet.classList.add('active');
        overlay.classList.add('active');
        overlay.style.display = 'block';
    }
}

function closeLangSheet() {
    const sheet = document.getElementById('lang-sheet');
    const overlay = document.getElementById('lang-sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.style.display = 'none';
        overlay.classList.remove('active');
    }
}


function filterLanguages() {
    const input = document.getElementById('langSearchInput');
    renderLangList(input.value);
}

function toggleLangSelection(code) {
    // Single Select Logic: Just set it
    tempSelectedLang = code;

    // Re-render to show update
    renderLangList(document.getElementById('langSearchInput').value);
}

function confirmLangSelection() {
    // Save to main filter state
    filterState.lang = tempSelectedLang;
    updateMainLangSummary();
    closeLangSheet();
}

function updateMainLangSummary() {
    const container = document.getElementById('langSummaryContainer');
    if (!container) return;

    let html = '';

    // Label Chip
    html += `<div class="filter-chip active" style="height:32px;">Seç</div>`;

    // Find the name of the current language
    let langName = 'Türkçe'; // Default fallback
    if (filterState.lang) {
        if (filterState.lang === 'Tümü') {
            langName = 'Tümü';
        } else {
            const langObj = allLanguages.find(l => l.code === filterState.lang);
            if (langObj) {
                langName = langObj.name;
            } else {
                langName = filterState.lang;
            }
        }
    }

    // Add Edit Button with Language Name
    html += `
        <button class="add-lang-btn" onclick="openLangSheet()">
            <i class="bi bi-pencil-fill" style="font-size: 12px; margin-right: 6px;"></i> ${langName}
        </button>
    `;

    container.innerHTML = html;
}

function clearFilters() {
    // Reset state visual only (In a real app, logic would reset too)
    document.querySelectorAll('.filter-chip').forEach(chip => chip.classList.remove('active'));

    // Set defaults active
    const chips = document.querySelectorAll('.filter-chip');
    chips.forEach(chip => {
        if (chip.innerText === 'En Yeni' || chip.innerText === '10') {
            chip.classList.add('active');
        }
    });

    // Reset Language to All
    filterState.lang = 'Tümü'; // Single string
    updateMainLangSummary();

    // Reset Dates
    const inputs = document.querySelectorAll('.bottom-sheet input[type="date"]');
    inputs.forEach(input => input.value = '');

    alert('Filtreler temizlendi ve varsayılana döndürüldü.');
}

function applyFilters() {
    closeSheet();

    let msg = `Filtreler Uygulandı:\n`;
    msg += `- Sıralama: ${filterState.sort}\n`;
    msg += `- Adet: ${filterState.limit}\n`;
    msg += `- Dil: ${filterState.lang}`; // It is just a string now

    // Update Pagination
    currentPage = 1;
    updatePaginationUI();

    setTimeout(() => {
        alert(msg);
    }, 300);
}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    // Initial UI updates
    updateMainLangSummary();

    // Close sheets when clicking overlay
    const overlay = document.getElementById('sheet-overlay');
    // Note: The ID in openSheet is 'sheet-overlay', but earlier code referenced 'lang-sheet-overlay'. 
    // We should be careful. The code at 573 refers to a variable `overlay` which is NOT defined in global scope!

    // Fix: Re-select overlay here
    if (overlay) {
        overlay.addEventListener('click', () => {
            closeSheet();
            closeFabMenu();
            const wizardOverlay = document.getElementById('wizard-overlay');
            if (wizardOverlay && wizardOverlay.classList.contains('active')) {
                // Wizard overlay closing logic if needed
            }
        });
    }
});

function renderLangList(filter = '') {
    const container = document.getElementById('langListContainer');
    if (!container) return;

    container.innerHTML = '';

    // "Tümü" option
    const allRow = document.createElement('div');
    const isAllSelected = (tempSelectedLang === 'Tümü');
    allRow.className = `lang-item-row ${isAllSelected ? 'selected' : ''}`;
    allRow.onclick = () => toggleLangSelection('Tümü');
    allRow.innerHTML = `
        <div class="lang-checkbox" style="border-radius:50%">
            ${isAllSelected ? '<i class="bi bi-circle-fill" style="font-size:12px;"></i>' : ''}
        </div>
        <div class="lang-name" style="font-weight:bold;">Tümü (Filtresiz)</div>
    `;
    container.appendChild(allRow);


    // TR-Safe Search
    const lowerFilter = filter.toLocaleLowerCase('tr');

    const filtered = allLanguages.filter(l =>
        l.name.toLocaleLowerCase('tr').includes(lowerFilter) ||
        l.code.toLocaleLowerCase('tr').includes(lowerFilter)
    );

    filtered.forEach(lang => {
        const isSelected = (tempSelectedLang === lang.code);

        const row = document.createElement('div');
        row.className = `lang-item-row ${isSelected ? 'selected' : ''}`;
        row.onclick = () => toggleLangSelection(lang.code);

        const radio = document.createElement('div');
        radio.className = 'lang-checkbox';
        radio.style.borderRadius = '50%';
        if (isSelected) radio.innerHTML = '<i class="bi bi-circle-fill" style="font-size:12px;"></i>';

        const code = document.createElement('div');
        code.className = 'lang-code';
        code.innerText = lang.code;

        const name = document.createElement('div');
        name.className = 'lang-name';
        name.innerText = lang.name;

        row.appendChild(radio);
        row.appendChild(code);
        row.appendChild(name);

        container.appendChild(row);
    });
}

// PAGINATION LOGIC
let currentPage = 1;
let totalItems = 124; // Mock total
let itemsPerPage = 10;

function updatePaginationUI() {
    // Parse limit from filterState
    itemsPerPage = parseInt(filterState.limit) || 10;

    const totalPages = Math.ceil(totalItems / itemsPerPage);

    // Ensure current page is valid
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startItem = (currentPage - 1) * itemsPerPage + 1;
    let endItem = currentPage * itemsPerPage;
    if (endItem > totalItems) endItem = totalItems;

    // Update Text
    const infoEl = document.getElementById('paginationInfo');
    if (infoEl) {
        infoEl.innerText = `${startItem} - ${endItem} / ${totalItems}`;
    }

    // Update Buttons
    const btnPrev = document.getElementById('btnPagePrev');
    const btnNext = document.getElementById('btnPageNext');

    if (btnPrev) btnPrev.disabled = (currentPage === 1);
    if (btnNext) btnNext.disabled = (currentPage === totalPages);
}

function nextPage() {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        updatePaginationUI();
        // Simulate refresh or scroll to top
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (scrollFrame) scrollFrame.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        updatePaginationUI();
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (scrollFrame) scrollFrame.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Update pagination on load
document.addEventListener('DOMContentLoaded', () => {
    updatePaginationUI();
    // Initial Load
    loadMoreContent();
    setupInfiniteScroll();
    setupPullToRefresh();
});


/* =========================================
   INFINITE SCROLL & PULL-TO-REFRESH LOGIC
   ========================================= */

let isLoading = false;
let ptrStartY = 0;
let ptrDistance = 0;
const PTR_THRESHOLD = 80; // Pixels to pull down to trigger refresh

const appScrollFrame = document.querySelector('.app-scroll-frame');
const mainContent = document.getElementById('mainContent');
const ptrSpinner = document.getElementById('ptr-spinner');
const infiniteLoader = document.getElementById('infinite-loader');

// Mock Data Generators
const mockTitles = [
    "Kürt Basınında Yeni Dönem Başlıyor",
    "Diyarbakır Kültür Sanat Festivali Takvimi Açıklandı",
    "Yerel Gazetecilik Çalıştayı Sona Erdi",
    "Yapay Zeka ve Medya İlişkisi Üzerine Notlar",
    "Geleneksel Gazetecilikten Dijital Medyaya Geçiş",
    "Sosyal Medya Kullanım İstatistikleri 2024",
    "Haftalık Teknoloji Özeti: Neler Oldu?",
    "Yazarlar Birliği Yeni Yönetimini Seçti",
    "Fotoğrafçılık Atölyesi Kayıtları Başladı",
    "Rojname Mobil Uygulaması Güncellendi"
];

const mockCategories = ["Gündem", "Kültür & Sanat", "Teknoloji", "Medya", "Yaşam"];
const mockAuthors = ["Ahmet Yılmaz", "Zeynep Demir", "Mehmet Kaya", "Ayşe Can", "Serdar Vural"];

function getRandomItem(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

function getRandomDate() {
    const start = new Date(2023, 0, 1);
    const end = new Date();
    const d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
    return d.toLocaleDateString('tr-TR');
}

function createCardElement(id) {
    const title = getRandomItem(mockTitles);
    const category = getRandomItem(mockCategories);
    const author = getRandomItem(mockAuthors);
    const date = getRandomDate();
    const views = Math.floor(Math.random() * 5000) + 100;

    // Status Logic
    const isPublished = Math.random() > 0.2;
    const statusText = isPublished ? "Yayında" : "Taslak";
    const statusColor = isPublished ? "var(--md-sys-color-primary)" : "#FFAB00";

    // Create Card with M3 Classes
    const card = document.createElement('div');
    card.className = 'm3-card'; // Using the class from style.css

    // Inner HTML Structure matching the expected layout in style.css
    card.innerHTML = `
        <div class="card-content-layout">
            <!-- Thumbnail Placeholder -->
            <div class="card-thumbnail" style="display:flex; align-items:center; justify-content:center; color:#999;">
                <i class="bi bi-image" style="font-size: 24px;"></i>
            </div>
            
            <div class="card-body-col">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                     <div>
                        <div class="meta-row" style="margin-top:0; margin-bottom:4px;">
                            <span style="color:${statusColor}; font-weight:500;">${statusText}</span>
                            <span>•</span>
                            <span>${category}</span>
                        </div>
                        <h3 style="margin:0; font-size:16px; font-weight:500; line-height:1.4;">${title}</h3>
                     </div>
                     <button class="icon-btn-small" style="width:32px; height:32px; margin-top:-8px; margin-right:-8px;" onclick="openContextMenu('${id}', '${title.replace(/'/g, "\\'")}', ${isPublished})">
                        <i class="bi bi-three-dots-vertical" style="font-size:18px;"></i>
                     </button>
                </div>

                <div class="meta-row" style="margin-top:8px;">
                     <span><i class="bi bi-calendar3" style="font-size:11px; margin-right:4px;"></i>${date}</span>
                     <span><i class="bi bi-eye" style="font-size:11px; margin-right:4px;"></i>${views}</span>
                     <span style="margin-left:auto;">${author}</span>
                </div>
            </div>
        </div>

    `;

    // Click handler for card interactions
    card.addEventListener('click', (e) => {
        // Handle Delete Button Specific Logic
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            if (confirm('Bu içeriği silmek istediğinize emin misiniz?')) {
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300); // Animation delay
            }
            return;
        }

        // Generic Card Click
        if (!e.target.closest('button')) {
            // alert(`Kart tıklandı: ${title}`);
        }
    });

    return card;
}

function loadMoreContent(isRefresh = false) {
    if (isLoading) return;
    isLoading = true;

    // Show loading indicator
    if (isRefresh) {
        if (ptrSpinner) ptrSpinner.classList.add('visible');
    } else {
        if (infiniteLoader) infiniteLoader.style.display = 'flex';
    }

    // Simulate Network Request
    setTimeout(() => {
        // If Refresh, clear existing content (keeping spinners)
        if (isRefresh) {
            // Remove all elements except spinners
            const cards = mainContent.querySelectorAll('.card');
            cards.forEach(el => el.remove());
            currentPage = 1;
        }

        // Generate N new cards
        const count = itemsPerPage;
        const fragment = document.createDocumentFragment();

        for (let i = 0; i < count; i++) {
            fragment.appendChild(createCardElement(i));
        }

        // Insert before the infinite loader
        if (infiniteLoader && mainContent) {
            mainContent.insertBefore(fragment, infiniteLoader);
        }

        // Update UI State
        isLoading = false;

        if (isRefresh) {
            if (ptrSpinner) {
                ptrSpinner.classList.remove('visible');
                // Reset styling
                ptrSpinner.style.height = '';
                ptrSpinner.style.opacity = '';
            }
            // Reset Scroll Logic for PTR
            ptrDistance = 0;
        } else {
            if (infiniteLoader) infiniteLoader.style.display = 'none';
            currentPage++;
        }

        updatePaginationUI(); // Update texts

    }, 1500); // 1.5s delay
}

/* --------------------------
   Infinite Scroll Listener
   -------------------------- */
function setupInfiniteScroll() {
    if (!appScrollFrame) return;

    appScrollFrame.addEventListener('scroll', () => {
        const { scrollTop, scrollHeight, clientHeight } = appScrollFrame;

        // Load more when user is 100px from the bottom
        if (scrollTop + clientHeight >= scrollHeight - 100) {
            // Check limits (using mock total)
            const currentTotal = (currentPage - 1) * itemsPerPage;
            if (currentTotal < totalItems) {
                loadMoreContent(false);
            }
        }
    });
}

/* --------------------------
   Pull-to-Refresh Logic
   -------------------------- */
function setupPullToRefresh() {
    if (!appScrollFrame || !ptrSpinner) return;

    appScrollFrame.addEventListener('touchstart', (e) => {
        if (appScrollFrame.scrollTop === 0) {
            ptrStartY = e.touches[0].clientY;
        }
    }, { passive: true });

    appScrollFrame.addEventListener('touchmove', (e) => {
        if (isLoading) return;

        const currentY = e.touches[0].clientY;
        if (appScrollFrame.scrollTop <= 0) {
            // Only tracking if at top
            ptrDistance = currentY - ptrStartY;

            if (ptrDistance > 0) {
                // Resistance effect
                const move = Math.min(ptrDistance * 0.5, 100);
                ptrSpinner.style.height = `${move}px`;
                ptrSpinner.style.opacity = Math.min(move / 60, 1);
            }
        }
    }, { passive: true });

    appScrollFrame.addEventListener('touchend', (e) => {
        if (appScrollFrame.scrollTop <= 0 && ptrDistance > PTR_THRESHOLD && !isLoading) {
            // Trigger Refresh
            loadMoreContent(true);
        } else {
            // Snap back
            if (ptrDistance > 0) {
                ptrSpinner.style.height = '0px';
                ptrSpinner.style.opacity = '0';
            }
        }
        ptrDistance = 0;
    });
}


/* --------------------------
   VIDEO EMBED WIZARD LOGIC
   -------------------------- */
let videoWizardCurrentStep = 1;
const videoWizardTotalSteps = 6;
const videoWizardData = {
    lang: 'TR',
    category: null,
    embedCode: '',
    originalTitle: '',
    title: '',
    duration: '',
    date: '',
    image: null,
    published: true
};

function openVideoWizard() {
    const wizard = document.getElementById('videoWizard');
    if (wizard) {
        wizard.classList.add('active');
        videoWizardCurrentStep = 1;
        updateVideoWizardUI();

        // Close other menus (FAB)
        const fab = document.getElementById('fabContainer');
        if (fab) fab.classList.remove('active');
    }
}

function closeVideoWizard() {
    const wizard = document.getElementById('videoWizard');
    if (wizard) wizard.classList.remove('active');
}

function updateVideoWizardUI() {
    // Hide all steps
    document.querySelectorAll('#videoWizard .wizard-step').forEach(step => {
        step.classList.remove('active');
    });
    // Show current
    const currentStepEl = document.getElementById('videoWizardStep' + videoWizardCurrentStep);
    if (currentStepEl) currentStepEl.classList.add('active');

    // Update Header
    const counterEl = document.getElementById('videoWizardStepCounter');
    if (counterEl) counterEl.innerText = `${videoWizardCurrentStep}/${videoWizardTotalSteps}`;
    const titleEl = document.getElementById('videoWizardStepTitle');
    // Optional: Dynamic Title based on step content

    // Update Progress Bar
    const progress = (videoWizardCurrentStep / videoWizardTotalSteps) * 100;
    const progressFill = document.getElementById('videoWizardProgressFill');
    if (progressFill) progressFill.style.width = `${progress}%`;

    // Update Buttons
    const btnPrev = document.getElementById('btnVideoWizardPrev');
    const btnNext = document.getElementById('btnVideoWizardNext');
    const btnFinish = document.getElementById('btnVideoWizardFinish');

    if (btnPrev) btnPrev.disabled = (videoWizardCurrentStep === 1);

    if (videoWizardCurrentStep === videoWizardTotalSteps) {
        if (btnNext) btnNext.style.display = 'none';
        if (btnFinish) btnFinish.style.display = 'block';
        updateVideoSummary();
    } else {
        if (btnNext) btnNext.style.display = 'block';
        if (btnFinish) btnFinish.style.display = 'none';
    }
}

function nextVideoWizardStep() {
    if (!validateVideoWizardStep(videoWizardCurrentStep)) return;

    if (videoWizardCurrentStep < videoWizardTotalSteps) {
        videoWizardCurrentStep++;
        updateVideoWizardUI();
    }
}

function prevVideoWizardStep() {
    if (videoWizardCurrentStep > 1) {
        videoWizardCurrentStep--;
        updateVideoWizardUI();
    }
}

function validateVideoWizardStep(step) {
    // Step 3: Embed Code
    if (step === 3) {
        const embedInput = document.getElementById('videoEmbedInput');
        if (embedInput && embedInput.value.length < 10) {
            alert('Lütfen geçerli bir embed kodu giriniz (iframe).');
            embedInput.focus();
            return false;
        }
    }
    // Step 4: Title
    if (step === 4) {
        const titleInput = document.getElementById('videoTitleInput');
        if (titleInput && titleInput.value.length < 3) {
            alert('Video başlığı en az 3 karakter olmalıdır.');
            titleInput.focus();
            return false;
        }
    }
    return true;
}

// Step 1: Language
function selectVideoWizardLang(el, lang) {
    document.querySelectorAll('#videoWizard .selection-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    videoWizardData.lang = lang;
}

// Step 5: Image Preview
function previewVideoWizardImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('videoWizardImgPreview');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            const label = document.querySelector('#videoWizard .image-upload-area p');
            if (label) label.innerText = "Görsel Değiştir";
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function updateVideoSummary() {
    const langSum = document.getElementById('videoSummaryLang');
    if (langSum) langSum.innerText = videoWizardData.lang;

    const catSum = document.getElementById('videoSummaryCategory');
    if (catSum) catSum.innerText = videoWizardData.category ? videoWizardData.category.name : 'Seçilmedi';

    const titleSum = document.getElementById('videoSummaryTitle');
    if (titleSum) titleSum.innerText = document.getElementById('videoTitleInput').value || '-';
}

function finishVideoWizard() {
    alert('Video başarıyla eklendi! (Simülasyon)');
    closeVideoWizard();
}

// Connect Global FAB "Video Ekle" Button
document.addEventListener('DOMContentLoaded', () => {
    const fabBtns = document.querySelectorAll('.fab-mini');
    fabBtns.forEach(btn => {
        if (btn.getAttribute('title') === 'Video Ekle') {
            // Override existing click
            btn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                openVideoWizard();
            };
        }
    });
});
