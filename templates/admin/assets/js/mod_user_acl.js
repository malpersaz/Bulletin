/**
 * Access Control List (ACL) — Reactive State Management & Persistence Engine
 * Production Mobile-First ES6+ Engine for Amed Framework (AF/Business CMS)
 *
 * Strict Code Separation: 100% English code, identifiers, and comments.
 * Zero inline event handlers or scripts. Pure DOM event delegation.
 */

'use strict';

class AclManager {
    /**
     * Initialize ACL Manager and State Store
     */
    constructor() {
        this.container = document.getElementById('aclContainer') || document.querySelector('.acl-container');
        this.siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '/';
        this.toastTimeout = null;
        this.counterTemplate = '%s of %s enabled';

        // Reactive Client State Store
        this.state = {
            activeGroup: 1,
            searchQuery: '',
            permissions: {}, // Structure: { [groupId]: { [modId]: { [roleId]: boolean } } }
            expandedModules: new Set(),
            isSaving: false
        };

        this.init();
    }

    /**
     * Bootstrap the Application
     */
    init() {
        if (!this.container) return;

        this.hydrateState();
        this.bindEvents();
        this.syncAll();
    }

    /**
     * Hydrate In-Memory Permission Matrix from Server-Rendered JSON / DOM Data
     */
    hydrateState() {
        // 1. Discover all modules in the DOM and mark them expanded by default
        const moduleCards = document.querySelectorAll('.acl-module-card, .module-card');
        moduleCards.forEach(card => {
            const rawModId = card.dataset.moduleId;
            if (rawModId !== undefined) {
                const modId = parseInt(rawModId, 10);
                this.state.expandedModules.add(isNaN(modId) ? rawModId : modId);
            }
        });

        // 2. Identify initial active group from DOM tabs
        const activeTab = document.querySelector('.acl-group-tab.is-active, .group-tab.is-active, .acl-group-tab, .group-tab');
        if (activeTab && activeTab.dataset.groupId) {
            const parsedGid = parseInt(activeTab.dataset.groupId, 10);
            this.state.activeGroup = isNaN(parsedGid) ? activeTab.dataset.groupId : parsedGid;
        }

        // 3. Hydrate permission dataset from #aclInitialData
        const initDataEl = document.getElementById('aclInitialData') || document.getElementById('acl-init-data');
        if (initDataEl && initDataEl.dataset) {
            // Case A: Structured test/fixture data (data-initial)
            if (initDataEl.dataset.initial) {
                try {
                    const parsed = JSON.parse(initDataEl.dataset.initial);
                    if (parsed.activeGroup) {
                        this.state.activeGroup = parsed.activeGroup;
                    }
                    if (parsed.permissions) {
                        this.state.permissions = parsed.permissions;
                    }
                } catch (err) {
                    console.error('Failed to parse data-initial JSON payload:', err);
                }
            }

            // Case B: Production CMS permissions list (data-permissions)
            if (initDataEl.dataset.permissions) {
                try {
                    const permList = JSON.parse(initDataEl.dataset.permissions);
                    if (Array.isArray(permList)) {
                        permList.forEach(item => {
                            const gid = parseInt(item.group_id, 10);
                            const mid = parseInt(item.mod_id, 10);
                            const rid = parseInt(item.role_id, 10);

                            if (!this.state.permissions[gid]) {
                                this.state.permissions[gid] = {};
                            }
                            if (!this.state.permissions[gid][mid]) {
                                this.state.permissions[gid][mid] = {};
                            }
                            this.state.permissions[gid][mid][rid] = true;
                        });
                    }
                } catch (err) {
                    console.error('Failed to parse data-permissions JSON payload:', err);
                }
            }

            // Case C: Localized permission counter template (data-counter-template)
            if (initDataEl.dataset.counterTemplate) {
                this.counterTemplate = initDataEl.dataset.counterTemplate;
            }
        }
    }

    /**
     * Bind DOM Event Listeners Using Pure Event Delegation
     */
    bindEvents() {
        // Pure Click Delegation on Document
        document.addEventListener('click', (e) => {
            // 1. User Group Navigation Tab Click
            const tab = e.target.closest ? e.target.closest('.acl-group-tab, .group-tab, [role="tab"]') : null;
            if (tab && tab.dataset && tab.dataset.groupId) {
                if (e.preventDefault) e.preventDefault();
                const gid = parseInt(tab.dataset.groupId, 10);
                this.switchGroup(isNaN(gid) ? tab.dataset.groupId : gid);
                return;
            }

            // 2. Module Accordion Header Click
            const header = e.target.closest ? e.target.closest('.acl-module-header, .module-header') : null;
            if (header) {
                const card = header.closest('.acl-module-card, .module-card');
                if (card && card.dataset && card.dataset.moduleId) {
                    if (e.preventDefault) e.preventDefault();
                    const modId = parseInt(card.dataset.moduleId, 10);
                    this.toggleAccordion(isNaN(modId) ? card.dataset.moduleId : modId);
                    return;
                }
            }

            // 3. Single Permission Switch Click
            const sw = e.target.closest ? e.target.closest('.acl-permission-switch, .permission-switch, [role="switch"]') : null;
            if (sw && sw.dataset && sw.dataset.modId && sw.dataset.roleId) {
                if (e.preventDefault) e.preventDefault();
                const modId = parseInt(sw.dataset.modId, 10);
                const roleId = parseInt(sw.dataset.roleId, 10);
                this.togglePermission(
                    isNaN(modId) ? sw.dataset.modId : modId,
                    isNaN(roleId) ? sw.dataset.roleId : roleId
                );
                return;
            }

            // 4. Batch Grant All Action Click
            const grantBtn = e.target.closest ? e.target.closest('[data-action="grant-all"]') : null;
            if (grantBtn && grantBtn.dataset && grantBtn.dataset.moduleId) {
                if (e.preventDefault) e.preventDefault();
                const modId = parseInt(grantBtn.dataset.moduleId, 10);
                this.grantAll(isNaN(modId) ? grantBtn.dataset.moduleId : modId);
                return;
            }

            // 5. Batch Revoke All Action Click
            const revokeBtn = e.target.closest ? e.target.closest('[data-action="revoke-all"]') : null;
            if (revokeBtn && revokeBtn.dataset && revokeBtn.dataset.moduleId) {
                if (e.preventDefault) e.preventDefault();
                const modId = parseInt(revokeBtn.dataset.moduleId, 10);
                this.revokeAll(isNaN(modId) ? revokeBtn.dataset.moduleId : modId);
                return;
            }

            // 6. Clear Search Query Click
            const clearBtn = e.target.closest ? e.target.closest('[data-action="clear-search"]') : null;
            if (clearBtn) {
                if (e.preventDefault) e.preventDefault();
                this.clearSearch();
                return;
            }

            // 7. Back Navigation Button Click
            const backBtn = e.target.closest ? e.target.closest('#btnAclBack') : null;
            if (backBtn) {
                if (e.preventDefault) e.preventDefault();
                this.navigateBack();
                return;
            }
        });

        // Real-Time Search Input Event
        const searchInput = document.getElementById('aclSearchInput') || 
                            document.getElementById('acl-search-input') || 
                            document.querySelector('.acl-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }

        // Keyboard Accessibility (Space and Enter Keys)
        document.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.key === 'Enter' || e.key === 'Spacebar') {
                const target = e.target;
                if (!target) return;

                // A. Switch Element Keyboard Toggle
                if (target.getAttribute && target.getAttribute('role') === 'switch') {
                    if (e.preventDefault) e.preventDefault();
                    target.click();
                    return;
                }

                // B. User Group Tab Keyboard Select
                if (target.classList && (target.classList.contains('acl-group-tab') || target.classList.contains('group-tab'))) {
                    if (e.preventDefault) e.preventDefault();
                    target.click();
                    return;
                }

                // C. Module Accordion Header Keyboard Toggle
                if (target.classList && (target.classList.contains('acl-module-header') || target.classList.contains('module-header'))) {
                    if (e.preventDefault) e.preventDefault();
                    target.click();
                    return;
                }
            }
        });
    }

    /**
     * Switch Active User Group
     */
    switchGroup(groupId) {
        this.state.activeGroup = groupId;

        // 1. Update Group Navigation Tabs DOM and Accessibility States
        const tabs = document.querySelectorAll('.acl-group-tab, .group-tab, [role="tab"]');
        tabs.forEach(tab => {
            const rawGid = tab.dataset.groupId;
            const isTarget = String(rawGid) === String(groupId);

            if (isTarget) {
                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
                tab.setAttribute('tabindex', '0');
                if (typeof tab.scrollIntoView === 'function') {
                    tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                }
            } else {
                tab.classList.remove('is-active');
                tab.setAttribute('aria-selected', 'false');
                tab.setAttribute('tabindex', '-1');
            }
        });

        // 2. Synchronize Switches for the Newly Active Group
        this.syncAllSwitches();

        // 3. Update Module Summary Badges and Global Metrics
        this.updateAllModuleCounters();
        this.updateGlobalMetrics();

        // 4. Reapply Active Search Filter If Query Is Present
        if (this.state.searchQuery) {
            this.handleSearch(this.state.searchQuery);
        }
    }

    /**
     * Synchronize All Permission Switches with State for Current Active Group
     */
    syncAllSwitches() {
        const activeGroup = this.state.activeGroup;
        const groupPerms = this.state.permissions[activeGroup] || {};

        const switches = document.querySelectorAll('.acl-permission-switch, .permission-switch, [role="switch"]');
        switches.forEach(sw => {
            const rawModId = sw.dataset.modId;
            const rawRoleId = sw.dataset.roleId;
            const modId = parseInt(rawModId, 10);
            const roleId = parseInt(rawRoleId, 10);

            const mKey = isNaN(modId) ? rawModId : modId;
            const rKey = isNaN(roleId) ? rawRoleId : roleId;

            const isGranted = !!(groupPerms[mKey] && groupPerms[mKey][rKey]);

            if (isGranted) {
                sw.classList.add('is-checked');
                sw.setAttribute('aria-checked', 'true');
            } else {
                sw.classList.remove('is-checked');
                sw.setAttribute('aria-checked', 'false');
            }
        });
    }

    /**
     * Toggle Collapsible Module Accordion
     */
    toggleAccordion(modId) {
        const card = document.querySelector(
            `.acl-module-card[data-module-id="${modId}"], .module-card[data-module-id="${modId}"]`
        );
        if (!card) return;

        const header = card.querySelector('.acl-module-header, .module-header');
        const isExpanded = this.state.expandedModules.has(modId);

        if (isExpanded) {
            this.state.expandedModules.delete(modId);
            card.classList.remove('is-expanded');
            card.classList.add('is-collapsed');
            if (header) header.setAttribute('aria-expanded', 'false');
        } else {
            this.state.expandedModules.add(modId);
            card.classList.add('is-expanded');
            card.classList.remove('is-collapsed');
            if (header) header.setAttribute('aria-expanded', 'true');
        }
    }

    /**
     * Toggle a Single Permission with Optimistic Update and Rollback
     */
    async togglePermission(modId, roleId) {
        const activeGroup = this.state.activeGroup;

        if (!this.state.permissions[activeGroup]) {
            this.state.permissions[activeGroup] = {};
        }
        if (!this.state.permissions[activeGroup][modId]) {
            this.state.permissions[activeGroup][modId] = {};
        }

        const prevState = !!this.state.permissions[activeGroup][modId][roleId];
        const nextState = !prevState;

        // 1. Optimistic Client State Update
        this.state.permissions[activeGroup][modId][roleId] = nextState;

        const sw = document.querySelector(
            `[data-mod-id="${modId}"][data-role-id="${roleId}"]`
        );
        if (sw) {
            if (nextState) {
                sw.classList.add('is-checked');
                sw.setAttribute('aria-checked', 'true');
            } else {
                sw.classList.remove('is-checked');
                sw.setAttribute('aria-checked', 'false');
            }
            sw.disabled = true;
        }

        this.updateGlobalMetrics();
        this.updateModuleCounter(modId);
        this.showProgress(true);

        // 2. Build Asynchronous AJAX Persistence Payload
        const formData = new FormData();
        formData.append('group_id', String(activeGroup));
        formData.append('mod_id', String(modId));
        formData.append('role_id', String(roleId));
        formData.append('state', nextState ? '1' : '0');
        formData.append('ajax_request', 'true');

        try {
            const response = await fetch(`${this.siteUrl}mod_user/toggle_permission`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();
            this.showProgress(false);
            if (sw) sw.disabled = false;

            if (response.ok && data.success) {
                this.showToast(data.message || 'Permission updated successfully');
                return true;
            } else {
                // Network or validation failure: Rollback
                this.rollbackPermission(modId, roleId, prevState);
                this.showToast(data && data.message ? data.message : 'Failed to update permission');
                return false;
            }
        } catch (err) {
            this.showProgress(false);
            if (sw) sw.disabled = false;
            this.rollbackPermission(modId, roleId, prevState);
            this.showToast('Network error while updating permission');
            return false;
        }
    }

    /**
     * Rollback Single Permission State on Persistence Failure
     */
    rollbackPermission(modId, roleId, prevState) {
        const activeGroup = this.state.activeGroup;
        if (this.state.permissions[activeGroup] && this.state.permissions[activeGroup][modId]) {
            this.state.permissions[activeGroup][modId][roleId] = prevState;
        }

        const sw = document.querySelector(
            `[data-mod-id="${modId}"][data-role-id="${roleId}"]`
        );
        if (sw) {
            if (prevState) {
                sw.classList.add('is-checked');
                sw.setAttribute('aria-checked', 'true');
            } else {
                sw.classList.remove('is-checked');
                sw.setAttribute('aria-checked', 'false');
            }
        }

        this.updateGlobalMetrics();
        this.updateModuleCounter(modId);
    }

    /**
     * Batch Action: Grant All Permissions for a Specific Module
     */
    async grantAll(modId) {
        return this.batchUpdate(modId, true);
    }

    /**
     * Batch Action: Revoke All Permissions for a Specific Module
     */
    async revokeAll(modId) {
        return this.batchUpdate(modId, false);
    }

    /**
     * Common Engine for Batch Grant / Revoke Operations
     */
    async batchUpdate(modId, targetState) {
        const activeGroup = this.state.activeGroup;

        if (!this.state.permissions[activeGroup]) {
            this.state.permissions[activeGroup] = {};
        }
        if (!this.state.permissions[activeGroup][modId]) {
            this.state.permissions[activeGroup][modId] = {};
        }

        const card = document.querySelector(
            `.acl-module-card[data-module-id="${modId}"], .module-card[data-module-id="${modId}"]`
        );
        if (!card) return false;

        const switches = card.querySelectorAll('.acl-permission-switch, .permission-switch, [role="switch"]');
        if (switches.length === 0) {
            // Edge case: Empty module with 0 roles
            return true;
        }

        const prevStates = [];
        const roleIds = [];

        // 1. Optimistic Update Across All Switches in Module
        switches.forEach(sw => {
            const rawRoleId = sw.dataset.roleId;
            const roleId = parseInt(rawRoleId, 10);
            const rKey = isNaN(roleId) ? rawRoleId : roleId;

            roleIds.push(rKey);
            prevStates.push({
                roleId: rKey,
                state: !!(this.state.permissions[activeGroup][modId] && this.state.permissions[activeGroup][modId][rKey])
            });

            this.state.permissions[activeGroup][modId][rKey] = targetState;

            if (targetState) {
                sw.classList.add('is-checked');
                sw.setAttribute('aria-checked', 'true');
            } else {
                sw.classList.remove('is-checked');
                sw.setAttribute('aria-checked', 'false');
            }
            sw.disabled = true;
        });

        this.updateGlobalMetrics();
        this.updateModuleCounter(modId);
        this.showProgress(true);

        // 2. Build Batch AJAX Payload with role_id[] Array
        const formData = new FormData();
        formData.append('group_id', String(activeGroup));
        formData.append('mod_id', String(modId));
        roleIds.forEach(id => {
            formData.append('role_id[]', String(id));
        });
        formData.append('state', targetState ? '1' : '0');
        formData.append('ajax_request', 'true');

        try {
            const response = await fetch(`${this.siteUrl}mod_user/toggle_permission`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();
            this.showProgress(false);
            switches.forEach(sw => { sw.disabled = false; });

            if (response.ok && data.success) {
                this.showToast(data.message || (targetState ? 'All permissions granted' : 'All permissions revoked'));
                return true;
            } else {
                // Rollback on Failure
                this.rollbackBatch(modId, prevStates, card);
                this.showToast(data && data.message ? data.message : 'Batch update failed');
                return false;
            }
        } catch (err) {
            this.showProgress(false);
            switches.forEach(sw => { sw.disabled = false; });
            this.rollbackBatch(modId, prevStates, card);
            this.showToast('Network error during batch update');
            return false;
        }
    }

    /**
     * Rollback Batch Module Permissions on Failure
     */
    rollbackBatch(modId, prevStates, card) {
        const activeGroup = this.state.activeGroup;

        prevStates.forEach(item => {
            if (this.state.permissions[activeGroup] && this.state.permissions[activeGroup][modId]) {
                this.state.permissions[activeGroup][modId][item.roleId] = item.state;
            }

            const sw = card.querySelector(`[data-role-id="${item.roleId}"]`);
            if (sw) {
                if (item.state) {
                    sw.classList.add('is-checked');
                    sw.setAttribute('aria-checked', 'true');
                } else {
                    sw.classList.remove('is-checked');
                    sw.setAttribute('aria-checked', 'false');
                }
            }
        });

        this.updateGlobalMetrics();
        this.updateModuleCounter(modId);
    }

    /**
     * Live Real-Time Search & Keyword Highlighting
     */
    handleSearch(query) {
        const cleanQuery = (query || '').trim().toLowerCase();
        this.state.searchQuery = cleanQuery;

        // 1. Toggle Clear Search Button Visibility
        const clearBtn = document.querySelector('[data-action="clear-search"], .acl-clear-search-btn, .clear-search-btn');
        if (clearBtn) {
            if (cleanQuery.length > 0) {
                clearBtn.classList.remove('is-hidden');
            } else {
                clearBtn.classList.add('is-hidden');
            }
        }

        const moduleCards = document.querySelectorAll('.acl-module-card, .module-card');
        const emptyState = document.getElementById('aclEmptyState') || 
                           document.getElementById('acl-empty-state');
        let totalVisibleCards = 0;

        moduleCards.forEach(card => {
            const rawModId = card.dataset.moduleId;
            const modId = parseInt(rawModId, 10);
            const mKey = isNaN(modId) ? rawModId : modId;

            const titleEl = card.querySelector('.module-title');
            const codeEl = card.querySelector('.module-code, .module-desc');
            const header = card.querySelector('.acl-module-header, .module-header');

            const titleMatched = this.applyHighlight(titleEl, cleanQuery);
            const codeMatched = this.applyHighlight(codeEl, cleanQuery);
            const moduleHeaderMatched = titleMatched || codeMatched;

            let visibleRowsInModule = 0;
            const rows = card.querySelectorAll('.acl-permission-row, .permission-row');

            rows.forEach(row => {
                const nameEl = row.querySelector('.permission-name');
                const descEl = row.querySelector('.permission-code, .permission-desc');

                const nameMatched = this.applyHighlight(nameEl, cleanQuery);
                const descMatched = this.applyHighlight(descEl, cleanQuery);
                const rowMatched = nameMatched || descMatched;

                if (!cleanQuery) {
                    row.classList.remove('is-hidden');
                    visibleRowsInModule++;
                } else if (moduleHeaderMatched || rowMatched) {
                    row.classList.remove('is-hidden');
                    visibleRowsInModule++;
                } else {
                    row.classList.add('is-hidden');
                }
            });

            if (!cleanQuery) {
                // Empty search restores all cards and user's accordion expansion state
                card.classList.remove('is-hidden');
                totalVisibleCards++;

                if (this.state.expandedModules.has(mKey)) {
                    card.classList.add('is-expanded');
                    card.classList.remove('is-collapsed');
                    if (header) header.setAttribute('aria-expanded', 'true');
                } else {
                    card.classList.remove('is-expanded');
                    card.classList.add('is-collapsed');
                    if (header) header.setAttribute('aria-expanded', 'false');
                }
            } else if (visibleRowsInModule > 0 || moduleHeaderMatched) {
                // Matching cards are shown and auto-expanded
                card.classList.remove('is-hidden');
                card.classList.add('is-expanded');
                card.classList.remove('is-collapsed');
                if (header) header.setAttribute('aria-expanded', 'true');
                totalVisibleCards++;
            } else {
                card.classList.add('is-hidden');
            }
        });

        // 2. Toggle Empty State Visibility
        if (emptyState) {
            if (cleanQuery.length > 0 && totalVisibleCards === 0) {
                emptyState.classList.remove('is-hidden');
            } else {
                emptyState.classList.add('is-hidden');
            }
        }
    }

    /**
     * Clear Search Query and Reset View
     */
    clearSearch() {
        const searchInput = document.getElementById('aclSearchInput') || 
                            document.getElementById('acl-search-input') || 
                            document.querySelector('.acl-search-input');
        if (searchInput) {
            searchInput.value = '';
            if (typeof searchInput.focus === 'function') {
                searchInput.focus();
            }
        }
        this.handleSearch('');
    }

    /**
     * Safely Escape Regular Expression Special Characters
     */
    escapeRegExp(string) {
        return String(string).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Highlight Matched Keyword Substrings While Preserving Clean Original Text
     */
    applyHighlight(element, query) {
        if (!element) return false;

        if (typeof element.dataset.origText === 'undefined') {
            element.dataset.origText = element.textContent.trim();
        }
        const originalText = element.dataset.origText;

        if (!query) {
            element.textContent = originalText;
            return false;
        }

        const escaped = this.escapeRegExp(query);
        try {
            const regex = new RegExp(`(${escaped})`, 'gi');
            if (regex.test(originalText)) {
                element.innerHTML = originalText.replace(regex, '<mark class="search-highlight">$1</mark>');
                return true;
            } else {
                element.textContent = originalText;
                return false;
            }
        } catch (err) {
            element.textContent = originalText;
            return false;
        }
    }

    /**
     * Recalculate and Synchronize Global Metrics Header
     */
    updateGlobalMetrics() {
        const activeGroup = this.state.activeGroup;
        const groupPerms = this.state.permissions[activeGroup] || {};
        let totalEnabled = 0;
        let totalAvailable = 0;

        const cards = document.querySelectorAll('.acl-module-card, .module-card');
        cards.forEach(card => {
            const rawModId = card.dataset.moduleId;
            const modId = parseInt(rawModId, 10);
            const mKey = isNaN(modId) ? rawModId : modId;

            const switches = card.querySelectorAll('.acl-permission-switch, .permission-switch, [role="switch"]');
            totalAvailable += switches.length;

            switches.forEach(sw => {
                const rawRoleId = sw.dataset.roleId;
                const roleId = parseInt(rawRoleId, 10);
                const rKey = isNaN(roleId) ? rawRoleId : roleId;

                if (groupPerms[mKey] && groupPerms[mKey][rKey]) {
                    totalEnabled++;
                }
            });
        });

        // 1. Update Enabled Count
        const enabledEl = document.getElementById('totalEnabledCount') || 
                          document.getElementById('total-enabled-count');
        if (enabledEl) {
            enabledEl.textContent = String(totalEnabled);
        }

        // 2. Update Available Count
        const availEl = document.getElementById('totalAvailableCount') || 
                        document.getElementById('group-badge-count');
        if (availEl) {
            availEl.textContent = String(totalAvailable);
        }

        // 3. Update Active Group Label
        const activeTab = document.querySelector(
            `.acl-group-tab[data-group-id="${activeGroup}"], .group-tab[data-group-id="${activeGroup}"]`
        );
        const labelEl = document.getElementById('activeGroupLabel') || 
                        document.getElementById('active-group-label');
        if (labelEl && activeTab) {
            const tabLabel = activeTab.querySelector('.tab-label') || activeTab;
            labelEl.textContent = activeTab.dataset.groupName || tabLabel.textContent.trim();
        }
    }

    /**
     * Recalculate Module Counter Badge ("X of Y enabled")
     */
    updateModuleCounter(modId) {
        const card = document.querySelector(
            `.acl-module-card[data-module-id="${modId}"], .module-card[data-module-id="${modId}"]`
        );
        if (!card) return;

        const activeGroup = this.state.activeGroup;
        const groupPerms = this.state.permissions[activeGroup] || {};
        const switches = card.querySelectorAll('.acl-permission-switch, .permission-switch, [role="switch"]');
        const total = switches.length;
        let enabled = 0;

        switches.forEach(sw => {
            const rawRoleId = sw.dataset.roleId;
            const roleId = parseInt(rawRoleId, 10);
            const rKey = isNaN(roleId) ? rawRoleId : roleId;

            if (groupPerms[modId] && groupPerms[modId][rKey]) {
                enabled++;
            }
        });

        const counter = card.querySelector('.acl-module-counter, .module-counter, [data-module-counter]');
        if (counter) {
            const template = this.counterTemplate || '%s of %s enabled';
            counter.textContent = template.replace('%s', String(enabled)).replace('%s', String(total));
            counter.classList.remove('all-enabled', 'none-enabled');

            if (total > 0 && enabled === total) {
                counter.classList.add('all-enabled');
            } else if (enabled === 0) {
                counter.classList.add('none-enabled');
            }
        }
    }

    /**
     * Recalculate Counters for All Modules
     */
    updateAllModuleCounters() {
        const cards = document.querySelectorAll('.acl-module-card, .module-card');
        cards.forEach(card => {
            const rawModId = card.dataset.moduleId;
            const modId = parseInt(rawModId, 10);
            const mKey = isNaN(modId) ? rawModId : modId;
            if (mKey !== undefined) {
                this.updateModuleCounter(mKey);
            }
        });
    }

    /**
     * Toggle Top Progress Bar Loading State
     */
    showProgress(visible) {
        this.state.isSaving = !!visible;
        const bar = document.getElementById('aclProgressBar') || 
                    document.getElementById('acl-progress-bar');
        if (bar) {
            if (visible) {
                bar.classList.add('is-saving');
                bar.setAttribute('aria-hidden', 'false');
            } else {
                bar.classList.remove('is-saving');
                bar.setAttribute('aria-hidden', 'true');
            }
        }
    }

    /**
     * Display Material Design 3 Floating Toast Notification
     */
    showToast(message) {
        const toast = document.getElementById('aclToast') || 
                      document.getElementById('acl-toast');
        const toastMsg = document.getElementById('aclToastMsg') || 
                         document.getElementById('toast-message');
        if (!toast) return;

        if (toastMsg) {
            toastMsg.textContent = message || 'Permissions updated';
        }

        toast.classList.add('is-visible', 'show');
        toast.setAttribute('aria-hidden', 'false');

        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }

        this.toastTimeout = setTimeout(() => {
            toast.classList.remove('is-visible', 'show');
            toast.setAttribute('aria-hidden', 'true');
        }, 1800);
    }

    /**
     * Navigate Back or Redirect to User Panel
     */
    navigateBack() {
        if (typeof window !== 'undefined' && window.history && window.history.length > 1) {
            window.history.back();
        } else if (typeof window !== 'undefined') {
            window.location.href = `${this.siteUrl}mod_user/panel`;
        }
    }

    /**
     * Synchronize All Visual States
     */
    syncAll() {
        this.syncAllSwitches();
        this.updateAllModuleCounters();
        this.updateGlobalMetrics();
    }
}

// Global Exposure and Safe Auto-Initialization
if (typeof window !== 'undefined') {
    window.AclManager = AclManager;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.aclManager = new AclManager();
        });
    } else {
        window.aclManager = new AclManager();
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AclManager };
}
