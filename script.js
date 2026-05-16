// Navigation Function
function navigate(pageId) {
    // Hide all pages
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    
    // Show target page
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.add('active');
    }

    // Update Nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-page') === pageId) {
            link.classList.add('active');
        }
    });

    // Close mobile menu if open
    document.getElementById('mobileMenu').classList.remove('open');
    
    // Scroll to top
    window.scrollTo(0, 0);
}

// Theme Toggle Logic
const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
    });
}

// Mobile Menu Toggle
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('open');
}

// Toast System
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Initialize application
document.addEventListener('DOMContentLoaded', () => {
    console.log("LiboLibrary Frontend Ready");
    initReaderPage();
});

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function splitTextToParagraphs(text) {
    return String(text ?? '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n')
        .split(/\n{2,}/)
        .map((p) => p.trim())
        .filter((p) => p.length > 0);
}

function renderPlainTextToHtml(text) {
    const paragraphs = splitTextToParagraphs(text);
    if (!paragraphs.length) {
        return '<p>No readable text was found for this book.</p>';
    }
    return paragraphs.map((p) => `<p>${escapeHtml(p).replace(/\n/g, '<br>')}</p>`).join('');
}

async function fetchBookData(id, gid) {
    if (!id && !gid) {
        throw new Error('Missing book identifiers (id/gid).');
    }
    const query = id ? `id=${encodeURIComponent(id)}` : `gid=${encodeURIComponent(String(gid))}`;
    const response = await fetch(`api/book.php?${query}`);
    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }
    const payload = await response.json();
    if (!payload?.ok || !payload?.book) {
        throw new Error(payload?.error || 'Book not found');
    }
    return payload.book;
}

async function fetchGutenbergText(gid) {
    if (!gid) return null;
    const idStr = String(gid);
    // Demo-friendly: load from local books_data instead of external URL.
    const url = `books_data/${encodeURIComponent(idStr)}.txt`;
    try {
        const response = await fetch(url);
        if (!response.ok) return null;
        const text = await response.text();
        return text && text.trim().length > 0 ? text : null;
    } catch (_) {
        return null;
    }
}

function setReaderStatus(message) {
    const statusEl = document.getElementById('readerStatus');
    if (statusEl) statusEl.textContent = message;
}

function initReaderPage() {
    const container = document.getElementById('readerContent');
    if (!container) return; // Not on reader.html

    const params = new URLSearchParams(window.location.search);
    const id = params.get('id') || '';
    const gid = params.get('gid') || '';
    const titleFromQuery = params.get('title') || 'Reader';
    const titleEl = document.getElementById('readerTitle');

    if (titleEl) titleEl.textContent = titleFromQuery;
    container.innerHTML = `
      <div class="reader-loading">
        <div class="spinner"></div>
        <p>Loading book content...</p>
      </div>
    `;
    setReaderStatus('Loading book content...');

    if (!gid) {
        container.innerHTML = `<p>Missing <b>gutenberg_id</b> (gid) in the URL.</p>`;
        setReaderStatus('Failed to load content');
        return;
    }

    (async () => {
        try {
            // Try backend first so we can use stored HTML when it exists.
            const book = await fetchBookData(id, gid);
            if (titleEl) titleEl.textContent = book.title || titleFromQuery;

            if (book.content_html && String(book.content_html).trim()) {
                container.innerHTML = String(book.content_html);
                setReaderStatus(`Loaded book ${book.id || ''}`.trim());
                return;
            }

            setReaderStatus('Fetching text from Project Gutenberg...');
            container.innerHTML = `
              <div class="reader-loading">
                <div class="spinner"></div>
                <p>Downloading the book text...</p>
              </div>
            `;
            const remoteText = await fetchGutenbergText(book.gutenberg_id || gid);
            container.innerHTML = renderPlainTextToHtml(remoteText || '');
            setReaderStatus(`Loaded Gutenberg text (${gid})`);
        } catch (error) {
            // If backend fails (or content_html is empty), still show Gutenberg text directly.
            try {
                setReaderStatus('Fetching text from Project Gutenberg...');
                container.innerHTML = `
                  <div class="reader-loading">
                    <div class="spinner"></div>
                    <p>Downloading the book text...</p>
                  </div>
                `;
                const remoteText = await fetchGutenbergText(gid);
                container.innerHTML = renderPlainTextToHtml(remoteText || '');
                setReaderStatus(`Loaded Gutenberg text (${gid})`);
            } catch (error2) {
                container.innerHTML = `<p>Could not load book content.</p><p style="opacity:.8">${escapeHtml(error?.message || error2?.message || 'Unknown error')}</p>`;
                setReaderStatus('Failed to load content');
            }
        }
    })();
}