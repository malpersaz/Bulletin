(function() {
    if (window.__amedShareInitialized) {
        return;
    }
    window.__amedShareInitialized = true;

    function toCleanReadableUrl(rawUrl) {
        if (!rawUrl) {
            return '';
        }
        try {
            return decodeURI(rawUrl);
        } catch (err) {
            return rawUrl;
        }
    }

    function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).catch(function() {
                return fallbackCopyText(text);
            });
        }
        return fallbackCopyText(text);
    }

    function fallbackCopyText(text) {
        return new Promise(function(resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.className = 'visually-hidden';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                var ok = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (ok) {
                    resolve();
                } else {
                    reject(new Error('Copy command failed'));
                }
            } catch (err) {
                document.body.removeChild(textarea);
                reject(err);
            }
        });
    }

    function initShareTooltips(root) {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip || !root) {
            return;
        }
        var selector = '.share-toolbar [data-bs-toggle="tooltip"], .share-popover-menu [data-bs-toggle="tooltip"], .detail-tools [data-bs-toggle="tooltip"]';
        var targets = root === document ? document.querySelectorAll(selector) : root.querySelectorAll('[data-bs-toggle="tooltip"]');
        targets.forEach(function(el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });
    }

    function closeAllSharePopovers(exceptWrapper) {
        document.querySelectorAll('.share-popover-wrapper').forEach(function(wrapper) {
            if (exceptWrapper && wrapper === exceptWrapper) {
                return;
            }
            var trigger = wrapper.querySelector('.share-trigger-btn');
            var menu = wrapper.querySelector('.share-popover-menu');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            if (menu) {
                menu.hidden = true;
                menu.setAttribute('hidden', '');
                menu.classList.remove('is-open');
            }
        });
    }

    function showCopySuccessFeedback(btn, copiedText, originalText, swapCheckIcon) {
        btn.classList.add('is-copied');

        var iconEl = swapCheckIcon ? btn.querySelector('i') : null;
        if (iconEl) {
            iconEl.classList.remove('bi-link-45deg');
            iconEl.classList.add('bi-check2');
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltip = bootstrap.Tooltip.getInstance(btn) || new bootstrap.Tooltip(btn);
            if (tooltip && typeof tooltip.setContent === 'function') {
                tooltip.setContent({'.tooltip-inner': copiedText});
                tooltip.show();
                setTimeout(function() {
                    tooltip.setContent({'.tooltip-inner': originalText});
                    tooltip.hide();
                }, 1800);
            }
        }

        setTimeout(function() {
            btn.classList.remove('is-copied');
            if (iconEl) {
                iconEl.classList.remove('bi-check2');
                iconEl.classList.add('bi-link-45deg');
            }
        }, 1800);

        if (btn.closest('.share-popover-menu')) {
            setTimeout(function() {
                closeAllSharePopovers(null);
            }, 900);
        }
    }

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.share-trigger-btn');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            var wrapper = trigger.closest('.share-popover-wrapper');
            var menu = wrapper ? wrapper.querySelector('.share-popover-menu') : null;
            if (!wrapper || !menu) {
                return;
            }
            var isExpanded = trigger.getAttribute('aria-expanded') === 'true' && !menu.hasAttribute('hidden');
            closeAllSharePopovers(wrapper);
            if (isExpanded) {
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
                menu.setAttribute('hidden', '');
                menu.classList.remove('is-open');
            } else {
                trigger.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
                menu.removeAttribute('hidden');
                menu.classList.add('is-open');
                initShareTooltips(menu);
            }
            return;
        }

        var printBtn = e.target.closest('[data-action="print"]');
        if (printBtn) {
            e.preventDefault();
            window.print();
            return;
        }

        var tiktokBtn = e.target.closest('[data-share-network="tiktok"]');
        if (tiktokBtn) {
            e.preventDefault();
            var title = tiktokBtn.getAttribute('data-share-title') || document.title;
            var url = toCleanReadableUrl(tiktokBtn.getAttribute('data-share-url') || window.location.href);
            var copiedText = tiktokBtn.getAttribute('data-copied-text') || 'Copied!';

            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(function() {});
                if (tiktokBtn.closest('.share-popover-menu')) {
                    setTimeout(function() {
                        closeAllSharePopovers(null);
                    }, 150);
                }
                return;
            }

            copyTextToClipboard(url).then(function() {
                showCopySuccessFeedback(tiktokBtn, copiedText, 'TikTok', false);
            }).catch(function() {});
            return;
        }

        var copyBtn = e.target.closest('.btn-copy-link, .btn-copy, [data-copy-url]');
        if (copyBtn) {
            e.preventDefault();
            var copyUrl = toCleanReadableUrl(copyBtn.getAttribute('data-copy-url') || window.location.href);
            var copiedLabel = copyBtn.getAttribute('data-copied-text') || 'Copied!';
            var originalLabel = copyBtn.getAttribute('data-original-text') || copyBtn.getAttribute('title') || 'Copy Link';

            copyTextToClipboard(copyUrl).then(function() {
                showCopySuccessFeedback(copyBtn, copiedLabel, originalLabel, true);
            }).catch(function(err) {
                console.error('Copy to clipboard failed:', err);
            });
            return;
        }

        var nativeToolbarBtn = e.target.closest('.share-toolbar .btn-native-share');
        if (nativeToolbarBtn) {
            e.preventDefault();
            if (navigator.share) {
                var shareTitle = nativeToolbarBtn.getAttribute('data-share-title') || document.title;
                var shareUrl = toCleanReadableUrl(nativeToolbarBtn.getAttribute('data-share-url') || window.location.href);
                navigator.share({
                    title: shareTitle,
                    url: shareUrl
                }).catch(console.error);
            }
            return;
        }

        var popoverLink = e.target.closest('.share-popover-menu a.social-share-btn');
        if (popoverLink) {
            setTimeout(function() {
                closeAllSharePopovers(null);
            }, 150);
            return;
        }

        if (!e.target.closest('.share-popover-wrapper')) {
            closeAllSharePopovers(null);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllSharePopovers(null);
        }
    });

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function() {
        if (navigator.share && window.innerWidth < 992) {
            document.querySelectorAll('.share-toolbar .btn-native-share').forEach(function(btn) {
                btn.style.display = 'inline-flex';
                var list = btn.nextElementSibling;
                if (list && list.classList.contains('share-buttons-list')) {
                    list.style.display = 'none';
                }
                if (btn.previousElementSibling && btn.previousElementSibling.classList.contains('share-text')) {
                    btn.previousElementSibling.style.display = 'none';
                }
            });
        }

        initShareTooltips(document);
    });
})();
