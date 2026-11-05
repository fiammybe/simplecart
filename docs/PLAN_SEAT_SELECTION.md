# Plan: plaatskeuze op een zaalkaart (simplecart 3.0)

## 1. Doel

Bezoekers kiezen voor een optreden in een zaal hun plaatsen rechtstreeks op een kaart (zaalplan), leggen die in de winkelwagen en rekenen af via de bestaande SEPA-checkout. Beheerders bouwen zaalplannen, koppelen ze aan optredens en volgen bezetting en toegangscontrole op.

Uitgangspunten:
- Bestaande flow (sessiewinkelwagen, `placeOrder`, SEPA-QR, e-mails, scan) blijft werken; gewone producten blijven mogelijk.
- Geen zware framework-afhankelijkheid: server-side PHP/Smarty + één kleine vanilla JS-module met inline SVG.
- Dubbele verkoop van een plaats moet technisch onmogelijk zijn (database-constraint, niet enkel applicatiecontrole).

## 2. Huidige situatie (relevant)

| Onderdeel | Nu |
|---|---|
| Objecten | `product`, `order`, `orderitem` (IPF, tabellen automatisch via `object_items`) |
| Winkelwagen | `$_SESSION['simplecart_cart'][product_id] = {product_id, quantity}` (`include/common.php`) |
| Bestelling | `SimplecartOrderHandler::placeOrder()` maakt order + orderitems; prijs uit product |
| Betaling | enkel SEPA, QR-code, status `pending → awaiting_payment → paid` |
| Scan | `scan.php` met `scan_token` per order, één scan per order |
| Updates | `include/onupdate.inc.php` + IPF-upgrade van tabellen |

Beperking: een cart-item is "product × aantal". Een plaats is uniek (aantal altijd 1, eigen identiteit) en moet **per optreden** bezet kunnen zijn. Dat vraagt een apart datamodel naast `product`.

## 3. Datamodel (nieuwe IPF-objecten)

Toevoegen aan `$modversion['object_items']` in `icms_version.php`.

1. **venue** (zaal): `venue_id`, `name`, `address`, `layout_json` (zie §4), `width`, `height`, `background_image` (optioneel plattegrond), `active`.
2. **event** (optreden): `event_id`, `venue_id`, `title`, `description`, `image`, `starts_at`, `doors_at`, `sales_open_at`, `sales_close_at`, `max_seats_per_order` (default 8), `active`.
3. **seatzone** (prijscategorie per optreden): `zone_id`, `event_id`, `name` (bv. "Balkon", "Parterre"), `price`, `color`. Prijs hangt dus aan de zone, niet aan de plaats.
4. **seat** (plaats, onderdeel van de zaal): `seat_id`, `venue_id`, `section`, `row_label`, `seat_number`, `x`, `y`, `type` (`seat|wheelchair|companion`), `blocked` (permanent onbruikbaar, bv. zichtbelemmering). Wordt gegenereerd uit/synchroon met `layout_json`.
5. **eventseat** (status van een plaats per optreden) – kern van de integriteit:
   `eventseat_id`, `event_id`, `seat_id`, `zone_id`, `status` (`available|held|sold|blocked`), `hold_token` (sessie-id), `hold_expires`, `order_id`.
   **Unieke index op `(event_id, seat_id)`**. Rijen worden bij publicatie van het optreden aangemaakt.
6. **orderitem** uitbreiden: `event_id`, `seat_id`, `seat_label` (snapshot, bv. "Rij C – plaats 12"), `item_type` (`product|seat`). Bestaande rijen krijgen `item_type = 'product'`.

Sleutelkeuze: status en reservatie in `eventseat`, niet in `orderitem`, zodat beschikbaarheid met één query per optreden opgehaald wordt en de unieke index dubbele verkoop blokkeert.

## 4. Zaalkaart: formaat en editor

**Formaat**: `layout_json` beschrijft de zaal als vectordata:
```json
{
  "viewBox": [0, 0, 1000, 700],
  "stage": {"x": 300, "y": 20, "w": 400, "h": 60, "label": "Podium"},
  "sections": [
    {"id": "parterre", "label": "Parterre",
     "rows": [{"label": "A", "y": 140, "seats": 20, "x0": 200, "dx": 30, "curve": 0}]}
  ],
  "labels": [{"x": 500, "y": 650, "text": "Ingang"}]
}
```
Rijen met `seats/x0/dx/curve` laten toe snel grote zalen te genereren (ook gebogen rijen); individuele afwijkingen (ontbrekende stoel, rolstoelplaats) via `overrides`.

**Rendering**: inline SVG, één `<circle>`/`<rect>` per plaats met `data-seat-id`. Geen canvas, zodat toetsenbord- en schermlezertoegang (focus, `aria-label="Rij C plaats 12, € 25, beschikbaar"`) haalbaar is.

**Admin-editor (fase 2)**: eenvoudige editor in `admin/venue.php`:
- Fase 2a: JSON + generator-formulier (rijen toevoegen met aantal, afstand, kromming) met live SVG-preview.
- Fase 2b (optioneel): slepen/selecteren van plaatsen, plattegrond-afbeelding als achtergrond, bulk-toewijzing van zone.
- Import/export van `layout_json` zodat zalen hergebruikt kunnen worden.

## 5. Reservatie- en winkelwagenflow

### 5.1 Front-end
- `index.php` toont lijst optredens (naast producten); nieuw `event.php?event_id=` toont de kaart.
- `event.php` rendert SVG server-side met initiële status; JS (`assets/js/seatmap.js`) voegt interactie toe: klik = selecteer/deselecteer, zoom/pan (pinch op mobiel), legenda per zone, totaalprijs, "Voeg toe aan winkelwagen".
- Beschikbaarheid wordt elke ~10–15 s ververst via `ajax/seatstatus.php?event_id=&since=` (JSON, alleen gewijzigde plaatsen) zodat bezette plaatsen grijs worden.
- Zonder JS: fallback-formulier met keuze per zone + aantal ("beste beschikbare plaatsen"), zodat de pagina bruikbaar blijft.

### 5.2 Tijdelijke vasthouding (hold)
- Bij "toevoegen aan winkelwagen" roept de browser `ajax/hold.php` aan (POST + CSRF-token `simplecart_seat`).
- Server doet atomair per plaats:
  ```sql
  UPDATE eventseat
     SET status='held', hold_token=:sid, hold_expires=:now+900
   WHERE event_id=:e AND seat_id=:s
     AND (status='available' OR (status='held' AND hold_expires < :now) OR hold_token=:sid)
  ```
  `affected_rows = 1` → gelukt; anders "plaats niet meer beschikbaar". Alles-of-niets per aanvraag (transactie), zodat een groep geen halve selectie krijgt.
- Hold-duur: 15 min (instelling), met zichtbare afteltimer in de winkelwagen; verlengen bij checkout-start (eenmalig).
- Verlopen holds worden "lazy" vrijgegeven (voorwaarde `hold_expires < now`) en extra opgeruimd door een opruimfunctie bij elke aanvraag; geen cronjob vereist.
- Winkelwagen: `$_SESSION['simplecart_cart']` krijgt een tweede sectie `seats[event_id][] = seat_id`. `simplecart_getCart()` controleert bij elke lezing of de holds nog geldig zijn en verwijdert verlopen plaatsen met melding.
- Beperkingen: `max_seats_per_order`, geen verkoop buiten `sales_open_at/sales_close_at`, geen verkoop na `starts_at`.

### 5.3 Checkout
- `placeOrder()` uitbreiden: voor elke plaats in de cart `UPDATE eventseat SET status='sold', order_id=:o WHERE ... AND hold_token=:sid AND hold_expires >= :now` binnen één databasetransactie. Mislukt één plaats → rollback, melding, cart opnieuw tonen.
- Prijs wordt **server-side** uit `seatzone.price` gehaald (nooit uit de browser), zoals nu voor producten.
- `orderitem` bevat snapshot (`product_name` = titel optreden, `seat_label`, prijs); aantal = 1.
- Betaling blijft SEPA. Nieuw: **betalingstermijn** (bv. 7 dagen). Onbetaalde orders (`pending/awaiting_payment`) vervallen automatisch en geven de plaatsen vrij (lazy check + admin-knop "vervallen orders vrijgeven"). Status `cancelled` en admin-annulatie geven plaatsen eveneens vrij.
- Bevestigingsmail en checkout-succespagina tonen optreden, datum, plaatsen en prijs per plaats.

## 6. Toegangscontrole (QR per ticket)

Nu: één `scan_token` per order, één scan per order. Voor zaalbezoek is **één QR per plaats** zinvoeler:
- `eventseat.ticket_token` + `scanned_at/scanned_by` per plaats; `scan.php` accepteert `?seat=…&token=…`.
- Order-QR blijft werken als "alle plaatsen van deze bestelling" en toont de lijst met reeds gescande plaatsen.
- `markScanned()` krijgt de bestaande atomaire `WHERE scanned_at = 0`-aanpak per plaats.
- Fase 5: optionele printbare/PDF-ticketpagina per bestelling.

## 7. Beheer (admin)

Nieuwe menu-items in `admin/menu.php`: **Zalen**, **Optredens**, plus uitgebreid dashboard.
- Zalen: aanmaken, kaart bewerken (§4), kopiëren.
- Optredens: koppelen aan zaal, zones en prijzen instellen, plaatsen aan zone toewijzen, plaatsen blokkeren (techniek, gasten, pers), publiceren (genereert `eventseat`-rijen).
- Bezettingsoverzicht: kaart met kleuren (vrij/gehouden/verkocht/geblokkeerd), klik op plaats toont bestelling en klant; handmatig plaats vrijgeven of toewijzen (bv. gratis ticket/kassaverkoop).
- Export: CSV met bezette plaatsen per optreden en gastenlijst.
- Dashboard: verkochte plaatsen en omzet per optreden, bezettingsgraad.
- Bestelbeheer: bij annuleren/terugbetalen vraag "plaatsen vrijgeven?".

## 8. Techniek en integratie met ImpressCMS

- **Tabellen**: nieuwe objecten komen automatisch via `object_items`; de nieuwe kolommen op `orderitem` via de IPF-upgrade. In `onupdate.inc.php` een migratiestap: bestaande `orderitem`-rijen `item_type='product'`; de unieke index `(event_id, seat_id)` expliciet aanmaken (IPF maakt geen unieke samengestelde indexen) en ontbrekende NULL's naar defaults zetten (zoals de bestaande `simplecart_replaceNullsWithEmptyStrings`). `SIMPLECART_DB_VERSION` verhogen.
- **Transacties**: tabellen moeten InnoDB zijn; controleren in de update en documenteren. Zonder InnoDB valt de rollback weg — dan alleen op de atomaire `UPDATE ... WHERE`-voorwaarden vertrouwen en bij mislukking expliciet terugdraaien.
- **Bestanden** (nieuw): `class/venue.php`, `event.php` (class), `seatzone.php`, `seat.php`, `eventseat.php`, `event.php` (front), `ajax/hold.php`, `ajax/release.php`, `ajax/seatstatus.php`, `assets/js/seatmap.js`, `assets/css/seatmap.css`, templates `simplecart_event.html.tpl`, `simplecart_admin_venue.html.tpl`, `simplecart_admin_event.html.tpl`, taalbestanden `english` + `nederlands`.
- **Wijzigingen**: `include/common.php` (cart-sectie voor plaatsen), `class/order.php` (`placeOrder`, status-hooks), `class/orderitem.php`, `class/OrderConfirmationEmail.php`, `checkout.php` + templates, `scan.php`, `icms_version.php` (objecten, templates, config: hold-minuten, betalingstermijn, max plaatsen).
- **Beveiliging**: CSRF-token op alle AJAX-POSTs, alle `event_id/seat_id` als int casten en tegen het optreden valideren, geen prijs of status uit de client, rate limiting op `hold.php` per sessie (bv. 30/min), uitvoer escapen in SVG-labels.
- **Prestaties**: één query per `seatstatus`-aanvraag op index `(event_id, status)`; payload enkel `[seat_id, status]`; kaart server-side gecached als `layout_json` ongewijzigd is. Realistisch bereik: zalen tot ~2 000 plaatsen zonder problemen.
- **Afhankelijkheden**: geen nieuwe composer-pakketten voor de kaart. (Het README noemt "geen client-side JS-laag"; dat wordt aangepast: JS is nu progressive enhancement voor de kaart. Let op: `assets/js/cart.js` is verouderd/ongebruikt en kan opgeruimd worden.)

## 9. Fasering

| Fase | Inhoud | Resultaat |
|---|---|---|
| 0 | Ontwerpkeuzes bevestigen (§11), InnoDB-check, testomgeving met demo-zaal | Akkoord op scope |
| 1 | Datamodel (venue, event, seatzone, seat, eventseat, orderitem-uitbreiding), migratie, basis-admin CRUD voor zaal/optreden/zone | Zaal en optreden aan te maken |
| 2 | Kaartgenerator + SVG-weergave (admin-preview en front), zone-toewijzing, publiceren | Kaart zichtbaar, read-only |
| 3 | Selectie, hold/release, cart-uitbreiding, timer, `placeOrder` met transactie, vervaltermijn orders | Volledige aankoopflow |
| 4 | Admin-bezettingskaart, blokkeren/vrijgeven, CSV-export, dashboard, mails | Operationeel voor organisatoren |
| 5 | QR per plaats, scan, printbare tickets | Toegangscontrole |
| 6 | Toegankelijkheid, mobiele UX (pinch-zoom), editor met slepen, afwerking en documentatie | Productiekwaliteit |

Fase 1–3 vormen een bruikbare eerste release (versie 3.0.0); 4–6 volgen als 3.1.x.

## 10. Testplan

- **Concurrentie** (belangrijkst): twee sessies houden dezelfde plaats tegelijk vast (script met parallelle requests); exact één slaagt. Idem bij `placeOrder`.
- Hold verloopt tijdens checkout → duidelijke foutmelding, geen order met ontbrekende plaatsen.
- Alles-of-niets: selectie van 4 plaatsen waarvan 1 bezet → geen enkele wordt vastgehouden.
- Prijsmanipulatie (POST met andere prijs/zone) wordt genegeerd.
- Order annuleren/vervallen → plaatsen worden vrij; opnieuw koopbaar.
- Migratie vanaf 2.2.0 met bestaande orders: oude bestellingen en scan werken ongewijzigd.
- Browsertests (Playwright): desktop en telefoon, toetsenbordnavigatie, schermlezerlabels, zonder JS fallback.
- Prestatie: zaal van 2 000 plaatsen, 100 gelijktijdige gebruikers op `seatstatus`.

## 11. Open vragen

1. Zijn het **genummerde plaatsen** overal, of ook staanplaatsen/tafels met capaciteit (dan `capacity` per zone i.p.v. per plaats)?
2. Welke **zaalgrootte** en hoeveel gelijktijdige kopers (bepaalt polling vs. websockets/SSE)?
3. Wil je **kassaverkoop/handmatige toewijzing** door beheerders en gratis tickets/uitnodigingen?
4. **Kortingen/tarieven** (kind, student, vrijwilliger — `helpende_hand` bestaat al) per plaats of per bestelling?
5. Moet een bestelling **meerdere optredens** mogen bevatten, of één optreden per winkelwagen?
6. Betalingstermijn en hold-duur: welke waarden (voorstel 15 min / 7 dagen)?
7. **Rolstoelplaatsen**: speciale regels (begeleider verplicht aan te koppelen)?
8. Is een **editor met slepen** vereist, of volstaat de rijen-generator voor de eerste release?

## 12. Risico's

| Risico | Maatregel |
|---|---|
| Dubbele verkoop | Unieke index + atomaire UPDATE + transactie, concurrency-tests |
| IPF maakt geen samengestelde unieke index | Handmatige SQL in install/update, controle in dashboard |
| Verlaten winkelwagens blokkeren plaatsen | Korte holds, lazy vrijgave, vervaltermijn orders |
| Complexe editor vertraagt project | Eerst generator + JSON, slepen later |
| Kaart onbruikbaar op telefoon | Pinch-zoom, grote tap-doelen, fallback zonder JS |
| Migratie breekt bestaande orders | `item_type`-default, migratietest met productiedump |
