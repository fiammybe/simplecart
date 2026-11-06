## Context

Simplecart 2.x gebruikt IPF-objecten (`product`, `order`, `orderitem`), een sessiewinkelwagen (`$_SESSION['simplecart_cart'][product_id]`), SEPA-betaling met QR-code en een scanpagina met één token per bestelling. Tabellen worden uit de IPF-objecten in `object_items` aangemaakt en geüpgraded. De kern gebruikt geen JavaScript-cartlaag.

Plaatskeuze is niet voor iedereen nodig en brengt aanzienlijke complexiteit (concurrentie, timers, kaart-editor). Ze mag de eenvoud van de kern niet aantasten.

## Goals / Non-Goals

**Goals**
- Plaatskeuze als aparte, optionele module; zonder de module verandert er niets voor gebruikers van simplecart.
- Kern blijft generiek: geen kennis van zalen, optredens of plaatsen.
- Geen dubbele verkoop van een plaats.
- Geen nieuwe composer-afhankelijkheden voor de kaart.

**Non-Goals**
- Staanplaatsen of tafels met capaciteit (open vraag).
- Kortingstarieven, kassaverkoop en PDF-tickets in de eerste release.
- Realtime push; polling volstaat.
- Andere betaalmethoden dan SEPA.
- Een volledig plug-inframework: de extension points zijn bewust klein en geschikt voor deze add-on, andere add-ons kunnen ze hergebruiken.

## Decisions

### D1. Aparte ImpressCMS-module in een eigen repository
De add-on is een eigen module (`dirname = simplecartseats`) in de repository `fiammybe/simplecart-seats`, met eigen versie, issues, CI en release-pakket. Simplecart bevat enkel de extension points en hun documentatie.
- Voordeel: onafhankelijke release-cyclus, kern blijft klein, bijdragers en issues gescheiden, duidelijk optioneel.
- Alternatief verworpen: featureschakelaar in de kern — tabellen en code zouden altijd meegeleverd worden en migraties raken alle gebruikers.
- Alternatief verworpen: add-on in dezelfde repository — koppelt releases en CI aan elkaar.
- Gevolg: het contract tussen beide repositories is de extension-API (`SIMPLECART_EXTENSION_API`) en moet strikt versiebeheerd zijn; de add-on declareert welke API-versie hij nodig heeft.
- Gevolg: de add-on heeft een eigen kopie van de ImpressCMS-testopzet; de kern levert een stub/testdouble van `SimplecartExtension` mee voor contracttests.

### D2. Extension points via ImpressCMS-preload-events
De kern dispatcht events via de ImpressCMS preload-handler (`icms::$preload->triggerEvent(...)`); de add-on luistert met een preload-klasse in `preload/`. Een dunne klasse `SimplecartExtension` in de kern verpakt de dispatch zodat de eventnamen en payloads op één plek gedocumenteerd en getest zijn. Zijn er geen luisteraars, dan doen de events niets.
- Alternatief verworpen: eigen registratietabel — extra database en beheer.
- Te verifiëren: de exacte API van de preload-handler in de ondersteunde ImpressCMS-versie (taak 0.2).

Events (naam → doel):
- `simplecart.cart.summary` → add-on voegt regels toe aan de winkelwageninhoud en totaal.
- `simplecart.order.collectLines` → add-on levert bestelregels (naam, prijs, aantal, meta).
- `simplecart.order.beforePlace` → add-on valideert en kan afbreken met een foutmelding.
- `simplecart.order.afterPlace` → add-on legt definitief vast (bv. plaatsen op `sold`), binnen dezelfde transactie.
- `simplecart.order.cancelled` → add-on geeft resources vrij.
- `simplecart.order.render` → extra tekst/regels voor mail en succespagina.
- `simplecart.scan.resolve` → add-on verwerkt eigen scan-URL's.

### D3. Winkelwagen: aparte sessiesectie
De kern behoudt `simplecart_cart`; de add-on beheert `$_SESSION['simplecartseats_cart']`. De kern toont alleen wat de event `cart.summary` teruggeeft. Eén optreden per winkelwagen in de eerste release.

### D4. Orderitems zonder kernmigratie
De add-on laat de kern per regel een gewoon `orderitem` aanmaken (`product_name` = titel + plaatslabel, `product_price`, `quantity = 1`). De koppeling plaats ↔ orderitem staat in de add-on-tabel `orderseat`. De kern-`orderitem` blijft ongewijzigd.
- Gevolg: bij deïnstallatie blijven bestellingen leesbaar en kloppend.
- Gevolg: `placeOrder()` moet een bestelling met enkel add-onregels toelaten (huidige controle "geen geldige items" wordt aangepast).

### D5. Status per optreden in `eventseat`
`eventseat(event_id, seat_id, zone_id, status, hold_token, hold_expires, order_id, ticket_token, scanned_at, scanned_by)` met unieke index `(event_id, seat_id)`.
- Alternatief verworpen: beschikbaarheid afleiden uit orderitems — traag en niet atomair.

### D6. Atomaire claim
Hold en verkoop met voorwaardelijke `UPDATE ... WHERE` en controle van `affected_rows`, per aanvraag in één transactie (alles of niets). `afterPlace` loopt in de transactie van `placeOrder()`; faalt de add-on, dan wordt de hele bestelling teruggedraaid. InnoDB vereist.

### D7. Lazy vervallen
Verlopen holds zijn claimbaar via `hold_expires < now`; vervallen bestellingen worden bij relevante aanvragen opgeruimd en kunnen in bulk door een beheerder vrijgegeven worden. Geen cronjob nodig.

### D8. Zaalkaart als JSON + inline SVG
`venue.layout_json` beschrijft viewBox, podium, secties en rijen (`seats`, `x0`, `dx`, `curve`, `overrides`). De server rendert SVG; `seatmap.js` voegt selectie, zoom/pan en polling toe. Elke plaats is focusbaar met `aria-label`.

### D9. Prijs per zone, server-side
Prijs hangt aan `seatzone`; `collectLines` leest de prijs opnieuw uit de database en nooit uit de client.

### D10. QR per plaats via `scan.resolve`
De add-on registreert eigen scan-URL's (`ticket_token` per plaats); de bestaande ordertoken van de kern blijft ongewijzigd en toont via `order.render` de scanstatus van de plaatsen.

### D11. Afhankelijkheid en compatibiliteit
De add-on controleert bij installatie en bij elke aanvraag dat simplecart actief is en minstens versie 2.3.0 heeft; zo niet, dan weigert hij te installeren respectievelijk blijft hij inactief met een melding. De kern controleert niets over de add-on.

## Risks / Trade-offs

- **Extension points te beperkt of instabiel** → klein houden, documenteren, strikt versiebeheer (`SIMPLECART_EXTENSION_API = 1`, semver-regels: breuk = nieuw hoofdnummer) en contracttests in de kern.
- **Versies lopen uit de pas tussen twee repositories** → de add-on controleert API-versie en kernversie bij installatie en gebruik; compatibiliteitsmatrix in beide README's; CI van de add-on test tegen de laagste en hoogste ondersteunde simplecart.
- **Preload-API verschilt per ImpressCMS-versie** → verificatie in taak 0.2; terugval op een eigen eenvoudige listener-registratie in `SimplecartExtension`.
- **Dubbele verkoop** → unieke index, atomaire UPDATE, transactie, concurrency-tests.
- **Add-on uitgeschakeld terwijl plaatsen gehouden worden** → holds verlopen vanzelf; verkochte plaatsen blijven verkocht.
- **MyISAM zonder transacties** → bij installatie controleren en weigeren.
- **Polling-belasting bij grote zalen** → minimale payload, `since`-parameter, index `(event_id, status)`; richtwaarde ~2 000 plaatsen.
- **Complexe editor** → eerst rijengenerator + JSON, slepen later.

## Migration Plan

1. Kern 2.3.0: extension points toevoegen; geen databasewijzigingen en geen gedragswijziging zonder add-on.
2. Add-on 1.0.0 (eigen repository en release): afzonderlijk te installeren; maakt eigen tabellen en index aan.
3. Rollback add-on: deïnstalleren verwijdert enkel add-on-tabellen na een bevestiging; bestellingen in de kern blijven bestaan.
4. Back-up van de database vóór installatie van de add-on wordt aanbevolen.

## Open Questions

1. Staanplaatsen/tafels met capaciteit, of enkel genummerde plaatsen?
2. Verwachte zaalgrootte en aantal gelijktijdige kopers?
3. Kassaverkoop, gratis tickets en handmatige toewijzing in 1.0?
4. Kortingstarieven (kind, student, vrijwilliger/`helpende_hand`)?
5. Meerdere optredens in één bestelling?
6. Definitieve waarden voor hold-duur (voorstel 15 min) en betalingstermijn (voorstel 7 dagen)?
7. Regels voor rolstoelplaatsen (begeleider koppelen)?
8. Is een sleep-editor vereist voor de eerste release?
9. Naam en eigenaar van de nieuwe repository (voorstel `fiammybe/simplecart-seats`) en licentie (voorstel MIT, zoals simplecart)?
