document.addEventListener('DOMContentLoaded', function() {
    // Print Action Handler
    document.querySelectorAll('[data-action="print"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    });

    // TikTok Share / Native Web Share
    document.querySelectorAll('[data-share-network="tiktok"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            var title = btn.getAttribute('data-share-title') || document.title;
            var url = btn.getAttribute('data-share-url') || window.location.href;
            var copiedText = btn.getAttribute('data-copied-text') || 'Copied!';

            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(function() {});
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        var tooltip = bootstrap.Tooltip.getInstance(btn);
                        if (tooltip) {
                            tooltip.setContent({'.tooltip-inner': copiedText});
                            setTimeout(function() {
                                tooltip.setContent({'.tooltip-inner': 'TikTok'});
                                tooltip.hide();
                            }, 2000);
                        }
                    }
                }).catch(function() {});
            }
        });
    });

    // Copy Link button handler
    document.querySelectorAll('.btn-copy-link, .btn-copy, [data-copy-url]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var copyUrl = btn.getAttribute('data-copy-url') || window.location.href;
            var copiedText = btn.getAttribute('data-copied-text') || 'Copied!';
            var originalText = btn.getAttribute('data-original-text') || btn.getAttribute('title') || 'Copy Link';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(copyUrl).then(function() {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        var tooltip = bootstrap.Tooltip.getInstance(btn);
                        if (tooltip) {
                            tooltip.setContent({'.tooltip-inner': copiedText});
                            setTimeout(function() {
                                tooltip.setContent({'.tooltip-inner': originalText});
                                tooltip.hide();
                            }, 2000);
                        }
                    }
                }).catch(function(err) {
                    console.error('Copy to clipboard failed:', err);
                });
            }
        });
    });

    // Mobile Native Share
    if (navigator.share && window.innerWidth < 992) {
        document.querySelectorAll('.btn-native-share').forEach(function(btn) {
            btn.style.display = 'inline-flex';
            var list = btn.nextElementSibling;
            if (list && list.classList.contains('share-buttons-list')) {
                list.style.display = 'none';
            }
            if (btn.previousElementSibling && btn.previousElementSibling.classList.contains('share-text')) {
                btn.previousElementSibling.style.display = 'none';
            }
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var shareTitle = btn.getAttribute('data-share-title') || document.title;
                var shareUrl = btn.getAttribute('data-share-url') || window.location.href;
                navigator.share({
                    title: shareTitle,
                    url: shareUrl
                }).catch(console.error);
            });
        });
    }

    // Initialize Bootstrap Tooltips for share toolbar if needed
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var shareTooltips = [].slice.call(document.querySelectorAll('.share-toolbar [data-bs-toggle="tooltip"]'));
        shareTooltips.forEach(function(el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });
    }
});
