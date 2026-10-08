// The demo is static files, so this script answers what the server would:
// search suggestions, the search page, and "Was this article helpful?"
// (remembered in this browser only). window.HELP_INDEX is written by build.php.
(() => {
    const articles = window.HELP_INDEX || [];
    const base = window.HELP_BASE || '/';

    const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const escapeRegExp = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const slugify = (text) => text.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
    const wordsOf = (query) => [...new Set(query.toLowerCase().trim().split(/\s+/).filter(Boolean))].sort((a, b) => b.length - a.length);

    const highlight = (text, words) => {
        if (words.length === 0) return escapeHtml(text);
        const pattern = new RegExp('(' + words.map(escapeRegExp).join('|') + ')', 'giu');
        return text.split(pattern).map((part, i) => (i % 2 ? '<mark>' + escapeHtml(part) + '</mark>' : escapeHtml(part))).join('');
    };

    const snippet = (article, words) => {
        const body = article.body;
        const lower = body.toLowerCase();
        for (const word of words) {
            const at = lower.indexOf(word);
            if (at >= 0) {
                const start = Math.max(0, at - 90);
                const end = Math.min(body.length, at + word.length + 90);
                let text = body.slice(start, end).trim();
                // Start and end on whole words, like the server.
                if (start > 0) text = '…' + text.replace(/^\S*\s+/, '');
                if (end < body.length) text = text.replace(/\s+\S*$/, '') + '…';
                return highlight(text, words);
            }
        }
        return highlight(article.excerpt, words);
    };

    // Same rules as the server: every word must appear; title matches first.
    const search = (query) => {
        const words = wordsOf(query);
        if (words.length === 0) return [];
        const phrase = query.toLowerCase().trim();
        const haystack = (a) => (a.title + ' ' + a.excerpt + ' ' + a.body).toLowerCase();
        const rank = (a) => (a.title.toLowerCase().includes(phrase) ? 0 : words.some((w) => a.title.toLowerCase().includes(w)) ? 1 : 2);
        return articles
            .filter((a) => words.every((w) => haystack(a).includes(w)))
            .sort((a, b) => rank(a) - rank(b) || b.views - a.views || a.title.localeCompare(b.title));
    };

    window.HelpCenterDemo = {
        suggest(query) {
            const words = wordsOf(query);
            return {
                query,
                results: search(query).slice(0, 6).map((a) => ({
                    title: a.title,
                    title_html: highlight(a.title, words),
                    url: a.url,
                    category: a.category,
                    snippet_html: snippet(a, words),
                })),
                all_url: base + 'search/?q=' + encodeURIComponent(query) + '&all=1',
            };
        },
    };

    // The search page.
    document.addEventListener('DOMContentLoaded', () => {
        if (!document.querySelector('.hc-results')) return;
        const params = new URLSearchParams(location.search);
        const query = (params.get('q') || '').trim().slice(0, 200);
        if (query === '') return;

        const exact = articles.find((a) => a.slug === slugify(query) || a.title.toLowerCase() === query.toLowerCase());
        if (exact && !params.has('all')) {
            location.replace(exact.url);
            return;
        }

        const words = wordsOf(query);
        const hits = search(query);
        document.title = 'Search: ' + query + ' · ' + document.title.split(' · ').pop();
        document.querySelector('.hc-bar .hc-search input').value = query;
        document.querySelector('.hc-main h1').textContent = 'Results for “' + query + '”';

        const results = document.querySelector('.hc-results');
        if (hits.length === 0) {
            results.innerHTML = '<p class="hc-empty" style="margin-top: 2rem">No articles match “' + escapeHtml(query) + '”. Try fewer or different words, or browse the topics on the left.</p>';
            return;
        }

        const count = document.createElement('span');
        count.className = 'hc-muted';
        count.textContent = hits.length + (hits.length === 1 ? ' article' : ' articles');
        document.querySelector('.hc-main .hc-head').append(count);

        results.innerHTML = '<ul class="hc-list">' + hits.map((a) =>
            '<li><a href="' + escapeHtml(a.url) + '">' + highlight(a.title, words) + '</a>'
            + (a.category ? '<span class="hc-chip">' + escapeHtml(a.category) + '</span>' : '')
            + '<span class="hc-list-text">' + snippet(a, words) + '</span></li>').join('') + '</ul>';
    });
})();
