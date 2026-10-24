/**
 * Amed Framework - Agent Dialogue Controller (mod_user_agent.js)
 * Modern, event-driven dialogue interaction powered by Gemini 3.8 Flash & SSE Streaming
 */
document.addEventListener('DOMContentLoaded', function() {
    const stream = document.getElementById('agentDialogueStream');
    const form = document.getElementById('agentChatForm');
    const input = document.getElementById('agentPromptInput');
    const sendBtn = document.getElementById('agentSendBtn');
    const clearBtn = document.getElementById('agentClearBtn');
    const chatUrlInput = document.getElementById('agentChatUrl');
    const streamUrlInput = document.getElementById('agentStreamUrl');
    const csrfUrlInput = document.getElementById('agentCsrfUrl');
    const csrfInput = document.getElementById('agentChatCsrf');
    const getCsrfToken = () => (csrfInput ? csrfInput.value : '');
    const csrfUrl = csrfUrlInput ? csrfUrlInput.value : '';

    if (!stream || !form || !input || !sendBtn) {
        return;
    }

    const wrapper = document.getElementById('agentDialogueWrapper') || stream;
    const i18n = {
        confirmWarningTitle: (wrapper && wrapper.dataset.confirmWarningTitle) || 'Sensitive / Destructive Action:',
        confirmBtnYes: (wrapper && wrapper.dataset.confirmBtnYes) || 'Yes, execute',
        confirmBtnNo: (wrapper && wrapper.dataset.confirmBtnNo) || 'Cancel',
        timeoutError: (wrapper && wrapper.dataset.timeoutError) || 'Response timed out. Please try again.',
        networkError: (wrapper && wrapper.dataset.networkError) || 'Network connection error.',
        generalError: (wrapper && wrapper.dataset.generalError) || 'An error occurred while processing the response.',
        cancelledMsg: (wrapper && wrapper.dataset.cancelledMsg) || 'Action confirmation cancelled.',
        concurrentError: (wrapper && wrapper.dataset.concurrentError) || 'Another AI task is currently in progress. Please wait.',
        thinkingMsg: (wrapper && wrapper.dataset.thinkingMsg) || 'Gemini is thinking...'
    };

    const chatUrl = chatUrlInput ? chatUrlInput.value : window.location.href;
    const streamUrl = streamUrlInput ? streamUrlInput.value : '';
    let isSubmitting = false;

    // Set initial timestamp on welcome bubble
    const initialTimeEl = stream.querySelector('[data-timestamp="now"]');
    if (initialTimeEl) {
        initialTimeEl.textContent = formatCurrentTime();
    }

    function scrollToBottom() {
        window.scrollTo({
            top: document.documentElement.scrollHeight,
            behavior: 'smooth'
        });
    }

    function formatCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return hours + ':' + minutes;
    }

    function updateActiveModel(modelName) {
        if (!modelName || typeof modelName !== 'string') return;
        const statusLabel = document.querySelector('.agent-status-label');
        if (statusLabel) {
            statusLabel.textContent = modelName;
        }
    }

    function generateRequestId() {
        return 'req_' + Date.now().toString(36) + '_' + Math.random().toString(36).substring(2, 9);
    }

    function adjustTextareaHeight() {
        input.style.height = 'auto';
        const newHeight = Math.min(input.scrollHeight, 110);
        input.style.height = newHeight + 'px';
    }

    input.addEventListener('input', adjustTextareaHeight);

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMarkdown(rawText) {
        const safe = escapeHtml(rawText);
        const lines = safe.split('\n');
        let html = '';
        let inList = false;

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();

            if (!line) {
                if (inList) {
                    html += '</ul>';
                    inList = false;
                }
                continue;
            }

            line = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            line = line.replace(/`(.*?)`/g, '<code>$1</code>');

            if (line.startsWith('* ') || line.startsWith('- ')) {
                if (!inList) {
                    html += '<ul>';
                    inList = true;
                }
                html += '<li>' + line.substring(2) + '</li>';
            } else {
                if (inList) {
                    html += '</ul>';
                    inList = false;
                }
                html += '<p>' + line + '</p>';
            }
        }

        if (inList) {
            html += '</ul>';
        }

        return html;
    }

    function appendBubble(role, contentText, isError = false) {
        const row = document.createElement('div');
        row.className = 'agent-message-row ' + (role === 'user' ? 'agent-row-user' : 'agent-row-model');

        if (role === 'model') {
            const avatar = document.createElement('div');
            avatar.className = 'agent-avatar';
            avatar.innerHTML = '<i class="bi bi-robot"></i>';
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'agent-bubble ' + (role === 'user' ? 'agent-bubble-user' : 'agent-bubble-model');
        if (isError) {
            bubble.classList.add('agent-bubble-error');
        }

        const content = document.createElement('div');
        content.className = 'agent-message-content';

        if (role === 'user') {
            const p = document.createElement('p');
            p.textContent = contentText;
            content.appendChild(p);
        } else {
            content.innerHTML = formatMarkdown(contentText);
        }
        bubble.appendChild(content);

        const time = document.createElement('div');
        time.className = 'agent-message-time';
        time.textContent = formatCurrentTime();
        bubble.appendChild(time);

        row.appendChild(bubble);
        stream.appendChild(row);
        scrollToBottom();
        return row;
    }

    function showThinking() {
        const row = document.createElement('div');
        row.className = 'agent-message-row agent-row-model agent-thinking-row';
        row.id = 'agentThinkingIndicator';

        const avatar = document.createElement('div');
        avatar.className = 'agent-avatar';
        avatar.innerHTML = '<i class="bi bi-robot"></i>';
        row.appendChild(avatar);

        const bubble = document.createElement('div');
        bubble.className = 'agent-bubble agent-bubble-model';
        bubble.innerHTML = '<div class="agent-thinking-dots"><span></span><span></span><span></span></div><div class="agent-progress-chips" id="agentProgressChips"></div>';

        row.appendChild(bubble);
        stream.appendChild(row);
        scrollToBottom();
        return row;
    }

    function hideThinking() {
        const thinkingRow = document.getElementById('agentThinkingIndicator');
        if (thinkingRow) {
            thinkingRow.remove();
        }
    }

    function updateProgressStatus(statusText) {
        const chipsContainer = document.getElementById('agentProgressChips');
        if (!chipsContainer || !statusText) return;
        let statusChip = chipsContainer.querySelector('.agent-chip-status');
        if (!statusChip) {
            statusChip = document.createElement('span');
            statusChip.className = 'agent-chip agent-chip-status';
            statusChip.innerHTML = '<i class="bi bi-hourglass-split"></i><span class="agent-chip-label"></span>';
            chipsContainer.prepend(statusChip);
        }
        const label = statusChip.querySelector('.agent-chip-label');
        if (label) label.textContent = statusText;
        scrollToBottom();
    }

    function addToolChip(toolName) {
        const chipsContainer = document.getElementById('agentProgressChips');
        if (!chipsContainer || !toolName) return;
        const chip = document.createElement('span');
        chip.className = 'agent-chip agent-chip-tool';
        chip.dataset.tool = toolName;
        chip.innerHTML = '<i class="bi bi-gear"></i><span>' + escapeHtml(toolName) + '</span>';
        chipsContainer.appendChild(chip);
        scrollToBottom();
    }

    function completeToolChip(toolName, elapsedMs = null) {
        const chipsContainer = document.getElementById('agentProgressChips');
        if (!chipsContainer || !toolName) return;
        const chips = chipsContainer.querySelectorAll('.agent-chip-tool');
        for (let i = chips.length - 1; i >= 0; i--) {
            if (chips[i].dataset.tool === toolName && !chips[i].classList.contains('agent-chip-done')) {
                chips[i].classList.remove('agent-chip-tool');
                chips[i].classList.add('agent-chip-done');
                const timeSuffix = (elapsedMs !== null && elapsedMs !== undefined) ? ' (' + elapsedMs + 'ms)' : '';
                chips[i].innerHTML = '<i class="bi bi-check2"></i><span>' + escapeHtml(toolName + timeSuffix) + '</span>';
                break;
            }
        }
        scrollToBottom();
    }

    async function refreshCsrfToken() {
        if (!csrfUrl) {
            return null;
        }
        try {
            const resp = await fetch(csrfUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (resp.status === 401 || !resp.ok) {
                return null;
            }
            const data = await resp.json();
            if (data && data.success && typeof data.csrf === 'string' && data.csrf.length === 32) {
                if (csrfInput) {
                    csrfInput.value = data.csrf;
                }
                return data.csrf;
            }
            return null;
        } catch (e) {
            return null;
        }
    }

    async function postAgentJson(payload, signal = null, isRetry = false) {
        const token = getCsrfToken();
        const bodyData = Object.assign({}, payload, { csrf: token });

        const resp = await fetch(chatUrl, {
            method: 'POST',
            signal: signal,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': token
            },
            body: JSON.stringify(bodyData)
        });

        let result = null;
        try {
            result = await resp.json();
        } catch (err) {
            throw new Error('HTTP ' + resp.status + ' (' + resp.statusText + ')');
        }

        if (resp.status === 403 && result && result.code === 'csrf_invalid' && !isRetry) {
            const newToken = await refreshCsrfToken();
            if (newToken) {
                return await postAgentJson(payload, signal, true);
            }
        }

        if (resp.status === 429 && result && result.code === 'concurrent_run') {
            throw new Error(result.error || i18n.concurrentError);
        }

        return { response: resp, data: result };
    }

    /**
     * SSE Streaming Dispatcher with 45s Inactivity Watchdog (§4 B2)
     */
    async function streamAgentRequest(payload, onEvent, signal = null, isRetry = false) {
        if (!streamUrl || typeof ReadableStream === 'undefined') {
            return false;
        }

        const token = getCsrfToken();
        const bodyData = Object.assign({}, payload, { csrf: token });

        const resp = await fetch(streamUrl, {
            method: 'POST',
            signal: signal,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream, application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': token
            },
            body: JSON.stringify(bodyData)
        });

        if (resp.status === 403) {
            let errJson = null;
            try {
                errJson = await resp.json();
            } catch (e) {}

            if (errJson && errJson.code === 'csrf_invalid' && !isRetry) {
                const newToken = await refreshCsrfToken();
                if (newToken) {
                    return await streamAgentRequest(payload, onEvent, signal, true);
                }
            }
            throw new Error(errJson && errJson.error ? errJson.error : 'Forbidden (403)');
        }

        if (resp.status === 429) {
            let errJson = null;
            try {
                errJson = await resp.json();
            } catch (e) {}
            const msg = (errJson && errJson.error) ? errJson.error : i18n.concurrentError;
            throw new Error(msg);
        }

        if (!resp.ok) {
            let errJson = null;
            try {
                errJson = await resp.json();
            } catch (e) {}
            throw new Error(errJson && errJson.error ? errJson.error : ('HTTP ' + resp.status));
        }

        const contentType = resp.headers.get('content-type') || '';
        if (!contentType.includes('text/event-stream')) {
            const data = await resp.json();
            if (data && data.type) {
                onEvent(data);
            } else if (data && data.success) {
                onEvent(Object.assign({ type: 'done' }, data));
            } else {
                onEvent(Object.assign({ type: 'error' }, data));
            }
            return true;
        }

        if (!resp.body || !resp.body.getReader) {
            return false;
        }

        const reader = resp.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let buffer = '';

        // 45-Second Activity Watchdog Timer (§4 B2)
        let watchdogTimer = null;
        const resetWatchdog = () => {
            if (watchdogTimer) clearTimeout(watchdogTimer);
            watchdogTimer = setTimeout(() => {
                try {
                    reader.cancel('Inactivity watchdog timeout');
                } catch (e) {}
            }, 45000);
        };
        resetWatchdog();

        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                resetWatchdog();
                buffer += decoder.decode(value, { stream: true });

                const parts = buffer.split('\n\n');
                buffer = parts.pop();

                for (const part of parts) {
                    const lines = part.split('\n');
                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (trimmed.startsWith(':')) {
                            // SSE comment / heartbeat
                            continue;
                        }
                        if (trimmed.startsWith('data:')) {
                            const jsonStr = trimmed.substring(5).trim();
                            if (jsonStr) {
                                try {
                                    const evt = JSON.parse(jsonStr);
                                    onEvent(evt);
                                } catch (e) {}
                            }
                        }
                    }
                }
            }
            return true;
        } finally {
            if (watchdogTimer) clearTimeout(watchdogTimer);
        }
    }

    function renderConfirmationCard(container, data, onConfirm, onCancel) {
        const confirmBox = document.createElement('div');
        confirmBox.className = 'agent-confirm-card';
        const toolName = escapeHtml(data.pending_tool || (data.pending_action && data.pending_action.tool) || '');
        const argsObj = data.pending_args || (data.pending_action && data.pending_action.args) || {};

        let argsHtml = '';
        if (argsObj && typeof argsObj === 'object') {
            const entries = Object.entries(argsObj);
            if (entries.length > 0) {
                argsHtml = '<div class="agent-confirm-details">';
                for (const [k, v] of entries) {
                    const valStr = typeof v === 'object' ? JSON.stringify(v) : String(v);
                    argsHtml += '<div><strong>' + escapeHtml(k) + ':</strong> <code>' + escapeHtml(valStr) + '</code></div>';
                }
                argsHtml += '</div>';
            }
        }

        confirmBox.innerHTML = `
            <div class="agent-confirm-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <span>${escapeHtml(i18n.confirmWarningTitle)} <code>${toolName}</code></span>
            </div>
            ${argsHtml}
            <div class="agent-confirm-actions">
                <button type="button" class="agent-btn-confirm">${escapeHtml(i18n.confirmBtnYes)}</button>
                <button type="button" class="agent-btn-cancel">${escapeHtml(i18n.confirmBtnNo)}</button>
            </div>
        `;
        container.appendChild(confirmBox);

        const confirmBtn = confirmBox.querySelector('.agent-btn-confirm');
        const cancelBtn = confirmBox.querySelector('.agent-btn-cancel');

        confirmBtn.addEventListener('click', function() {
            confirmBox.remove();
            onConfirm();
        });

        cancelBtn.addEventListener('click', function() {
            confirmBox.remove();
            onCancel();
        });
    }

    async function handleConfirmationFlow(confirmationId) {
        showThinking();
        const confController = new AbortController();
        const confTimeoutId = setTimeout(() => confController.abort(), 240000);
        const reqId = generateRequestId();

        let streamHandled = false;
        try {
            streamHandled = await streamAgentRequest({
                confirm_action_id: confirmationId,
                request_id: reqId
            }, function(evt) {
                if (evt.type === 'status') {
                    updateProgressStatus(evt.message || i18n.thinkingMsg);
                } else if (evt.type === 'tool_call') {
                    addToolChip(evt.tool || 'tool');
                } else if (evt.type === 'tool_result') {
                    completeToolChip(evt.tool || 'tool', evt.elapsed_ms || null);
                } else if (evt.type === 'confirmation_required') {
                    hideThinking();
                    updateActiveModel(evt.model);
                    const bubbleRow = appendBubble('model', evt.reply || '');
                    const bubbleContent = bubbleRow.querySelector('.agent-message-content');
                    if (bubbleContent) {
                        const nextConfId = (evt.pending_action && evt.pending_action.id) || evt.confirmation_id || '';
                        renderConfirmationCard(bubbleContent, evt, () => handleConfirmationFlow(nextConfId), () => handleCancelAction(nextConfId));
                    }
                } else if (evt.type === 'done') {
                    hideThinking();
                    updateActiveModel(evt.model);
                    appendBubble('model', evt.reply || '');
                } else if (evt.type === 'error') {
                    hideThinking();
                    appendBubble('model', evt.error || i18n.generalError, true);
                }
            }, confController.signal);
        } catch (streamErr) {
            clearTimeout(confTimeoutId);
            hideThinking();
            const msg = streamErr.name === 'AbortError'
                ? i18n.timeoutError
                : (streamErr.message || i18n.networkError);
            appendBubble('model', msg, true);
            return;
        }

        if (streamHandled) {
            clearTimeout(confTimeoutId);
            hideThinking();
            return;
        }

        // Fallback to POST
        try {
            const confResObj = await postAgentJson({
                confirm_action_id: confirmationId,
                request_id: reqId
            }, confController.signal);
            clearTimeout(confTimeoutId);
            hideThinking();

            const confResult = confResObj ? confResObj.data : null;
            if (confResult && confResult.requires_confirmation) {
                updateActiveModel(confResult.model);
                const bubbleRow = appendBubble('model', confResult.reply || '');
                const bubbleContent = bubbleRow.querySelector('.agent-message-content');
                if (bubbleContent) {
                    const nextConfId = (confResult.pending_action && confResult.pending_action.id) || confResult.confirmation_id || '';
                    renderConfirmationCard(bubbleContent, confResult, () => handleConfirmationFlow(nextConfId), () => handleCancelAction(nextConfId));
                }
            } else if (confResult && confResult.success && confResult.reply) {
                updateActiveModel(confResult.model);
                appendBubble('model', confResult.reply);
            } else {
                appendBubble('model', (confResult && confResult.error) ? confResult.error : i18n.generalError, true);
            }
        } catch (confErr) {
            clearTimeout(confTimeoutId);
            hideThinking();
            const msg = confErr.name === 'AbortError'
                ? i18n.timeoutError
                : (confErr.message || i18n.networkError);
            appendBubble('model', msg, true);
        }
    }

    function handleCancelAction(confirmationId) {
        postAgentJson({
            cancel_action_id: confirmationId
        }).catch(() => {});
        appendBubble('model', i18n.cancelledMsg);
    }

    // Form submit handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (isSubmitting) return;

        const promptText = input.value.trim();
        if (!promptText) {
            input.focus();
            return;
        }

        isSubmitting = true;
        sendBtn.disabled = true;
        input.disabled = true;

        const userBubble = appendBubble('user', promptText);
        input.value = '';
        adjustTextareaHeight();
        showThinking();

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 240000);
        const reqId = generateRequestId();

        let streamHandled = false;
        let gotAnyEvent = false;

        try {
            streamHandled = await streamAgentRequest({
                prompt: promptText,
                request_id: reqId
            }, function(evt) {
                gotAnyEvent = true;
                if (evt.type === 'status') {
                    updateProgressStatus(evt.message || i18n.thinkingMsg);
                } else if (evt.type === 'tool_call') {
                    addToolChip(evt.tool || 'tool');
                } else if (evt.type === 'tool_result') {
                    completeToolChip(evt.tool || 'tool', evt.elapsed_ms || null);
                } else if (evt.type === 'confirmation_required') {
                    hideThinking();
                    updateActiveModel(evt.model);
                    const bubbleRow = appendBubble('model', evt.reply || '');
                    const bubbleContent = bubbleRow.querySelector('.agent-message-content');
                    if (bubbleContent) {
                        const confId = (evt.pending_action && evt.pending_action.id) || evt.confirmation_id || '';
                        renderConfirmationCard(bubbleContent, evt, () => handleConfirmationFlow(confId), () => handleCancelAction(confId));
                    }
                } else if (evt.type === 'done') {
                    hideThinking();
                    updateActiveModel(evt.model);
                    appendBubble('model', evt.reply || '');
                } else if (evt.type === 'error') {
                    hideThinking();
                    appendBubble('model', evt.error || i18n.generalError, true);
                }
            }, controller.signal);
        } catch (streamErr) {
            clearTimeout(timeoutId);
            hideThinking();
            const msg = streamErr.name === 'AbortError'
                ? i18n.timeoutError
                : (streamErr.message || i18n.networkError);
            appendBubble('model', msg, true);
            input.value = promptText;
            adjustTextareaHeight();
            if (userBubble && userBubble.parentNode && !gotAnyEvent) {
                userBubble.remove();
            }
            isSubmitting = false;
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
            return;
        }

        if (streamHandled) {
            clearTimeout(timeoutId);
            hideThinking();
            isSubmitting = false;
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
            return;
        }

        // Fallback to synchronous POST
        try {
            const resObj = await postAgentJson({ prompt: promptText, request_id: reqId }, controller.signal);
            clearTimeout(timeoutId);
            hideThinking();

            const result = resObj ? resObj.data : null;

            if (result && result.requires_confirmation) {
                updateActiveModel(result.model);
                const bubbleRow = appendBubble('model', result.reply);
                const bubbleContent = bubbleRow.querySelector('.agent-message-content');
                if (bubbleContent) {
                    const confirmationId = (result.pending_action && result.pending_action.id) || result.confirmation_id || '';
                    renderConfirmationCard(bubbleContent, result, () => handleConfirmationFlow(confirmationId), () => handleCancelAction(confirmationId));
                }
            } else if (result && result.success && result.reply) {
                updateActiveModel(result.model);
                appendBubble('model', result.reply);
            } else {
                const errMsg = (result && (result.error || result.message)) ? (result.error || result.message) : i18n.generalError;
                appendBubble('model', errMsg, true);
                input.value = promptText;
                adjustTextareaHeight();
                if (userBubble && userBubble.parentNode) {
                    userBubble.remove();
                }
            }
        } catch (err) {
            clearTimeout(timeoutId);
            hideThinking();
            const msg = err.name === 'AbortError'
                ? i18n.timeoutError
                : (err.message || i18n.networkError);
            appendBubble('model', msg, true);
            input.value = promptText;
            adjustTextareaHeight();
            if (userBubble && userBubble.parentNode) {
                userBubble.remove();
            }
        } finally {
            isSubmitting = false;
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });

    // Clear dialogue handler
    if (clearBtn) {
        clearBtn.addEventListener('click', async function() {
            if (stream.children.length <= 1) {
                return;
            }

            clearBtn.disabled = true;
            try {
                const resObj = await postAgentJson({
                    prompt: '',
                    clear_history: true
                });

                if (resObj && resObj.data && resObj.data.success) {
                    const defaultModel = (wrapper && wrapper.dataset.defaultModel) || 'gemini-3.8-flash';
                    updateActiveModel(defaultModel);
                    const initialGreeting = stream.firstElementChild ? stream.firstElementChild.cloneNode(true) : null;
                    stream.innerHTML = '';
                    if (initialGreeting) {
                        const timeEl = initialGreeting.querySelector('.agent-message-time');
                        if (timeEl) {
                            timeEl.textContent = formatCurrentTime();
                        }
                        stream.appendChild(initialGreeting);
                    }
                } else {
                    const errMsg = (resObj && resObj.data && (resObj.data.error || resObj.data.message))
                        ? (resObj.data.error || resObj.data.message)
                        : i18n.generalError;
                    appendBubble('model', errMsg, true);
                }
            } catch (err) {
                appendBubble('model', (err.message || i18n.networkError), true);
            } finally {
                clearBtn.disabled = false;
                input.focus();
            }
        });
    }

    input.focus();
});
