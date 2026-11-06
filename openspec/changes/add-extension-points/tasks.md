## 0. Voorbereiding
- [ ] 0.1 Preload-handler van de ondersteunde ImpressCMS-versie verifiëren (custom events, luisteraars per module)
- [ ] 0.2 Afstemmen met de add-on-specificatie in `fiammybe/simplecart-seats` over payloads

## 1. Implementatie
- [ ] 1.1 `SimplecartExtension`-klasse met dispatch en `SIMPLECART_EXTENSION_API`
- [ ] 1.2 Event `cart.summary` in `simplecart_getCartSummary()`; render in `index.php`/`checkout.php`
- [ ] 1.3 Events `order.collectLines`, `order.beforePlace`, `order.afterPlace` in `placeOrder()`, binnen één transactie
- [ ] 1.4 `placeOrder()` aanvaardt bestellingen met enkel add-onregels
- [ ] 1.5 Event `order.cancelled` bij status `cancelled` en verwijderen
- [ ] 1.6 Event `order.render` in bevestigingsmail en succespagina
- [ ] 1.7 Event `scan.resolve` in `scan.php`
- [ ] 1.8 Versie 2.3.0 in `icms_version.php`

## 2. Tests en documentatie
- [ ] 2.1 Contracttests per event (payload, resultaat, foutafhandeling)
- [ ] 2.2 Regressietest: zonder luisteraars is het gedrag identiek aan 2.2.0
- [ ] 2.3 Test: faalt `afterPlace`, dan bestaat geen bestelling
- [ ] 2.4 `docs/EXTENSION_POINTS.md` met events, payloads, voorbeeld en compatibiliteitsmatrix
- [ ] 2.5 Testdouble/stub van `SimplecartExtension` voor add-on-tests
- [ ] 2.6 README en release notes; release 2.3.0 vóór add-on 1.0.0
