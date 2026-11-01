/**
 * Month Popover Engine - Amed Framework / Amîda Theme
 * Accessible multilingual month name translation popovers for news and articles.
 * Strictly adheres to AGENTS.md Rule 2 (Strict Code Separation Mandate).
 */
(function () {
    'use strict';

    /**
     * Canonical 12-month dictionary for Northern Kurdish, English, Turkish, and Arabic.
     * Includes dialectal/common variants and suffixed stems to ensure resilient fallbacks
     * even if editorial content omits explicit data attributes.
     */
    const MONTH_CATALOG = [
        {
            key: 'rebendan',
            names: { ku: 'Rêbendan', en: 'January', tr: 'Ocak', ar: 'كانون الثاني' },
            aliases: ['rêbendan', 'rebendan', 'çile', 'cile', 'kanûna duyem', 'kanuna duyem']
        },
        {
            key: 'resemi',
            names: { ku: 'Reşemî', en: 'February', tr: 'Şubat', ar: 'شباط' },
            aliases: ['reşemî', 'resemi', 'sibat']
        },
        {
            key: 'adar',
            names: { ku: 'Adar', en: 'March', tr: 'Mart', ar: 'آذار' },
            aliases: ['adar']
        },
        {
            key: 'avrel',
            names: { ku: 'Avrêl', en: 'April', tr: 'Nisan', ar: 'نيسان' },
            aliases: ['avrêl', 'avrel', 'nîsan', 'nisan']
        },
        {
            key: 'gulan',
            names: { ku: 'Gulan', en: 'May', tr: 'Mayıs', ar: 'أيار' },
            aliases: ['gulan']
        },
        {
            key: 'pusper',
            names: { ku: 'Pûşper', en: 'June', tr: 'Haziran', ar: 'حزيران' },
            aliases: ['pûşper', 'pusper', 'hezîran', 'heziran']
        },
        {
            key: 'tirmeh',
            names: { ku: 'Tîrmeh', en: 'July', tr: 'Temmuz', ar: 'تموز' },
            aliases: ['tîrmeh', 'tirmeh']
        },
        {
            key: 'gelawej',
            names: { ku: 'Gelawêj', en: 'August', tr: 'Ağustos', ar: 'آب' },
            aliases: ['gelawêj', 'gelawej', 'tebax']
        },
        {
            key: 'rezber',
            names: { ku: 'Rezber', en: 'September', tr: 'Eylül', ar: 'أيلول' },
            aliases: ['rezber', 'îlon', 'ilon']
        },
        {
            key: 'kewcer',
            names: { ku: 'Kewçêr', en: 'October', tr: 'Ekim', ar: 'تشرين الأول' },
            aliases: ['kewçêr', 'kewcer', 'cotmeh', 'çiriya pêşîn', 'ciriye pesin']
        },
        {
            key: 'sermawez',
            names: { ku: 'Sermawez', en: 'November', tr: 'Kasım', ar: 'تشرين الثاني' },
            aliases: ['sermawez', 'middar', 'miydar', 'mijdar', 'çiriya paşîn', 'ciriye pasin']
        },
        {
            key: 'berfanbar',
            names: { ku: 'Berfanbar', en: 'December', tr: 'Aralık', ar: 'كانون الأول' },
            aliases: ['berfanbar', 'kanûn', 'kanun', 'kanûna pêşîn', 'kanuna pesin']
        }
    ];

    /**
     * Normalize a token string by stripping punctuation, extra whitespace,
     * and known Kurdish case suffixes (e.g. "Kewçêrê" -> "kewçêr").
     */
    function normalizeToken(str) {
        if (!str) return '';
        let cleaned = String(str)
            .trim()
            .toLowerCase()
            .replace(/['"`’‘]/g, '')
            .replace(/[.,:;!?()[\]{}]/g, '');

        // Kurdish grammatical suffix stripping (e.g., -ê, -yê, -a, -an)
        if (cleaned.length > 5) {
            if (cleaned.endsWith('yê') || cleaned.endsWith('ye')) {
                cleaned = cleaned.slice(0, -2);
            } else if (cleaned.endsWith('ê') || cleaned.endsWith('e')) {
                cleaned = cleaned.slice(0, -1);
            }
        }
        return cleaned;
    }

    /**
     * Resolve month translations from explicit data attributes or fallback dictionary.
     */
    function resolveMonth(trigger) {
        const rawKu = trigger.dataset.monthKu || '';
        const rawEn = trigger.dataset.monthEn || '';
        const rawTr = trigger.dataset.monthTr || '';
        const rawAr = trigger.dataset.monthAr || '';

        // If explicit dataset is complete, prioritize it
        if (rawKu && rawEn && rawTr && rawAr) {
            return {
                ku: rawKu,
                en: rawEn,
                tr: rawTr,
                ar: rawAr
            };
        }

        // Match against dictionary via textContent or partial dataset
        const textToken = normalizeToken(trigger.textContent);
        const dataToken = normalizeToken(rawKu);

        let match = MONTH_CATALOG.find(function (item) {
            return item.aliases.some(function (alias) {
                const normAlias = normalizeToken(alias);
                return normAlias === textToken || normAlias === dataToken;
            });
        });

        // Substring fallback (e.g. if text is "Kewçêrê")
        if (!match && textToken.length >= 4) {
            match = MONTH_CATALOG.find(function (item) {
                return item.aliases.some(function (alias) {
                    const normAlias = normalizeToken(alias);
                    return textToken.startsWith(normAlias) || normAlias.startsWith(textToken);
                });
            });
        }

        const fallback = match ? match.names : {
            ku: trigger.textContent.trim(),
            en: '',
            tr: '',
            ar: ''
        };

        return {
            ku: rawKu || fallback.ku,
            en: rawEn || fallback.en,
            tr: rawTr || fallback.tr,
            ar: rawAr || fallback.ar || (match && match.names.arAlt ? match.names.arAlt : '')
        };
    }

    let activePopover = null;

    /**
     * Close currently opened popover and restore trigger accessibility state.
     */
    function closeActivePopover() {
        if (activePopover) {
            if (activePopover._trigger) {
                activePopover._trigger.setAttribute('aria-expanded', 'false');
            }
            activePopover.remove();
            activePopover = null;
        }
    }

    /**
     * Position popover relative to trigger element with edge and scroll bounds protection.
     */
    function positionPopover(trigger, pop) {
        const rect = trigger.getBoundingClientRect();
        const popRect = pop.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const scrollX = window.pageXOffset || document.documentElement.scrollLeft || 0;
        const scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;

        let top = rect.bottom + scrollY + 6;
        let placement = 'bottom';

        // Flip to top if overflow occurs at the bottom of the viewport
        if (rect.bottom + popRect.height + 12 > viewportHeight && rect.top - popRect.height - 12 > 0) {
            top = rect.top + scrollY - popRect.height - 6;
            placement = 'top';
        }

        let left = rect.left + scrollX + (rect.width / 2) - (popRect.width / 2);

        // Keep inside horizontal viewport margins
        const minLeft = scrollX + 10;
        const maxLeft = scrollX + viewportWidth - popRect.width - 10;

        if (left < minLeft) left = minLeft;
        if (left > maxLeft) left = Math.max(minLeft, maxLeft);

        pop.style.top = Math.round(top) + 'px';
        pop.style.left = Math.round(left) + 'px';
        pop.setAttribute('data-placement', placement);
    }

    /**
     * Build and display month popover box.
     */
    function openPopover(trigger) {
        closeActivePopover();

        const month = resolveMonth(trigger);

        const pop = document.createElement('div');
        pop.className = 'month-popover-box';
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Navên Mehan');
        pop._trigger = trigger;

        let html = '';
        if (month.ku) {
            html += '<div class="month-row"><span class="month-lang">Kurdî</span><span class="month-val">' + escapeHtml(month.ku) + '</span></div>';
        }
        if (month.en) {
            html += '<div class="month-row"><span class="month-lang">English</span><span class="month-val">' + escapeHtml(month.en) + '</span></div>';
        }
        if (month.tr) {
            html += '<div class="month-row"><span class="month-lang">Türkçe</span><span class="month-val">' + escapeHtml(month.tr) + '</span></div>';
        }
        if (month.ar) {
            html += '<div class="month-row"><span class="month-lang">العربية</span><span class="month-val" dir="rtl">' + escapeHtml(month.ar) + '</span></div>';
        }

        pop.innerHTML = html;
        document.body.appendChild(pop);

        trigger.setAttribute('aria-expanded', 'true');
        activePopover = pop;

        positionPopover(trigger, pop);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Initialize attributes on matching DOM triggers for accessibility.
     */
    function decorateTriggers() {
        const triggers = document.querySelectorAll('.month-popover');
        triggers.forEach(function (trigger) {
            if (!trigger.hasAttribute('role')) {
                trigger.setAttribute('role', 'button');
            }
            if (!trigger.hasAttribute('tabindex')) {
                trigger.setAttribute('tabindex', '0');
            }
            if (!trigger.hasAttribute('aria-haspopup')) {
                trigger.setAttribute('aria-haspopup', 'dialog');
            }
            if (!trigger.hasAttribute('aria-expanded')) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /**
     * Global event listeners for interaction and dismissal.
     */
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.month-popover');
        if (!trigger) {
            if (activePopover && !activePopover.contains(e.target)) {
                closeActivePopover();
            }
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if (activePopover && activePopover._trigger === trigger) {
            closeActivePopover();
            return;
        }

        openPopover(trigger);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (activePopover) {
                const trigger = activePopover._trigger;
                closeActivePopover();
                if (trigger && typeof trigger.focus === 'function') {
                    trigger.focus();
                }
            }
            return;
        }

        const trigger = e.target.closest('.month-popover');
        if (trigger && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            e.stopPropagation();
            if (activePopover && activePopover._trigger === trigger) {
                closeActivePopover();
            } else {
                openPopover(trigger);
            }
        }
    });

    window.addEventListener('resize', function () {
        if (activePopover && activePopover._trigger) {
            positionPopover(activePopover._trigger, activePopover);
        }
    });

    window.addEventListener('scroll', function () {
        if (activePopover && activePopover._trigger) {
            positionPopover(activePopover._trigger, activePopover);
        }
    }, { passive: true });

    // Initial DOM decoration
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', decorateTriggers);
    } else {
        decorateTriggers();
    }

    // Expose global initializer
    window.initializeMonthPopovers = function () {
        decorateTriggers();
    };

    window.AFMonthPopover = {
        open: openPopover,
        close: closeActivePopover,
        refresh: decorateTriggers,
        resolve: resolveMonth
    };
})();
