/**
 * Amed Framework - Agent Dialogue Controller (mod_user_agent.js)
 * Modern, event-driven dialogue interaction powered by Gemini 3.8 Flash
 */
document.addEventListener('DOMContentLoaded', function() {
    const stream = document.getElementById('agentDialogueStream');
    const form = document.getElementById('agentChatForm');
    const input = document.getElementById('agentPromptInput');
    const sendBtn = document.getElementById('agentSendBtn');
    const clearBtn = document.getElementById('agentClearBtn');
    const chatUrlInput = document.getElementById('agentChatUrl');

    if (!stream || !form || !input || !sendBtn) {
        return;
    }

    const chatUrl = chatUrlInput ? chatUrlInput.value : window.location.href;
    let conversationHistory = [];
    let isSubmitting = false;

    // Set initial timestamp on welcome bubble
    const initialTimeEl = stream.querySelector('[data-timestamp="now"]');
    if (initialTimeEl) {
        initialTimeEl.textContent = formatCurrentTime();
    }

    // Auto-scroll helper
    function scrollToBottom() {
        window.scrollTo({
            top: document.documentElement.scrollHeight,
            behavior: 'smooth'
        });
    }

    // Format current time HH:MM
    function formatCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return hours + ':' + minutes;
    }

    // Textarea auto-resize
    function adjustTextareaHeight() {
        input.style.height = 'auto';
        const newHeight = Math.min(input.scrollHeight, 110);
        input.style.height = newHeight + 'px';
    }

    input.addEventListener('input', adjustTextareaHeight);

    // Keyboard handling: Enter to send, Shift+Enter for new line
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    // Escape HTML for safe rendering
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Simple markdown formatting for bold, lists, and code
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

            // Bold
            line = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Inline code
            line = line.replace(/`(.*?)`/g, '<code>$1</code>');

            // Bullet lists
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

    // Append a message bubble to the dialogue stream
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

    // Append thinking indicator
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
        bubble.innerHTML = '<div class="agent-thinking-dots"><span></span><span></span><span></span></div>';

        row.appendChild(bubble);
        stream.appendChild(row);
        scrollToBottom();
        return row;
    }

    // Remove thinking indicator
    function hideThinking() {
        const thinkingRow = document.getElementById('agentThinkingIndicator');
        if (thinkingRow) {
            thinkingRow.remove();
        }
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

        // Set submitting state
        isSubmitting = true;
        sendBtn.disabled = true;
        input.disabled = true;

        // Render user prompt
        appendBubble('user', promptText);

        // Reset input box
        input.value = '';
        adjustTextareaHeight();

        // Show thinking animation
        showThinking();

        try {
            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    prompt: promptText,
                    history: conversationHistory
                })
            });

            hideThinking();

            let result = null;
            try {
                result = await response.json();
            } catch (parseErr) {
                throw new Error('HTTP ' + response.status + ' (' + response.statusText + ')');
            }

            if (result && result.success && result.reply) {
                appendBubble('model', result.reply);

                // Update memory history for multi-turn context
                conversationHistory.push({ role: 'user', text: promptText });
                conversationHistory.push({ role: 'model', text: result.reply });

                // Keep memory concise (last 10 turns)
                if (conversationHistory.length > 20) {
                    conversationHistory = conversationHistory.slice(-20);
                }
            } else {
                const errMsg = (result && (result.error || result.message)) ? (result.error || result.message) : 'Çewtî di bersivdayînê de çêbû.';
                appendBubble('model', errMsg, true);
            }
        } catch (err) {
            hideThinking();
            appendBubble('model', 'Çewtiya girêdana torê çêbû: ' + (err.message || 'Server unreachable'), true);
        } finally {
            isSubmitting = false;
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });

    // Clear dialogue handler
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (conversationHistory.length === 0 && stream.children.length <= 1) {
                return;
            }

            conversationHistory = [];

            // Reset stream to initial greeting
            const initialGreeting = stream.firstElementChild ? stream.firstElementChild.cloneNode(true) : null;
            stream.innerHTML = '';
            if (initialGreeting) {
                const timeEl = initialGreeting.querySelector('.agent-message-time');
                if (timeEl) {
                    timeEl.textContent = formatCurrentTime();
                }
                stream.appendChild(initialGreeting);
            }
            input.focus();
        });
    }

    // Focus input on initial page load
    input.focus();
});
