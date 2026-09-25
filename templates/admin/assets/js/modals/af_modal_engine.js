/**
 * Reusable HTML5 AJAX Modal Navigation Stack Engine
 * Lightweight SPA modal manager for Admin Panel
 */
(function() {
    window.AFModalEngine = {
        stack: [],
        historyInitialized: false,
        loadedModules: {},
        skipHistoryModals: new Set(),

        moduleMap: {
            'newsletter-sheet': 'modal_newsletter',
            'redirect-sheet': 'modal_redirects',
            'site-status-modal': 'modal_site_status',
            'theme-management-modal': 'modal_theme_logo',
            'logo-icon-management-modal': 'modal_theme_logo',
            'site-settings-menu-modal': 'modal_theme_logo',
            'stats-modal': 'modal_theme_logo',
            'modal-dynamic-dialog': 'modal_user_profile'
        },

        ensureModuleForModal: function(modalId, callback) {
            const idStr = typeof modalId === 'string' ? modalId : (modalId ? modalId.id : '');
            const moduleName = this.moduleMap[idStr];
            if (moduleName) {
                this.loadModule(moduleName, callback);
            } else {
                if (typeof callback === 'function') callback();
            }
        },

        initHistory: function() {
            if (this.historyInitialized) return;
            this.historyInitialized = true;
            
            window.addEventListener('popstate', (e) => {
                if (Date.now() - (window.lastFileInputTime || 0) < 3000) {
                    return;
                }
                if (window.GalleryManager && window.GalleryManager.isUploading) {
                    return;
                }

                if (e.state && e.state.isAFModal) {
                    const targetDepth = e.state.stackDepth || 1;
                    const targetModalId = e.state.modalId;

                    while (this.stack.length > targetDepth) {
                        const poppedId = this.stack.pop();
                        if (!this.skipHistoryModals.has(poppedId)) {
                            this.closeSingle(poppedId);
                        }
                    }

                    if (targetModalId && !this.stack.includes(targetModalId)) {
                        this.stack.push(targetModalId);
                    } else if (targetModalId && this.stack[this.stack.length - 1] !== targetModalId) {
                        const idx = this.stack.indexOf(targetModalId);
                        if (idx !== -1) {
                            this.stack = this.stack.slice(0, idx + 1);
                        }
                    }

                    document.querySelectorAll('dialog.active, .bottom-sheet.active, .stats-modal-dialog.active').forEach(d => {
                        const dId = d.id;
                        if (dId !== targetModalId && !this.skipHistoryModals.has(dId)) {
                            this.closeSingle(dId || d);
                        }
                    });

                    if (this.stack.length > 0) {
                        const topModalId = this.stack[this.stack.length - 1];
                        this.showSingle(topModalId);
                    }
                } else if (this.stack.length > 0) {
                    const topModalId = this.stack[this.stack.length - 1];
                    if (this.skipHistoryModals.has(topModalId)) {
                        return;
                    }
                    this.closeAllInternal(true);
                }
            });
        },

        loadModule: function(moduleName, callback) {
            if (this.loadedModules[moduleName]) {
                if (typeof callback === 'function') callback();
                return;
            }
            const script = document.createElement('script');
            const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '/');
            const siteVersion = (window.siteData && window.siteData.version) ? window.siteData.version : Date.now();
            script.src = siteUrl + 'templates/admin/assets/js/modals/' + moduleName + '.js?v=' + siteVersion;
            script.onload = () => {
                this.loadedModules[moduleName] = true;
                if (typeof callback === 'function') callback();
            };
            script.onerror = (err) => {
                console.error('Failed to load modal module script:', moduleName, err);
                if (typeof callback === 'function') callback(err);
            };
            document.head.appendChild(script);
        },

        push: function(modalOrUrl, options = {}) {
            this.initHistory();
            const self = this;
            
            if (typeof modalOrUrl === 'string' && (modalOrUrl.includes('/') || modalOrUrl.startsWith('mod_') || options.isUrl)) {
                const targetModalId = options.modalId || 'modal-dynamic-dialog';
                self.pushModalElement(targetModalId, modalOrUrl, options);
                return this.loadAjax(modalOrUrl, options.containerId || 'modal-dynamic-body', (err, html) => {
                    if (err) {
                        console.error('Failed to load modal AJAX content:', err);
                    }
                });
            }

            return this.pushModalElement(modalOrUrl, null, options);
        },

        pushModalElement: function(modalId, url = null, options = {}) {
            const self = this;
            const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
            if (!modal) return;

            const idStr = typeof modalId === 'string' ? modalId : modal.id;
            const overlayId = options.overlayId || (typeof modalId === 'string' ? modalId + '-overlay' : null);
            const overlay = overlayId ? document.getElementById(overlayId) : (document.getElementById(idStr.replace(/-sheet$/, '') + '-overlay') || (idStr === 'user-menu-sheet' ? document.getElementById('user-menu-overlay') : null));

            if (self.stack.length > 0) {
                const currentTopId = self.stack[self.stack.length - 1];
                if (currentTopId !== idStr) {
                    self.closeSingle(currentTopId);
                }
            }

            const executeOpen = () => {
                if (modal.tagName && modal.tagName.toLowerCase() === 'dialog') {
                    if (!modal.open) {
                        modal.showModal();
                        modal.classList.add('active');
                    }
                    if (!modal.dataset.cancelBound) {
                        modal.dataset.cancelBound = 'true';
                        modal.addEventListener('cancel', (evt) => {
                            evt.preventDefault();
                            if (window.GalleryManager && window.GalleryManager.isUploading) {
                                return;
                            }
                            if (Date.now() - (window.lastFileInputTime || 0) < 3000) {
                                return;
                            }
                            if (evt.target === modal) {
                                self.back();
                            }
                        });
                    }
                    if (!modal.dataset.clickBound) {
                        modal.dataset.clickBound = 'true';
                        modal.addEventListener('click', (evt) => {
                            if (evt.target === modal) {
                                if (window.GalleryManager && window.GalleryManager.isUploading) {
                                    return;
                                }
                                if (Date.now() - (window.lastFileInputTime || 0) < 3000) {
                                    return;
                                }
                                if (evt.clientX === 0 && evt.clientY === 0) {
                                    return;
                                }
                                const rect = modal.getBoundingClientRect();
                                const isInDialog = (
                                    rect.top <= evt.clientY &&
                                    evt.clientY <= rect.top + rect.height &&
                                    rect.left <= evt.clientX &&
                                    evt.clientX <= rect.left + rect.width
                                );
                                if (!isInDialog) {
                                    self.back();
                                }
                            }
                        });
                    }
                } else {
                    modal.classList.add('active');
                }
                if (overlay) overlay.classList.add('active');

                if (navigator.vibrate) navigator.vibrate(10);

                const existingIdx = self.stack.indexOf(idStr);
                if (existingIdx !== -1) {
                    self.stack = self.stack.slice(0, existingIdx + 1);
                } else {
                    self.stack.push(idStr);
                }

                if (options && options.skipHistory) {
                    self.skipHistoryModals.add(idStr);
                } else {
                    self.skipHistoryModals.delete(idStr);
                }

                if (!options.skipHistory && (url || idStr)) {
                    const pageUrl = url || window.location.pathname + '#' + idStr;
                    const currentState = window.history.state;
                    if (currentState && currentState.isAFModal && currentState.modalId === idStr) {
                        history.replaceState({ isAFModal: true, modalId: idStr, stackDepth: self.stack.length }, '', pageUrl);
                    } else {
                        history.pushState({ isAFModal: true, modalId: idStr, stackDepth: self.stack.length }, '', pageUrl);
                    }
                }
            };

            executeOpen();
            self.ensureModuleForModal(modalId);
        },

        prefetchAllModules: function() {
            // Modules are loaded on-demand via ensureModuleForModal
        },

        open: function(modalId, overlayId = null, parentModalId = null) {
            if (parentModalId) {
                this.push(parentModalId, { skipHistory: true });
            }
            this.push(modalId, { overlayId: overlayId });
        },

        pop: function() {
            this.back();
        },

        back: function() {
            if (this.stack.length > 0) {
                if (window.history.state && window.history.state.isAFModal && window.history.length > 1) {
                    history.back();
                } else {
                    const executePop = () => {
                        this.popSingleInternal(false);
                        if (this.stack.length > 0) {
                            const prevModalId = this.stack[this.stack.length - 1];
                            this.showSingle(prevModalId);
                        }
                    };

                    executePop();
                }
            } else {
                this.closeAll();
            }
        },

        popSingleInternal: function(isPopState = false) {
            if (this.stack.length === 0) return;
            const modalId = this.stack.pop();
            this.closeSingle(modalId);
        },

        closeSingle: function(modalId) {
            if (!modalId) return;
            const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
            if (modal) {
                if (modal.tagName && modal.tagName.toLowerCase() === 'dialog' && modal.open) {
                    modal.close();
                }
                modal.classList.remove('active');
                const idStr = typeof modalId === 'string' ? modalId : modal.id;
                if (idStr) {
                    this.skipHistoryModals.delete(idStr);
                }
                const overlay = document.getElementById(idStr + '-overlay') || 
                                document.getElementById(idStr + 'Overlay') ||
                                document.getElementById(idStr.replace(/-sheet$/, '') + '-overlay') ||
                                (idStr === 'user-menu-sheet' ? document.getElementById('user-menu-overlay') : null);
                if (overlay) overlay.classList.remove('active');
                if (idStr === 'user-menu-sheet' && typeof showUserMenuMain === 'function') {
                    setTimeout(showUserMenuMain, 300);
                }
            }
        },

        showSingle: function(modalId) {
            if (!modalId) return;
            const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
            if (modal) {
                if (modal.tagName && modal.tagName.toLowerCase() === 'dialog') {
                    if (!modal.open) modal.showModal();
                }
                modal.classList.add('active');
                const idStr = typeof modalId === 'string' ? modalId : modal.id;
                const overlay = document.getElementById(idStr + '-overlay') || 
                                document.getElementById(idStr + 'Overlay') ||
                                document.getElementById(idStr.replace(/-sheet$/, '') + '-overlay') ||
                                (idStr === 'user-menu-sheet' ? document.getElementById('user-menu-overlay') : null);
                if (overlay) overlay.classList.add('active');
            }
        },

        closeAll: function() {
            this.closeAllInternal(false);
        },

        closeAllInternal: function(isPopState = false) {
            while (this.stack.length > 0) {
                const modalId = this.stack.pop();
                this.closeSingle(modalId);
            }
            document.querySelectorAll('dialog[open]').forEach(d => {
                d.close();
                d.classList.remove('active');
            });
            document.querySelectorAll('.stats-modal-overlay.active, .overlay.active').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.stats-modal-dialog.active, .bottom-sheet.active').forEach(el => {
                el.classList.remove('active');
            });

            if (!isPopState && window.history.state && window.history.state.isAFModal) {
                history.back();
            }
        },

        loadAjax: function(url, containerId, callback) {
            const container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
            if (!container) return Promise.reject('Container not found');

            let targetUrl = url;
            if (typeof targetUrl === 'string' && targetUrl.startsWith('http')) {
                try {
                    const parsed = new URL(targetUrl, window.location.href);
                    targetUrl = parsed.pathname + parsed.search + parsed.hash;
                } catch (e) {}
            }

            const ajaxUrl = (typeof targetUrl === 'string' && !targetUrl.includes('ajax=1')) ? (targetUrl + (targetUrl.includes('?') ? '&ajax=1' : '?ajax=1')) : targetUrl;
            
            const isAlreadyLoaded = container.dataset.loadedUrl === ajaxUrl && container.children.length > 0;
            if (!isAlreadyLoaded && (!container.children.length || container.innerHTML.trim() === '')) {
                const loadingHtml = '<div style="text-align: center; padding: 40px 20px; color: var(--md-sys-color-on-surface-variant, #49454F);"><i class="bi bi-arrow-repeat" style="font-size: 28px; display: inline-block; animation: spin 1s infinite linear;"></i><p style="margin-top: 12px; font-weight: 600;">Loading...</p></div>';
                container.innerHTML = loadingHtml;
            }

            const executeScripts = async (targetContainer) => {
                if (!targetContainer) return;
                const scripts = Array.from(targetContainer.querySelectorAll('script'));
                for (const oldScript of scripts) {
                    await new Promise((resolve) => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.async = false;
                        if (oldScript.src) {
                            newScript.onload = () => resolve();
                            newScript.onerror = () => resolve();
                            newScript.src = oldScript.src;
                            if (oldScript.parentNode) {
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            } else {
                                targetContainer.appendChild(newScript);
                            }
                        } else {
                            newScript.textContent = oldScript.textContent;
                            if (oldScript.parentNode) {
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            } else {
                                targetContainer.appendChild(newScript);
                            }
                            resolve();
                        }
                    });
                }
            };

            const updateContainer = async (content, runScripts = false) => {
                container.innerHTML = content;
                container.dataset.loadedUrl = ajaxUrl;
                if (runScripts) await executeScripts(container);
            };

            return fetch(ajaxUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(res => {
                if (res.status === 401) {
                    return res.json().then(data => {
                        if (data && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        throw new Error('Unauthorized');
                    }).catch(() => {
                        throw new Error('HTTP ' + res.status);
                    });
                }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(html => {
                if (!html || !html.trim()) {
                    throw new Error('Empty HTML response');
                }

                if (html.trim().startsWith('{')) {
                    try {
                        const jsonRes = JSON.parse(html);
                        if (jsonRes.redirect) {
                            window.location.href = jsonRes.redirect;
                            return html;
                        }
                        if (jsonRes.status === 'error' || jsonRes.error) {
                            const errMessage = jsonRes.message || 'Permission denied or request failed.';
                            const errorHtml = '<div style="text-align: center; padding: 40px 20px; color: var(--md-sys-color-error, #b00020);"><i class="bi bi-shield-lock-fill" style="font-size: 32px;"></i><p style="margin-top: 12px; font-weight: 600;">' + errMessage + '</p><button type="button" class="m3-btn-outlined" onclick="AFModalEngine.back()" style="margin-top: 16px; min-height: 40px; border-radius: 12px; padding: 0 16px; cursor: pointer;">Go Back</button></div>';
                            updateContainer(errorHtml);
                            self.showToast(errMessage, 'error');
                            if (typeof callback === 'function') callback(new Error(errMessage));
                            return html;
                        }
                    } catch (e) {}
                }

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;

                const metaContainer = tempDiv.querySelector('[data-modal-title]');
                const dynamicTitle = document.getElementById('modal-dynamic-title');

                if (metaContainer && dynamicTitle) {
                    const titleText = metaContainer.getAttribute('data-modal-title');
                    const iconClass = metaContainer.getAttribute('data-modal-icon') || 'bi-gear';
                    dynamicTitle.innerHTML = `<i class="bi ${iconClass}"></i><h3>${titleText}</h3>`;
                }

                // Strip any duplicate headers (inner header bars, top app bars)
                const redundantHeaders = tempDiv.querySelectorAll('.top-app-bar, .modal-header-bar');
                redundantHeaders.forEach(hdr => {
                    if (!metaContainer && dynamicTitle) {
                        const titleWrapper = hdr.querySelector('.modal-title-wrapper') || hdr.querySelector('.headline');
                        if (titleWrapper) {
                            dynamicTitle.innerHTML = titleWrapper.innerHTML;
                        }
                    }
                    hdr.remove();
                });

                updateContainer(tempDiv.innerHTML, true);
                if (typeof callback === 'function') callback(null, html);
                return html;
            })
            .catch(err => {
                console.error('AJAX modal load error:', err);
                const errMsg = err && err.message ? err.message : String(err);
                const errorHtml = '<div style="text-align: center; padding: 40px 20px; color: var(--md-sys-color-error, #b00020);"><i class="bi bi-exclamation-triangle-fill" style="font-size: 32px;"></i><p style="margin-top: 12px; font-weight: 600;">Failed to load content</p><div style="font-size: 11px; opacity: 0.75; margin-top: 4px;">' + errMsg + '</div><button type="button" class="m3-btn-outlined" onclick="AFModalEngine.back()" style="margin-top: 16px; min-height: 40px; border-radius: 12px; padding: 0 16px;">Go Back</button></div>';
                updateContainer(errorHtml);
                if (typeof callback === 'function') callback(err);
            });
        },

        showToast: function(message, type = 'success', timer = 2500) {
            const activeModal = document.querySelector('dialog[open]');
            if (activeModal) {
                const existing = activeModal.querySelector('.m3-modal-toast');
                if (existing) existing.remove();

                const toast = document.createElement('div');
                toast.className = 'm3-modal-toast ' + (type === 'error' ? 'toast-error' : 'toast-success');

                const isError = type === 'error';
                const bgGradient = isError
                    ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
                    : 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                const shadowColor = isError
                    ? 'rgba(239, 68, 68, 0.45)'
                    : 'rgba(16, 185, 129, 0.45)';

                toast.style.cssText = `position: absolute !important; top: 72px !important; left: 50% !important; transform: translate(-50%, 0) !important; width: calc(100% - 32px) !important; max-width: 340px !important; padding: 14px 20px !important; border-radius: 20px !important; font-size: 15px !important; font-weight: 700 !important; line-height: 1.4 !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 12px !important; text-align: center !important; box-sizing: border-box !important; z-index: 99999 !important; pointer-events: none !important; background: ${bgGradient} !important; color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35) !important; box-shadow: 0 12px 32px -4px ${shadowColor}, 0 4px 12px rgba(0, 0, 0, 0.2) !important; backdrop-filter: blur(16px) !important; -webkit-backdrop-filter: blur(16px) !important; animation: toastSlideDown 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important;`;

                const icon = isError ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
                toast.innerHTML = `<i class="bi ${icon}" style="font-size: 24px !important; color: #ffffff !important; flex-shrink: 0 !important; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)) !important;"></i><span style="font-size: 15px !important; font-weight: 700 !important; color: #ffffff !important; font-family: system-ui, -apple-system, sans-serif !important;">${message}</span>`;
                activeModal.appendChild(toast);

                setTimeout(() => {
                    toast.style.animation = 'toastFadeOut 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
                    setTimeout(() => toast.remove(), 300);
                }, timer);
                return;
            }

            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    icon: type,
                    title: message,
                    position: 'center',
                    timer: timer,
                    showConfirmButton: false,
                    width: '300px',
                    customClass: {
                        container: 'af-toast-container',
                        popup: 'af-toast-popup m3-swal-popup'
                    }
                });
            }
        }
    };

    window.showAFToast = function(message, type = 'success', timer = 2500) {
        if (window.AFModalEngine && window.AFModalEngine.showToast) {
            return window.AFModalEngine.showToast(message, type, timer);
        }
        const activeModal = document.querySelector('dialog[open]');
        if (activeModal) {
            const existing = activeModal.querySelector('.m3-modal-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = 'm3-modal-toast ' + (type === 'error' ? 'toast-error' : 'toast-success');

            const isError = type === 'error';
            const bgGradient = isError
                ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
                : 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            const shadowColor = isError
                ? 'rgba(239, 68, 68, 0.45)'
                : 'rgba(16, 185, 129, 0.45)';

            toast.style.cssText = `position: absolute !important; top: 72px !important; left: 50% !important; transform: translate(-50%, 0) !important; width: calc(100% - 32px) !important; max-width: 340px !important; padding: 14px 20px !important; border-radius: 20px !important; font-size: 15px !important; font-weight: 700 !important; line-height: 1.4 !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 12px !important; text-align: center !important; box-sizing: border-box !important; z-index: 99999 !important; pointer-events: none !important; background: ${bgGradient} !important; color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35) !important; box-shadow: 0 12px 32px -4px ${shadowColor}, 0 4px 12px rgba(0, 0, 0, 0.2) !important; backdrop-filter: blur(16px) !important; -webkit-backdrop-filter: blur(16px) !important; animation: toastSlideDown 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important;`;

            const icon = isError ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
            toast.innerHTML = `<i class="bi ${icon}" style="font-size: 24px !important; color: #ffffff !important; flex-shrink: 0 !important; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)) !important;"></i><span style="font-size: 15px !important; font-weight: 700 !important; color: #ffffff !important; font-family: system-ui, -apple-system, sans-serif !important;">${message}</span>`;
            activeModal.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'toastFadeOut 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
                setTimeout(() => toast.remove(), 300);
            }, timer);
            return;
        }

        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: type,
                title: message,
                position: 'center',
                timer: timer,
                showConfirmButton: false,
                width: '300px',
                customClass: {
                    container: 'af-toast-container',
                    popup: 'af-toast-popup m3-swal-popup'
                }
            });
        }
    };

    const initAFEngine = () => {
        if (window.AFModalEngine && window.AFModalEngine.prefetchAllModules) {
            window.AFModalEngine.prefetchAllModules();
        }

        const urlParams = new URLSearchParams(window.location.search);
        const targetModal = urlParams.get('open_modal');
        if (targetModal) {
            const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '');
            setTimeout(() => {
                if (targetModal === 'info') {
                    if (typeof openUserMenuSheet === 'function') openUserMenuSheet();
                    AFModalEngine.push(siteUrl + 'mod_user/info');
                } else if (targetModal === 'change_password') {
                    if (typeof openUserMenuSheet === 'function') openUserMenuSheet();
                    AFModalEngine.push(siteUrl + 'mod_user/change_password');
                } else if (targetModal === 'redirects' && typeof openRedirectModal === 'function') {
                    openRedirectModal();
                } else if (targetModal === 'newsletter' && typeof openNewsletterModal === 'function') {
                    openNewsletterModal();
                }
            }, 50);
        }
    };

    document.addEventListener('click', (e) => {
        if (e.target && (e.target.type === 'file' || (e.target.tagName === 'INPUT' && e.target.type === 'file'))) {
            window.lastFileInputTime = Date.now();
        }
    }, true);
    document.addEventListener('change', (e) => {
        if (e.target && (e.target.type === 'file' || (e.target.tagName === 'INPUT' && e.target.type === 'file'))) {
            window.lastFileInputTime = Date.now();
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAFEngine);
    } else {
        initAFEngine();
    }
})();
