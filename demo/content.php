<?php

declare(strict_types=1);

// The demo help center: Halyard, a fictional B2B billing SaaS (the same
// company as the laravel-wiki demo). Categories first, then articles:
// [category slug, title, days since last update, views, [yes, no], Markdown].

return [
    'categories' => [
        ['🚀', 'Getting started', 'Set up your workspace, invite your team and send your first invoice.'],
        ['🧾', 'Billing & invoices', 'Plans, invoices, credit notes and VAT.'],
        ['💳', 'Payments', 'Getting paid by card and bank transfer, refunds and payouts.'],
        ['🔌', 'Integrations', 'Connect Halyard to your accounting tools and your own app.'],
        ['🔐', 'Account & security', 'Sign-in, two-factor authentication, and your data.'],
        ['🛠️', 'Troubleshooting', 'Fixes for the problems people run into most.'],
    ],

    'articles' => [
        ['getting-started', 'Create your Halyard workspace', 41, 2310, [148, 6], <<<'MD'
A workspace holds your company's customers, invoices and payments. Most teams need one; agencies billing for several brands often create one per brand.

## Sign up

1. Go to **halyard.app/signup** and enter your work email.
2. Open the confirmation email and click **Confirm my email**. The link works for 24 hours.
3. Choose a password, or sign in with Google.

## Set up your company details

Your company details appear on every invoice, so it's worth getting them right before you send the first one.

1. Open **Settings → Company**.
2. Fill in your legal name, address and tax ID.
3. Upload a logo. Square PNG or SVG, at least 256 × 256 pixels.
4. Choose your default currency. You can still invoice in other currencies later.

> **Tip:** your legal name is what customers see on invoices and bank statements. If you trade under another name, add it as the **Trading name** and both will show.

## What's next

- [[Send your first invoice]]
- [[Invite your team]] so colleagues can help
- [[Accept card payments]] to get paid faster
MD],

        ['getting-started', 'Send your first invoice', 40, 3125, [201, 14], <<<'MD'
You can send an invoice in about two minutes. You'll need your company details filled in first, see [[Create your Halyard workspace]].

## Create the invoice

1. Click **New invoice** in the top bar.
2. Pick a customer, or type a new name and press **Enter** to create one.
3. Add line items: a description, a quantity and a unit price. Halyard adds them up and applies tax.
4. Set the due date. The default is 30 days; change it in **Settings → Invoices**.

## Send it

Click **Send invoice**. Your customer gets an email with a link to view, download and pay the invoice online.

| Status | What it means |
|---|---|
| Draft | Only your team can see it |
| Sent | Emailed to your customer, not paid yet |
| Overdue | Past its due date and still unpaid |
| Paid | Fully paid; Halyard records the payment for you |

> **Note:** once an invoice is sent, its number and amounts can't change. To correct one, [[Issue a credit note]] and send a new invoice.

## Didn't arrive?

If your customer can't find the email, see [[Invoice emails are not arriving]].
MD],

        ['getting-started', 'Invite your team', 33, 980, [64, 3], <<<'MD'
Everyone on your team gets their own sign-in, so you can see who did what.

## Send an invitation

1. Open **Settings → Team** and click **Invite people**.
2. Enter one or more email addresses and pick a role.
3. Click **Send invitations**. Invitations expire after 7 days.

## Roles

| Role | Can do |
|---|---|
| Owner | Everything, including billing for your Halyard plan and deleting the workspace |
| Admin | Everything except the Halyard plan and deleting the workspace |
| Finance | Create and send invoices, record payments, issue refunds |
| Viewer | See invoices and reports; change nothing |

> **Tip:** give your accountant the **Viewer** role. They can download everything they need without being able to change it.

Each workspace has one owner. To hand over ownership, the current owner opens **Settings → Team**, clicks the new owner's name and chooses **Make owner**.

For extra safety, ask everyone to [[Turn on two-factor authentication]].
MD],

        ['billing-invoices', 'Download an invoice', 12, 4870, [312, 41], <<<'MD'
You can download any invoice as a PDF, and export several at once as a CSV for your accountant.

## Download one invoice

1. Open **Invoices** and click the invoice.
2. Click **Download PDF** in the top right.

Your customer can do the same from the link in their invoice email, without signing in.

## Export several invoices

1. Open **Invoices** and filter the list, for example by date or status.
2. Click **Export** and choose **CSV** or **ZIP of PDFs**.
3. We email you a download link when the export is ready. Large exports can take a few minutes.

> **Tip:** exports follow your filters. To send your accountant last quarter's paid invoices, filter by **Paid** and the quarter's dates first.

## Download your Halyard subscription invoices

Invoices for your own Halyard plan are in **Settings → Plan & billing → Billing history**. To change what's on them, see [[Add VAT and tax IDs to invoices]].

## The PDF looks wrong

Logos and addresses come from **Settings → Company**. Change them there and download the PDF again: past invoices keep their original details, by law, but you can re-issue one with [[Issue a credit note]].
MD],

        ['billing-invoices', 'Change your plan', 20, 1640, [97, 18], <<<'MD'
You can move to another Halyard plan at any time. Only the workspace owner can change the plan.

## Upgrade

1. Open **Settings → Plan & billing**.
2. Click **Change plan** and choose the new plan.
3. Confirm. The new features are available straight away.

You pay the difference for the rest of the current billing period, shown before you confirm.

## Downgrade

Downgrades take effect at the end of your current billing period, so you keep what you paid for. If you use features the smaller plan doesn't include, we list them before you confirm.

| Plan | Invoices per month | Team members | API access |
|---|---|---|---|
| Starter | 50 | 2 | No |
| Growth | 1,000 | 10 | Yes |
| Scale | Unlimited | Unlimited | Yes, with higher limits |

> **Heads up:** the [[Use the Halyard API|API]] is not available on Starter. Moving down to Starter stops any integration that uses it.

## Switching between monthly and yearly

Yearly billing costs two months less. Switch in **Settings → Plan & billing → Billing cycle**; the change applies at your next renewal.
MD],

        ['billing-invoices', 'Issue a credit note', 18, 1120, [58, 22], <<<'MD'
A credit note cancels all or part of an invoice that's already been sent. Use one to fix a mistake or to give a customer money back.

## When to use a credit note

- You charged the wrong amount or the wrong customer.
- A customer returned something or cancelled part of an order.
- You agreed to a discount after sending the invoice.

Sent invoices can't be edited, because their numbers must stay in sequence for your tax records. A credit note keeps the record straight.

## Create one

1. Open the invoice and click **More → Issue credit note**.
2. Choose **Full** to cancel the whole invoice, or **Partial** and pick the lines and amounts.
3. Add a reason. Your customer sees it.
4. Click **Issue credit note**.

## What happens to the money

| Invoice status | What the credit note does |
|---|---|
| Unpaid | Reduces what your customer owes |
| Paid | Leaves a credit on the customer's account, or [[Refund a customer|refunds]] them if you tick **Refund the payment** |

> **Note:** credit notes get their own numbering, starting with `CN-`. You'll find them under **Invoices → Credit notes**.
MD],

        ['billing-invoices', 'Add VAT and tax IDs to invoices', 9, 1385, [71, 19], <<<'MD'
Halyard calculates tax on each invoice from the rates you set up, and prints your tax ID and your customer's.

## Add your tax ID

1. Open **Settings → Company → Tax**.
2. Add your VAT number, GST number or other tax ID. We check EU VAT numbers against VIES as you save.

## Set up tax rates

1. Open **Settings → Tax rates** and click **New tax rate**.
2. Give it a name customers will understand, like `VAT 20%`.
3. Set the percentage and whether prices include it.

Pick a default rate for new invoices, or choose one per line item.

## Reverse charge in the EU

When you invoice a business in another EU country and they've given you a valid VAT number:

1. Add their VAT number to the customer's details.
2. Halyard switches the invoice to 0% and adds the reverse-charge note for you.

> **Heads up:** we check the format and the VIES register, but you remain responsible for charging the right tax. If in doubt, ask your accountant.

## Customer tax IDs

Add a customer's tax ID on their page, under **Billing details**. It appears on every invoice you send them from then on. Past invoices don't change; see [[Download an invoice]] for why.
MD],

        ['payments', 'Accept card payments', 26, 2210, [133, 9], <<<'MD'
Let customers pay invoices by card from the invoice email. Most invoices with card payments switched on are paid in under three days.

## Turn on card payments

1. Open **Settings → Payments** and click **Connect payments**.
2. Enter your business and bank details. This takes about five minutes.
3. Once verified, every new invoice shows a **Pay now** button.

Verification usually takes a few minutes, and up to two working days if we need documents.

## Fees

| Card | Fee per payment |
|---|---|
| European cards | 1.4% + €0.25 |
| UK cards | 1.5% + £0.20 |
| International cards | 2.9% + €0.25 |

Fees are taken from the payment before it reaches you; see [[When will I get paid?]].

## Bank transfers

Bank transfer details are printed on every invoice. When a transfer arrives, record it with **Mark as paid**, or connect your bank under [[Connect Xero or QuickBooks]] to match transfers automatically.

> **Tip:** to nudge late payers, turn on reminders in **Settings → Invoices → Reminders**. Halyard sends a polite email before and after the due date.
MD],

        ['payments', 'When will I get paid?', 15, 2960, [176, 35], <<<'MD'
Card payments reach your bank account on a rolling schedule. The first payout takes a little longer while we verify your account.

## Payout schedule

| | First payout | After that |
|---|---|---|
| EU and UK | 7 days after your first payment | 2 working days after each payment |
| Rest of the world | 14 days after your first payment | 7 days after each payment |

Payouts are sent once a day, and bundle every payment that's ready. Weekends and bank holidays can add a day.

## See what's on the way

Open **Payments → Payouts** to see each payout, its status and the payments it includes.

| Status | Meaning |
|---|---|
| Pending | Waiting for its payout date |
| In transit | Sent to your bank |
| Paid | Your bank confirmed it |
| Failed | Your bank sent it back; check your bank details |

> **Note:** refunds and fees come out of your next payout. If they're larger than it, the difference is taken from your bank account. See [[Refund a customer]].

## A payout is late

If a payout is **In transit** for more than three working days, ask your bank for a payment with the reference shown in Halyard. Still missing? Contact support with the payout ID, which starts with `po_`.
MD],

        ['payments', 'Refund a customer', 22, 1590, [102, 11], <<<'MD'
You can refund all or part of a card payment for up to 180 days.

## Refund a payment

1. Open **Payments** and click the payment.
2. Click **Refund**.
3. Enter the amount, or leave it to refund everything.
4. Choose a reason and click **Refund payment**.

Halyard creates a credit note for the invoice automatically, so your books stay right. To refund without a card payment, for example a bank transfer, [[Issue a credit note]] and send the money back from your bank.

## How long it takes

The customer sees the refund on their statement in **5 to 10 working days**, depending on their bank. Halyard's fee on the original payment isn't returned.

> **Tip:** your customer gets an email confirming the refund, with the amount and a reference they can give their bank if they can't find it.

## Undoing a refund

Refunds can't be cancelled once made. If you refunded by mistake, send the customer a new invoice for the amount.
MD],

        ['payments', 'Handle failed payments', 6, 1240, [61, 27], <<<'MD'
Card payments fail when a card has expired, has no funds, or the bank blocks the payment. Halyard retries automatically, and tells you and your customer.

## Automatic retries

For subscriptions and saved cards, we retry a failed payment on this schedule:

1. 1 day after the failure
2. 3 days after
3. 7 days after

After the last retry the invoice stays **Overdue** and we stop trying.

## What your customer sees

They get an email after each failure, with a link to update their card. Once they do, we try again straight away.

## What you can do

- Open the invoice to see the reason the bank gave, like `insufficient_funds` or `expired_card`.
- Click **Retry now** after the customer tells you they've fixed it.
- Turn off retries for one customer under **Customer → Payment settings**.

> **Heads up:** if a customer says they were charged even though the payment shows as failed, see [[A customer was charged twice]].

Get a webhook for every failure to act on it in your own app, see [[Set up webhooks]].
MD],

        ['integrations', 'Connect Xero or QuickBooks', 30, 1460, [88, 13], <<<'MD'
Halyard syncs invoices, payments and customers to your accounting software, so nobody types them in twice.

## Connect

1. Open **Settings → Integrations**.
2. Click **Connect** next to Xero or QuickBooks Online.
3. Sign in to your accounting software and allow access.
4. Choose the accounts to sync to: sales, fees and your bank account.

## What syncs

| From Halyard | Becomes |
|---|---|
| Customers | Contacts |
| Invoices and credit notes | Sales invoices and credit notes |
| Card payments | Payments against the invoices |
| Fees | A spend transaction on your fees account |
| Payouts | A bank transfer, ready to reconcile |

Syncing runs every 15 minutes. Click **Sync now** to run it straight away.

> **Note:** syncing goes one way, from Halyard. Changes made in Xero or QuickBooks aren't copied back.

## Something didn't sync

Open **Settings → Integrations → Sync log**. Each failed item says why, usually a deleted account or a tax rate that doesn't exist on the other side. Fix it and click **Retry**.
MD],

        ['integrations', 'Use the Halyard API', 14, 1870, [94, 16], <<<'MD'
The Halyard API lets your own app create customers, send invoices and read payments. It's available on the Growth and Scale plans; see [[Change your plan]].

## Create an API key

1. Open **Settings → Developers → API keys**.
2. Click **New key**, name it after the app that will use it, and choose its permissions.
3. Copy the key. It's shown once.

Keys starting with `hk_test_` work with test data; `hk_live_` keys work with real data.

## Make your first request

```bash
curl https://api.halyard.app/v1/invoices \
  -H "Authorization: Bearer hk_test_4f2a…" \
  -d customer=cus_81Hd2 \
  -d "lines[0][description]=Consulting, March" \
  -d "lines[0][amount]=120000" \
  -d currency=eur
```

Amounts are in the smallest currency unit: `120000` is €1,200.00.

## Limits

| Plan | Requests per minute |
|---|---|
| Growth | 100 |
| Scale | 1,000 |

Over the limit, the API answers `429 Too Many Requests` with a `Retry-After` header.

> **Tip:** don't poll for payments. [[Set up webhooks]] and we'll tell your app the moment something changes.
MD],

        ['integrations', 'Set up webhooks', 11, 1130, [69, 12], <<<'MD'
Webhooks tell your app when something happens in Halyard, like an invoice being paid, so you don't have to keep asking the [[Use the Halyard API|API]].

## Add an endpoint

1. Open **Settings → Developers → Webhooks** and click **Add endpoint**.
2. Enter the HTTPS URL that should receive events.
3. Pick the events you want, or **All events**.
4. Copy the signing secret.

## Events

| Event | Sent when |
|---|---|
| `invoice.sent` | An invoice is emailed to a customer |
| `invoice.paid` | An invoice is fully paid |
| `invoice.overdue` | An invoice passes its due date unpaid |
| `payment.failed` | A card payment fails, see [[Handle failed payments]] |
| `payout.paid` | A payout reaches your bank |

## Check the signature

Every request has a `Halyard-Signature` header. Compute an HMAC-SHA256 of the raw body with your signing secret and compare:

```php
$expected = hash_hmac('sha256', $request->getContent(), config('services.halyard.webhook_secret'));

abort_unless(hash_equals($expected, $request->header('Halyard-Signature')), 401);
```

> **Heads up:** answer with a `2xx` within 10 seconds, then do slow work in a queue. Slower answers count as failures, see [[Webhook deliveries are failing]].
MD],

        ['account-security', 'Turn on two-factor authentication', 35, 1015, [87, 4], <<<'MD'
Two-factor authentication asks for a code from your phone as well as your password, so a stolen password isn't enough to get in.

## Turn it on

1. Click your name in the bottom left and open **Account → Security**.
2. Click **Turn on two-factor authentication**.
3. Scan the QR code with an authenticator app such as 1Password, Google Authenticator or Authy.
4. Enter the 6-digit code the app shows.
5. Save your recovery codes somewhere safe.

> **Tip:** each recovery code works once. If you lose your phone, sign in with one and set up two-factor authentication again.

## Require it for everyone

Owners and admins can make it mandatory in **Settings → Security → Require two-factor authentication**. Team members without it are asked to set it up the next time they sign in.

## Lost your phone and your recovery codes?

Ask another owner or admin to reset two-factor authentication for you in **Settings → Team**. If you're the only owner, contact support from your account email; we'll verify your identity before we reset it.

Can't remember your password either? [[Reset your password]] first.
MD],

        ['account-security', 'Reset your password', 44, 2640, [190, 12], <<<'MD'
Forgot your password? It happens. You can set a new one in a minute.

## Reset it

1. On the sign-in page, click **Forgot your password?**
2. Enter your email address and click **Send reset link**.
3. Open the email and click **Choose a new password**. The link works for 60 minutes.
4. Choose a new password: at least 12 characters.

You stay signed in on the device you reset from, and are signed out everywhere else.

## No email?

- Check your spam folder for a message from `no-reply@halyard.app`.
- Make sure you used the address you signed up with. If you signed up with Google, use **Sign in with Google** instead; there's no password to reset.
- Still nothing after 10 minutes? See [[Invoice emails are not arriving]]; the same fixes apply.

> **Note:** we never ask for your password by email or phone. If someone does, it isn't us.
MD],

        ['account-security', 'Export or delete your data', 52, 540, [31, 5], <<<'MD'
You own your data. You can take a full copy at any time, and delete your workspace when you leave.

## Export everything

1. Open **Settings → Data → Export**.
2. Click **Export workspace**.
3. We email the owner a link to a ZIP file within an hour.

| File | Contains |
|---|---|
| `customers.csv` | Every customer and their billing details |
| `invoices.csv` | Every invoice and credit note |
| `payments.csv` | Every payment, refund and payout |
| `pdfs/` | A PDF of every invoice, see [[Download an invoice]] |

## Delete your workspace

Only the owner can delete a workspace.

1. Cancel your plan in **Settings → Plan & billing**.
2. Open **Settings → Data → Delete workspace** and type the workspace name to confirm.

> **Heads up:** deletion is permanent after 30 days. Until then, contact support to restore the workspace. Tax law may require us to keep invoice records for longer; we keep only what the law requires and nothing else.
MD],

        ['troubleshooting', 'Invoice emails are not arriving', 4, 1980, [92, 38], <<<'MD'
If a customer says they never got an invoice, it's almost always a spam filter or a typo in their address.

## Check the delivery log

Open the invoice and look at **Activity**. Each email shows one of:

| Status | Meaning | What to do |
|---|---|---|
| Delivered | Their mail server accepted it | Ask them to check spam and any "Promotions" tab |
| Bounced | The address doesn't exist | Fix the address on the customer and click **Resend** |
| Blocked | Their server refused it | Ask their IT team to allow `halyard.app` |
| Deferred | Their server asked us to try later | Nothing; we retry for 24 hours |

## Send from your own domain

Invoices sent from your own domain are much less likely to be filtered.

1. Open **Settings → Email → Sending domain**.
2. Add the three DNS records we show you.
3. Click **Verify**. DNS changes can take up to an hour.

> **Tip:** you can always copy the invoice link with **Share → Copy link** and send it yourself.

Customers who need a copy of an old invoice can download it from that link; see [[Download an invoice]].
MD],

        ['troubleshooting', 'A customer was charged twice', 2, 860, [37, 21], <<<'MD'
It looks like a double charge, but most of the time one of the two is a temporary hold that disappears by itself.

## Check what actually happened

1. Open **Payments** and search for the customer.
2. Count the payments with the status **Succeeded** for that invoice.

| You see | What happened | What to do |
|---|---|---|
| One succeeded, one failed | The first attempt failed; the bank shows a pending hold | Nothing. The hold drops off in 3 to 5 working days |
| Two succeeded | The customer paid twice, for example from two browser tabs | [[Refund a customer|Refund]] one of them |
| One succeeded | The second charge is on another invoice, or from another company | Ask the customer for the date and amount on their statement |

## Tell your customer

> **Tip:** customers worry less when they hear from you first. Something like: "We can see the second charge is a temporary hold from your bank. It will disappear within five working days and you won't be charged twice."

## Still stuck?

Contact support with the payment IDs (they start with `pay_`). We'll look at what the card network recorded.

For payments that failed and were retried, see [[Handle failed payments]].
MD],

        ['troubleshooting', 'Webhook deliveries are failing', 1, 410, [18, 9], <<<'MD'
When your endpoint doesn't answer in time, or answers with an error, Halyard retries the event and eventually turns the endpoint off.

## See what went wrong

Open **Settings → Developers → Webhooks**, click the endpoint, then **Recent deliveries**. Each attempt shows the response code and the first 1,000 characters of the body.

| You see | Likely cause |
|---|---|
| `timeout` | Your endpoint took more than 10 seconds |
| `401` or `403` | Your signature check or your firewall rejected us |
| `404` | The URL changed |
| `5xx` | An error in your app; check its logs |

## Retries

We retry failed deliveries for 3 days, with longer gaps each time. After 3 days of failures the endpoint is turned off and the owner gets an email.

## Fix it and catch up

1. Fix the cause, then click **Turn on** if the endpoint was turned off.
2. Click **Resend** on any failed delivery, or **Resend all failed** to replay them in order.

> **Heads up:** events can arrive more than once and out of order. Use the event `id` to skip duplicates, and keep [[Set up webhooks#Check the signature|checking the signature]] on every request.
MD],
    ],

    // Drafts only editors see: they show up in the editor screenshots.
    'drafts' => [
        ['billing-invoices', 'Usage-based billing (beta)', <<<'MD'
Usage-based billing lets you charge for what customers use, like API calls or seats, instead of a fixed price.

## Join the beta

Ask your account manager, or reply to the beta invitation email.

## Report usage

Send usage with the [[Use the Halyard API|API]]:

```bash
curl https://api.halyard.app/v1/usage \
  -H "Authorization: Bearer hk_test_4f2a…" \
  -d subscription=sub_9Kd1 \
  -d quantity=1200
```

> **Note:** usage is billed at the end of each period, on the next invoice.
MD],
    ],

    // Comments left with "No" votes, for the editor's feedback page.
    'comments' => [
        ['when-will-i-get-paid', 'It doesn’t say what happens with payouts over Christmas.'],
        ['download-an-invoice', 'I need the invoices in my customer’s language, is that possible?'],
        ['invoice-emails-are-not-arriving', 'We added the DNS records yesterday and it still says pending.'],
        ['handle-failed-payments', 'Can I change the retry schedule? 7 days is too long for us.'],
        ['issue-a-credit-note', 'What if the invoice was in a different currency?'],
        ['a-customer-was-charged-twice', 'Our customer’s bank says both charges are final, not holds.'],
    ],
];
