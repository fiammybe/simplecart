## Why

Simplecart verkoopt producten met een aantal. Voor optredens in een zaal moeten bezoekers hun plaatsen op een zaalkaart kunnen kiezen. Een plaats is uniek en hoort bij één optreden, dus het model "product × aantal" volstaat niet. Niet elke simplecart-installatie heeft dit nodig (veel gebruikers verkopen enkel producten), en de kern moet eenvoudig blijven. Daarom wordt plaatskeuze een **optionele add-on**: een aparte ImpressCMS-module die alleen werkt als ze geïnstalleerd wordt, en simplecart zonder de add-on gedraagt zich exact zoals nu.

## What Changes

**Kern (simplecart, kleine, generieke uitbreiding)**
- Extension points (events) in winkelwagen, bestelling, annulatie en scan, zodat add-ons eigen regels kunnen aanleveren en valideren zonder dat de kern iets over plaatsen weet.
- `placeOrder()` aanvaardt ook regels van add-ons (een bestelling met enkel add-onregels is geldig).
- Geen databasewijzigingen in de kern: geen nieuwe kolommen, geen migratie van bestaande bestellingen.

**Add-on `simplecartseats` (nieuwe module, vereist simplecart)**
- Eigen objecten: zaal (`venue`), optreden (`event`), prijszone (`seatzone`), plaats (`seat`), plaatsstatus per optreden (`eventseat`) en koppeling naar bestelregels (`orderseat`).
- Zaalkaart als inline SVG met interactieve plaatskeuze (progressive enhancement, fallback per zone zonder JavaScript).
- Tijdelijke vasthouding van plaatsen (standaard 15 minuten), atomaire reservatie, eigen winkelwagensectie in de sessie.
- Prijs server-side uit de zone; onbetaalde bestellingen vervallen na een instelbare termijn.
- QR per plaats voor toegangscontrole.
- Eigen beheerschermen, configuratie, taalbestanden en versienummering.
- Uitschakelen of deïnstalleren laat verkochte bestellingen en orderitems in de kern intact.

## Capabilities

### New Capabilities
- `core-extension-points`: generieke hooks in simplecart waarop add-ons kunnen aanhaken.
- `addon-packaging`: installatie, afhankelijkheden, activatie en deïnstallatie van de add-on.
- `venue-management`: zalen, optredens, zones en plaatsen beheren en publiceren.
- `seat-map`: zaalkaart renderen en beschikbaarheid tonen.
- `seat-reservation`: plaatsen vasthouden en vrijgeven.
- `seat-checkout`: plaatsen in winkelwagen, bestelling en betaling.
- `ticket-validation`: toegang per plaats scannen.

### Modified Capabilities
Geen: er bestaan nog geen OpenSpec-specs voor de huidige simplecart-functionaliteit. Het ongewijzigd blijven van de bestaande flow is vastgelegd in `core-extension-points`.

## Impact

- Kern (`src/`): `include/common.php` (cart-summary hook), `class/order.php` (`placeOrder`, annulatie), `scan.php`, `checkout.php` en templates (render van extra regels), `class/OrderConfirmationEmail.php`, nieuw `class/Extension.php` (registratie en dispatch). Versie 2.3.0.
- Add-on (nieuwe map `addons/simplecartseats/`): eigen `icms_version.php`, `class/*`, `preload/`, `ajax/`, `assets/`, `templates/`, `language/`, `admin/`; eigen release-pakket via de bestaande release-builder.
- Database: enkel de add-on voegt tabellen toe (met unieke index `(event_id, seat_id)`); vereist InnoDB.
- Documentatie: README beschrijft de add-on als optioneel; de uitspraak "geen client-side JavaScript" geldt voor de kern, niet voor de add-on.
