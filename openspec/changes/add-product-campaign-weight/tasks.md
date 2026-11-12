## 1. Gewicht op campagnes en producten

- [x] 1.1 Veld `weight` toevoegen aan `SimplecartCampaign` (int, standaard 0, besturingselement `text`) en cast in `beforeInsert` van de handler
- [x] 1.2 Veld `weight` toevoegen aan `SimplecartProduct` en cast in `beforeInsert` van de handler
- [x] 1.3 Taalconstanten `_MI_SIMPLECART_CAMPAIGN_WEIGHT` en `_MI_SIMPLECART_PRODUCT_WEIGHT` in elke taalmap van `src/language/`, met uitleg dat het gewicht van een product binnen zijn campagne geldt
- [x] 1.4 Moduleversie verhogen in `src/icms_version.php`

## 2. Volgorde in de winkel

- [x] 2.1 `getProducts()` laat SQL-sortering op naam vallen en sorteert in PHP op `[groep, campagnegewicht, campagnenaam, productgewicht, productnaam, product_id]`, met producten zonder campagne in groep 1
- [x] 2.2 Campagnes één keer laden voor de sortering (geen query per product)
- [x] 2.3 Controleren dat `simplecart_getProductList()` en `index.php` de volgorde behouden en het winkelmandje ongewijzigd blijft

## 3. Beheer

- [x] 3.1 Kolom `weight` en `setDefaultSort('weight')` in `src/admin/campaign.php`
- [x] 3.2 Kolom `weight` en `setDefaultSort('weight')` in `src/admin/product.php`

## 4. Tests en controle

- [x] 4.1 `php -l` (php74) op elk gewijzigd PHP-bestand
- [x] 4.2 Sorteervolgorde testen met gemengde campagnes, producten zonder campagne, negatieve gewichten en gelijke gewichten (alle scenario's uit de spec)
- [ ] 4.3 Module bijwerken op een testsite met bestaande gegevens: kolommen aangemaakt, bestaande rijen op 0, winkelvolgorde gelijk aan vroeger
- [ ] 4.4 Handmatig in het beheer en in de winkel nakijken
- [x] 4.5 README bijwerken met uitleg over gewichten en de virtuele laatste campagne
