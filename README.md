# Simplecart 2.8.0

A simple shopping cart module for ImpressCMS. Define products and campaigns, control product availability, collect orders, and track payments.

## Features

- Organize products into campaigns, add product images, and control availability and display order.
- Collect checkout answers with configurable questions for every order or specific campaigns. Supported formats include text, email, URL, numbers, dates, single choice, and multiple choice; answers are kept with the order and can be shown in the customer's language.
- Provide SEPA bank-transfer payment details, including an EPC-standard QR code, after checkout. Configure the beneficiary name, IBAN, optional BIC, and currency. The payment reference is included in the admin order list and confirmation email.
- Manage and scan orders, close orders from the scan page, and review dashboard charts and filters for campaign, product, status, and choice-question answers.
- Extend cart lines, order handling, order rendering, and scan workflows through the add-on extension API.

The storefront and checkout use PHP, ImpressCMS Smarty templates, and session-backed cart storage. Dashboard filtering and charts run in the browser.

## Documentation

- [Dashboard](docs/DASHBOARD.md): order filters, charts, and how metrics are calculated.
- [Questions at checkout](docs/ORDER_QUESTIONS.md): campaign-specific questions, answer formats, validation, and languages.
- [Extension points](docs/EXTENSION_POINTS.md): API and hooks for add-ons.
- [SEPA QR code setup](docs/SEPA_QR_CODE_SETUP.md): payment configuration and QR code behavior.
