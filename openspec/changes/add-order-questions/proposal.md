## Why

Wie een bestelling plaatst, moet soms meer vertellen dan naam, e-mail, telefoon en adres. Nu bestaat daarvoor één vastgelegde vraag ("Helpende hand") die in de template, in `checkout.php`, in `placeOrder()`, in een kolom van de bestelling en in beide e-mails ingebakken zit. Een nieuwe vraag of een vraag voor één campagne vraagt telkens codewijzigingen. Bovendien wordt de e-mail "betaling ontvangen" verstuurd op het moment dat een beheerder de bestelling als betaald markeert, en dus in de taal van de beheerder in plaats van die van de klant.

## What Changes

- Beheerders definiëren **vragen** met volgorde, formaat en verplicht/aanvullend. Een vraag hoort bij één campagne of bij geen enkele (algemene vraag, gesteld bij elke bestelling).
- Formaten komen uit de IPF-datatypes (tekst, tekstvak, e-mail, url, geheel getal, getal, datum) en uit **keuzevragen** (radio of keuzelijst) met een lijst opties. De validatieregels van IPF gelden: een e-mailvraag controleert op een geldig e-mailadres.
- Bij het afrekenen krijgt de klant de algemene vragen plus de vragen van elke campagne waartoe een product in de winkelwagen behoort, op volgorde. Verplichte vragen blokkeren de bestelling zolang ze leeg of ongeldig zijn.
- Antwoorden worden bij de bestelling opgeslagen als **momentopname**: het label (met de taaltags) en de waarde, los van de vraag. Een vraag wijzigen of verwijderen verandert oude bestellingen niet.
- Labels, hulptekst en opties gebruiken de standaard taaltags `[en]…[/en]`. Eén hulpfunctie lost ze op, ook wanneer de multilanguage-instelling van het systeem uitstaat.
- De bestelling onthoudt de **taal van de klant**. De e-mails "bevestiging" en "betaling ontvangen" gebruiken die taal, met teksten uit een taalcatalogus per taal die los staat van de `define()`-constanten.
- Antwoorden verschijnen in beide e-mails, in het beheer van de bestelling en op de scanpagina.
- De vaste vraag "Helpende hand" wordt een algemene keuzevraag. In versie N worden de vraag aangemaakt en oude bestellingen omgezet naar antwoorden; in versie N+1 verdwijnen de kolom `helpende_hand` en de vaste code.
- De payload van `order.render` (context `mail`) krijgt het veld `language`. Dat is additief; `SIMPLECART_EXTENSION_API` blijft 1 en `API_REVISION` stijgt.

## Capabilities

### New Capabilities
- `order-questions`: vragen per campagne of algemeen, formaten met IPF-validatie, keuzevragen, volgorde en verplicht/aanvullend, antwoorden als momentopname, weergave in mail, beheer en scan, en de omzetting van "Helpende hand".
- `customer-language`: de taal van de klant op de bestelling, een taalcatalogus voor e-mails en het oplossen van `[xx]…[/xx]`-teksten in die taal.

### Modified Capabilities
Geen: er bestaan nog geen gearchiveerde specs voor de huidige functionaliteit.

## Impact

- Hangt af van de campagnes (branch `llm/simplecart-campaigns-b86957`, commit `e49f70c`, nog niet gemerged en niet gepusht). Die moet eerst naar `main` worden gerebased (conflictgevoelig: `icms_version.php`, `include/common.php`, `onupdate.inc.php`, taalbestanden) en gemerged worden voordat deze wijziging begint.
- Code: nieuwe klassen voor vraag, antwoord en taalcatalogus; `src/class/order.php` (`placeOrder()`, kolom `customer_language`), `src/checkout.php`, `OrderConfirmationEmail.php`, `PaymentReceivedEmail.php`, `src/admin/*`, `src/scan.php`, templates, taalbestanden (`language/<taal>/email.php` nieuw), `src/include/onupdate.inc.php`, `src/icms_version.php` (`object_items`, versie).
- Database: nieuwe tabellen voor vragen en antwoorden en een nieuwe kolom op de bestelling, allemaal via IPF `object_items`. De kolom `helpende_hand` blijft in versie N staan.
- Extension-API: additief (`language` in de payload van `order.render`).
- Documentatie: `docs/EXTENSION_POINTS.md`, README, uitleg over de taaltags en een taalcatalogus toevoegen.
- Tests: validatie per formaat, vereniging van vragen bij gemengde winkelwagens, momentopnamen, taalcatalogus in twee talen.
