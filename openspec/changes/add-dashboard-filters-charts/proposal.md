## Why

Het beheerdashboard toont één vaste samenvatting ("Overview") en een lijst verkochte producten. Een beheerder kan niet per campagne, product of antwoord op een vraag kijken, en niet zien hoe bestellingen en omzet zich in de tijd ontwikkelen. Daarnaast laadt het dashboard alle orders en doet het per orderitem een aparte query. Per campagne komen er nooit meer dan 200 bestellingen, dus dit kan in één keer geladen en in de browser gefilterd worden.

## What Changes

- **Momentopname op `orderitem`**: twee nieuwe kolommen `product_id` en `campaign_id`, ingevuld bij het plaatsen van een bestelling (extensieregels krijgen 0). Bestaande items worden eenmalig aangevuld op basis van de productnaam; wat niet eenduidig te koppelen is, blijft 0 en heet "Overig/onbekend".
- **Dashboard met filters**: filteren op campagne, product, status en het antwoord op een keuzevraag, en op een combinatie daarvan (EN tussen verschillende facetten, OF binnen één facet). De filterstatus staat in de URL, zodat een gefilterd overzicht bewaard kan worden.
- **Bedragen volgen het filter**: filters selecteren bestellingen; zodra product of campagne actief is, tellen aantallen stuks en bedragen enkel de passende items. Het aantal bestellingen blijft het aantal bestellingen.
- **Evoluties met grafieken**: bestellingen per dag en waarde per dag, met een schakelaar voor cumulatief en voor dag, week of maand. Dagen zonder bestelling tellen als 0.
- **Gevendorde Chart.js** in `assets/js/vendor/`, zonder CDN en zonder build-stap. De filterlogica is vanilla JavaScript.
- **Eén gegevensverzameling** in plaats van een query per item. De gegevens gaan als JSON naar de pagina, zonder naam, e-mail, telefoon of adres.
- Moduleversie **2.8.0** (nieuwe functie plus schemawijziging).

## Capabilities

### New Capabilities
- `order-item-origin`: een orderitem bewaart bij het plaatsen van de bestelling het product en de campagne waaruit het kwam, en bestaande items worden eenmalig aangevuld.
- `admin-dashboard`: het beheerdashboard met samenvatting, filters op campagne, product, status en antwoord, en grafieken van de evolutie in de tijd.

### Modified Capabilities
<!-- geen: de vragen en antwoorden zelf veranderen niet -->

## Impact

- `src/class/orderitem.php` (twee nieuwe variabelen), `src/class/order.php` (`placeOrder()` vult ze), een nieuwe migratieklasse naast `SimplecartLegacyHelpMigration`, aangeroepen vanuit `src/include/onupdate.inc.php`.
- `src/admin/index.php` en `src/templates/simplecart_admin_dashboard.html.tpl` worden herschreven; nieuwe gegevensklasse voor het dashboard; nieuwe `assets/js/dashboard.js` en `assets/js/vendor/chart.umd.min.js` (MIT).
- Taalbestanden `english` en `nederlands` (`admin.php`, `modinfo.php`).
- `src/icms_version.php`: versie 2.8.0.
- Schemawijziging: twee INT-kolommen met standaardwaarde 0, door IPF toegevoegd bij de module-update. Niets wordt gedropt.
- Geen wijziging aan e-mails, scanpagina, extension points of het afrekenen voor klanten.
- Hangt af van `add-order-questions` en de campagnes: antwoordfilters werken op `orderanswer.question_id`, `question_label` en `answer_value`.
