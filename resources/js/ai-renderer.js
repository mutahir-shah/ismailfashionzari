// resources/js/ai-renderer.js

// --- DOM Safety Helpers ---
function textNode(str) {
    return document.createTextNode(str || '');
}

function createElement(tag, className = '', text = '') {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text) el.appendChild(textNode(text));
    return el;
}

function isSafeUrl(url) {
    if (!url || typeof url !== 'string') return false;
    // Allow relative URLs starting with / or #
    if (url.startsWith('/') || url.startsWith('#')) return true;
    try {
        const base = (typeof window !== 'undefined' && window.location && window.location.origin && window.location.origin !== 'null') 
            ? window.location.origin 
            : 'http://localhost';
        const parsed = new URL(url, base);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch (e) {
        return false;
    }
}

// Ensure svgs object exists globally or is passed in.
const defaultSvgs = {
    alert: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    link: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>',
    error: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
};

function renderAIMessage(role, content, responseType = 'text', metadata = null, svgs = defaultSvgs, labels = {}) {
    const msgWrapper = createElement('div', 'ai-message ai-message-' + role);
    const bubble = createElement('div', 'ai-bubble');
    
    // Main content
    if (content) {
        bubble.appendChild(textNode(content));
    }

    // Render structured metadata safely
    if (role === 'assistant' && metadata) {
        const structuredContainer = createElement('div', 'ai-structured-content');
        
        // Warnings
        if (Array.isArray(metadata.warnings) && metadata.warnings.length > 0) {
            metadata.warnings.forEach(warn => {
                const warnEl = createElement('div', 'ai-warning');
                warnEl.innerHTML = svgs.alert;
                warnEl.appendChild(textNode(' ' + warn));
                structuredContainer.appendChild(warnEl);
            });
        }
        
        // Errors (top-level)
        if (Array.isArray(metadata.errors) && metadata.errors.length > 0) {
            metadata.errors.forEach(err => {
                const errEl = createElement('div', 'ai-error');
                errEl.innerHTML = svgs.error;
                errEl.appendChild(textNode(' ' + err));
                structuredContainer.appendChild(errEl);
            });
        }
        
        // Fail closed reason / Meta Errors
        if (metadata.metadata && metadata.metadata.failed_closed) {
            const errEl = createElement('div', 'ai-error');
            errEl.innerHTML = svgs.error;
            const reason = metadata.metadata.reason || labels.restrictedScope || '';
            errEl.appendChild(textNode(' ' + (labels.requestBlocked || '') + ' ' + reason));
            structuredContainer.appendChild(errEl);
        }

        // Cards
        if (Array.isArray(metadata.cards) && metadata.cards.length > 0) {
            const grid = createElement('div', 'ai-cards-grid');
            metadata.cards.forEach(card => {
                const cardEl = createElement('div', 'ai-card');
                cardEl.appendChild(createElement('div', 'ai-card-title', String(card.title || '')));
                cardEl.appendChild(createElement('div', 'ai-card-value', String(card.value || '')));
                grid.appendChild(cardEl);
            });
            structuredContainer.appendChild(grid);
        }

        // Table (using table.columns and table.rows)
        if (metadata.table && Array.isArray(metadata.table.columns) && Array.isArray(metadata.table.rows)) {
            if (metadata.table.rows.length > 0) {
                const tableWrapper = createElement('div', 'ai-table-wrapper');
                const table = createElement('table', 'ai-table');
                
                const thead = createElement('thead');
                const trHead = createElement('tr');
                metadata.table.columns.forEach(col => {
                    const th = createElement('th', '', String(col));
                    trHead.appendChild(th);
                });
                thead.appendChild(trHead);
                table.appendChild(thead);
                
                const tbody = createElement('tbody');
                metadata.table.rows.forEach(row => {
                    const tr = createElement('tr');
                    // Handle array or object rows
                    if (Array.isArray(row)) {
                        row.forEach(cell => {
                            const td = createElement('td', '', String(cell !== null ? cell : ''));
                            tr.appendChild(td);
                        });
                    } else if (typeof row === 'object' && row !== null) {
                        metadata.table.columns.forEach(col => {
                            const cell = row[col] !== undefined ? row[col] : '';
                            const td = createElement('td', '', String(cell !== null ? cell : ''));
                            tr.appendChild(td);
                        });
                    }
                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                tableWrapper.appendChild(table);
                structuredContainer.appendChild(tableWrapper);
            }
        }

        // Top-level Links
        if (Array.isArray(metadata.links)) {
            metadata.links.forEach(link => {
                if (link.label && link.url && isSafeUrl(link.url)) {
                    const linkWrap = createElement('div', 'ai-link');
                    const a = createElement('a');
                    a.href = link.url;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    a.innerHTML = svgs.link;
                    a.appendChild(textNode(' ' + String(link.label)));
                    linkWrap.appendChild(a);
                    structuredContainer.appendChild(linkWrap);
                }
            });
        }

        if (structuredContainer.childNodes.length > 0) {
            bubble.appendChild(structuredContainer);
        }
    }

    msgWrapper.appendChild(bubble);
    return msgWrapper;
}

function renderAIError(message, svgs = defaultSvgs) {
    const msgWrapper = createElement('div', 'ai-message ai-message-assistant');
    const bubble = createElement('div', 'ai-bubble');
    
    const errEl = createElement('div', 'ai-error');
    errEl.innerHTML = svgs.error;
    errEl.appendChild(textNode(' ' + String(message)));
    
    bubble.appendChild(errEl);
    msgWrapper.appendChild(bubble);
    return msgWrapper;
}

// Export for Node/Jest if running in a module environment
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        textNode,
        createElement,
        isSafeUrl,
        renderAIMessage,
        renderAIError,
        defaultSvgs
    };
} 

if (typeof window !== 'undefined') {
    window.renderAIMessage = renderAIMessage;
    window.renderAIError = renderAIError;
    window.defaultSvgs = defaultSvgs;
}
