## 0. Voorbereiding
- [ ] 0.1 Open vragen uit design.md beantwoorden en scope bevestigen
- [ ] 0.2 Preload-handler van de ondersteunde ImpressCMS-versie verifiëren (custom events, luisteraars per module)
- [ ] 0.3 Repository `fiammybe/simplecart-seats` laten aanmaken (door de eigenaar), met licentie, README en toegang voor deze sessie
- [ ] 0.4 Testomgeving met ImpressCMS, InnoDB-controle en demo-zaal

## 1. Kern: extension points (simplecart 2.3.0)
- [ ] 1.1 `SimplecartExtension`-klasse met dispatch en `SIMPLECART_EXTENSION_API`
- [ ] 1.2 Event `cart.summary` in `simplecart_getCartSummary()` en render in `index.php`/`checkout.php`
- [ ] 1.3 Events `order.collectLines`, `order.beforePlace`, `order.afterPlace` in `placeOrder()`, binnen één transactie
- [ ] 1.4 `placeOrder()` aanvaardt bestellingen met enkel add-onregels
- [ ] 1.5 Event `order.cancelled` bij annulatie, verwijdering en statuswijziging naar `cancelled`
- [ ] 1.6 Event `order.render` in bevestigingsmail en succespagina
- [ ] 1.7 Event `scan.resolve` in `scan.php`
- [ ] 1.8 Contracttests en regressietest: zonder luisteraars is het gedrag identiek aan 2.2.0
- [ ] 1.9 Documentatie van de extension points in `docs/EXTENSION_POINTS.md`, inclusief compatibiliteitsmatrix
- [ ] 1.10 Testdouble/stub van `SimplecartExtension` voor add-on-tests publiceren
- [ ] 1.11 Release 2.3.0 vóór de add-on 1.0.0 vrijgeven

## 2. Add-on (repository `simplecart-seats`): skelet en datamodel
- [ ] 2.0 Repository opzetten: structuur `src/`, `openspec/`, CI (lint, tests, release-pakket), `.gitignore`; specs `addon-packaging`, `venue-management`, `seat-map`, `seat-reservation`, `seat-checkout`, `ticket-validation` uit deze wijziging verplaatsen naar een change `add-seat-selection` daar
- [ ] 2.1 `src/icms_version.php` met afhankelijkheidscontrole op simplecart ≥ 2.3.0 en extension-API ≥ 1
- [ ] 2.2 IPF-klassen `venue`, `event`, `seatzone`, `seat`, `eventseat`, `orderseat`
- [ ] 2.3 Installatiehook: InnoDB-controle, unieke index `(event_id, seat_id)`
- [ ] 2.4 Deïnstallatiehook met bevestiging; configuratie (hold-duur, betalingstermijn, maximum plaatsen)
- [ ] 2.5 Taalstrings (`english`, `nederlands`)
- [ ] 2.6 Preload-klasse die op de kern-events luistert
- [ ] 2.7 Release-builder (GitHub Actions) voor het add-on-pakket, naar het voorbeeld van simplecart

## 3. Add-on: beheer van zalen en optredens
- [ ] 3.1 Admin CRUD voor zalen met rijengenerator en live SVG-preview
- [ ] 3.2 Admin CRUD voor optredens en zones; plaatsen aan zones toewijzen
- [ ] 3.3 Plaatsen blokkeren; optreden publiceren (genereert `eventseat`-rijen)

## 4. Add-on: zaalkaart op de frontend
- [ ] 4.1 `event.php` en overzicht van optredens
- [ ] 4.2 Server-side SVG-rendering met statussen en toegankelijkheidslabels
- [ ] 4.3 `seatmap.js`: selectie, zoom/pan, legenda, totaalprijs
- [ ] 4.4 Fallback zonder JavaScript
- [ ] 4.5 `ajax/seatstatus.php` met `since` en polling

## 5. Add-on: reservatie, winkelwagen en checkout
- [ ] 5.1 `ajax/hold.php` en `ajax/release.php` met CSRF en rate limiting
- [ ] 5.2 Atomaire alles-of-niets hold; eigen sessiesectie en timer
- [ ] 5.3 `cart.summary`, `collectLines`, `beforePlace`, `afterPlace` implementeren
- [ ] 5.4 Prijs server-side, `orderseat`-koppeling
- [ ] 5.5 Vervallen bestellingen en vrijgave bij `order.cancelled`; admin-actie voor bulk-vrijgave
- [ ] 5.6 Mail en succespagina via `order.render`

## 6. Add-on: bezetting en rapportering
- [ ] 6.1 Bezettingskaart in admin met bestelling/klant per plaats
- [ ] 6.2 Handmatig vrijgeven of blokkeren
- [ ] 6.3 CSV-export en dashboardcijfers

## 7. Add-on: toegangscontrole
- [ ] 7.1 `ticket_token` per plaats en QR-generatie
- [ ] 7.2 `scan.resolve` met atomaire scan per plaats; ordertoken toont alle plaatsen
- [ ] 7.3 Optioneel: printbare tickets

## 8. Kwaliteit en afwerking
- [ ] 8.1 Concurrentietests (parallelle holds en `placeOrder`)
- [ ] 8.2 Tests: verlopen hold, deels bezette selectie, prijsmanipulatie, annulatie, add-on uitgeschakeld
- [ ] 8.3 Test: kern 2.3.0 zonder add-on, en upgrade van 2.2.0 met bestaande bestellingen
- [ ] 8.3b CI-matrix van de add-on tegen laagste en hoogste ondersteunde simplecart
- [ ] 8.4 Browsertests (desktop, telefoon, toetsenbord, zonder JS)
- [ ] 8.5 Prestatietest 2 000 plaatsen
- [ ] 8.6 README's (beide repositories), release notes, versies kern 2.3.0 en add-on 1.0.0
