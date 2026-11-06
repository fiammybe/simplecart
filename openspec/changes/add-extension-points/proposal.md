## Why

Simplecart wordt uitgebreid met een optionele add-on voor plaatskeuze op een zaalkaart (repository `fiammybe/simplecart-seats`). De kern mag daarvoor niets over zalen of plaatsen weten, maar moet add-ons wel toelaten eigen regels aan winkelwagen en bestelling toe te voegen, bestellingen te valideren en af te ronden, annulaties te volgen en scans af te handelen. Dit voorstel voegt die generieke extension points toe; ze zijn ook door andere add-ons bruikbaar.

## What Changes

- Nieuwe klasse `SimplecartExtension` die events dispatcht via de ImpressCMS preload-handler, plus een API-versie `SIMPLECART_EXTENSION_API`.
- Events `cart.summary`, `order.collectLines`, `order.beforePlace`, `order.afterPlace`, `order.cancelled`, `order.render` en `scan.resolve`.
- `placeOrder()` aanvaardt ook bestellingen die enkel uit add-onregels bestaan en rondt af binnen één transactie.
- Documentatie van het contract in `docs/EXTENSION_POINTS.md` met compatibiliteitsmatrix.
- Zonder add-on blijft het gedrag identiek aan 2.2.0: geen databasewijzigingen, geen gedragswijziging. Versie 2.3.0.

## Capabilities

### New Capabilities
- `core-extension-points`: generieke hooks in simplecart waarop add-ons kunnen aanhaken.

### Modified Capabilities
Geen: er bestaan nog geen OpenSpec-specs voor de huidige functionaliteit.

## Impact

- Code: `src/include/common.php`, `src/class/order.php`, `src/class/OrderConfirmationEmail.php`, `src/scan.php`, `src/checkout.php`, `src/index.php` en templates; nieuw `src/class/Extension.php`; `src/icms_version.php` (versie 2.3.0).
- Documentatie: `docs/EXTENSION_POINTS.md`, README.
- Geen databasewijzigingen.
- Gerelateerd: add-on-specificatie in `fiammybe/simplecart-seats` (`openspec/changes/add-seat-selection/`). Simplecart 2.3.0 moet vóór add-on 1.0.0 uitkomen.
