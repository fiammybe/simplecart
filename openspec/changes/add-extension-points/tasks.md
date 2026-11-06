## 0. Voorbereiding
- [x] 0.1 Preload-handler van ImpressCMS 2.0.4 geverifieerd: ongeschikt, eigen registratie gekozen (design D1)
- [ ] 0.2 Afstemmen met de add-on-specificatie in `fiammybe/simplecart-seats` over payloads

## 1. Implementatie
- [x] 1.1 `SimplecartExtension`-klasse met dispatch en `SIMPLECART_EXTENSION_API`
- [x] 1.2 Events `cart.summary` en `cart.emptied`; render in `index.php`/`checkout.php`
- [x] 1.3 Events `order.collectLines`, `order.beforePlace`, `order.afterPlace` in `placeOrder()`, binnen één transactie
- [x] 1.4 `placeOrder()` aanvaardt bestellingen met enkel add-onregels
- [x] 1.5 Event `order.cancelled` bij status `cancelled` en verwijderen
- [x] 1.6 Event `order.render` in bevestigingsmail en succespagina
- [x] 1.7 Event `scan.resolve` in `scan.php`
- [x] 1.8 Versie 2.3.0 in `icms_version.php`

## 2. Tests en documentatie
- [x] 2.1 Contracttests per event (payload, resultaat, foutafhandeling)
- [x] 2.2 Regressietest: zonder luisteraars is het gedrag identiek aan 2.2.0
- [x] 2.3 Test: faalt `afterPlace`, dan bestaat geen bestelling
- [x] 2.4 `docs/EXTENSION_POINTS.md` met events, payloads, voorbeeld en compatibiliteitsmatrix
- [x] 2.5 Testdouble/stub van `SimplecartExtension` voor add-on-tests
- [ ] 2.6 README en release notes; release 2.3.0 vóór add-on 1.0.0
