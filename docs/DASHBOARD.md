# Dashboard

Beheer → **Dashboard** toont de bestellingen met filters en grafieken. De pagina laadt alle bestellingen één keer; het filteren en tekenen gebeurt in de browser, zonder de pagina te herladen. In de pagina staan geen namen, e-mailadressen, telefoonnummers, adressen of vrije-tekstantwoorden.

## Filters

| Filter | Wat |
|---|---|
| Campagne | De campagne van de items in de bestelling. |
| Product | De producten in de bestelling. |
| Status | In behandeling, betaald, afgesloten of geannuleerd. |
| Antwoorden op vragen | Per keuzevraag (keuzerondjes, keuzelijst, meerkeuze) de gekozen opties. Vragen met vrije tekst, e-mail, getal of datum zijn geen filter. |

- Binnen één filter geldt **of**: twee campagnes kiezen toont bestellingen uit de ene of de andere.
- Tussen filters geldt **en**: campagne A én status betaald.
- Een filter op campagne én product moet bij hetzelfde item passen.
- *Filters wissen* zet alles terug.
- De filters staan in het adres na `#`. Een gefilterd overzicht kan dus als bladwijzer bewaard of gedeeld worden. Waarden die niet bestaan worden genegeerd.

## Wat de cijfers tellen

- Het aantal bestellingen is altijd het aantal bestellingen dat bij de filters hoort.
- **Zonder** filter op campagne of product is het bedrag van een bestelling haar totaal.
- **Met** een filter op campagne of product tellen bedragen en aantallen enkel de items die aan die filter voldoen. Een bestelling met een item van campagne A (20,00) en een item van campagne B (10,00) telt bij campagne A dus als één bestelling van 20,00.
- *Betaald* is de status betaald of afgesloten, *Openstaand* is in behandeling. Geannuleerde bestellingen tellen enkel mee in het totaal.

## Overig/onbekend

Items die niet bij een product of campagne horen staan onder **Overig/onbekend**: add-onregels van extensies, en items van vóór versie 2.8.0 die niet te koppelen waren. Ze blijven in de cijfers.

## Grafieken

Twee diagrammen: het aantal bestellingen en de waarde van de bestellingen.

- **Periode**: dag, week (vanaf maandag) of maand. Perioden zonder bestelling tellen als 0, van de eerste tot de laatste bestelling.
- **Weergave**: per periode (staven) of cumulatief (lijn).
- De dag wordt bepaald in de tijdzone van de site; die staat bij de grafiek.
- Onder elk diagram staan dezelfde cijfers als tabel.

## Herkomst van items (vanaf 2.8.0)

Elk item bewaart bij het bestellen het product en de campagne waaruit het kwam, ook als het product later hernoemd, verplaatst of verwijderd wordt.

Bij de update naar 2.8.0 worden bestaande items eenmalig aangevuld. Dat gebeurt op de productnaam:

- Past de naam bij **precies één** product, dan krijgt het item dat product en **de huidige campagne van dat product**. Voor een oud item is dat een benadering: een product dat sindsdien naar een andere campagne verhuisde, valt in de nieuwe.
- Past de naam bij geen of meerdere producten, dan blijft het item onder *Overig/onbekend*.

De aanvulling kan veilig opnieuw lopen: ze raakt enkel items zonder product.

## Techniek

- `SimplecartDashboardData` bouwt de gegevens met één vaste set queries (bestellingen, items, antwoorden).
- `assets/js/dashboard.js` doet het filteren en rekenen; `assets/js/vendor/chart.umd.min.js` is Chart.js 4.6.1 (MIT, licentie in `chart.LICENSE.md`).
