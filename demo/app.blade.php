{{-- A pretend page from the Halyard app, to show <x-help::articles> in context. Rendered by demo/build.php. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plan & billing · Halyard</title>
    <style>
        :root { --app-bg: #f6f7f9; --app-card: #ffffff; --app-line: #e3e6eb; --app-ink: #1b2330; --app-text-2: #556070; --app-brand: #0f766e; --app-side: #13202e; color-scheme: light; }
        @media (prefers-color-scheme: dark) { :root { --app-bg: #0e141b; --app-card: #151d26; --app-line: #26313d; --app-ink: #e8edf3; --app-text-2: #9aa7b6; --app-brand: #2dd4bf; --app-side: #0a1016; color-scheme: dark; } }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--app-bg); color: var(--app-ink); font: 14px/1.55 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .app { display: grid; grid-template-columns: 15rem minmax(0, 1fr); min-height: 100vh; }
        .side { background: var(--app-side); color: #cbd5e1; padding: 1.25rem 1rem; }
        .logo { display: flex; align-items: center; gap: .6rem; color: #fff; font-weight: 700; font-size: 1.05rem; margin: 0 .5rem 1.75rem; }
        .logo span { display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 8px; background: #14b8a6; color: #04201d; font-size: .9rem; }
        .side a { display: block; color: inherit; text-decoration: none; padding: .5rem .75rem; border-radius: 7px; margin-bottom: .15rem; }
        .side a.on { background: rgb(255 255 255 / .08); color: #fff; font-weight: 500; }
        .main { padding: 2rem 2.5rem 3rem; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.75rem; }
        .top h1 { margin: 0; font-size: 1.5rem; letter-spacing: -.02em; }
        .top p { margin: .2rem 0 0; color: var(--app-text-2); }
        .avatar { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 50%; background: var(--app-brand); color: #fff; font-weight: 600; font-size: .8rem; }
        .grid { display: grid; grid-template-columns: minmax(0, 1fr) 20rem; gap: 1.5rem; align-items: start; }
        .card { background: var(--app-card); border: 1px solid var(--app-line); border-radius: 12px; padding: 1.25rem 1.4rem; margin-bottom: 1.25rem; }
        .card h2 { font-size: .95rem; margin: 0 0 .9rem; }
        .plan { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .plan strong { font-size: 1.35rem; }
        .muted { color: var(--app-text-2); }
        .btn { display: inline-block; border: 1px solid var(--app-line); background: var(--app-card); color: var(--app-ink); border-radius: 8px; padding: .5rem .85rem; font: inherit; font-weight: 500; text-decoration: none; }
        .btn.primary { background: var(--app-brand); border-color: var(--app-brand); color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-weight: 500; color: var(--app-text-2); font-size: .8rem; }
        th, td { padding: .6rem 0; border-bottom: 1px solid var(--app-line); }
        tr:last-child td { border-bottom: 0; }
        .num { text-align: right; }
        .pill { display: inline-block; padding: .1rem .55rem; border-radius: 999px; font-size: .75rem; background: color-mix(in srgb, var(--app-brand) 14%, transparent); color: var(--app-brand); font-weight: 500; }
        .note { font: 12px/1.4 ui-monospace, Menlo, monospace; color: var(--app-text-2); border: 1px dashed var(--app-line); border-radius: 8px; padding: .5rem .65rem; margin-bottom: .6rem; background: var(--app-card); }
        .rail > * + * { margin-top: 1rem; }
        @media (max-width: 62rem) { .grid { grid-template-columns: minmax(0, 1fr); } }
        @media (max-width: 48rem) { .app { grid-template-columns: minmax(0, 1fr); } .side { display: none; } .main { padding: 1.25rem 16px 2rem; } }
    </style>
</head>
<body>
    <div class="app">
        <nav class="side" aria-label="Halyard">
            <div class="logo"><span>H</span>Halyard</div>
            <a href="#">Dashboard</a>
            <a href="#">Invoices</a>
            <a href="#">Customers</a>
            <a href="#">Payments</a>
            <a href="#">Reports</a>
            <a class="on" href="#">Settings</a>
            <a href="{{ route('help.home') }}">Help center</a>
        </nav>
        <main class="main">
            <div class="top">
                <div>
                    <h1>Plan &amp; billing</h1>
                    <p>Settings for Northwind Studio</p>
                </div>
                <span class="avatar" aria-hidden="true">MO</span>
            </div>

            <div class="grid">
                <div>
                    <section class="card">
                        <h2>Your plan</h2>
                        <div class="plan">
                            <div>
                                <strong>Growth</strong> <span class="muted">· €49 a month, billed monthly</span><br>
                                <span class="muted">812 of 1,000 invoices used this month · Renews 1 November</span>
                            </div>
                            <a class="btn primary" href="{{ route('help.articles.show', 'change-your-plan') }}">Change plan</a>
                        </div>
                    </section>

                    <section class="card">
                        <h2>Payment method</h2>
                        <div class="plan">
                            <span>Visa ending 4242 · expires 08/28</span>
                            <a class="btn" href="#">Update card</a>
                        </div>
                    </section>

                    <section class="card">
                        <h2>Billing history</h2>
                        <table>
                            <thead><tr><th>Invoice</th><th>Date</th><th>Status</th><th class="num">Amount</th></tr></thead>
                            <tbody>
                                <tr><td>HAL-2026-0310</td><td>1 Oct 2026</td><td><span class="pill">Paid</span></td><td class="num">€58.80</td></tr>
                                <tr><td>HAL-2026-0274</td><td>1 Sep 2026</td><td><span class="pill">Paid</span></td><td class="num">€58.80</td></tr>
                                <tr><td>HAL-2026-0231</td><td>1 Aug 2026</td><td><span class="pill">Paid</span></td><td class="num">€58.80</td></tr>
                                <tr><td>HAL-2026-0198</td><td>1 Jul 2026</td><td><span class="pill">Paid</span></td><td class="num">€58.80</td></tr>
                            </tbody>
                        </table>
                    </section>
                </div>

                <div class="rail">
                    <div>
                        <div class="note">&lt;x-help::articles category="billing-invoices" :limit="4" /&gt;</div>
                        <x-help::articles category="billing-invoices" :limit="4" title="Help with billing" />
                    </div>
                    <div>
                        <div class="note">&lt;x-help::articles search="refund" :limit="3" /&gt;</div>
                        <x-help::articles search="refund" :limit="3" title="About refunds" />
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
