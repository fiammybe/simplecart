## Context

Simplecart 2.x gebruikt IPF-objecten (`product`, `order`, `orderitem`), een sessiewinkelwagen (`$_SESSION['simplecart_cart'][product_id]`), SEPA-betaling met QR-code en een scanpagina met één token per bestelling. Tabellen worden uit de IPF-objecten in `object_items` aangemaakt en geüpgraded. Er is geen JavaScript-cartlaag in gebruik.

## Goals / Non-Goals

**Goals**
- Plaatsen kiezen op een kaart voor één optreden tegelijk, zonder dubbele verkoop.
- Bestaande productflow, SEPA-betaling en e-mails blijven werken.
- Geen nieuwe composer-afhankelijkheden voor de kaart.

**Non-Goals**
- Staanplaatsen of tafels met capaciteit (open vraag, niet in 3.0).
- Kortingstarieven per plaats, kassaverkoop en PDF-tickets (latere fasen).
- Realtime push (websockets); polling volstaat.
- Online betaalproviders; enkel SEPA.

## Decisions

### D1. Status per optreden in `eventseat`
De status leeft in `eventseat(event_id, seat_id, zone_id, status, hold_token, hold_expires, order_id, ticket_token, scanned_at, scanned_by)`, niet in `orderitem`.
- Voordeel: één query per optreden voor beschikbaarheid, en een unieke index `(event_id, seat_id)` maakt dubbele verkoop onmogelijk op databaseniveau.
- Alternatief verworpen: beschikbaarheid afleiden uit `orderitem` — traag en niet atomair te claimen.

### D2. Atomaire claim via voorwaardelijke UPDATE
Hold en verkoop gebeuren met `UPDATE ... WHERE status/ hold_token / hold_expires` en controle van `affected_rows`, per aanvraag in één transactie (alles of niets).
- Alternatief verworpen: eerst lezen, dan schrijven (race condition).
- Gevolg: InnoDB is vereist; de update controleert dit en documenteert het.

### D3. Lazy vervallen van holds en bestellingen
Verlopen holds zijn claimbaar via de voorwaarde `hold_expires < now`; er is geen cronjob. Vervallen onbetaalde bestellingen worden bij relevante aanvragen opgeruimd en kunnen door een beheerder in bulk vrijgegeven worden.
- Alternatief verworpen: cronjob — niet beschikbaar op elke ImpressCMS-installatie.

### D4. Zaalkaart als JSON + inline SVG
`venue.layout_json` beschrijft viewBox, podium, secties en rijen (`seats`, `x0`, `dx`, `curve`, `overrides`). Bij opslaan worden `seat`-rijen gesynchroniseerd. De server rendert de SVG; `seatmap.js` voegt selectie, zoom/pan en polling toe.
- Alternatief verworpen: canvas — slechtere toegankelijkheid; externe bibliotheek — onnodige afhankelijkheid.
- Toegankelijkheid: elke plaats is focusbaar met `aria-label` (rij, nummer, prijs, status).

### D5. Prijs per zone, server-side
Prijs hangt aan `seatzone`, nooit aan de client. `placeOrder()` leest de prijs bij het afronden opnieuw uit de zone en bewaart een snapshot in `orderitem`.

### D6. Winkelwagen uitgebreid, niet vervangen
De sessie krijgt naast productregels een sectie `seats[event_id][] = seat_id`. `simplecart_getCart()` valideert holds bij elke lezing. Eén optreden per winkelwagen in 3.0 (vereenvoudigt vragen, timer en maximum).

### D7. QR per plaats
`ticket_token` per `eventseat`; `scan.php` accepteert plaats + token met dezelfde atomaire `WHERE scanned_at = 0`-techniek als `markScanned()`. De bestaande ordertoken blijft werken en toont de scanstatus van alle plaatsen.

### D8. Migratie
IPF voegt kolommen en tabellen toe. `onupdate.inc.php` zet bestaande `orderitem`-rijen op `item_type = 'product'`, vervangt NULL's door defaults en maakt de unieke index `(event_id, seat_id)` handmatig aan, omdat IPF geen samengestelde unieke index kent.

## Risks / Trade-offs

- **Dubbele verkoop** → unieke index, atomaire UPDATE, transactie, concurrency-tests.
- **Verlaten winkelwagens blokkeren plaatsen** → korte holds, lazy vrijgave, betalingstermijn.
- **Polling-belasting bij grote zalen** → minimale payload `[seat_id, status]` met `since`-parameter, index `(event_id, status)`; richtwaarde tot ~2 000 plaatsen.
- **MyISAM-installaties zonder transacties** → controle bij update; terugval op enkel atomaire UPDATE's met expliciete compensatie.
- **Complexe editor vertraagt oplevering** → eerst rijengenerator + JSON, slepen later.
- **Migratie van bestaande orders** → default `item_type`, migratietest met productiedump.

## Migration Plan

1. Release 3.0.0 met updatehook: nieuwe tabellen, kolommen, index, `item_type`-backfill.
2. Rollback: module-update terugdraaien kan niet automatisch; back-up van de database vóór update is vereist (in release notes).
3. Bestaande producten en bestellingen blijven functioneel; nieuwe functies zijn onzichtbaar tot er een optreden is gepubliceerd.

## Open Questions

1. Staanplaatsen/tafels met capaciteit, of enkel genummerde plaatsen?
2. Verwachte zaalgrootte en aantal gelijktijdige kopers?
3. Kassaverkoop, gratis tickets en handmatige toewijzing in 3.0?
4. Kortingstarieven (kind, student, vrijwilliger/`helpende_hand`)?
5. Meerdere optredens in één bestelling?
6. Definitieve waarden voor hold-duur (voorstel 15 min) en betalingstermijn (voorstel 7 dagen)?
7. Regels voor rolstoelplaatsen (begeleider koppelen)?
8. Is een sleep-editor vereist voor de eerste release?
