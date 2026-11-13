## Why

De winkel toont alle producten alfabetisch op naam. Een beheerder kan niet bepalen welke campagne of welk product vooraan staat, bijvoorbeeld om een lopende actie bovenaan te zetten. Vragen hebben al een gewicht (`weight`); producten en campagnes niet.

## What Changes

- Campagnes en producten krijgen een **gewicht** (geheel getal, laagste eerst, standaard 0), instelbaar in het beheer.
- De productlijst in de winkel wordt gegroepeerd per campagne:
  1. eerst op het gewicht van de campagne;
  2. producten zonder campagne vormen een **virtuele campagne** die altijd als laatste komt, ongeacht welk gewicht de echte campagnes hebben;
  3. binnen een campagne op het gewicht van het product.
- Bij gelijk gewicht blijft de huidige volgorde op naam gelden, zodat bestaande gegevens (alles 0) er hetzelfde uitzien als nu.
- De beheerlijsten van campagnes en producten tonen het gewicht en sorteren er standaard op.

## Capabilities

### New Capabilities
- `product-ordering`: gewicht op campagnes en producten en de volgorde waarin de winkel de producten toont, met producten zonder campagne als virtuele laatste campagne.

### Modified Capabilities
Geen: er bestaan nog geen gearchiveerde specs voor de huidige functionaliteit.

## Impact

- Code: `src/class/campaign.php` en `src/class/product.php` (veld `weight`, sortering in `getProducts()`), `src/admin/campaign.php`, `src/admin/product.php`, taalbestanden (`_MI_SIMPLECART_CAMPAIGN_WEIGHT`, `_MI_SIMPLECART_PRODUCT_WEIGHT` in alle talen), `src/icms_version.php` (moduleversie).
- Database: twee nieuwe kolommen (`weight`) via de IPF-objectdefinities; geen `sql/mysql.sql`. Bestaande rijen krijgen 0. Controleer dat de IPF-upgrade de kolommen toevoegt zonder gegevens te verliezen.
- Geen wijziging aan de extension-API, het winkelmandje of de volgorde van vragen.
- Tests: sorteervolgorde met gemengde campagnes en producten zonder campagne; gelijke gewichten.
