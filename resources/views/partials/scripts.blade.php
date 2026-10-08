<script>
    // Progressive enhancement only: every feature here also works as a plain
    // link or form without JavaScript.
    (() => {
        const root = document.getElementById('help-center');
        if (!root || root.dataset.enhanced) return;
        root.dataset.enhanced = '1';

        // A static demo can answer instead of the server (see demo/).
        const demo = window.HelpCenterDemo || null;

        // On small screens the menu starts folded so the article comes first.
        if (matchMedia('(max-width: 63.99rem)').matches) root.querySelector('.hc-menu')?.removeAttribute('open');

        // Search suggestions as you type.
        root.querySelectorAll('input[data-help-suggest]').forEach((input) => {
            const list = document.getElementById(input.getAttribute('aria-controls'));
            let timer, pending, active = -1;

            const options = () => [...list.querySelectorAll('[role=option]')];
            const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
            const highlight = (index) => {
                const items = options();
                items.forEach((item, i) => item.classList.toggle('is-active', i === index));
                active = index;
                if (items[index]) input.setAttribute('aria-activedescendant', items[index].id);
                else input.removeAttribute('aria-activedescendant');
            };
            const el = (tag, className, html) => { const node = document.createElement(tag); node.className = className; if (html !== undefined) node.innerHTML = html; return node; };

            const show = (data) => {
                list.replaceChildren();
                if (data.results.length === 0) {
                    const empty = el('p', 'hc-suggest-empty');
                    empty.textContent = 'No articles match yet. Press Enter to search everything.';
                    list.append(empty);
                }
                data.results.forEach((result, i) => {
                    const link = el('a', '');
                    link.href = result.url;
                    link.id = list.id + '-' + i;
                    link.setAttribute('role', 'option');
                    // title_html and snippet_html arrive escaped, with <mark> around matches.
                    link.append(el('span', 'hc-suggest-title', result.title_html));
                    link.append(el('span', 'hc-suggest-meta', (result.category ? escapeHtml(result.category) + ' · ' : '') + result.snippet_html));
                    list.append(link);
                });
                if (data.results.length > 0) {
                    const all = el('a', 'hc-suggest-all');
                    all.href = data.all_url;
                    all.id = list.id + '-all';
                    all.setAttribute('role', 'option');
                    all.textContent = 'See all results for “' + data.query + '”';
                    list.append(all);
                }
                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
                active = -1;
            };

            const escapeHtml = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

            input.addEventListener('input', () => {
                clearTimeout(timer);
                const query = input.value.trim();
                if (query.length < 2) { pending?.abort(); close(); return; }
                timer = setTimeout(async () => {
                    pending?.abort();
                    pending = new AbortController();
                    try {
                        const data = demo
                            ? demo.suggest(query)
                            : await (await fetch(input.dataset.helpSuggest + '?q=' + encodeURIComponent(query), { signal: pending.signal, headers: { Accept: 'application/json' } })).json();
                        if (input.value.trim() === query) show(data);
                    } catch (error) {
                        if (error.name !== 'AbortError') close();
                    }
                }, 160);
            });

            input.addEventListener('keydown', (event) => {
                const items = options();
                if (list.hidden || items.length === 0) return;
                if (event.key === 'ArrowDown') { event.preventDefault(); highlight((active + 1) % items.length); }
                else if (event.key === 'ArrowUp') { event.preventDefault(); highlight(active <= 0 ? items.length - 1 : active - 1); }
                else if (event.key === 'Enter' && items[active]) { event.preventDefault(); location.href = items[active].href; }
                else if (event.key === 'Escape') { close(); }
            });

            input.addEventListener('blur', () => setTimeout(close, 150));
            input.addEventListener('focus', () => { if (list.childElementCount > 0 && input.value.trim().length >= 2) { list.hidden = false; input.setAttribute('aria-expanded', 'true'); } });
        });

        // "Was this article helpful?" without leaving the page.
        const box = root.querySelector('.hc-feedback');
        if (box) {
            const key = 'help-feedback:' + box.dataset.article;
            if (demo) {
                try { box.dataset.state = localStorage.getItem(key) || box.dataset.state; } catch (error) {}
            }

            box.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const data = new FormData(form, event.submitter);
                    let state;
                    if (demo) {
                        state = data.get('helpful') === '1' ? 'yes' : (String(data.get('comment') || '').trim() ? 'done' : 'no');
                        try { localStorage.setItem(key, state); } catch (error) {}
                    } else {
                        const fallback = () => {
                            // Let the browser post the form the ordinary way.
                            if (event.submitter?.name) form.append(Object.assign(document.createElement('input'), { type: 'hidden', name: event.submitter.name, value: event.submitter.value }));
                            form.submit();
                        };
                        try {
                            const response = await fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
                            if (!response.ok) return fallback();
                            state = (await response.json()).state;
                        } catch (error) {
                            return fallback();
                        }
                    }
                    box.dataset.state = state;
                    box.querySelector('.hc-fb-' + state + ' textarea, .hc-fb-' + state + ' [tabindex]')?.focus();
                });
            });
        }
    })();
</script>
