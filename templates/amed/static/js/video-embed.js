/**
 * video-embed.js
 * Multi-Provider Facade Video Player Controller (X, YouTube, Vimeo, HTML5)
 * Strict Code Separation: External script with keyboard accessibility and theme synchronization.
 */

(function () {
    'use strict';

    function getActiveTheme() {
        const docTheme = document.documentElement.getAttribute('data-bs-theme');
        if (docTheme === 'dark' || docTheme === 'light') {
            return docTheme;
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function activateVideoEmbed(stage) {
        if (!stage || stage.dataset.mounted === 'true') {
            return;
        }

        const embedSrc = stage.dataset.embedSrc;
        if (!embedSrc) {
            return;
        }

        stage.dataset.mounted = 'true';
        const provider = stage.dataset.provider || 'youtube';
        const title = stage.dataset.title || 'Video Player';
        const theme = getActiveTheme();

        let finalSrc = embedSrc;

        if (provider === 'x') {
            if (/theme=[a-z]+/i.test(finalSrc)) {
                finalSrc = finalSrc.replace(/theme=[a-z]+/i, 'theme=' + theme);
            } else {
                finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'theme=' + theme;
            }
        } else if (provider === 'youtube') {
            if (!/[?&]autoplay=1/.test(finalSrc)) {
                finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'autoplay=1';
            }
        } else if (provider === 'facebook') {
            if (!/[?&]autoplay=(true|1)/i.test(finalSrc)) {
                finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'autoplay=true';
            }
        }

        const iframe = document.createElement('iframe');
        iframe.className = 'video-embed-mounted-frame';
        iframe.src = finalSrc;
        iframe.title = title;
        iframe.setAttribute('loading', 'lazy');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        if (provider === 'x' || provider === 'facebook') {
            iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation');
        }
        if (provider === 'x') {
            iframe.setAttribute('scrolling', 'no');
        }
        iframe.setAttribute('allowfullscreen', 'true');
        iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');

        const facade = stage.querySelector('.video-embed-facade');
        if (facade) {
            facade.remove();
        }

        stage.appendChild(iframe);
        iframe.focus();
    }

    function initVideoEmbedPlayers() {
        const stages = document.querySelectorAll('[data-video-embed-facade]');
        stages.forEach(function (stage) {
            const facade = stage.querySelector('.video-embed-facade');
            if (!facade || facade.dataset.bound === 'true') {
                return;
            }
            facade.dataset.bound = 'true';

            facade.addEventListener('click', function (e) {
                e.preventDefault();
                activateVideoEmbed(stage);
            });

            facade.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
                    e.preventDefault();
                    activateVideoEmbed(stage);
                }
            });
        });
    }

    const ALLOWED_X_ORIGINS = [
        'https://platform.twitter.com',
        'https://syndication.twitter.com',
        'https://platform.x.com'
    ];

    // Auto-resize listener for X (Twitter) embedded tweet frames
    window.addEventListener('message', function (event) {
        if (!event.data || ALLOWED_X_ORIGINS.indexOf(event.origin) === -1) {
            return;
        }
        let data = event.data;

        if (typeof data === 'string' && data.startsWith('{') && data.includes('resize')) {
            try {
                data = JSON.parse(data);
            } catch (err) {
                return;
            }
        }

        const payload = (data && typeof data === 'object' && data['twttr.embed']) ? data['twttr.embed'] : data;
        if (payload && payload.method === 'twttr.private.resize' && Array.isArray(payload.params) && payload.params[0]) {
            const height = parseInt(payload.params[0].height, 10);
            if (height >= 180 && height <= 4000) {
                const iframes = document.querySelectorAll('.video-embed-stage--x iframe');
                iframes.forEach(function (frame) {
                    if (frame.contentWindow === event.source) {
                        frame.style.height = height + 'px';
                        frame.style.minHeight = height + 'px';
                        const parentStage = frame.closest('.video-embed-stage--x');
                        if (parentStage) {
                            parentStage.style.minHeight = height + 'px';
                        }
                    }
                });
            }
        }
    });

    // Theme Change Synchronizer
    const themeObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-bs-theme') {
                const newTheme = getActiveTheme();
                const xFrames = document.querySelectorAll('.video-embed-stage--x iframe.video-embed-mounted-frame');
                xFrames.forEach(function (frame) {
                    let s = frame.src;
                    if (s && s.includes('platform.twitter.com')) {
                        if (/theme=[a-z]+/i.test(s)) {
                            frame.src = s.replace(/theme=[a-z]+/i, 'theme=' + newTheme);
                        }
                    }
                });
            }
        });
    });

    if (document.documentElement) {
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVideoEmbedPlayers);
    } else {
        initVideoEmbedPlayers();
    }

    window.initVideoEmbedPlayers = initVideoEmbedPlayers;
})();
