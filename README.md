# simplecart 2.0.0
A simple shopping cart for ImpressCMS. Define products, add to cart, follow orders.

This version does ONLY support SEPA direct debit payments. No other payment methods are supported. The module is designed to be simple and easy to use, with a focus on SEPA payments.

## Technology
The storefront and checkout use standard PHP with ImpressCMS Smarty templates and session-backed cart storage. There is no client-side AJAX or JavaScript cart layer in the current module flow.

## Documentation
- [Questions at checkout](docs/ORDER_QUESTIONS.md): questions per campaign or for every order, answer formats with validation, language tags and the language of the customer.
- Order in the shop: campaigns and products have a weight (lowest first). The shop lists campaigns by weight and, inside a campaign, products by weight. Products without a campaign form a virtual campaign that always comes last. Equal weights are sorted by name.
- [Extension points](docs/EXTENSION_POINTS.md): hooks for add-ons.
