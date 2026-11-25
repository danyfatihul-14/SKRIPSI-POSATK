window.__aiChatHistory = window.__aiChatHistory || [];

function askPreset(text) {
    const input = document.getElementById('aiQuestionInput');
    if (!input) return;
    input.value = text;
    input.focus();
    handleSendAiMessage(new Event('submit'));
}

function clearAiChat() {
    window.__aiChatHistory = [];
    const chatBox = document.getElementById('aiChatBox');
    if (!chatBox) return;
    chatBox.innerHTML = `
        <div class="ai-msg ai-bot">
            <div class="ai-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">🤖</div>
            <div class="ai-bubble" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b;">
                <div style="font-weight: 700; font-size: 13px; margin-bottom: 4px; color: #d97706;">Asisten Pintar Toko</div>
                <div style="font-size: 13.5px; line-height: 1.6;">
                    Chat dibersihkan. Silakan tanyakan hal lain mengenai performa toko Anda.
                </div>
            </div>
        </div>
    `;
}

async function handleSendAiMessage(e) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const input = document.getElementById('aiQuestionInput');
    if (!input) return;

    const question = (input.value || '').trim();
    if (!question) return;

    const storeSelect = document.getElementById('aiStoreSelect');
    const storeId = storeSelect ? storeSelect.value : null;
    const csrfToken = document.getElementById('aiCsrfToken')?.value 
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || '';

    // Append user bubble
    appendMessage('user', question);
    input.value = '';

    // Append bot typing bubble
    const loadingBubbleId = 'loading-' + Date.now();
    appendLoading(loadingBubbleId);

    // Toggle button state
    setSendingState(true);

    try {
        const response = await fetch('/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question: question,
                store_id: storeId,
                history: (window.__aiChatHistory || []).slice(-4)
            })
        });

        const data = await response.json();
        removeLoading(loadingBubbleId);

        if (!response.ok || !data.success) {
            const errMsg = data.error || 'Terjadi kesalahan saat memproses data.';
            appendMessage('bot', `⚠️ Maaf: ${errMsg}`);
        } else {
            window.__aiChatHistory.push({ q: question, a: data.answer });
            appendBotAnswer(data.answer, data.data, data.sql);
        }
    } catch (err) {
        removeLoading(loadingBubbleId);
        appendMessage('bot', `⚠️ Gagal menghubungi server: ${err.message}`);
    } finally {
        setSendingState(false);
    }
}

function setSendingState(isSending) {
    const btn = document.getElementById('aiSendBtn');
    const icon = document.getElementById('aiSendIcon');
    const loading = document.getElementById('aiSendLoading');
    if (!btn) return;

    if (isSending) {
        btn.disabled = true;
        btn.style.opacity = '0.7';
        if (icon) icon.style.display = 'none';
        if (loading) loading.style.display = 'inline-block';
    } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        if (icon) icon.style.display = 'inline-block';
        if (loading) loading.style.display = 'none';
        document.getElementById('aiQuestionInput')?.focus();
    }
}

function appendMessage(role, text) {
    const chatBox = document.getElementById('aiChatBox');
    if (!chatBox) return;

    const msgDiv = document.createElement('div');
    msgDiv.className = `ai-msg ai-${role}`;

    if (role === 'user') {
        msgDiv.innerHTML = `
            <div class="ai-avatar" style="background: #3b82f6; color: white;">👤</div>
            <div class="ai-bubble">${escapeHtml(text)}</div>
        `;
    } else {
        msgDiv.innerHTML = `
            <div class="ai-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">🤖</div>
            <div class="ai-bubble">${formatMarkdown(text)}</div>
        `;
    }

    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function appendBotAnswer(answer, tableData, sql) {
    const chatBox = document.getElementById('aiChatBox');
    if (!chatBox) return;

    const msgDiv = document.createElement('div');
    msgDiv.className = 'ai-msg ai-bot';

    let tableHtml = '';
    if (Array.isArray(tableData) && tableData.length > 0) {
        const keys = Object.keys(tableData[0]);
        tableHtml = `
            <div style="margin-top: 12px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
                <button type="button" onclick="toggleDetails(this)" style="background: transparent; border: none; font-size: 11.5px; font-weight: 700; color: #d97706; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 4px;">
                    <span>📊 Lihat Rincian Data Tabel (${tableData.length} baris)</span>
                    <span style="font-size: 10px;">▼</span>
                </button>
                <div class="ai-table-wrap" style="display: none; margin-top: 8px; max-height: 220px; overflow-y: auto;">
                    <table class="ai-data-table">
                        <thead>
                            <tr>${keys.map(k => `<th>${escapeHtml(k)}</th>`).join('')}</tr>
                        </thead>
                        <tbody>
                            ${tableData.map(row => `<tr>${keys.map(k => `<td>${escapeHtml(row[k] !== null ? String(row[k]) : '-')}</td>`).join('')}</tr>`).join('')}
                        </tbody>
                    </table>
                    ${sql ? `<div style="margin-top: 6px; font-size: 10.5px; color: #64748b; font-family: monospace; background: #e2e8f0; padding: 4px 8px; border-radius: 4px; overflow-x: auto;">Query: ${escapeHtml(sql)}</div>` : ''}
                </div>
            </div>
        `;
    }

    msgDiv.innerHTML = `
        <div class="ai-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">🤖</div>
        <div class="ai-bubble">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 4px; color: #d97706;">Jawaban Analisis:</div>
            <div style="font-size: 13.5px; line-height: 1.6;">${formatMarkdown(answer)}</div>
            ${tableHtml}
        </div>
    `;

    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function toggleDetails(btn) {
    const wrap = btn.nextElementSibling;
    if (!wrap) return;
    if (wrap.style.display === 'none') {
        wrap.style.display = 'block';
        const indicator = btn.querySelector('span:last-child');
        if (indicator) indicator.textContent = '▲';
    } else {
        wrap.style.display = 'none';
        const indicator = btn.querySelector('span:last-child');
        if (indicator) indicator.textContent = '▼';
    }
}

function appendLoading(id) {
    const chatBox = document.getElementById('aiChatBox');
    if (!chatBox) return;

    const msgDiv = document.createElement('div');
    msgDiv.className = 'ai-msg ai-bot';
    msgDiv.id = id;
    msgDiv.innerHTML = `
        <div class="ai-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">🤖</div>
        <div class="ai-bubble" style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b;">
            <svg style="width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Menganalisis database transaksi & stok...</span>
        </div>
    `;
    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function removeLoading(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function formatMarkdown(text) {
    if (!text) return '';
    let html = escapeHtml(text);
    // bold
    html = html.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
    // italic
    html = html.replace(/\*(.*?)\*/g, '<i>$1</i>');
    // newlines
    html = html.replace(/\n/g, '<br>');
    return html;
}
