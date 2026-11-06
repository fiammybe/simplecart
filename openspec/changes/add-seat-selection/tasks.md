## 0. Voorbereiding
- [ ] 0.1 Open vragen uit design.md beantwoorden en scope bevestigen
- [ ] 0.2 Testomgeving met ImpressCMS, InnoDB-controle en demo-zaal

## 1. Datamodel en migratie
- [ ] 1.1 IPF-klassen `venue`, `event`, `seatzone`, `seat`, `eventseat` aanmaken en registreren in `object_items`
- [ ] 1.2 `orderitem` uitbreiden met `item_type`, `event_id`, `seat_id`, `seat_label`
- [ ] 1.3 `onupdate.inc.php`: backfill `item_type='product'`, NULL-defaults, unieke index `(event_id, seat_id)`, InnoDB-controle
- [ ] 1.4 Configuratie-items: hold-duur, betalingstermijn, maximum plaatsen per bestelling
- [ ] 1.5 Taalstrings (`english`, `nederlands`)

## 2. Beheer: zalen en optredens
- [ ] 2.1 Admin CRUD voor zalen met rijengenerator en live SVG-preview
- [ ] 2.2 Admin CRUD voor optredens en zones; plaatsen aan zones toewijzen
- [ ] 2.3 Plaatsen blokkeren; optreden publiceren (genereert `eventseat`-rijen)
- [ ] 2.4 Menu-items en templates (`admin/menu.php`)

## 3. Zaalkaart op de frontend
- [ ] 3.1 `event.php` en `index.php`-overzicht van optredens
- [ ] 3.2 Server-side SVG-rendering met statussen en toegankelijkheidslabels
- [ ] 3.3 `seatmap.js`: selectie, zoom/pan (pinch), legenda, totaalprijs
- [ ] 3.4 Fallback zonder JavaScript (keuze per zone + aantal)
- [ ] 3.5 `ajax/seatstatus.php` met `since`-parameter en polling

## 4. Reservatie en winkelwagen
- [ ] 4.1 `ajax/hold.php` en `ajax/release.php` met CSRF en rate limiting
- [ ] 4.2 Atomaire alles-of-niets hold met transactie
- [ ] 4.3 Cart-uitbreiding in `include/common.php` met validatie en timer
- [ ] 4.4 Grenzen: verkoopvenster, maximum plaatsen, één optreden per cart

## 5. Checkout en bestellingen
- [ ] 5.1 `placeOrder()`: plaatsen op `sold` in één transactie, server-side prijs, snapshot in `orderitem`
- [ ] 5.2 Checkout-template, succespagina en bevestigingsmail met plaatsdetails
- [ ] 5.3 Vervallen onbetaalde bestellingen en vrijgave bij annulatie
- [ ] 5.4 Admin-actie "vervallen bestellingen vrijgeven"

## 6. Beheer: bezetting en rapportering
- [ ] 6.1 Bezettingskaart in admin met bestelling/klant per plaats
- [ ] 6.2 Handmatig vrijgeven of blokkeren
- [ ] 6.3 CSV-export per optreden
- [ ] 6.4 Dashboardcijfers (verkocht, omzet, bezettingsgraad)

## 7. Toegangscontrole
- [ ] 7.1 `ticket_token` per plaats en QR-generatie
- [ ] 7.2 `scan.php` per plaats met atomaire scan; ordertoken toont alle plaatsen
- [ ] 7.3 Optioneel: printbare tickets

## 8. Kwaliteit en afwerking
- [ ] 8.1 Concurrentietests (parallelle holds en `placeOrder`)
- [ ] 8.2 Tests: verlopen hold, deels bezette selectie, prijsmanipulatie, annulatie
- [ ] 8.3 Migratietest vanaf 2.2.0 met bestaande bestellingen
- [ ] 8.4 Browsertests (desktop, telefoon, toetsenbord, zonder JS)
- [ ] 8.5 Prestatietest 2 000 plaatsen
- [ ] 8.6 README, release notes en versie 3.0.0
