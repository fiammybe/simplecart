## 0. Voorbereiding
- [ ] 0.1 PR 38 (afsluiten vanaf de scanpagina) is gemerged

## 1. Kern
- [ ] 1.1 `SimplecartExtension::API_REVISION = 2` en documentatie
- [ ] 1.2 `SimplecartExtension::resolveScan()`: payload met `method` en `action`, validatie en normalisatie van `actions`
- [ ] 1.3 `scan.php`: POST-tak voor add-on-scans (rechten, CSRF-token, dispatch, redirect)
- [ ] 1.4 Template: knoppen met token en optionele bevestiging
- [ ] 1.5 `order.render` met `allow_close = false` verbergt en weigert het afsluiten
- [ ] 1.6 Taalstrings (`english`, `nederlands`)
- [ ] 1.7 `docs/EXTENSION_POINTS.md` bijwerken

## 2. Tests
- [ ] 2.1 Actienamen en labels worden gevalideerd en geëscaped
- [ ] 2.2 Een POST met een ongeldig token of zonder beheerderrechten bereikt de add-on niet
- [ ] 2.3 `allow_close = false` weigert het afsluiten bij GET en POST
- [ ] 2.4 Een add-on zonder acties werkt zoals voorheen

## 3. Afronding
- [ ] 3.1 Release-notes; versie van simplecart verhogen volgens de volgorde van de PR's
