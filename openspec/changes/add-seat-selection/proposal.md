## Why

Simplecart verkoopt vandaag producten met een aantal. Voor optredens in een zaal moeten bezoekers hun plaatsen zelf op een zaalkaart kunnen kiezen. Een plaats is uniek en hoort bij één optreden, dus het model "product × aantal" volstaat niet. Zonder plaatskeuze moeten organisatoren plaatsen handmatig toewijzen en kan een plaats dubbel verkocht worden.

## What Changes

- Nieuwe objecten: zaal (`venue`), optreden (`event`), prijszone (`seatzone`), plaats (`seat`) en plaatsstatus per optreden (`eventseat`).
- Zaalkaart als inline SVG, gegenereerd uit een JSON-beschrijving van rijen en secties, met interactieve plaatskeuze op de frontend (progressive enhancement; fallback per zone zonder JavaScript).
- Tijdelijke vasthouding van gekozen plaatsen (standaard 15 minuten) met atomaire reservatie en afteltimer.
- Winkelwagen en checkout ondersteunen plaatsen naast gewone producten; prijs komt server-side uit de zone.
- Onbetaalde bestellingen vervallen na een instelbare termijn en geven hun plaatsen vrij.
- Toegangscontrole per plaats (QR per ticket) naast de bestaande QR per bestelling.
- Beheerschermen voor zalen, optredens, zones, bezetting, blokkeren/vrijgeven en CSV-export.
- **BREAKING (intern)**: `orderitem` krijgt `item_type`, `event_id`, `seat_id`, `seat_label`; `SimplecartOrderHandler::placeOrder()` en de sessiestructuur van de winkelwagen worden uitgebreid. Bestaande bestellingen blijven ongewijzigd werken (`item_type = 'product'`).
- De README-uitspraak "geen client-side JavaScript-laag" vervalt voor de zaalkaart.

## Capabilities

### New Capabilities
- `venue-management`: zalen, optredens, zones en plaatsen beheren en publiceren.
- `seat-map`: zaalkaart renderen en beschikbaarheid tonen op de frontend.
- `seat-reservation`: plaatsen tijdelijk vasthouden en vrijgeven.
- `seat-checkout`: plaatsen opnemen in winkelwagen, bestelling en betaling, inclusief vervallen bestellingen.
- `ticket-validation`: toegang per plaats scannen.

### Modified Capabilities
Geen: er bestaan nog geen OpenSpec-specs voor de huidige productflow. Het gedrag van de bestaande productflow blijft behouden en is gedekt in `seat-checkout`.

## Impact

- Code: `src/icms_version.php` (object_items, templates, config), `src/include/common.php` (cart), `src/class/order.php`, `src/class/orderitem.php`, `src/class/OrderConfirmationEmail.php`, `src/checkout.php`, `src/scan.php`, `src/admin/menu.php`, `src/include/onupdate.inc.php`.
- Nieuw: klassen `venue`, `event`, `seatzone`, `seat`, `eventseat`; pagina `event.php`; `ajax/hold.php`, `ajax/release.php`, `ajax/seatstatus.php`; `assets/js/seatmap.js`, `assets/css/seatmap.css`; admin- en frontendtemplates; taalstrings (`english`, `nederlands`).
- Database: vijf nieuwe tabellen, uitgebreide `orderitem`, unieke index `(event_id, seat_id)` op `eventseat`; vereist InnoDB voor transacties.
- Configuratie: hold-duur, betalingstermijn, maximum plaatsen per bestelling.
- Versie: 3.0.0 (fase 1–3), uitbreidingen in 3.1.x.
