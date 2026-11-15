## 1. Herkomst op orderitem

- [x] 1.1 `SimplecartOrderitem`: `product_id` en `campaign_id` (INT, standaard 0), verborgen in formulieren
- [x] 1.2 `placeOrder()`: productregels bewaren `product_id` en de `campaign_id` van het product op dat moment; extensieregels laten beide op 0
- [x] 1.3 `SimplecartOrderitemOriginMigration`: items met `product_id = 0` koppelen aan het product met exact dezelfde naam, enkel bij precies één treffer; idempotent
- [x] 1.4 `icms_module_update_simplecart()` roept de migratie aan na de twee bestaande migraties, met dezelfde foutafhandeling (log en `false`)
- [x] 1.5 `tests/`: herkomst bij een productregel, een productregel zonder campagne, een extensieregel, en de migratie (eenduidig, geen treffer, dubbele naam, tweede uitvoering doet niets)
- [x] 1.6 Controleren dat order-detail, e-mails en scanpagina ongewijzigd werken (ze gebruiken enkel naam, prijs en aantal)

## 2. Gegevens voor het dashboard

- [x] 2.1 `SimplecartDashboardData`: drie queries (orders, items, keuzeantwoorden), samengevoegd op `order_id`, zonder persoonsgegevens en zonder vrije-tekstantwoorden
- [x] 2.2 Datum per order als `Y-m-d` in de tijdzone van de site; namen, labels en opties opgelost naar de beheerderstaal
- [x] 2.3 Lookup-lijsten voor campagnes, producten, keuzevragen en de opties uit de bestaande antwoorden, plus "Overig/onbekend"
- [x] 2.4 JSON schrijven met `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`
- [x] 2.5 `tests/`: dataset bevat geen naam, e-mail, telefoon of adres; meerkeuze geeft meerdere opties; verwijderd product en add-onregel komen onder onbekend; lege dataset

## 3. Filterlogica en berekening (JavaScript)

- [ ] 3.1 `assets/js/dashboard.js`: toestand, `of` binnen en `en` tussen filters, voorwaarde per order en per item (design D4)
- [ ] 3.2 Eén functie die per order zijn bijdrage berekent; samenvatting, producttabel en grafieken gebruiken die
- [ ] 3.3 Periodes dag, week (maandag) en maand op datumstrings, UTC-rekenen, lege perioden met 0, cumulatief
- [ ] 3.4 Filterstatus in `location.hash`: schrijven, lezen, `hashchange`, onbekende waarden negeren
- [ ] 3.5 Alles in de DOM via `textContent`, nooit `innerHTML`

## 4. Pagina en grafieken

- [ ] 4.1 Chart.js vendoren in `assets/js/vendor/` met licentiebestand; vaste versie noteren
- [ ] 4.2 `admin/index.php` herschrijven: dataset bouwen en de template laden; geen N+1 meer
- [ ] 4.3 `simplecart_admin_dashboard.html.tpl`: filterbalk (fieldsets met selectievakjes, geen tabellen), samenvatting, producttabel, twee diagrammen met keuzes voor periode en cumulatief, `<details>` met tabel, "Filters wissen", `<noscript>`
- [ ] 4.4 Scripts en CSS laden uit `assets/` met een versieparameter; eigen CSS onder een eigen klasse
- [ ] 4.5 Taalstrings in `language/english` en `language/nederlands` (`admin.php`, `modinfo.php` waar nodig)

## 5. Afronding

- [ ] 5.1 `icms_version.php`: `version` en `status_version` naar 2.8.0
- [ ] 5.2 `docs/DASHBOARD.md`: filters, bedragen bij itemfilters, "Overig/onbekend" en de aanvulling van oude items als benadering
- [ ] 5.3 `php -l` op alle gewijzigde PHP-bestanden (7.4), `composer test`, en handmatig testen op een testsite: update van 2.7.0, nieuwe bestelling, filtercombinaties, grafiek per dag, week en maand, gewiste filters, URL met filters, leeg resultaat
