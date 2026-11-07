## Context

Zie `proposal.md` voor de motivatie. Relevante stand van zaken:

- Simplecart 2.3.x heeft producten, bestellingen en orderitems, een sessiewinkelwagen, `SimplecartOrderHandler::placeOrder()` en extension points (`SimplecartExtension`). De tabellen worden door IPF beheerd via `object_items` (op de campagnetak; vóór die tak via `sql/mysql.sql`).
- Campagnes (tak `llm/simplecart-campaigns-b86957`, nog niet gemerged) hebben een start en einde; een product hoort bij hoogstens één campagne (`campaign_id`, 0 = geen). Bestellingen kennen geen campagne.
- De vraag "Helpende hand" is vast ingebakken: een kolom `helpende_hand`, vier opties in de template, vier taalconstanten, hulpmethoden in beide e-mailklassen.
- De IPF-validatie zit in `icms_core_Object::cleanVars()` en hangt af van het datatype per variabele. Uit de broncode: enkel `XOBJ_DTYPE_EMAIL` geeft een echte fout bij een ongeldige waarde; `INT`, `FLOAT` en `CURRENCY` casten stil; `URL` zet `http://` voor een waarde zonder foutmelding; `TXTBOX` controleert de maximale lengte; een lege, niet-numerieke waarde slaat alle controles over, ook `required`. Foutmeldingen noemen de variabelenaam, niet het label.
- De module gebruikt `define()`-constanten voor teksten, die per verzoek in één taal geladen worden. Emails zijn ermee opgebouwd (ongeveer 30 constanten) en kunnen dus niet in een andere taal worden opgemaakt.
- Meertalige teksten gebruiken de ImpressCMS-tags uit de instelling `ml_tags`. `easiestml()` is een uitvoerfilter (`ob_start('easiestml')`) dat enkel werkt als `ml_enable` aan staat en de volledige pagina herschrijft.
- `placeOrder()` start alleen een transactie wanneer er add-onregels of `afterPlace`-luisteraars zijn.

## Goals / Non-Goals

**Goals:**
- Vragen zonder codewijziging toevoegen, per campagne of algemeen.
- Antwoorden valideren met IPF waar IPF regels heeft, en streng waar IPF stil is.
- Antwoorden als onveranderlijke momentopname bij de bestelling.
- E-mails altijd in de taal van de klant, met één plaats waar taaltags worden opgelost.
- Een veilige, herhaalbare overgang van "Helpende hand".

**Non-Goals:**
- Voorwaardelijke vragen (vraag B hangt af van antwoord A).
- Meerkeuze (meerdere opties tegelijk), bestanden uploaden, telefoon- of IBAN-formaten.
- Antwoorden bewerken na het bestellen, of filteren/exporteren op antwoord.
- De taal van de beheerinterface of van de scanpagina veranderen.
- Een volledig vertaalraamwerk: enkel de e-mails krijgen een eigen catalogus.
- De campagnes zelf; die komen uit de afhankelijke wijziging.

## Decisions

### D1. Gegevensmodel: vraag en antwoord als IPF-objecten
`simplecart_question`: `question_id`, `campaign_id` (0 = algemeen), `label` (tekstvak, bevat taaltags), `help` (tekstvak), `format`, `options` (tekstvak, één optie per regel), `required`, `active`, `weight`, `system_key` (leeg, behalve voor de omgezette vraag). `simplecart_orderanswer`: `answer_id`, `order_id`, `question_id` (zachte koppeling, geen verwijderingscascade), `question_label`, `question_format`, `weight`, `answer_value`. Labels en opties zijn tekstvakken in plaats van `TXTBOX(255)`, omdat taaltags de lengte verdubbelen of verdrievoudigen.
- Een antwoord leeft op eigen benen: label, formaat en volgorde worden gekopieerd, net zoals `orderitem.product_name`. De zachte koppeling dient enkel om te weten bij welke vraag het hoorde.
- Alternatief verworpen: kolommen op `order` per vraag — vraagt schemawijzigingen per nieuwe vraag.
- Alternatief verworpen: één serialized-veld op `order` — niet doorzoekbaar en onduidelijk bij wijzigingen.
- Een vraag wordt bij verwijdering van de campagne mee verwijderd (`beforeDelete` van de campagne); de antwoorden niet.

### D2. Vragen bepalen: algemeen + campagnes van de winkelwagen
Een resolver geeft de actieve vragen terug waarvan `campaign_id = 0` of `campaign_id` voorkomt in de producten van de winkelwagen, gedupliceerd per vraag, gesorteerd op groep (algemeen eerst, dan campagnes op `campaign_id`) en daarna `weight`, `question_id`. De formuliertemplate en `placeOrder()` roepen dezelfde resolver aan, zodat de server nooit vertrouwt op wat het formulier toonde. Extensieregels (add-ons) horen bij geen campagne en brengen dus enkel de algemene vragen.
- Alternatief verworpen: één campagne per winkelwagen afdwingen — verandert de winkel voor alle klanten en hoort niet bij deze functie.

### D3. Formaten: IPF-datatype plus een eigen strikte laag
| Formaat | IPF-datatype | Extra controle |
|---|---|---|
| tekst | `TXTBOX` | maximale lengte |
| tekstvak | `TXTAREA` | geen |
| e-mailadres | `EMAIL` | geen (IPF doet het) |
| url | `URL` | geldige `http(s)`-url na normalisatie |
| geheel getal | `INT` | `FILTER_VALIDATE_INT` vóór het casten |
| getal | `FLOAT` | numerieke controle, komma of punt als decimaalteken |
| datum | `TXTBOX` | geldige datum, bewaard als `Y-m-d` |
| radio, keuzelijst | `TXTBOX` | waarde moet een optie zijn |

De validatie bouwt per verzoek een tijdelijk IPF-object (`initVar` per vraag met het datatype en de lengte), vult het met de antwoorden en roept `cleanVars()` aan. Zo gelden de IPF-regels (e-mail, lengte, URL-normalisatie) letterlijk. Daarna volgen onze eigen controles voor wat IPF niet doet: verplicht (omdat IPF lege waarden overslaat), geheel getal, getal, datum, url en opties. Foutmeldingen van IPF noemen de variabelenaam; we vertalen ze naar een melding met het label van de vraag.
- De hoofdlijn is dus: **IPF waar IPF streng is, wij waar IPF stil is**. Dat is bewust eerlijker dan beweren dat IPF alle formaten valideert.
- Alternatief verworpen: zelf `DataFilter::checkVar()` per formaat aanroepen — dupliceert de afhandeling van IPF en loopt ermee uit de pas.
- Alternatief verworpen: vrije reguliere expressies per vraag — te veel vrijheid, moeilijk te beheren.

### D4. Keuzevragen: de index wordt gepost, het label wordt bewaard
Het formulier post voor radio en keuzelijst de plaats (1-gebaseerd) van de optie, niet de tekst. De server zet die plaats om naar de optie van de actuele lijst en bewaart het label met taaltags. Dat vermijdt tags in formulierwaarden en maakt de controle "waarde ligt in de lijst" eenvoudig. Veldnamen zijn `q_<question_id>`.
- Gevolg: een optie wijzigen na het bestellen verandert oude antwoorden niet, maar filteren op een stabiele sleutel kan niet (non-goal).

### D5. Antwoorden schrijven binnen de bestelling
Validatie gebeurt in `placeOrder()` vóór de bestelling wordt aangemaakt (samen met `validateCustomer()`). Het schrijven van antwoorden gebeurt na de orderitems, in dezelfde transactie. De transactie wordt nu ook gestart wanneer er te bewaren antwoorden zijn, en niet enkel bij add-onregels of `afterPlace`-luisteraars.
- Zonder InnoDB geldt de bestaande weigering enkel voor add-onregels. Voor antwoorden valt het systeem terug op compensatie: faalt een antwoord, dan wordt de aangemaakte bestelling verwijderd (`beforeDelete` verwijdert items en antwoorden) en wordt de fout doorgegeven.
- Alternatief verworpen: antwoorden vóór de bestelling schrijven — heeft een bestelnummer nodig.
- `beforeDelete` van een bestelling verwijdert ook de antwoorden.

### D6. Taal van de klant en de oplosser
`order.customer_language` (varchar 50) bevat de naam van de taalmap (`english`, `nederlands`). `checkout.php` geeft de actieve taal (`$icmsConfig['language']`) mee; `placeOrder()` valideert tegen de mappen in `language/` en valt terug op de standaardtaal van de site. Een lege waarde (oude bestelling) betekent: de actieve taal van het verzoek, zoals nu.
Een klasse `SimplecartLanguage` doet het werk:
- `normalize($language)`: geldig → dezelfde waarde, anders de terugval.
- `tagFor($language)`: zoekt in de systeeminstellingen `ml_tags`/`ml_names` (zoals de multilanguage-preload ze combineert) de bijbehorende tag; onbekend → geen tag.
- `resolve($text, $language)`: is `easiestml()` beschikbaar, dan wordt tijdelijk de globale `$easiestml_lang` gezet, `easiestml()` aangeroepen en de waarde hersteld (ook bij een uitzondering). Zo niet, of is er geen tag, dan blijft het eerste blok over en verdwijnen de tags.
- Webpagina's laten de uitvoerfilter het werk doen, maar de resolver wordt ook op webpagina's gebruikt wanneer `ml_enable` uitstaat, zodat nooit ruwe tags getoond worden.

### D7. Taalcatalogus voor e-mails (optie A)
Per taal een bestand `language/<taal>/email.php` dat een array teruggeeft met enkel de teksten die e-mails nodig hebben, ongeveer 18 e-mailspecifieke teksten en 7 gedeelde labels (naam, e-mail, telefoon, totaal, ...) die dubbel bestaan met `main.php`. `SimplecartEmailText::forLanguage($language)->get($key)` zoekt in de taal, dan de standaardtaal van de site, dan Engels, en geeft anders de sleutel terug (dus nooit een fatale fout). `OrderConfirmationEmail` en `PaymentReceivedEmail` krijgen de taal van de bestelling en gebruiken enkel nog de catalogus. De `define()`-constanten blijven voor webpagina's.
- Alternatief verworpen: de constantenbestanden parsen — breekbaar.
- Alternatief verworpen: constanten per taal definiëren met een voorvoegsel — verdubbelt alle bestaande code.
- De legacy-labels `help_1` tot `help_4` staan tijdelijk in de catalogus (voor de omzetting, D8) en verdwijnen in N+1.

### D8. Omzetting van "Helpende hand" in twee versies
Versie N: `helpende_hand` blijft als variabele bestaan (de IPF-synchronisatie draait vóór `onUpdate` en laat een niet-gedeclareerde kolom vallen). De `onUpdate`-functie, die draait nadat de nieuwe tabellen bestaan, doet in één herhaalbare stap:
1. Zoek de vraag met `system_key = 'legacy_helpende_hand'`; ontbreekt ze, maak ze aan als algemene, verplichte keuzevraag. De opties en het label worden samengesteld uit de catalogus in alle aanwezige talen, met de tags uit `ml_tags` (staat multilanguage uit, dan enkel de standaardtaal).
2. Voor elke bestelling met een niet-lege `helpende_hand` en zonder antwoord met die vraag: schrijf een antwoord met het label van de overeenkomende optie.
3. Nieuwe bestellingen schrijven de kolom niet meer; e-mails en schermen lezen ze niet meer, zodat de waarde niet tweemaal verschijnt.
Versie N+1: de variabele, de kolom, de vaste template, de vaste code en de legacy-teksten verdwijnen.
- Terugdraaien van N naar vorige versie: oude bestellingen behouden hun kolomwaarde; nieuwe bestellingen missen ze.

### D9. Veilige uitvoer
Antwoorden worden altijd geëscapet (Smarty `escape:html`, `htmlspecialchars` in code). Omdat `easiestml` de volledige HTML herschrijft, worden vrije antwoorden op HTML-pagina's ook gevrijwaard van taaltags door `[` te vervangen door `&#91;`. Antwoorden van keuzevragen zijn labels van de beheerder en worden wél opgelost; vrije antwoorden nooit. De resolver kiest op basis van het formaat dat in de momentopname staat.

### D10. Extension-API en versie
Het event `order.render` (context `mail`) krijgt `language` in de payload; `SimplecartExtension::API_REVISION` stijgt met één en `SIMPLECART_EXTENSION_API` blijft 1 (additief). De releasevolgorde van de afhankelijke wijzigingen (`add-scan-actions` verhoogt de revisie ook) wordt bij het mergen gecontroleerd.

## Risks / Trade-offs

- **Campagnetak niet gemerged en achter op `main`** → eerst rebasen en mergen (conflictgevoelig: `icms_version.php`, `common.php`, `onupdate.inc.php`, taalbestanden); taak 0.1 blokkeert de rest.
- **IPF valideert minder dan verwacht** (stil casten, `required` voor lege waarden) → eigen strikte laag (D3) en tests per formaat.
- **Tijdelijk IPF-object is ongebruikelijk** → in één klasse afgeschermd en getest tegen de echte `cleanVars()`; valt de aanpak tegen, dan blijft de interface (formaat → fout of genormaliseerde waarde) gelijk en kan de binnenkant wisselen.
- **IPF maakt een nieuwe kolom `NOT NULL`** → standaardwaarden bij het declareren van variabelen en de `NULL`-opruiming in `onupdate.inc.php` uitbreiden.
- **Tags verdubbelen de lengte van labels** → tekstvakken in plaats van `TXTBOX(255)`.
- **Dubbele labels in de e-mailcatalogus** → bewust (keuze A); een test controleert dat de sleutels in alle catalogi bestaan.
- **Datums en bedragen in e-mails volgen de taal niet** → de opmaak van bedrag en datum in de e-mailklassen wordt tijdens de uitvoering nagekeken; volgt ze nu de actieve taal, dan komt de keuze in de catalogus (zie Open Questions).
- **Klant wijzigt taal tijdens het afrekenen** → de taal bij het plaatsen van de bestelling telt.
- **Een tag in een vrij antwoord** → D9.

## Migration Plan

1. Wacht tot de campagnes in `main` staan (taak 0.1).
2. Versie N: tabellen en kolom via IPF, `onUpdate` zet "Helpende hand" om (D8); de e-mails en schermen schakelen over op antwoorden en op de catalogus.
3. Controle op een kopie van productiegegevens: aantal omgezette antwoorden gelijk aan aantal bestellingen met een waarde; e-mails in beide talen.
4. Versie N+1: kolom en vaste code verwijderen.
5. Terugdraaien van N: geen gegevensverlies; nieuwe bestellingen missen de oude kolomwaarde.

## Besluiten tijdens de uitvoering

Deze punten weken af van of vulden de beslissingen hierboven aan.

- **Opslagtype (D1).** Label, hulptekst, opties en de gekopieerde tekst van een antwoord zijn `XOBJ_DTYPE_SOURCE` in plaats van `TXTAREA`. Het opschonen van HTML bij het opslaan zou tekens als `&` in entiteiten omzetten, die we bij de uitvoer opnieuw zouden escapen. Nu staat de ruwe tekst in de database en wordt er één keer geëscapet. Het veld `required` heet `is_required`, om een mogelijk conflict met SQL te vermijden.
- **Oplosser (D6).** `SimplecartLanguage::resolve()` roept `easiestml()` niet aan en wijzigt `$easiestml_lang` niet. Ze leest de blokken zelf met de tags uit de systeeminstellingen, aangevuld met een vaste lijst (`en`, `nl`, `fr`, `de`, `es`). Daardoor is er geen globale toestand om te herstellen en werkt ze identiek met multilanguage aan of uit. Omdat alle teksten al in PHP worden opgelost, heeft het uitvoerfilter van het systeem op de pagina's niets meer te doen, behalve het onschadelijk maken van tags in vrije antwoorden (D9).
- **Standaardtaal (Open Question 2).** De standaardtaal van de site is de eerste taal in `ml_names` wanneer multilanguage aan staat, anders de taal in de siteconfiguratie. De terugvalketen voor e-mailteksten is: taal van de bestelling, standaardtaal, Engels.
- **Datums en bedragen in e-mails (Open Question 1).** Bedragen gebruiken al een vaste opmaak (`1,234.50`) en zijn dus taalonafhankelijk. De besteldatum komt nu uit de catalogus (`DATE_FORMAT`, bijvoorbeeld `d-m-Y H:i` voor Nederlands) in plaats van uit de opmaak van de aanvraag, zodat de mail in de taal van de klant niet de datumopmaak van de beheerder toont.
- **Vragen kiezen (D2).** `getForCart()` leest alle vragen en filtert in PHP: de tabel is klein en zo is het gedrag identiek in de tests en in de database.
- **API-revisie (D10).** `SimplecartExtension::API_REVISION` bestond nog niet en wordt hier ingevoerd met waarde 2. Een andere wijziging die de revisie ook verhoogt (`add-scan-actions`) moet er 3 van maken wanneer ze na deze merge, en 2 houden wanneer ze ervoor komt en deze wijziging 3 wordt.
- **Nulopruiming (taak 4.4).** De nieuwe kolommen en tabellen worden door IPF aangemaakt met een standaardwaarde. Bestaande rijen krijgen daardoor geen `NULL`, dus `onupdate.inc.php` hoeft niet verder aangepast te worden.
- **Niet geverifieerd in ImpressCMS.** De tests gebruiken stand-ins voor IPF, waarvan `cleanVars()` een kopie is van de logica in ImpressCMS 2.0.x. Beheerpagina, formulier, filter in de lijst en de update zelf zijn niet in een draaiende ImpressCMS uitgeprobeerd.
