## Why

Sinds PR 38 verandert het scannen van een bestelling niets meer bij het openen van de link: afsluiten gebeurt met een knop (POST met CSRF-token). Add-ons die `scan.resolve` gebruiken (bijvoorbeeld een ticketscan) kunnen die veilige flow niet volgen. Ze zien alleen een GET en moeten de scan dus bij het openen van de link registreren. Een linkvoorbeeld in een chat-app of een QR-app die de URL eerst opent kan zo een ticket "gebruiken" voordat iemand binnen is.

## What Changes

- Het event `scan.resolve` krijgt in de payload `method` (`GET` of `POST`) en `action` (alleen bij POST).
- Een resultaat van `scan.resolve` mag `actions` bevatten: knoppen met `name`, `label` en optioneel `confirm` (bevestigingstekst) en `style`.
- De kern toont elke actie als knop in een formulier met CSRF-token. Bij een POST controleert de kern het token en de beheerderrechten en stuurt daarna `scan.resolve` met `method = POST` en de gekozen actie. Daarna volgt een redirect naar de GET-URL met de melding van de add-on.
- Een add-on kan het afsluiten van een bestelling onderdrukken: een resultaat van `order.render` (context `scan`) met `allow_close = false` verbergt de knop "Bestelling afsluiten" en weigert het afsluiten ook bij een POST.
- De logica verhuist uit `scan.php` naar `SimplecartExtension::resolveScan()`, zodat ze testbaar is.
- `SimplecartExtension::API_REVISION` (nu 2) laat een add-on controleren of de kern acties ondersteunt. `SIMPLECART_EXTENSION_API` blijft 1: de wijziging is additief.

## Capabilities

### New Capabilities
- `scan-actions`: acties met CSRF-bescherming bij scans van add-ons, en een add-on die het afsluiten van een bestelling onderdrukt.

### Modified Capabilities
Geen: de specs in `add-extension-points` zijn nog niet gearchiveerd; deze wijziging breidt de eis "Scan-uitbreiding" daarvan uit.

## Impact

- Code: `src/class/Extension.php` (API_REVISION, `resolveScan()`), `src/scan.php`, `src/templates/simplecart_order_scan.html.tpl`, taalbestanden, `docs/EXTENSION_POINTS.md`, tests.
- Hangt af van PR 38 (de POST-flow en de knop in `scan.php`).
- Oudere add-ons die bij een GET registreren blijven werken, met hun oude gedrag.
- Add-on `simplecart-seats` past `onScanResolve` aan (zie zijn eigen OpenSpec-wijziging).
