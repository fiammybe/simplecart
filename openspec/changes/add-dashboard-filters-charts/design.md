## Context

Zie `proposal.md` voor de motivatie en de `specs/` voor het gedrag. Relevante stand van zaken:

- `admin/index.php` haalt alle orders en alle orderitems op en roept per item `orderHandler->get()` aan (een query per item). Alles is één groep "Overview"; de template toont twee tabellen.
- Een order kent geen campagne. Een orderitem kent enkel `product_name`, `product_price`, `quantity`, `subtotal`. Een campagne is dus enkel af te leiden via productnaam → product → `campaign_id`, wat breekt bij hernoemen, verwijderen of verplaatsen. Een winkelwagen kan producten uit meerdere campagnes bevatten.
- Antwoorden (`orderanswer`) bewaren `question_id` (zacht), `question_label`, `question_format` en `answer_value` als momentopname. Bij keuzevragen is de waarde een optie met taaltags; bij meerkeuze één optie per regel.
- De module past migraties toe in `icms_module_update_simplecart()`, ná de IPF-synchronisatie, met een klasse per migratie (`SimplecartLegacyHelpMigration`, `SimplecartLegacyContactMigration`). Tabellen worden door IPF beheerd via `object_items`, zonder `sql/mysql.sql`. De module gebruikt `$db->queryF()`.
- Een campagne heeft nooit meer dan 200 bestellingen; het totaal over alle campagnes blijft in de orde van enkele duizenden orders.
- De beheerpagina's staan achter `admin/header.php` (admincontrole). Er is nog geen JavaScript in de beheerinterface van de module.

## Goals / Non-Goals

**Goals:**
- Elke combinatie van filters toepassen zonder pagina te herladen.
- Eén vaste set queries voor het dashboard, onafhankelijk van het aantal orders.
- Cijfers en grafieken uit dezelfde berekening, zodat ze nooit uit de pas lopen.
- Geen persoonsgegevens in de pagina.

**Non-Goals:**
- Geen export (CSV/Excel) en geen opslag van benoemde filters.
- Geen filter of grafiek op vrije-tekstantwoorden.
- De bestellijst in `admin/order.php` en de statusfilter daar blijven ongewijzigd.
- Geen aantallen per filteroptie ("campagne A (37)") en geen opdeling van de grafiek per campagne of status; de gegevens laten dat later toe.
- Geen paginering of serverkant-filtering: bij enkele duizenden orders is dat niet nodig.

## Decisions

### D1. Herkomst als momentopname op `orderitem`
`orderitem` krijgt `product_id` en `campaign_id` (INT, standaard 0, verborgen in formulieren). `placeOrder()` vult ze bij productregels uit het product waaruit het item gemaakt wordt; extensieregels laten ze op 0. De waarden zijn zachte koppelingen zonder cascade, net als `orderanswer.question_id`.
- Alternatief verworpen: afleiden via productnaam bij het tonen. Breekt bij hernoemen, verwijderen en verplaatsen, en geeft bij dubbele namen willekeurige antwoorden.
- Alternatief verworpen: `campaign_id` op `order`. Een winkelwagen kan meerdere campagnes bevatten, dus één waarde per order is fout of verliest informatie.

### D2. Aanvullen van bestaande items: een derde migratieklasse
Een nieuwe `SimplecartOrderitemOriginMigration`, aangeroepen in `icms_module_update_simplecart()` naast de twee bestaande. Ze leest alle producten één keer (naam → product), werkt per orderitem met `product_id = 0` enkel bij als de naam bij precies één product past, en laat de rest op 0. Ze is idempotent omdat ze alleen items met `product_id = 0` aanraakt en dus een tweede keer niets vindt dat ze kan koppelen dat ze niet al gekoppeld had.
- De backfill gebruikt de huidige campagne van het product; dat is bij oude items een benadering. Dat is aanvaard en wordt in de release notes vermeld.
- Een fout in de migratie geeft `false` terug en wordt gelogd, zoals de bestaande migraties.
- Extensieregels uit het verleden blijven op 0 en verschijnen als "Overig/onbekend".

### D3. Eén gegevensverzameling, als JSON in de pagina
Een nieuwe klasse `SimplecartDashboardData` bouwt de dataset met drie queries (orders, items, keuzeantwoorden) en voegt ze in PHP samen op `order_id`. Er komt geen query per item of per order meer bij. Per order: `id`, `date` (`Y-m-d` in de tijdzone van de site, zodat JavaScript geen tijdzones hoeft te kennen), `status`, `total`, items (`product_id`, `campaign_id`, `name`, `quantity`, `amount`) en antwoorden (`question_id` → lijst van opties). Naast de orders staan lookup-lijsten: campagnes (id, naam), producten (id, naam), en de keuzevragen met de opties die in antwoorden voorkomen.
- De JSON staat in `<script type="application/json" id="simplecart-dashboard-data">` en wordt geschreven met `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, zodat een productnaam of optie met `</script>` of een aanhalingsteken de pagina niet kan breken.
- Namen van producten, campagnes en opties worden server-side opgelost naar de taal van de beheerder (`SimplecartLanguage::resolve`). In de browser wordt alles via `textContent` geschreven, nooit via `innerHTML`.
- Enkel keuzevragen (radio, keuzelijst, meerkeuze) komen in de dataset. Vrije-tekstantwoorden, namen, e-mail, telefoon en adres worden niet meegegeven.
- De opties per vraag komen uit de bestaande antwoorden, niet uit de huidige optielijst. Zo blijven opties die later gewijzigd of verwijderd werden filterbaar zolang er bestellingen mee bestaan. Vragen worden gegroepeerd op `question_id`; het label is dat van de huidige vraag, en bij een verwijderde vraag het label uit de meest recente momentopname.
- Alternatief verworpen: filteren op de server met GET-parameters. Meer PHP, een paginaherlading per klik, en een combinatie van facetten vergt dan dynamische queries over drie tabellen.
- Alternatief verworpen: een JSON-endpoint dat de browser opvraagt. Voegt een tweede toegangspad en een CSRF-/rechtendiscussie toe zonder voordeel bij deze omvang.

### D4. Filterlogica: eerst orders, dan items
Een bestelling voldoet aan de filters wanneer:
1. haar status in de gekozen statussen valt (of er geen statusfilter is), én
2. voor elke gekozen keuzevraag minstens één van haar antwoorden in de gekozen opties valt, én
3. bij een actieve campagne- of productfilter minstens één van haar items aan alle actieve itemfilters tegelijk voldoet (campagne én product op hetzelfde item).

Binnen één filter geldt of, tussen filters en. Gemeten wordt dan zo: is een campagne- of productfilter actief, dan zijn stuks en bedragen de som van de passende items; anders is het bedrag het `total_amount` van de order. Het aantal bestellingen is altijd het aantal orders dat voldoet. "Betaald" is status `paid` of `closed`, "openstaand" is `pending`, zoals nu; andere statussen tellen enkel mee in het totaal.
- "Overig/onbekend" is een eigen keuze (waarde 0) in de filters op product en campagne.
- Eén functie berekent per order zijn bijdrage `{count, amount, quantity, items}`; samenvatting, productentabel en grafieken gebruiken allemaal die functie, zodat ze niet uit de pas lopen.

### D5. Grafieken: Chart.js, gevendord
`assets/js/vendor/chart.umd.min.js` (vaste versie, MIT-licentie ernaast in `chart.LICENSE.md`), geladen met een gewone `<script>`-tag; geen CDN en geen build-stap. Twee diagrammen: aantal bestellingen en waarde, elk als staafdiagram per periode, of als lijn bij cumulatief. Filterwijzigingen werken de datasets bij met `chart.update()` in plaats van opnieuw te tekenen.
- Toegankelijkheid: elk diagram krijgt een `aria-label` en een `<details>` met dezelfde cijfers als tabel (zelfde berekening als de grafiek), want canvas is niet leesbaar voor hulpsoftware.
- Alternatieven verworpen: uPlot (tijdreeksen, maar staven en tooltips kosten meer eigen werk), Frappe Charts (traag onderhoud), eigen SVG (haalbaar, maar meer code om te onderhouden voor twee diagrammen).
- Kleuren komen uit CSS-variabelen of een kleine vaste reeks die in licht en donker leesbaar is, geen kleur als enige onderscheid (staaf versus lijn, plus de tabel).

### D6. Periodes in JavaScript, op datumstrings
Omdat de server elke order al een `Y-m-d` geeft, groepeert JavaScript op die tekst: dag = de datum zelf, week = de maandag van de ISO-week, maand = `Y-m`. Datums worden met UTC-rekenen verschoven, zodat zomertijd geen dag overslaat of verdubbelt. Tussen de eerste en laatste periode met een bestelling worden lege perioden met 0 aangevuld. De tijdzone van de site wordt bij de grafiek vermeld.

### D7. Filterstatus in `location.hash`
De filters en de weergave staan in de hash, bijvoorbeeld `#c=3,5&p=12&s=paid&q7=Optie%20A&g=week&m=cum`. De hash blijft aan de clientkant, wordt niet naar de server gestuurd en vraagt geen serverlogica. Bij het laden worden waarden die niet in de dataset voorkomen genegeerd. `hashchange` en de voor- en achterknop werken mee.

### D8. Gebruikersinterface
Een filterbalk met per facet een `<fieldset>` met selectievakjes (campagne, product, status, en één vouwbare groep per keuzevraag), geen tabellen, `<details>` voor het inklappen. Daaronder de samenvatting, de twee diagrammen met keuzes voor periode en cumulatief, en de producttabel. Een knop "Filters wissen" herstelt de beginstand. Zonder JavaScript toont een `<noscript>`-melding dat het dashboard JavaScript vraagt.
- Het admin-thema is dat van ImpressCMS, niet het Bulma van het klantenthema. We gebruiken de bestaande `table`-klassen van het huidige dashboard en eigen minimale CSS onder een eigen klasse, zonder extra bibliotheken.
- Scripts en CSS komen uit `assets/` en worden met een versieparameter geladen zodat een update niet door de cache blijft hangen.

### D9. Versie
`icms_version.php`: `version` en `status_version` gaan naar 2.8.0. Er is geen aparte schemaversie nodig; IPF voegt de kolommen toe bij de module-update.

## Risks / Trade-offs

- [Backfill is een benadering voor oude items] → Enkel eenduidige namen worden gekoppeld, de rest wordt "Overig/onbekend"; vermelden in de release notes.
- [Dataset groeit mee met het aantal orders] → Enkele duizenden orders zijn enkele honderden kB JSON. Als dat ooit niet meer volstaat, is een server-side voorfilter op datum of campagne een aanvulling zonder wijziging van de filterregels.
- [Verschil tussen `total_amount` en de som van de items] → Zonder item-filter gebruiken we `total_amount`, met item-filter de som van de itemsubtotalen. De twee zijn gelijk zolang bestellingen via `placeOrder()` komen; de specs leggen die regels vast.
- [Gegevens uit de database in een `<script>` en in de DOM] → JSON met `JSON_HEX_*`, DOM enkel via `textContent`, geen persoonsgegevens in de dataset (spec "Geen persoonsgegevens").
- [Chart.js is een grote vendored dependency] → Vaste versie, licentiebestand erbij, enkel op de dashboardpagina geladen.
- [Antwoordfilters tonen oude opties naast nieuwe als een beheerder de optietekst wijzigt] → Aanvaard: antwoorden zijn momentopnames (zie `order-questions`), dus het zijn terecht verschillende waarden.
- [Dagen worden anders geteld dan de beheerder verwacht bij tijdzoneverschil] → De dag wordt in de tijdzone van de site bepaald en dat staat bij de grafiek.

## Migration Plan

1. Moduleversie 2.8.0 installeren via de bestaande module-update. IPF voegt `product_id` en `campaign_id` toe aan `orderitem` (standaard 0).
2. `icms_module_update_simplecart()` voert de aanvulling van bestaande items uit, na de twee bestaande migraties.
3. Een eerste dashboardbezoek na de update toont items zonder koppeling onder "Overig/onbekend".

Terugdraaien: de nieuwe kolommen zijn onschadelijk voor oudere code. Een volledige terugkeer naar 2.7.0 laat ze staan of IPF laat ze vallen; er gaat geen andere data verloren.

## Open Questions

- Welke vaste Chart.js-versie we vendoren: die wordt bij de uitvoering gekozen (laatste 4.x die op dat moment minstens twee weken oud is) en in `chart.LICENSE.md` vastgelegd.
