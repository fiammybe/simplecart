## 0. Voorbereiding
- [x] 0.1 PR 38 (afsluiten vanaf de scanpagina) is gemerged

## 1. Kern
- [x] 1.1 `SimplecartExtension::API_REVISION = 3` en documentatie (revisie 2 werd al gebruikt door `add-order-questions`)
- [x] 1.2 `SimplecartExtension::resolveScan()`: payload met `method` en `action`, validatie en normalisatie van `actions`
- [x] 1.3 `scan.php`: POST-tak voor add-on-scans (rechten, CSRF-token, dispatch, redirect)
- [x] 1.4 Template: knoppen met token en optionele bevestiging
- [x] 1.5 `order.render` met `allow_close = false` verbergt en weigert het afsluiten
- [x] 1.6 Taalstrings (`english`, `nederlands`)
- [x] 1.7 `docs/EXTENSION_POINTS.md` bijwerken

## 2. Tests
- [x] 2.1 Actienamen en labels worden gevalideerd en geëscaped
- [x] 2.2 Een POST met een ongeldig token of zonder beheerderrechten bereikt de add-on niet
- [x] 2.3 `allow_close = false` weigert het afsluiten bij GET en POST
- [x] 2.4 Een add-on zonder acties werkt zoals voorheen
- [ ] 2.5 Handmatig nakijken op een testsite: knop, bevestiging, redirect na POST, en een bestelling met `allow_close = false`

## 3. Afronding
- [x] 3.1 Release-notes; versie van simplecart verhogen volgens de volgorde van de PR's (2.7.0)
