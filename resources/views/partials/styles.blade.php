<style>
    /* Ruvelo house style, scoped to .hc so it can't leak into a host layout.
       Override any --help-* variable to re-theme. */
    .hc {
        --help-bg: #ffffff;
        --help-subtle: #f8f8fc;
        --help-muted: #f0f0f7;
        --help-line: #e4e4ef;
        --help-ink: #16162a;
        --help-text-2: #4b4b63;
        --help-text-3: #74748b;
        --help-accent: #3d4eff;
        --help-accent-2: #a78bfa;
        --help-accent-ink: #2b38d6;
        --help-accent-soft: #eef0ff;
        --help-on-accent: #ffffff;
        --help-danger: #e5384f;
        --help-danger-soft: #fdecef;
        --help-success: #167a4a;
        --help-success-soft: #e8f8ef;
        --help-warning: #b26a00;
        --help-warning-soft: #fff4e0;
        --help-radius: 8px;
        --help-radius-lg: 14px;
        --help-sans: "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        --help-mono: "Geist Mono", ui-monospace, "SF Mono", "Cascadia Code", Menlo, Consolas, monospace;
        background: var(--help-bg); color: var(--help-ink); font: 15px/1.6 var(--help-sans); -webkit-font-smoothing: antialiased;
        color-scheme: light;
    }
    @media (prefers-color-scheme: dark) {
        .hc {
            --help-bg: #11111c;
            --help-subtle: #171725;
            --help-muted: #1f1f30;
            --help-line: #2a2a3f;
            --help-ink: #f1f1f8;
            --help-text-2: #b6b6cc;
            --help-text-3: #8787a3;
            --help-accent: #8f9bff;
            --help-accent-2: #c4b5fd;
            --help-accent-ink: #b3bbff;
            --help-accent-soft: #1e2150;
            --help-on-accent: #0b0b1a;
            --help-danger: #ff6b80;
            --help-danger-soft: #331520;
            --help-success: #74d6a2;
            --help-success-soft: #0f2b1f;
            --help-warning: #ffc266;
            --help-warning-soft: #2e2210;
            color-scheme: dark;
        }
    }
    .hc *, .hc *::before, .hc *::after { box-sizing: border-box; }
    .hc a { color: var(--help-accent); text-decoration: none; }
    .hc a:hover { text-decoration: underline; text-underline-offset: 3px; }
    .hc :focus-visible { outline: 2px solid var(--help-accent); outline-offset: 2px; border-radius: 4px; }
    .hc [hidden] { display: none !important; }
    .hc-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

    /* Header */
    .hc-bar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.5rem; padding: .75rem max(16px, calc((100% - 90rem) / 2 + 24px)); background: color-mix(in srgb, var(--help-bg) 88%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid var(--help-line); }
    .hc-brand { display: inline-flex; align-items: center; gap: .6rem; font-weight: 600; font-size: .95rem; color: var(--help-ink) !important; letter-spacing: -.01em; }
    .hc-brand:hover { text-decoration: none !important; }
    .hc-mark { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: 7px; background: linear-gradient(135deg, var(--help-accent), var(--help-accent-2)); color: #fff; font-size: .85rem; font-weight: 700; box-shadow: 0 4px 12px -4px color-mix(in srgb, var(--help-accent) 60%, transparent); }
    .hc-bar nav { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; align-items: center; font-size: .9rem; margin-left: auto; }
    .hc-bar nav a:not(.hc-btn) { color: var(--help-text-2); }
    .hc-bar nav a:not(.hc-btn):hover, .hc-bar nav a[aria-current] { color: var(--help-ink); text-decoration: none; }
    .hc-bar nav a[aria-current]:not(.hc-btn) { font-weight: 500; }
    .hc-bar .hc-search { flex: 0 1 26rem; margin-left: auto; }
    .hc-bar .hc-search + nav { margin-left: 0; }

    /* Search box and suggestions */
    .hc-search { position: relative; }
    .hc-search::before { content: ""; position: absolute; left: .8rem; top: 50%; width: .85rem; height: .85rem; translate: 0 -50%; background: var(--help-text-3); pointer-events: none; -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M7 1.5a5.5 5.5 0 0 1 4.38 8.83l3.15 3.14-1.06 1.06-3.14-3.15A5.5 5.5 0 1 1 7 1.5Zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z'/%3E%3C/svg%3E") center / contain no-repeat; mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M7 1.5a5.5 5.5 0 0 1 4.38 8.83l3.15 3.14-1.06 1.06-3.14-3.15A5.5 5.5 0 1 1 7 1.5Zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z'/%3E%3C/svg%3E") center / contain no-repeat; }
    .hc-search input[type=search] { padding-left: 2.2rem; background: var(--help-muted); border-color: transparent; }
    .hc-search input[type=search]:focus { background: var(--help-bg); }
    .hc-suggest { position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + .4rem); background: var(--help-bg); border: 1px solid var(--help-line); border-radius: var(--help-radius-lg); padding: .35rem; text-align: left; box-shadow: 0 24px 60px -32px color-mix(in srgb, var(--help-accent) 60%, transparent); }
    .hc .hc-suggest a { display: block; padding: .55rem .75rem; border-radius: var(--help-radius); color: var(--help-ink); }
    .hc .hc-suggest a:hover, .hc .hc-suggest a.is-active { background: var(--help-accent-soft); text-decoration: none; }
    .hc-suggest-title { display: block; font-weight: 500; font-size: .925rem; }
    .hc-suggest-meta { display: block; color: var(--help-text-3); font-size: .8rem; margin-top: .1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hc .hc-suggest a.hc-suggest-all { color: var(--help-accent); font-size: .85rem; font-weight: 500; border-top: 1px solid var(--help-line); border-radius: 0 0 var(--help-radius) var(--help-radius); margin-top: .25rem; }
    .hc-suggest-empty { padding: .6rem .75rem; color: var(--help-text-3); font-size: .875rem; margin: 0; }
    .hc mark { background: var(--help-accent-soft); color: inherit; border-radius: 3px; padding: 0 .1em; }

    /* Hero */
    .hc-hero { position: relative; text-align: center; padding: 4.5rem 16px 3.75rem; background: radial-gradient(60rem 22rem at 50% -6rem, color-mix(in srgb, var(--help-accent-2) 22%, transparent), transparent 70%), linear-gradient(var(--help-accent-soft), var(--help-bg)); border-bottom: 1px solid var(--help-line); }
    .hc-hero h1 { font-size: clamp(2.1rem, 5vw, 3.25rem); letter-spacing: -.045em; margin: 0 0 .6rem; line-height: 1.08; }
    .hc-hero p { color: var(--help-text-2); font-size: 1.05rem; margin: 0 auto 2rem; max-width: 34rem; }
    .hc-hero .hc-search { max-width: 40rem; margin: 0 auto; }
    .hc-hero .hc-search::before { left: 1.15rem; width: 1.1rem; height: 1.1rem; }
    .hc-hero .hc-search input[type=search] { font-size: 1.05rem; padding: 1rem 1.1rem 1rem 3rem; border-radius: var(--help-radius-lg); background: var(--help-bg); border: 1px solid var(--help-line); box-shadow: 0 24px 60px -32px color-mix(in srgb, var(--help-accent) 60%, transparent); }
    .hc-hero .hc-search input[type=search]:focus { border-color: var(--help-accent); box-shadow: 0 0 0 4px var(--help-accent-soft), 0 24px 60px -32px color-mix(in srgb, var(--help-accent) 60%, transparent); }
    .hc-hero-hint { margin-top: 1rem !important; margin-bottom: 0 !important; font-size: .875rem !important; color: var(--help-text-3) !important; }
    .hc-hero-hint a { color: var(--help-text-2) !important; border-bottom: 1px dashed var(--help-line); }
    .hc-hero-hint a:hover { color: var(--help-accent) !important; text-decoration: none !important; }

    /* Home sections */
    .hc-page { max-width: 72rem; margin: 0 auto; padding: 0 24px 4rem; }
    .hc-section { margin-top: 3.25rem; }
    .hc-section > h2 { font-size: 1.25rem; margin: 0 0 1.1rem; }
    .hc-cards { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 19rem), 1fr)); }
    .hc .hc-card { display: flex; flex-direction: column; gap: .35rem; height: 100%; padding: 1.25rem 1.3rem 1.15rem; border: 1px solid var(--help-line); border-radius: var(--help-radius-lg); color: var(--help-ink); background: var(--help-bg); transition: border-color .15s, background .15s; }
    .hc .hc-card:hover { border-color: var(--help-accent); background: linear-gradient(160deg, var(--help-accent-soft), var(--help-bg) 70%); text-decoration: none; }
    .hc-card-icon { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 10px; background: var(--help-accent-soft); font-size: 1.3rem; line-height: 1; margin-bottom: .45rem; }
    .hc-card strong { font-weight: 600; font-size: 1.05rem; letter-spacing: -.01em; }
    .hc-card-text { color: var(--help-text-2); font-size: .9rem; line-height: 1.5; }
    .hc-card-count { margin-top: auto; padding-top: .5rem; color: var(--help-text-3); font-size: .8rem; }
    .hc-popular { list-style: none; margin: 0; padding: 0; display: grid; gap: 0 2.5rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 24rem), 1fr)); }
    .hc-popular li { border-bottom: 1px solid var(--help-line); }
    .hc .hc-popular a { display: flex; gap: .75rem; align-items: baseline; padding: .8rem 0; color: var(--help-ink); font-weight: 500; }
    .hc .hc-popular a:hover { color: var(--help-accent); text-decoration: none; }
    .hc-popular small { color: var(--help-text-3); font-weight: 400; margin-left: auto; white-space: nowrap; font-size: .8rem; }

    /* Three columns, like the Laravel docs: menu, article, outline. */
    .hc-shell { max-width: 90rem; margin: 0 auto; padding: 0 24px; display: grid; grid-template-columns: 16rem minmax(0, 1fr) 13rem; gap: 3.5rem; }
    .hc-shell:not(:has(.hc-outline)) { grid-template-columns: 16rem minmax(0, 1fr); }
    .hc-shell--wide, .hc-shell--wide:not(:has(.hc-outline)) { grid-template-columns: minmax(0, 1fr); max-width: 80rem; }
    .hc-main { min-width: 0; max-width: 48rem; padding: 2.25rem 0 3rem; }
    .hc-shell--wide .hc-main { max-width: none; }
    .hc-foot { max-width: 90rem; margin: 0 auto; padding: 1.25rem 24px 2rem; color: var(--help-text-3); font-size: .8rem; }
    .hc-foot-in { border-top: 1px solid var(--help-line); padding-top: 1.25rem; display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; justify-content: space-between; }
    .hc .hc-foot a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }

    .hc-sidebar, .hc-outline { position: sticky; top: 4.25rem; align-self: start; max-height: calc(100vh - 4.25rem); overflow-y: auto; padding: 2.25rem 0 2rem; scrollbar-width: thin; }
    .hc-menu summary { display: none; }
    .hc-menu-links { font-size: .9rem; }
    .hc-menu-links h2 { display: flex; align-items: center; gap: .45rem; font-size: .8rem; font-weight: 600; letter-spacing: 0; color: var(--help-ink); margin: 1.6rem 0 .5rem; }
    .hc-menu-links h2:first-child { margin-top: 0; }
    .hc .hc-menu-links h2 a { color: inherit; }
    .hc-menu-links ul { list-style: none; margin: 0; padding: 0; border-left: 1px solid var(--help-line); }
    .hc .hc-menu-links li a { display: block; margin-left: -1px; padding: .3rem .85rem; border-left: 2px solid transparent; color: var(--help-text-2); line-height: 1.4; }
    .hc .hc-menu-links li a:hover { color: var(--help-ink); border-left-color: var(--help-text-3); text-decoration: none; }
    .hc .hc-menu-links li a[aria-current] { color: var(--help-accent-ink); border-left-color: var(--help-accent); background: linear-gradient(90deg, var(--help-accent-soft), transparent); font-weight: 500; }
    .hc-menu-links .hc-chip { margin-left: .35rem; }

    .hc-outline h2 { font-size: .8rem; font-weight: 600; letter-spacing: 0; margin: 0 0 .75rem; }
    .hc-outline ul { list-style: none; margin: 0; padding: 0; font-size: .85rem; }
    .hc-outline li { margin: .4rem 0; }
    .hc-outline li.is-sub { padding-left: .85rem; }
    .hc .hc-outline a { color: var(--help-text-3); }
    .hc .hc-outline a:hover { color: var(--help-accent); text-decoration: none; }

    @media (max-width: 80rem) {
        .hc-outline { display: none; }
        .hc-shell, .hc-shell:not(:has(.hc-outline)) { grid-template-columns: 15rem minmax(0, 1fr); gap: 2.5rem; }
    }
    @media (max-width: 63.99rem) {
        .hc-shell, .hc-shell:not(:has(.hc-outline)) { grid-template-columns: minmax(0, 1fr); gap: 0; padding: 0 16px; }
        .hc-sidebar { position: static; max-height: none; padding: 1rem 0 0; }
        .hc-menu { border: 1px solid var(--help-line); border-radius: var(--help-radius-lg); padding: .25rem 1rem; }
        .hc-menu[open] { padding-bottom: 1rem; }
        .hc-menu summary { display: list-item; cursor: pointer; font-weight: 500; font-size: .9rem; padding: .5rem 0; }
        .hc-main { padding-top: 1.5rem; }
        .hc-bar { padding-inline: 16px; }
        .hc-bar .hc-search { flex-basis: 100%; order: 3; margin-left: 0; }
        .hc-bar nav { margin-left: auto; }
        .hc-page { padding-inline: 16px; }
        .hc-hero { padding: 3rem 16px 2.5rem; }
        .hc-foot { padding-inline: 16px; }
    }

    /* Type */
    .hc h1, .hc h2, .hc h3, .hc h4 { line-height: 1.25; font-weight: 600; letter-spacing: -.02em; color: var(--help-ink); }
    .hc-main h1 { font-size: 2rem; margin: 0; overflow-wrap: anywhere; }
    .hc-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; list-style: none; margin: 0 0 1rem; padding: 0; font-size: .85rem; color: var(--help-text-3); }
    .hc-crumbs li + li::before { content: "/"; margin-right: .35rem; color: var(--help-line); }
    .hc .hc-crumbs a { color: var(--help-text-2); }
    .hc-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem 1rem; }
    .hc-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .hc .hc-actions a { font-size: .875rem; color: var(--help-text-2); border: 1px solid var(--help-line); border-radius: var(--help-radius); padding: .35rem .75rem; }
    .hc .hc-actions a:hover { color: var(--help-accent-ink); border-color: var(--help-accent); background: var(--help-accent-soft); text-decoration: none; }
    .hc-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; color: var(--help-text-3); font-size: .85rem; margin: .75rem 0 2.25rem; }
    .hc-lede { color: var(--help-text-2); font-size: 1.05rem; margin: .6rem 0 0; }
    .hc-title-icon { display: inline-grid; place-items: center; width: 2.75rem; height: 2.75rem; border-radius: 12px; background: var(--help-accent-soft); font-size: 1.4rem; margin-bottom: 1rem; }
    .hc-muted { color: var(--help-text-3); }
    .hc-chip { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; padding: .1rem .6rem; font-size: .75rem; font-weight: 500; background: var(--help-accent-soft); color: var(--help-accent-ink) !important; white-space: nowrap; line-height: 1.6; }
    .hc-chip--draft { background: var(--help-warning-soft); color: var(--help-warning) !important; }
    .hc-chip--good { background: var(--help-success-soft); color: var(--help-success) !important; }
    .hc-chip--bad { background: var(--help-danger-soft); color: var(--help-danger) !important; }
    .hc-flash { display: flex; align-items: center; gap: .6rem; background: var(--help-subtle); border: 1px solid var(--help-line); padding: .6rem .9rem; border-radius: var(--help-radius); margin: 0 0 1.75rem; font-size: .9rem; }
    .hc-flash::before { content: ""; width: .5rem; height: .5rem; border-radius: 50%; background: var(--help-accent); flex: none; }

    /* Lists of articles */
    .hc-list { list-style: none; padding: 0; margin: 1.5rem 0; border-top: 1px solid var(--help-line); }
    .hc-list li { padding: .9rem 0; border-bottom: 1px solid var(--help-line); display: flex; flex-wrap: wrap; align-items: baseline; gap: .25rem .75rem; }
    .hc-list li:hover { background: linear-gradient(90deg, var(--help-accent-soft), transparent 60%); }
    .hc-list a { font-weight: 500; }
    .hc-list-text { flex-basis: 100%; color: var(--help-text-2); font-size: .875rem; }
    .hc-list small { color: var(--help-text-3); }

    /* Article */
    .hc-prose { font-size: 1rem; line-height: 1.75; overflow-wrap: break-word; color: var(--help-ink); }
    .hc-prose > :first-child { margin-top: 0; }
    .hc-prose h2 { font-size: 1.375rem; margin: 2.5rem 0 .75rem; }
    .hc-prose h3 { font-size: 1.125rem; margin: 1.75rem 0 .5rem; }
    .hc .hc-prose a { text-decoration: underline; text-decoration-color: color-mix(in srgb, currentColor 30%, transparent); text-underline-offset: 3px; }
    .hc .hc-prose a:hover { text-decoration-color: currentColor; background: var(--help-accent-soft); border-radius: 3px; }
    .hc .hc-prose a.help-link--missing { color: var(--help-danger); text-decoration-style: dashed; text-decoration-color: currentColor; }
    .hc .hc-prose .heading-permalink { opacity: 0; margin-left: .4rem; color: var(--help-text-3); text-decoration: none; font-weight: 400; }
    .hc-prose :is(h1,h2,h3,h4,h5,h6):hover .heading-permalink { opacity: 1; }
    .hc-prose h2, .hc-prose h3 { scroll-margin-top: 5rem; }
    .hc-prose code { font: .85em var(--help-mono); background: var(--help-muted); padding: .15em .4em; border-radius: 5px; }
    .hc-prose pre { background: var(--help-subtle); border: 1px solid var(--help-line); padding: .9rem 1.1rem; border-radius: var(--help-radius-lg); overflow-x: auto; line-height: 1.55; }
    .hc-prose pre code { background: none; padding: 0; font-size: .85rem; }
    .hc-prose blockquote { margin: 1.5rem 0; padding: .75rem 1.15rem; background: var(--help-accent-soft); border-radius: var(--help-radius-lg); color: var(--help-ink); }
    .hc-prose blockquote p { margin: .3rem 0; }
    .hc-prose blockquote strong:first-child { color: var(--help-accent-ink); }
    .hc-prose table { border-collapse: collapse; display: block; overflow-x: auto; font-size: .9rem; }
    .hc-prose th { background: var(--help-subtle); font-weight: 500; text-align: left; }
    .hc-prose th, .hc-prose td { border: 1px solid var(--help-line); padding: .45rem .8rem; }
    .hc-prose img { max-width: 100%; border-radius: var(--help-radius); }
    .hc-prose ol li::marker { color: var(--help-accent); font-weight: 600; }
    .hc-prose li + li { margin-top: .25rem; }
    .hc-prose li:has(> input[type=checkbox]) { list-style: none; margin-left: -1.3rem; }
    .hc-prose input[type=checkbox] { accent-color: var(--help-accent); margin-right: .35rem; }

    /* Was this helpful? */
    .hc-feedback { margin-top: 3rem; padding: 1.25rem 1.4rem; border: 1px solid var(--help-line); border-radius: var(--help-radius-lg); scroll-margin-top: 5rem; }
    .hc-feedback > div { display: none; }
    .hc-feedback[data-state=ask] .hc-fb-ask, .hc-feedback[data-state=yes] .hc-fb-yes, .hc-feedback[data-state=no] .hc-fb-no, .hc-feedback[data-state=done] .hc-fb-done { display: block; }
    .hc-fb-ask form { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
    .hc-feedback h2 { font-size: 1rem; margin: 0; letter-spacing: -.01em; }
    .hc-fb-ask h2 { margin-right: auto; }
    .hc-fb-buttons { display: flex; gap: .5rem; }
    .hc-feedback p { margin: 0; color: var(--help-text-2); }
    .hc-fb-no label { margin-top: .75rem; }
    .hc-fb-no textarea { min-height: 5.5rem; font: inherit; }
    .hc-thanks { display: flex; align-items: center; gap: .6rem; font-weight: 500; color: var(--help-ink) !important; }
    .hc-thanks::before { content: "✓"; display: grid; place-items: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: var(--help-success-soft); color: var(--help-success); font-size: .8rem; flex: none; }

    .hc-related { margin-top: 2.5rem; }
    .hc-related h2, .hc-aside-title { font-size: .9rem; color: var(--help-text-3); font-weight: 500; letter-spacing: 0; margin: 0 0 .6rem; }
    .hc-related ul { margin: 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; gap: .4rem; }
    .hc .hc-related a { display: inline-block; background: var(--help-accent-soft); border: 1px solid transparent; border-radius: 999px; padding: .3rem .85rem; color: var(--help-accent-ink); font-weight: 500; font-size: .9rem; }
    .hc .hc-related a:hover { border-color: var(--help-accent); color: var(--help-accent); text-decoration: none; }

    .hc-contact { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem 1.5rem; margin-top: 2.5rem; padding: 1.4rem 1.5rem; background: var(--help-accent-soft); border-radius: var(--help-radius-lg); }
    .hc-contact h2 { font-size: 1.05rem; margin: 0 0 .2rem; color: var(--help-accent-ink); letter-spacing: -.01em; }
    .hc-contact p { margin: 0; color: var(--help-text-2); font-size: .925rem; }

    /* Controls */
    .hc .hc-btn, .hc button { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; font: 500 .875rem/1 var(--help-sans); padding: .6rem .95rem; border-radius: var(--help-radius); border: 1px solid var(--help-accent); background: var(--help-accent); color: var(--help-on-accent); cursor: pointer; box-shadow: 0 6px 16px -8px color-mix(in srgb, var(--help-accent) 70%, transparent); text-decoration: none; }
    .hc .hc-btn:hover, .hc button:hover { background: var(--help-accent-ink); border-color: var(--help-accent-ink); text-decoration: none; }
    .hc .hc-btn--quiet, .hc button.hc-btn--quiet { background: transparent; color: var(--help-ink); border-color: var(--help-line); box-shadow: none; }
    .hc .hc-btn--quiet:hover, .hc button.hc-btn--quiet:hover { background: var(--help-accent-soft); border-color: var(--help-accent); color: var(--help-accent-ink); }
    .hc button.hc-btn--danger { background: transparent; color: var(--help-danger); border-color: var(--help-line); box-shadow: none; }
    .hc button.hc-btn--danger:hover { background: var(--help-danger-soft); border-color: var(--help-danger); }
    .hc button.hc-btn--small, .hc .hc-btn--small { padding: .35rem .6rem; font-size: .8rem; }
    .hc input[type=text], .hc input[type=search], .hc input[type=number], .hc select, .hc textarea { font: inherit; font-size: .925rem; color: inherit; background: var(--help-bg); border: 1px solid var(--help-line); border-radius: var(--help-radius); padding: .55rem .75rem; width: 100%; }
    .hc input:focus, .hc textarea:focus, .hc select:focus { outline: none; border-color: var(--help-accent); box-shadow: 0 0 0 3px var(--help-accent-soft); }
    .hc textarea { font: .875rem/1.65 var(--help-mono); min-height: 24rem; resize: vertical; }
    .hc label { display: block; font-weight: 500; font-size: .85rem; margin: 1.25rem 0 .4rem; }
    .hc-error { color: var(--help-danger); font-size: .85rem; margin: .4rem 0 0; }
    .hc-row { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-top: 1.25rem; }
    .hc-hint { color: var(--help-text-3); font-size: .8rem; margin: .35rem 0 0; }
    .hc nav[role=navigation] { margin-top: 1.5rem; font-size: .875rem; }
    .hc nav[role=navigation] svg { width: 1rem; height: 1rem; }

    /* Editor area */
    .hc-tabs { display: flex; flex-wrap: wrap; gap: .25rem; margin: 0 0 1.75rem; border-bottom: 1px solid var(--help-line); }
    .hc .hc-tabs a { padding: .6rem .9rem; margin-bottom: -1px; border-bottom: 2px solid transparent; color: var(--help-text-2); font-size: .9rem; font-weight: 500; }
    .hc .hc-tabs a:hover { color: var(--help-ink); text-decoration: none; }
    .hc .hc-tabs a[aria-current] { color: var(--help-accent-ink); border-bottom-color: var(--help-accent); }
    .hc-filters { display: flex; flex-wrap: wrap; gap: .5rem; align-items: end; margin: 0 0 1.25rem; }
    .hc-filters > * { flex: 0 1 auto; }
    .hc-filters input[type=search] { min-width: 14rem; }
    .hc-filters select { width: auto; }
    .hc-table-wrap { overflow-x: auto; border: 1px solid var(--help-line); border-radius: var(--help-radius-lg); }
    .hc-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .hc-table th { text-align: left; font-weight: 500; color: var(--help-text-2); background: var(--help-subtle); font-size: .8rem; }
    .hc-table th, .hc-table td { padding: .7rem .9rem; border-bottom: 1px solid var(--help-line); vertical-align: middle; }
    .hc-table tr:last-child td { border-bottom: 0; }
    .hc-table td.num, .hc-table th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .hc-table tbody tr:hover { background: linear-gradient(90deg, var(--help-accent-soft), transparent 70%); }
    .hc-table a { font-weight: 500; }
    .hc-bar-meter { display: inline-block; width: 3.5rem; height: .4rem; border-radius: 999px; background: var(--help-muted); vertical-align: middle; margin-right: .5rem; overflow: hidden; }
    .hc-bar-meter span { display: block; height: 100%; background: var(--help-success); border-radius: inherit; }
    .hc-stats { display: flex; flex-wrap: wrap; gap: .5rem; margin-left: auto; }

    .hc-editor { display: grid; gap: 1rem 2.5rem; }
    .hc-editor-preview { min-width: 0; border-top: 1px solid var(--help-line); }
    .hc-fields { display: grid; gap: 0 1rem; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); }
    @media (min-width: 64rem) {
        .hc-editor { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .hc-editor-preview { border-top: 0; border-left: 1px solid var(--help-line); padding-left: 2.5rem; }
        .hc-editor textarea[name=body] { min-height: 56vh; }
    }
    .hc-editor textarea[name=excerpt] { min-height: 4.5rem; font-family: var(--help-sans); }
    .hc-editor-preview > h1 { font-size: 1.75rem; margin: 0 0 1.25rem; }
    details.hc-help { margin-top: .75rem; font-size: .85rem; color: var(--help-text-2); }
    details.hc-help summary { cursor: pointer; color: var(--help-text-3); }
    details.hc-help code { font-family: var(--help-mono); }

    .hc-cat-row { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .75rem 1rem; align-items: start; padding: 1.1rem 0; border-bottom: 1px solid var(--help-line); }
    .hc-cat-row form.hc-cat-edit { display: grid; gap: .5rem; grid-template-columns: 4.5rem minmax(0, 1fr); }
    .hc-cat-row form.hc-cat-edit textarea { grid-column: 1 / -1; min-height: 3.5rem; font-family: var(--help-sans); }
    .hc-cat-move { display: flex; flex-direction: column; gap: .25rem; }
    .hc-cat-tools { grid-column: 2; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-size: .85rem; }
    .hc-cat-tools form { display: inline; }
    .hc-comment { padding: 1rem 0; border-bottom: 1px solid var(--help-line); }
    .hc-comment blockquote { margin: .4rem 0 0; padding: .6rem .9rem; background: var(--help-subtle); border-radius: var(--help-radius); color: var(--help-ink); }
    .hc-empty { padding: 2.5rem 1rem; text-align: center; color: var(--help-text-3); border: 1px dashed var(--help-line); border-radius: var(--help-radius-lg); }
</style>
