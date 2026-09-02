document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const page = body.dataset.agentPage || 'chat';
    const endpoint = body.dataset.agentEndpoint || '';
    const chatUrl = body.dataset.agentChatUrl || '';
    const conversationsEndpoint = body.dataset.agentConversationsEndpoint || '';
    const uploadsEndpoint = body.dataset.agentUploadsEndpoint || '';
    const fileUploadEndpoint = body.dataset.agentFileUploadEndpoint || '';
    const uploadDeleteBaseUrl = (body.dataset.agentUploadDeleteBase || '').replace(/\/$/, '');
    const uploadMaxFileBytes = Number.parseInt(body.dataset.agentUploadMaxFileBytes || '0', 10);
    const uploadMaxRequestBytes = Number.parseInt(body.dataset.agentUploadMaxRequestBytes || '0', 10);
    const uploadMaxFileLabel = body.dataset.agentUploadMaxFileLabel || '';
    const uploadMaxRequestLabel = body.dataset.agentUploadMaxRequestLabel || '';
    const conversationBaseUrl = body.dataset.agentConversationBase || '';
    const deleteConversationBaseUrl = `${conversationBaseUrl.replace(/\/$/, '')}`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let conversationId = null;
    let isPolling = false;
    let isLoadingConversations = false;
    let lastMessageId = null;
    let waitTimeoutSeconds = 1800;
    let waitDeadlineAt = null;
    let pendingAssistantWrapper = null;
    let pollTimer = null;
    let uploadPollTimer = null;
    let isLoadingUploads = false;
    let isSubmittingUploads = false;
    const renderedMessageIds = new Set();
    const pollingIntervalMs = 5000;
    const conversationCookieKey = 'agent_conversation_id';

    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const openSidebarBtn = document.getElementById('openSidebarBtn');
    const closeSidebarBtn = document.getElementById('closeSidebarBtn');
    const settingsOverlay = document.getElementById('settingsOverlay');
    const settingsModal = document.getElementById('settingsModal');
    const openSettingsBtn = document.getElementById('openSettingsBtn');
    const userOpenSettingsBtn = document.getElementById('userOpenSettingsBtn');
    const closeSettingsBtn = document.getElementById('closeSettingsBtn');
    const closeSettingsBtn2 = document.getElementById('closeSettingsBtn2');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');
    const chatList = document.getElementById('chatList');
    const emptyState = document.getElementById('emptyState');
    const chatStatusNotice = document.getElementById('chatStatusNotice');
    const conversationList = document.getElementById('conversationList');
    const conversationEmpty = document.getElementById('conversationEmpty');
    const composerForm = document.getElementById('composerForm');
    const composer = document.getElementById('composer');
    const submitButton = composerForm?.querySelector('button[type="submit"]') || null;
    const newChatBtn = document.getElementById('newChatBtn');
    const quickChipsRow = document.getElementById('quickChipsRow');
    const toggleChips = document.getElementById('toggleChips');
    const toggleChipsDot = document.getElementById('toggleChipsDot');
    const resetUiBtn = document.getElementById('resetUiBtn');
    const fileDropzone = document.getElementById('fileDropzone');
    const filePickerTrigger = document.getElementById('filePickerTrigger');
    const filePickerInput = document.getElementById('filePickerInput');
    const fileList = document.getElementById('fileList');
    const fileEmptyState = document.getElementById('fileEmptyState');
    const fileActionBtn = document.getElementById('fileActionBtn');
    const fileUploadNotice = document.getElementById('fileUploadNotice');
    const uploadHistoryList = document.getElementById('uploadHistoryList');
    const uploadHistoryEmpty = document.getElementById('uploadHistoryEmpty');
    const uploadRefreshBtn = document.getElementById('uploadRefreshBtn');

    let selectedFiles = [];

    function getCookie(name) {
        const cookies = document.cookie.split(';').map((cookie) => cookie.trim());
        const entry = cookies.find((cookie) => cookie.startsWith(`${name}=`));
        if (!entry) {
            return null;
        }

        return decodeURIComponent(entry.split('=')[1] || '');
    }

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
    }

    function clearCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax`;
    }

    function setConversationId(value) {
        const parsed = Number.parseInt(String(value), 10);
        if (Number.isNaN(parsed) || parsed <= 0) {
            return;
        }

        conversationId = parsed;
        setCookie(conversationCookieKey, parsed, 7);
        updateActiveConversation();
        void loadConversationList();
    }

    function clearConversationId() {
        conversationId = null;
        clearCookie(conversationCookieKey);
        updateActiveConversation();
    }

    function setFileUploadNotice(message, tone = 'error') {
        if (!fileUploadNotice) {
            return;
        }

        fileUploadNotice.classList.remove(
            'hidden',
            'border-red-200',
            'bg-red-50',
            'text-red-700',
            'border-emerald-200',
            'bg-emerald-50',
            'text-emerald-700',
        );

        if (!message) {
            fileUploadNotice.textContent = '';
            fileUploadNotice.classList.add('hidden');
            return;
        }

        fileUploadNotice.textContent = message;

        if (tone === 'success') {
            fileUploadNotice.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
            return;
        }

        fileUploadNotice.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    }

    function openSidebar() {
        if (!sidebar || !sidebarOverlay) {
            return;
        }

        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
        if (!sidebar || !sidebarOverlay) {
            return;
        }

        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openSettings() {
        if (!settingsOverlay || !settingsModal) {
            return;
        }

        settingsOverlay.classList.remove('hidden');
        settingsModal.classList.remove('hidden');
        closeUserMenu();
    }

    function closeSettings() {
        if (!settingsOverlay || !settingsModal) {
            return;
        }

        settingsOverlay.classList.add('hidden');
        settingsModal.classList.add('hidden');
    }

    function toggleUserMenu() {
        userMenu?.classList.toggle('hidden');
    }

    function closeUserMenu() {
        userMenu?.classList.add('hidden');
    }

    openSidebarBtn?.addEventListener('click', openSidebar);
    closeSidebarBtn?.addEventListener('click', closeSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);
    openSettingsBtn?.addEventListener('click', openSettings);
    userOpenSettingsBtn?.addEventListener('click', openSettings);
    closeSettingsBtn?.addEventListener('click', closeSettings);
    closeSettingsBtn2?.addEventListener('click', closeSettings);
    settingsOverlay?.addEventListener('click', closeSettings);

    userMenuBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleUserMenu();
    });

    document.addEventListener('click', () => {
        closeUserMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
            closeSettings();
            closeUserMenu();
        }
    });

    function updateEmptyState() {
        if (!emptyState || !chatList) {
            return;
        }

        emptyState.classList.toggle('hidden', chatList.children.length > 0);
    }

    function updateActiveConversation() {
        if (!conversationList) {
            return;
        }

        const items = conversationList.querySelectorAll('[data-conversation-id]');
        items.forEach((item) => {
            const id = Number.parseInt(item.dataset.conversationId || '', 10);
            const isActive = Number.isFinite(id) && id === conversationId;
            item.classList.toggle('bg-gray-800', isActive);
            item.classList.toggle('text-white', isActive);
            item.classList.toggle('text-gray-400', !isActive);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function sanitizeLinkUrl(url) {
        const normalized = String(url).trim().replaceAll('&amp;', '&');
        if (normalized === '') {
            return '';
        }

        if (normalized.startsWith('/') || normalized.startsWith('#')) {
            return normalized;
        }

        try {
            const parsed = new URL(normalized);
            if (['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol)) {
                return parsed.toString();
            }
        } catch {
            return '';
        }

        return '';
    }

    function formatInlineMarkdown(text) {
        const inlineCodes = [];
        let formatted = escapeHtml(text).replace(/`([^`\n]+)`/g, (match, code) => {
            const token = `@@INLINE_CODE_${inlineCodes.length}@@`;
            inlineCodes.push(`<code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-[0.85em] text-gray-800">${code}</code>`);
            return token;
        });

        formatted = formatted.replace(/\[([^\]]+)]\(([^)\s]+)(?:\s+"[^"]*")?\)/g, (match, label, url) => {
            const safeUrl = sanitizeLinkUrl(url);
            if (safeUrl === '') {
                return label;
            }

            return `<a class="underline decoration-amber-300 hover:decoration-amber-500" href="${escapeHtml(safeUrl)}" target="_blank" rel="noopener noreferrer">${label}</a>`;
        });

        formatted = formatted
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
            .replace(/~~([^~]+)~~/g, '<s>$1</s>')
            .replaceAll('\n', '<br>');

        inlineCodes.forEach((html, index) => {
            formatted = formatted.replaceAll(`@@INLINE_CODE_${index}@@`, html);
        });

        return formatted;
    }

    function renderMarkdown(text) {
        const normalized = String(text ?? '').replace(/\r\n?/g, '\n').trim();
        if (normalized === '') {
            return '';
        }

        const codeBlocks = [];
        const withCodeTokens = normalized.replace(/```([a-zA-Z0-9_-]+)?\n?([\s\S]*?)```/g, (match, language, code) => {
            const token = `@@CODE_BLOCK_${codeBlocks.length}@@`;
            const safeCode = escapeHtml(String(code ?? '').replace(/\n$/, ''));
            const safeLanguage = language ? `<div class="mb-2 text-[10px] uppercase tracking-wider text-gray-400">${escapeHtml(language)}</div>` : '';
            codeBlocks.push(
                `<div class="my-3">${safeLanguage}<pre class="overflow-x-auto rounded-lg bg-gray-900 p-3 text-gray-100"><code class="font-mono text-[0.85em] leading-relaxed">${safeCode}</code></pre></div>`,
            );
            return `\n${token}\n`;
        });

        const blocks = [];
        const paragraphLines = [];
        const quoteLines = [];
        let listType = null;
        let listItems = [];
        let tableLines = [];

        function flushParagraph() {
            if (paragraphLines.length === 0) {
                return;
            }

            blocks.push(`<p>${formatInlineMarkdown(paragraphLines.join('\n'))}</p>`);
            paragraphLines.length = 0;
        }

        function flushQuote() {
            if (quoteLines.length === 0) {
                return;
            }

            const quoteHtml = quoteLines.map((line) => formatInlineMarkdown(line)).join('<br>');
            blocks.push(`<blockquote class="my-2 border-l-4 border-amber-300 pl-3 text-gray-700">${quoteHtml}</blockquote>`);
            quoteLines.length = 0;
        }

        function flushList() {
            if (listType === null || listItems.length === 0) {
                return;
            }

            const tag = listType === 'ol' ? 'ol' : 'ul';
            const className = tag === 'ol' ? 'my-2 list-decimal space-y-1 pl-5' : 'my-2 list-disc space-y-1 pl-5';
            const itemsHtml = listItems.map((item) => `<li>${formatInlineMarkdown(item)}</li>`).join('');
            blocks.push(`<${tag} class="${className}">${itemsHtml}</${tag}>`);
            listType = null;
            listItems = [];
        }

        function isMarkdownTableLine(line) {
            if (!line.includes('|')) {
                return false;
            }

            return line.trim().startsWith('|');
        }

        function parseMarkdownTableCells(line) {
            const trimmed = line.trim().replace(/^\|/, '').replace(/\|$/, '');
            return trimmed.split('|').map((cell) => cell.trim());
        }

        function isMarkdownTableSeparator(cell) {
            return /^:?-{3,}:?$/.test(cell);
        }

        function getTableCellAlignClass(separator) {
            const hasStartColon = separator.startsWith(':');
            const hasEndColon = separator.endsWith(':');
            if (hasStartColon && hasEndColon) {
                return 'text-center';
            }

            if (hasEndColon) {
                return 'text-right';
            }

            return 'text-left';
        }

        function flushTable() {
            if (tableLines.length === 0) {
                return;
            }

            const headerCells = parseMarkdownTableCells(tableLines[0]);
            const separatorCells = tableLines.length > 1 ? parseMarkdownTableCells(tableLines[1]) : [];
            const isValidTable = headerCells.length > 0
                && separatorCells.length === headerCells.length
                && separatorCells.every((cell) => isMarkdownTableSeparator(cell));

            if (!isValidTable) {
                blocks.push(`<p>${formatInlineMarkdown(tableLines.join('\n'))}</p>`);
                tableLines = [];
                return;
            }

            const alignmentClasses = separatorCells.map((cell) => getTableCellAlignClass(cell));
            const headerHtml = headerCells
                .map((cell, index) => `<th class="border border-gray-200 bg-gray-50 px-3 py-2 font-semibold text-gray-700 ${alignmentClasses[index]}">${formatInlineMarkdown(cell)}</th>`)
                .join('');

            const bodyHtml = tableLines
                .slice(2)
                .map((line) => {
                    const rowCells = parseMarkdownTableCells(line);
                    const normalizedCells = headerCells.map((unused, index) => rowCells[index] || '');
                    const rowHtml = normalizedCells
                        .map((cell, index) => `<td class="border border-gray-200 px-3 py-2 align-top text-gray-700 ${alignmentClasses[index]}">${formatInlineMarkdown(cell)}</td>`)
                        .join('');

                    return `<tr>${rowHtml}</tr>`;
                })
                .join('');

            blocks.push(
                `<div class="my-3 overflow-x-auto"><table class="w-full border-collapse text-left text-sm"><thead><tr>${headerHtml}</tr></thead><tbody>${bodyHtml}</tbody></table></div>`,
            );

            tableLines = [];
        }

        function flushOpenBlocks() {
            flushParagraph();
            flushQuote();
            flushList();
            flushTable();
        }

        const lines = withCodeTokens.split('\n');
        lines.forEach((line) => {
            const trimmed = line.trim();

            if (trimmed === '') {
                flushOpenBlocks();
                return;
            }

            if (/^@@CODE_BLOCK_\d+@@$/.test(trimmed)) {
                flushOpenBlocks();
                blocks.push(trimmed);
                return;
            }

            if (isMarkdownTableLine(trimmed)) {
                flushParagraph();
                flushQuote();
                flushList();
                tableLines.push(trimmed);
                return;
            }

            if (tableLines.length > 0) {
                flushTable();
            }

            const headingMatch = trimmed.match(/^(#{1,3})\s+(.+)$/);
            if (headingMatch) {
                flushOpenBlocks();
                const level = headingMatch[1].length;
                const headingText = formatInlineMarkdown(headingMatch[2]);
                const className = level === 1 ? 'mt-2 mb-1 text-base font-semibold' : level === 2 ? 'mt-2 mb-1 text-sm font-semibold' : 'mt-2 mb-1 text-sm font-medium';
                blocks.push(`<h${level} class="${className}">${headingText}</h${level}>`);
                return;
            }

            if (/^(-{3,}|\*{3,}|_{3,})$/.test(trimmed)) {
                flushOpenBlocks();
                blocks.push('<hr class="my-3 border-gray-200">');
                return;
            }

            const quoteMatch = trimmed.match(/^>\s?(.*)$/);
            if (quoteMatch) {
                flushParagraph();
                flushList();
                quoteLines.push(quoteMatch[1]);
                return;
            }

            const unorderedMatch = trimmed.match(/^[-*+]\s+(.+)$/);
            if (unorderedMatch) {
                flushParagraph();
                flushQuote();
                if (listType !== 'ul') {
                    flushList();
                    listType = 'ul';
                }
                listItems.push(unorderedMatch[1]);
                return;
            }

            const orderedMatch = trimmed.match(/^\d+\.\s+(.+)$/);
            if (orderedMatch) {
                flushParagraph();
                flushQuote();
                if (listType !== 'ol') {
                    flushList();
                    listType = 'ol';
                }
                listItems.push(orderedMatch[1]);
                return;
            }

            flushQuote();
            flushList();
            paragraphLines.push(line);
        });

        flushOpenBlocks();

        let html = blocks.join('');
        codeBlocks.forEach((codeHtml, index) => {
            html = html.replaceAll(`@@CODE_BLOCK_${index}@@`, codeHtml);
        });

        return html;
    }

    function looksLikeRichText(text) {
        return /<\/?[a-z][\s\S]*>/i.test(text);
    }

    function tryDecodeJsonString(text) {
        const trimmed = text.trim();
        if (!(trimmed.startsWith('"') && trimmed.endsWith('"'))) {
            return text;
        }

        try {
            const parsed = JSON.parse(trimmed);
            if (typeof parsed === 'string') {
                return parsed;
            }
        } catch {
            return text;
        }

        return text;
    }

    function normalizeAssistantRawText(text) {
        let normalized = String(text ?? '');
        normalized = tryDecodeJsonString(normalized);

        const hasRealNewline = normalized.includes('\n');
        if (!hasRealNewline && /\\r\\n|\\n|\\r/.test(normalized)) {
            normalized = normalized
                .replace(/\\r\\n/g, '\n')
                .replace(/\\n/g, '\n')
                .replace(/\\r/g, '\n');
        }

        normalized = normalized
            .replace(/\\\*/g, '*')
            .replace(/\\_/g, '_')
            .replace(/\\#/g, '#')
            .replace(/\\`/g, '`')
            .replace(/\\>/g, '>')
            .replace(/\\\|/g, '|')
            .replace(/\\\[/g, '[')
            .replace(/\\\]/g, ']')
            .replace(/\\\(/g, '(')
            .replace(/\\\)/g, ')');

        return normalized;
    }

    function sanitizeRichText(html) {
        const template = document.createElement('template');
        template.innerHTML = html;

        const allowedTags = new Set(['p', 'br', 'strong', 'em', 'b', 'i', 'u', 's', 'ul', 'ol', 'li', 'code', 'pre', 'a', 'blockquote', 'h1', 'h2', 'h3', 'table', 'thead', 'tbody', 'tr', 'th', 'td']);

        function sanitizeNode(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                return document.createTextNode(node.textContent || '');
            }

            if (node.nodeType !== Node.ELEMENT_NODE) {
                return null;
            }

            const tag = node.tagName.toLowerCase();
            if (!allowedTags.has(tag)) {
                const fragment = document.createDocumentFragment();
                node.childNodes.forEach((child) => {
                    const cleanChild = sanitizeNode(child);
                    if (cleanChild) {
                        fragment.appendChild(cleanChild);
                    }
                });
                return fragment;
            }

            const cleanElement = document.createElement(tag);
            if (tag === 'a') {
                const href = sanitizeLinkUrl(node.getAttribute('href') || '');
                if (href !== '') {
                    cleanElement.setAttribute('href', href);
                    cleanElement.setAttribute('target', '_blank');
                    cleanElement.setAttribute('rel', 'noopener noreferrer');
                    cleanElement.className = 'underline decoration-amber-300 hover:decoration-amber-500';
                }
            }

            if (tag === 'pre') {
                cleanElement.className = 'my-3 overflow-x-auto rounded-lg bg-gray-900 p-3 text-gray-100';
            }

            if (tag === 'code') {
                cleanElement.className = 'font-mono text-[0.85em]';
            }

            if (tag === 'blockquote') {
                cleanElement.className = 'my-2 border-l-4 border-amber-300 pl-3 text-gray-700';
            }

            if (tag === 'table') {
                cleanElement.className = 'my-3 w-full border-collapse text-left text-sm';
            }

            if (tag === 'thead') {
                cleanElement.className = 'bg-gray-50';
            }

            if (tag === 'th') {
                cleanElement.className = 'border border-gray-200 px-3 py-2 font-semibold text-gray-700';
            }

            if (tag === 'td') {
                cleanElement.className = 'border border-gray-200 px-3 py-2 align-top text-gray-700';
            }

            node.childNodes.forEach((child) => {
                const cleanChild = sanitizeNode(child);
                if (cleanChild) {
                    cleanElement.appendChild(cleanChild);
                }
            });

            return cleanElement;
        }

        const wrapper = document.createElement('div');
        template.content.childNodes.forEach((child) => {
            const cleanChild = sanitizeNode(child);
            if (cleanChild) {
                wrapper.appendChild(cleanChild);
            }
        });

        return wrapper.innerHTML;
    }

    function setAssistantMessageContent(element, text) {
        if (!element) {
            return;
        }

        const value = normalizeAssistantRawText(text);
        const trimmed = value.trim();
        if (trimmed === '') {
            element.textContent = '';
            return;
        }

        if (looksLikeRichText(trimmed)) {
            const sanitizedRichText = sanitizeRichText(trimmed);
            if (sanitizedRichText !== '') {
                element.innerHTML = sanitizedRichText;
                return;
            }
        }

        element.innerHTML = renderMarkdown(trimmed);
    }

    function formatTimestamp(createdAt = null) {
        try {
            const value = createdAt ?? new Date().toISOString();

            return new Intl.DateTimeFormat('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date(value));
        } catch {
            return new Intl.DateTimeFormat('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date());
        }
    }

    function updateMessageTimestamp(wrapper, createdAt = null) {
        if (!wrapper) {
            return;
        }

        const timestampElement = wrapper.querySelector('[data-message-timestamp]');

        if (!timestampElement) {
            return;
        }

        timestampElement.textContent = formatTimestamp(createdAt);
    }

    function wireCopyButtons(scope) {
        const buttons = scope.querySelectorAll('.copyBtn');
        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const bubble = btn.closest('.group');
                const textNode = bubble?.querySelector('.agent-message-body, p.text-sm');
                const text = textNode ? textNode.innerText : '';
                navigator.clipboard.writeText(text);
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i>Copiado';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-regular fa-copy mr-1"></i>Copiar';
                }, 900);
            });
        });
    }

    function addMessage(type, text, options = {}) {
        if (!chatList) {
            return null;
        }

        const messageId = options.id ? Number.parseInt(String(options.id), 10) : null;
        if (messageId && renderedMessageIds.has(messageId)) {
            return null;
        }

        const wrapper = document.createElement('div');
        if (messageId) {
            wrapper.dataset.messageId = String(messageId);
            renderedMessageIds.add(messageId);
            lastMessageId = messageId;
        }

        const timestamp = formatTimestamp(options.createdAt);
        const toolbar = `
        <div class="absolute -top-3 right-2 opacity-0 group-hover:opacity-100 transition flex gap-1">
          <button class="copyBtn bg-white border border-gray-200 text-gray-600 hover:text-gray-900 hover:border-gray-300 rounded-md px-2 py-1 text-[10px] shadow-sm">
            <i class="fa-regular fa-copy mr-1"></i>Copiar
          </button>
        </div>
      `;

        if (type === 'assistant') {
            wrapper.className = 'flex items-start gap-4';
            wrapper.innerHTML = `
          <div class="w-9 h-9 rounded-full bg-amber-500 flex items-center justify-center text-white shrink-0 shadow-md text-sm">
            <i class="fa-solid fa-robot"></i>
          </div>
            <div class="relative group bg-white p-4 rounded-2xl rounded-tl-none border border-gray-200 shadow-sm max-w-[85%] text-gray-800">
            ${toolbar}
            <p class="font-bold text-amber-600 text-[10px] mb-1 uppercase tracking-wider">CodaFácil IA</p>
            <div class="agent-message-body text-sm leading-relaxed break-words [&_p]:mb-2 [&_p:last-child]:mb-0 [&_ul]:my-2 [&_ol]:my-2 [&_h1]:mt-2 [&_h1]:mb-1 [&_h2]:mt-2 [&_h2]:mb-1 [&_h3]:mt-2 [&_h3]:mb-1"></div>
            <p class="mt-2 text-[10px] text-gray-400" data-message-timestamp>${timestamp}</p>
          </div>
        `;
            const assistantBody = wrapper.querySelector('.agent-message-body');
            setAssistantMessageContent(assistantBody, text);
        } else if (type === 'user') {
            wrapper.className = 'flex items-start gap-4 flex-row-reverse';
            wrapper.innerHTML = `
          <div class="w-9 h-9 rounded-full bg-gray-800 flex items-center justify-center text-white shrink-0 shadow-md text-sm">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="relative group bg-amber-500 p-4 rounded-2xl rounded-tr-none shadow-sm max-w-[85%] text-white">
            ${toolbar}
            <p class="text-sm leading-relaxed whitespace-pre-wrap">${escapeHtml(text)}</p>
            <p class="mt-2 text-[10px] text-white/70 text-right" data-message-timestamp>${timestamp}</p>
          </div>
        `;
        } else {
            wrapper.className = 'flex justify-center';
            wrapper.innerHTML = `
          <div class="relative group w-full max-w-[92%] md:max-w-[85%] rounded-2xl border border-amber-200 bg-amber-50 shadow-sm p-4 border-l-4 border-amber-400">
            ${toolbar}
            <div class="flex items-start gap-3">
              <div class="mt-0.5 text-gray-500">
                <i class="fa-solid fa-circle-info"></i>
              </div>
              <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-900">Informação</p>
                <p class="mt-1 text-sm leading-relaxed text-amber-900 whitespace-pre-wrap">${escapeHtml(text)}</p>
              </div>
            </div>
          </div>
            `;
        }

        chatList.appendChild(wrapper);
        wireCopyButtons(wrapper);
        updateEmptyState();
        scrollToBottom();

        return wrapper;
    }

    function createPendingAssistantBubble() {
        if (!chatList || pendingAssistantWrapper) {
            return;
        }

        pendingAssistantWrapper = addMessage('assistant', '');
        const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
        if (assistantBody) {
            assistantBody.innerHTML = '<span class="inline-flex items-center gap-2 text-gray-400"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Processando...</span></span>';
        }
    }

    function removePendingAssistantBubble() {
        pendingAssistantWrapper?.remove();
        pendingAssistantWrapper = null;
        updateEmptyState();
    }

    function scrollToBottom() {
        const main = document.querySelector('main');
        if (!main) {
            return;
        }

        main.scrollTop = main.scrollHeight;
    }

    function setComposerLocked(locked) {
        if (composer) {
            composer.disabled = locked;
            composer.placeholder = locked
                ? 'Aguardando a resposta do agente...'
                : 'Escreva sua mensagem...';
        }

        if (submitButton) {
            if (locked) {
                submitButton.setAttribute('disabled', 'disabled');
            } else {
                submitButton.removeAttribute('disabled');
            }
        }

        if (chatStatusNotice) {
            chatStatusNotice.classList.toggle('hidden', !locked);
        }
    }

    function stopPolling() {
        isPolling = false;
        waitDeadlineAt = null;
        if (pollTimer) {
            window.clearTimeout(pollTimer);
            pollTimer = null;
        }
        setComposerLocked(false);
    }

    function startPolling(timeoutSeconds) {
        if (page !== 'chat' || !conversationId) {
            return;
        }

        waitTimeoutSeconds = Number.isFinite(timeoutSeconds) && timeoutSeconds > 0
            ? timeoutSeconds
            : waitTimeoutSeconds;
        waitDeadlineAt = Date.now() + (waitTimeoutSeconds * 1000);
        isPolling = true;
        setComposerLocked(true);
        createPendingAssistantBubble();
        void pollConversation();
    }

    async function pollConversation() {
        if (!isPolling || page !== 'chat' || !conversationId) {
            return;
        }

        if (waitDeadlineAt !== null && Date.now() >= waitDeadlineAt) {
            const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
            if (assistantBody) {
                setAssistantMessageContent(assistantBody, 'Nao foi possivel responder agora. Tente novamente em instantes.');
            }
            stopPolling();
            return;
        }

        const payload = await fetchConversation(true);
        if (!payload) {
            const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
            if (assistantBody) {
                setAssistantMessageContent(assistantBody, 'Nao foi possivel responder agora. Tente novamente.');
            }
            stopPolling();
            return;
        }

        if (payload.status === 'waiting') {
            createPendingAssistantBubble();
            pollTimer = window.setTimeout(() => {
                void pollConversation();
            }, pollingIntervalMs);
            return;
        }

        if (payload.status === 'error' && payload.messages.length === 0) {
            const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
            if (assistantBody) {
                setAssistantMessageContent(assistantBody, 'Nao foi possivel responder agora. Tente novamente em instantes.');
            }
        } else {
            removePendingAssistantBubble();
        }

        stopPolling();
    }

    function updateConversationUi(payload) {
        if (payload?.conversation_id) {
            setConversationId(payload.conversation_id);
        }

        if (Array.isArray(payload?.messages) && payload.messages.length > 0) {
            const hasAssistantMessage = payload.messages.some((message) => message?.role === 'assistant');
            if (hasAssistantMessage) {
                removePendingAssistantBubble();
            }

            payload.messages.forEach((message) => {
                if (!message || !message.role || !message.content) {
                    return;
                }

                addMessage(message.role, message.content, {
                    id: message.id ?? null,
                    createdAt: message.created_at ?? null,
                });
            });
        }

        void loadConversationList();
    }

    async function fetchConversation(incremental = false) {
        if (!conversationBaseUrl || !conversationId) {
            return null;
        }

        const baseUrl = conversationBaseUrl.replace(/\/$/, '');
        const params = new URLSearchParams();
        if (incremental && lastMessageId) {
            params.set('after', String(lastMessageId));
        }

        const query = params.toString();

        try {
            const response = await fetch(`${baseUrl}/${conversationId}${query ? `?${query}` : ''}`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                if (!incremental) {
                    clearConversationId();
                }
                return null;
            }

            const payload = await response.json();
            updateConversationUi(payload);
            return payload;
        } catch {
            return null;
        }
    }

    async function loadConversationHistory() {
        if (!chatList || !conversationId) {
            return;
        }

        chatList.innerHTML = '';
        renderedMessageIds.clear();
        lastMessageId = null;
        removePendingAssistantBubble();
        updateEmptyState();

        const payload = await fetchConversation(false);
        if (!payload) {
            return;
        }

        if (payload.status === 'waiting') {
            startPolling(waitTimeoutSeconds);
        } else {
            stopPolling();
        }
    }

    async function renameConversation(id, currentTitle) {
        if (!conversationBaseUrl || !csrfToken) {
            return;
        }

        const nextTitle = window.prompt('Defina o título da conversa:', currentTitle || 'Conversa');
        if (nextTitle === null) {
            return;
        }

        const normalizedTitle = nextTitle.trim();
        if (normalizedTitle === '') {
            return;
        }

        const baseUrl = conversationBaseUrl.replace(/\/$/, '');
        try {
            const response = await fetch(`${baseUrl}/${id}/titulo`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    title: normalizedTitle,
                }),
            });

            if (!response.ok) {
                return;
            }

            await loadConversationList();
        } catch {
            // ignore rename failures
        }
    }

    async function deleteConversation(id) {
        if (!deleteConversationBaseUrl || !csrfToken) {
            return;
        }

        const confirmed = window.confirm('Deseja apagar este chat? Esta ação não poderá ser desfeita.');
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(`${deleteConversationBaseUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                return;
            }

            if (conversationId === id) {
                stopPolling();
                clearConversationId();
                renderedMessageIds.clear();
                lastMessageId = null;
                removePendingAssistantBubble();
                if (chatList) {
                    chatList.innerHTML = '';
                }
                updateEmptyState();
            }

            await loadConversationList();
        } catch {
            // ignore delete failures
        }
    }

    function renderConversationList(conversations) {
        if (!conversationList || !conversationEmpty) {
            return;
        }

        conversationList.innerHTML = '';

        if (!Array.isArray(conversations) || conversations.length === 0) {
            conversationEmpty.classList.remove('hidden');
            return;
        }

        conversationEmpty.classList.add('hidden');

        conversations.forEach((conversation) => {
            if (!conversation || !conversation.id) {
                return;
            }

            const item = document.createElement('div');
            item.className = 'group flex items-start gap-1 rounded-lg px-2 py-1 transition hover:bg-gray-800/60';

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.dataset.conversationId = String(conversation.id);
            trigger.className = 'min-w-0 flex-1 text-left flex items-start gap-3 px-2 py-2 rounded-lg transition text-sm text-gray-400 hover:text-white';

            const title = conversation.title || 'Conversa';
            const preview = conversation.preview || '';
            const statusBadge = conversation.status === 'waiting'
                ? '<span class="mt-1 inline-flex rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-amber-300">Aguardando</span>'
                : '';

            trigger.innerHTML = `
                <i class="fa-regular fa-message text-gray-500 group-hover:text-white mt-0.5"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-current">${escapeHtml(title)}</p>
                    ${preview ? `<p class="mt-1 text-[11px] text-gray-500 group-hover:text-gray-300 truncate">${escapeHtml(preview)}</p>` : ''}
                    ${statusBadge}
                </div>
            `;

            trigger.addEventListener('click', () => {
                stopPolling();
                removePendingAssistantBubble();
                setConversationId(conversation.id);
                closeSidebar();

                if (page !== 'chat') {
                    if (chatUrl !== '') {
                        window.location.href = chatUrl;
                    }
                    return;
                }

                void loadConversationHistory();
            });

            item.appendChild(trigger);

            const renameBtn = document.createElement('button');
            renameBtn.type = 'button';
            renameBtn.className = 'mt-1 inline-flex h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-700 hover:text-white';
            renameBtn.setAttribute('aria-label', 'Renomear conversa');
            renameBtn.innerHTML = '<i class="fa-solid fa-pen text-[11px]"></i>';
            renameBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                void renameConversation(conversation.id, title);
            });

            item.appendChild(renameBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'mt-1 inline-flex h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 transition hover:bg-red-500/20 hover:text-red-300';
            deleteBtn.setAttribute('aria-label', 'Apagar conversa');
            deleteBtn.innerHTML = '<i class="fa-solid fa-trash text-[11px]"></i>';
            deleteBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                void deleteConversation(conversation.id);
            });

            item.appendChild(deleteBtn);
            conversationList.appendChild(item);
        });

        updateActiveConversation();
    }

    async function loadConversationList() {
        if (!conversationsEndpoint || isLoadingConversations || !conversationList || !conversationEmpty) {
            return;
        }

        isLoadingConversations = true;
        try {
            const response = await fetch(conversationsEndpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            renderConversationList(payload.conversations || []);
        } catch {
            // ignore errors
        } finally {
            isLoadingConversations = false;
        }
    }

    function autoResize() {
        if (!composer) {
            return;
        }

        composer.style.height = 'auto';
        composer.style.height = `${Math.min(composer.scrollHeight, 160)}px`;
    }

    function applyPrompt(prompt) {
        if (!composer) {
            return;
        }

        composer.value = prompt;
        autoResize();
        composer.focus();
    }

    function consumePromptFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const prompt = params.get('prompt');
        if (!prompt || !prompt.trim()) {
            return null;
        }

        params.delete('prompt');
        const query = params.toString();
        const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;
        window.history.replaceState({}, document.title, nextUrl);

        return prompt.trim();
    }

    function syncToggleChipsUi() {
        if (!toggleChips || !toggleChipsDot) {
            return;
        }

        const on = toggleChips.checked;
        quickChipsRow?.classList.toggle('hidden', !on);
        toggleChips.parentElement.classList.toggle('bg-amber-500', on);
        toggleChips.parentElement.classList.toggle('bg-gray-200', !on);
        toggleChipsDot.style.transform = on ? 'translateX(16px)' : 'translateX(0px)';
    }

    toggleChips?.addEventListener('change', syncToggleChipsUi);

    document.querySelectorAll('.fontBtn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const font = btn.getAttribute('data-font');
            if (!composer) {
                return;
            }

            composer.classList.remove('text-sm', 'text-base', 'text-lg');
            if (font === 'sm') {
                composer.classList.add('text-sm');
            }
            if (font === 'base') {
                composer.classList.add('text-base');
            }
            if (font === 'lg') {
                composer.classList.add('text-lg');
            }
        });
    });

    resetUiBtn?.addEventListener('click', () => {
        if (toggleChips) {
            toggleChips.checked = true;
        }
        syncToggleChipsUi();
        if (composer) {
            composer.classList.remove('text-base', 'text-lg');
            composer.classList.add('text-sm');
        }
    });

    composer?.addEventListener('input', autoResize);

    composer?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            composerForm?.requestSubmit();
        }
    });

    composerForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (isPolling || !composer) {
            return;
        }

        const text = composer.value.trim();
        if (!text) {
            return;
        }

        const userWrapper = addMessage('user', text, {
            createdAt: new Date().toISOString(),
        });
        if (!userWrapper) {
            return;
        }

        composer.value = '';
        autoResize();
        createPendingAssistantBubble();

        if (!endpoint || !csrfToken) {
            const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
            if (assistantBody) {
                setAssistantMessageContent(assistantBody, 'Nao foi possivel iniciar o atendimento agora. Tente novamente.');
            }
            return;
        }

        setComposerLocked(true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId,
                }),
            });

            if (!response.ok) {
                let errorMessage = `Erro HTTP ${response.status}.`;
                const contentType = response.headers.get('Content-Type') || '';

                if (contentType.includes('application/json')) {
                    try {
                        const errorPayload = await response.json();
                        errorMessage = errorPayload.error || errorPayload.message || errorMessage;
                    } catch {
                        // ignore json parse failure
                    }
                }

                const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
                if (assistantBody) {
                    setAssistantMessageContent(assistantBody, errorMessage);
                }
                stopPolling();
                return;
            }

            const payload = await response.json();
            if (payload.conversation_id) {
                setConversationId(payload.conversation_id);
            }
            if (payload.message_id) {
                lastMessageId = payload.message_id;
                const parsedMessageId = Number.parseInt(String(payload.message_id), 10);
                if (!Number.isNaN(parsedMessageId)) {
                    renderedMessageIds.add(parsedMessageId);
                    userWrapper.dataset.messageId = String(parsedMessageId);
                }
            }
            updateMessageTimestamp(userWrapper, payload.message_created_at ?? null);

            waitTimeoutSeconds = Number.parseInt(String(payload.wait_timeout_seconds || waitTimeoutSeconds), 10) || waitTimeoutSeconds;
            startPolling(waitTimeoutSeconds);
        } catch {
            const assistantBody = pendingAssistantWrapper?.querySelector('.agent-message-body');
            if (assistantBody) {
                setAssistantMessageContent(assistantBody, 'Nao foi possivel responder agora. Tente novamente.');
            }
            stopPolling();
        }
    });

    document.querySelectorAll('.quick-chip, .empty-chip').forEach((btn) => {
        btn.addEventListener('click', () => {
            const prompt = btn.getAttribute('data-prompt') || '';
            applyPrompt(prompt);
        });
    });

    newChatBtn?.addEventListener('click', (event) => {
        stopPolling();
        clearConversationId();
        renderedMessageIds.clear();
        lastMessageId = null;
        removePendingAssistantBubble();

        if (page === 'chat' && chatList) {
            chatList.innerHTML = '';
            updateEmptyState();
            composer?.focus();
            event.preventDefault();
        }
    });

    function renderSelectedFiles() {
        if (!fileList || !fileEmptyState) {
            return;
        }

        fileList.innerHTML = '';
        fileEmptyState.classList.toggle('hidden', selectedFiles.length > 0);

        selectedFiles.forEach((file) => {
            const item = document.createElement('div');
            item.className = 'rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm';
            item.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">${escapeHtml(file.name)}</p>
                        <p class="mt-1 text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB • PDF</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-amber-700">Pronto</span>
                </div>
            `;
            fileList.appendChild(item);
        });

        updateFileActionButton();
    }

    function appendSelectedFiles(fileCollection) {
        const incomingFiles = Array.from(fileCollection || []);
        const nextSelectedFiles = [...selectedFiles];
        let nextTotalBytes = selectedFilesTotalBytes(nextSelectedFiles);
        const rejectedFiles = [];

        incomingFiles.forEach((file) => {
            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            if (!isPdf) {
                rejectedFiles.push(file.name);
                return;
            }

            if (uploadMaxFileBytes > 0 && Number(file.size || 0) > uploadMaxFileBytes) {
                rejectedFiles.push(file.name);
                return;
            }

            const exists = nextSelectedFiles.some((selectedFile) => (
                selectedFile.name === file.name
                && selectedFile.size === file.size
                && selectedFile.lastModified === file.lastModified
            ));

            if (!exists) {
                const candidateTotalBytes = nextTotalBytes + Number(file.size || 0);

                if (uploadMaxRequestBytes > 0 && candidateTotalBytes > uploadMaxRequestBytes) {
                    rejectedFiles.push(file.name);
                    return;
                }

                nextSelectedFiles.push(file);
                nextTotalBytes = candidateTotalBytes;
            }
        });

        selectedFiles = nextSelectedFiles;
        renderSelectedFiles();

        if (rejectedFiles.length === 0) {
            if (incomingFiles.length > 0) {
                setFileUploadNotice('');
            }

            return;
        }

        const preview = rejectedFiles.slice(0, 3).join(', ');
        const remainingCount = rejectedFiles.length - Math.min(rejectedFiles.length, 3);
        const remaining = remainingCount > 0 ? ` e mais ${remainingCount}` : '';

        setFileUploadNotice(`Nao foi possivel adicionar ${preview}${remaining}. O agente aceita apenas PDFs de ate ${getUploadMaxFileLabel()} por arquivo e ${getUploadMaxRequestLabel()} por envio.`);
    }

    function updateFileActionButton() {
        if (!fileActionBtn) {
            return;
        }

        const disabled = selectedFiles.length === 0 || isSubmittingUploads;
        fileActionBtn.disabled = disabled;
        fileActionBtn.classList.toggle('opacity-60', disabled);
        fileActionBtn.textContent = isSubmittingUploads ? 'Recebendo arquivos...' : 'Enviar PDFs ao Drive';
    }

    function formatBytes(bytes) {
        const size = Number(bytes || 0);

        if (size <= 0) {
            return '0 KB';
        }

        if (size >= 1024 * 1024) {
            return `${(size / 1024 / 1024).toFixed(2)} MB`;
        }

        return `${Math.max(1, Math.round(size / 1024))} KB`;
    }

    function getUploadMaxFileLabel() {
        if (uploadMaxFileLabel) {
            return uploadMaxFileLabel;
        }

        return uploadMaxFileBytes > 0 ? formatBytes(uploadMaxFileBytes) : '0 KB';
    }

    function getUploadMaxRequestLabel() {
        if (uploadMaxRequestLabel) {
            return uploadMaxRequestLabel;
        }

        return uploadMaxRequestBytes > 0 ? formatBytes(uploadMaxRequestBytes) : '0 KB';
    }

    function selectedFilesTotalBytes(files = selectedFiles) {
        return (files || []).reduce((total, file) => total + Number(file?.size || 0), 0);
    }

    function extractUploadErrorMessage(payload) {
        if (!payload || typeof payload !== 'object') {
            return '';
        }

        if (typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message.trim();
        }

        if (!payload.errors || typeof payload.errors !== 'object') {
            return '';
        }

        const firstGroup = Object.values(payload.errors).find((messages) => Array.isArray(messages) && messages.length > 0);

        if (!Array.isArray(firstGroup)) {
            return '';
        }

        const [firstMessage] = firstGroup;

        return typeof firstMessage === 'string' ? firstMessage : '';
    }

    async function parseErrorPayload(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            return null;
        }

        return response.json().catch(() => null);
    }

    function formatDateTime(value) {
        if (!value) {
            return '--';
        }

        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return '--';
        }

        return parsed.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function uploadStatusLabel(status) {
        if (status === 'processed') {
            return 'Processado';
        }

        if (status === 'failed') {
            return 'Falhou';
        }

        return 'Processando';
    }

    function uploadStatusClasses(status) {
        if (status === 'processed') {
            return 'bg-emerald-50 text-emerald-700';
        }

        if (status === 'failed') {
            return 'bg-red-50 text-red-700';
        }

        return 'bg-amber-50 text-amber-700';
    }

    async function deleteFailedUpload(id, button) {
        if (!uploadDeleteBaseUrl || !csrfToken) {
            return;
        }

        const confirmed = window.confirm('Deseja apagar este item falhado? Esta ação não poderá ser desfeita.');
        if (!confirmed) {
            return;
        }

        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-not-allowed');

        try {
            const response = await fetch(`${uploadDeleteBaseUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                button.disabled = false;
                button.classList.remove('opacity-60', 'cursor-not-allowed');
                return;
            }

            const parentCard = button.closest('[data-upload-item-id]');
            parentCard?.remove();

            if (uploadHistoryList && uploadHistoryEmpty && uploadHistoryList.children.length === 0) {
                uploadHistoryEmpty.classList.remove('hidden');
            }
        } catch {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    }

    function renderUploadHistory(uploads) {
        if (!uploadHistoryList || !uploadHistoryEmpty) {
            return;
        }

        uploadHistoryList.innerHTML = '';
        uploadHistoryEmpty.classList.toggle('hidden', Array.isArray(uploads) && uploads.length > 0);

        (uploads || []).forEach((upload) => {
            const item = document.createElement('article');
            item.dataset.uploadItemId = String(upload.id || '');
            item.className = 'rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm';
            item.innerHTML = `
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(upload.original_name || 'Arquivo.pdf')}</p>
                        <p class="mt-1 text-xs text-gray-500">Operador: ${escapeHtml(upload.uploaded_by || 'Sistema')}</p>
                        <p class="mt-1 text-xs text-gray-500">Enviado em ${escapeHtml(formatDateTime(upload.created_at))} • ${escapeHtml(formatBytes(upload.size_bytes))}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] ${uploadStatusClasses(upload.status)}">
                        ${uploadStatusLabel(upload.status)}
                    </span>
                </div>
                ${upload.error_message ? `<p class="mt-3 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-700">${escapeHtml(upload.error_message)}</p>` : ''}
                ${upload.can_delete ? `
                    <div class="mt-3 flex justify-end">
                        <button type="button"
                                data-upload-delete-id="${escapeHtml(upload.id)}"
                                class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                                aria-label="Apagar item falhado">
                            <i class="fa-solid fa-trash text-[11px]"></i>
                            Apagar item
                        </button>
                    </div>
                ` : ''}
            `;
            uploadHistoryList.appendChild(item);
        });

    }

    function scheduleUploadPolling() {
        if (uploadPollTimer) {
            window.clearTimeout(uploadPollTimer);
        }

        if (page !== 'files') {
            return;
        }

        uploadPollTimer = window.setTimeout(() => {
            void loadUploadHistory();
        }, pollingIntervalMs);
    }

    async function loadUploadHistory() {
        if (!uploadsEndpoint || isLoadingUploads) {
            scheduleUploadPolling();
            return;
        }

        isLoadingUploads = true;

        try {
            const response = await fetch(uploadsEndpoint, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                scheduleUploadPolling();
                return;
            }

            const payload = await response.json();
            renderUploadHistory(payload.uploads || []);
        } catch {
            // ignore polling failures
        } finally {
            isLoadingUploads = false;
            scheduleUploadPolling();
        }
    }

    async function submitSelectedFiles() {
        if (!fileUploadEndpoint || !csrfToken || selectedFiles.length === 0 || isSubmittingUploads) {
            return;
        }

        const totalBytes = selectedFilesTotalBytes();
        const hasOversizedFile = uploadMaxFileBytes > 0
            && selectedFiles.some((file) => Number(file.size || 0) > uploadMaxFileBytes);

        if (hasOversizedFile) {
            setFileUploadNotice(`Cada PDF deve ter no maximo ${getUploadMaxFileLabel()}.`);
            return;
        }

        if (uploadMaxRequestBytes > 0 && totalBytes > uploadMaxRequestBytes) {
            setFileUploadNotice(`O envio total deve ter no maximo ${getUploadMaxRequestLabel()}.`);
            return;
        }

        isSubmittingUploads = true;
        updateFileActionButton();
        setFileUploadNotice('');

        const formData = new FormData();
        selectedFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await fetch(fileUploadEndpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            if (response.ok) {
                selectedFiles = [];
                renderSelectedFiles();
                setFileUploadNotice('');
                void loadUploadHistory();
                return;
            }

            const payload = await parseErrorPayload(response);
            const message = extractUploadErrorMessage(payload);

            if (response.status === 413) {
                setFileUploadNotice(message || `O servidor recusou o envio. O limite atual e ${getUploadMaxRequestLabel()} por envio.`);
                return;
            }

            setFileUploadNotice(message || 'Nao foi possivel enviar os arquivos agora. Tente novamente.');
        } catch {
            setFileUploadNotice('Falha de conexao ao enviar os arquivos. Tente novamente.');
        } finally {
            isSubmittingUploads = false;
            updateFileActionButton();
        }
    }

    filePickerTrigger?.addEventListener('click', () => {
        filePickerInput?.click();
    });

    filePickerInput?.addEventListener('change', (event) => {
        const files = event.target.files;
        if (!files) {
            return;
        }

        appendSelectedFiles(files);
        event.target.value = '';
    });

    fileDropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        fileDropzone.classList.add('border-amber-500', 'bg-amber-50');
    });

    fileDropzone?.addEventListener('dragleave', () => {
        fileDropzone.classList.remove('border-amber-500', 'bg-amber-50');
    });

    fileDropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        fileDropzone.classList.remove('border-amber-500', 'bg-amber-50');
        if (event.dataTransfer?.files) {
            appendSelectedFiles(event.dataTransfer.files);
        }
    });

    fileActionBtn?.addEventListener('click', () => {
        void submitSelectedFiles();
    });

    uploadRefreshBtn?.addEventListener('click', () => {
        void loadUploadHistory();
    });

    uploadHistoryList?.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target.closest('[data-upload-delete-id]') : null;
        if (!(target instanceof HTMLButtonElement)) {
            return;
        }

        const id = Number.parseInt(target.dataset.uploadDeleteId || '', 10);
        if (Number.isNaN(id) || id <= 0) {
            return;
        }

        void deleteFailedUpload(id, target);
    });

    const initialPrompt = consumePromptFromUrl();
    const savedConversationId = getCookie(conversationCookieKey);

    if (savedConversationId) {
        setConversationId(savedConversationId);
    }

    if (page === 'chat') {
        if (initialPrompt) {
            clearConversationId();
            renderedMessageIds.clear();
            lastMessageId = null;
            applyPrompt(initialPrompt);
            composerForm?.requestSubmit();
        } else if (conversationId) {
            void loadConversationHistory();
        }
    }

    updateEmptyState();
    autoResize();
    syncToggleChipsUi();
    renderSelectedFiles();
    updateFileActionButton();
    void loadConversationList();

    if (page === 'files') {
        void loadUploadHistory();
    }
});
