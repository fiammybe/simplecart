# order-questions Specification

## Purpose

Laat beheerders vragen definiëren die klanten bij het afrekenen beantwoorden, per campagne of voor elke bestelling, met een formaat dat het antwoord valideert. De antwoorden blijven bij de bestelling bewaard, ook als de vraag later verandert.

## Requirements

### Requirement: Vragen beheren
Het systeem SHALL beheerders toelaten een vraag aan te maken, te wijzigen, te (de)activeren en te verwijderen, met een label, een hulptekst, een formaat, een volgorde, de eigenschap verplicht of aanvullend, en een campagne of geen campagne.

#### Scenario: Vraag voor een campagne
- **WHEN** een beheerder een vraag aanmaakt met een campagne
- **THEN** hoort de vraag bij die campagne en wordt ze alleen gesteld wanneer de winkelwagen een product van die campagne bevat

#### Scenario: Algemene vraag
- **WHEN** een beheerder een vraag aanmaakt zonder campagne
- **THEN** wordt de vraag bij elke bestelling gesteld

#### Scenario: Inactieve vraag
- **WHEN** een vraag niet actief is
- **THEN** wordt ze niet gesteld, en blijven bestaande antwoorden bewaard

#### Scenario: Campagne verwijderd
- **WHEN** een campagne wordt verwijderd
- **THEN** worden de vragen van die campagne verwijderd en blijven antwoorden op bestaande bestellingen ongewijzigd

### Requirement: Volgorde
Het systeem SHALL de vragen tonen in de volgorde van hun gewicht, met de algemene vragen vóór de vragen van campagnes, en SHALL de volgorde ook gebruiken in e-mails, beheer en scanpagina.

#### Scenario: Eigen volgorde
- **WHEN** een beheerder twee vragen van dezelfde campagne een ander gewicht geeft
- **THEN** verschijnen ze bij het afrekenen in die volgorde

### Requirement: Formaten
Het systeem SHALL de volgende formaten aanbieden: tekst, tekstvak, e-mailadres, url, geheel getal, getal, datum, radio en keuzelijst. De formaten tekst, tekstvak, e-mailadres, url en getal volgen de datatypes van IPF en gebruiken de validatieregels van IPF waar die bestaan.

#### Scenario: E-mailadres
- **WHEN** een klant bij een vraag met formaat e-mailadres een ongeldig adres invult
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Geheel getal
- **WHEN** een klant bij een vraag met formaat geheel getal "abc" of "1.5" invult
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Datum
- **WHEN** een klant bij een vraag met formaat datum een ongeldige datum invult
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Maximale lengte
- **WHEN** het antwoord op een tekstvraag langer is dan de toegelaten lengte
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

### Requirement: Keuzevragen
Het systeem SHALL bij een vraag met formaat radio of keuzelijst een lijst opties bijhouden, de opties tonen in de volgorde van die lijst, en een antwoord alleen aanvaarden als het overeenkomt met een optie van de lijst op het moment van bestellen.

#### Scenario: Geldige keuze
- **WHEN** een klant een optie kiest
- **THEN** wordt het label van die optie bij de bestelling bewaard

#### Scenario: Waarde buiten de lijst
- **WHEN** een verzoek een waarde meestuurt die niet overeenkomt met een optie
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

### Requirement: Verplicht en aanvullend
Het systeem SHALL een bestelling weigeren zolang een verplichte vraag geen antwoord heeft, en SHALL een aanvullende vraag zonder antwoord toelaten, zonder een leeg antwoord te bewaren.

#### Scenario: Verplichte vraag leeg
- **WHEN** een klant een verplichte vraag niet invult of enkel spaties invult
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Aanvullende vraag leeg
- **WHEN** een klant een aanvullende vraag niet invult
- **THEN** wordt de bestelling geplaatst zonder antwoord op die vraag

#### Scenario: Aanvullende vraag met ongeldig antwoord
- **WHEN** een klant een aanvullende vraag met formaat e-mailadres invult met een ongeldig adres
- **THEN** wordt de bestelling niet geplaatst

### Requirement: Vragen bij het afrekenen
Het systeem SHALL bij het afrekenen de algemene vragen stellen plus de vragen van elke campagne waartoe minstens één product in de winkelwagen behoort, elke vraag één keer, en SHALL die verzameling op de server opnieuw bepalen bij het plaatsen van de bestelling.

#### Scenario: Gemengde winkelwagen
- **WHEN** de winkelwagen producten van campagne A en campagne B bevat
- **THEN** krijgt de klant de algemene vragen, de vragen van A en de vragen van B

#### Scenario: Meerdere producten van dezelfde campagne
- **WHEN** de winkelwagen twee producten van campagne A bevat
- **THEN** wordt elke vraag van A één keer gesteld

#### Scenario: Winkelwagen verandert na het tonen van het formulier
- **WHEN** een klant een formulier verstuurt waarvan de vragen niet meer overeenkomen met de winkelwagen
- **THEN** valideert het systeem tegen de vragen die bij de huidige winkelwagen horen en negeert het antwoorden op vragen die niet gesteld worden

### Requirement: Ingevulde antwoorden bij een fout
Het systeem SHALL bij een weigering het formulier opnieuw tonen met alle ingevulde antwoorden en een foutmelding bij elke ongeldige vraag, waarbij de melding het label van de vraag noemt in de taal van de klant.

#### Scenario: Fout bij één vraag
- **WHEN** één antwoord ongeldig is
- **THEN** zijn de overige antwoorden nog ingevuld en noemt de melding het label van de ongeldige vraag

### Requirement: Antwoorden als momentopname
Het systeem SHALL bij het plaatsen van een bestelling voor elk beantwoord antwoord het label (met taaltags), het formaat, de volgorde en de waarde bewaren, los van de vraag.

#### Scenario: Vraag wijzigt achteraf
- **WHEN** een beheerder het label of de opties van een vraag wijzigt nadat een bestelling is geplaatst
- **THEN** toont die bestelling nog steeds het label en het antwoord zoals bij het bestellen

#### Scenario: Vraag verwijderd achteraf
- **WHEN** een vraag wordt verwijderd
- **THEN** blijven de antwoorden op bestaande bestellingen zichtbaar

#### Scenario: Bestelling verwijderd
- **WHEN** een bestelling wordt verwijderd
- **THEN** worden de antwoorden van die bestelling mee verwijderd

### Requirement: Samen met de bestelling bewaren
Het systeem SHALL de antwoorden samen met de bestelling bewaren, zodat een mislukte opslag van de antwoorden geen bestelling zonder antwoorden achterlaat.

#### Scenario: Opslag van een antwoord mislukt
- **WHEN** het bewaren van een antwoord mislukt
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding

### Requirement: Weergave van antwoorden
Het systeem SHALL de antwoorden tonen in de e-mails "bevestiging" en "betaling ontvangen", in het beheer van de bestelling en op de scanpagina, in de volgorde van de momentopname, met het label in de taal van de e-mail of van de bekijker.

#### Scenario: Bevestigingsmail
- **WHEN** een bestelling antwoorden heeft
- **THEN** bevat de bevestigingsmail elk antwoord onder zijn label

#### Scenario: Bestelling zonder antwoorden
- **WHEN** een bestelling geen antwoorden heeft
- **THEN** bevatten de e-mails geen leeg onderdeel voor antwoorden

### Requirement: Veilige uitvoer van antwoorden
Het systeem SHALL antwoorden van klanten escapen bij elke weergave, en SHALL tekst die op een taaltag lijkt (`[en]…[/en]`) in een vrij antwoord niet door de vertaalfilter van het systeem laten omzetten.

#### Scenario: HTML in een antwoord
- **WHEN** een klant `<script>` invult in een tekstvak
- **THEN** wordt dit als tekst getoond en niet uitgevoerd

#### Scenario: Taaltags in een antwoord
- **WHEN** een klant `[en]hello[/en][nl]dag[/nl]` invult
- **THEN** toont elk scherm en elke e-mail die tekst letterlijk

### Requirement: Omzetting van de vraag Helpende hand
Het systeem SHALL de vaste vraag "Helpende hand" omzetten naar een algemene keuzevraag met dezelfde vier opties in beide talen, SHALL bestaande bestellingen met die waarde omzetten naar antwoorden, en SHALL die omzetting herhaalbaar maken zonder dubbele vragen of antwoorden.

#### Scenario: Bestaande bestelling
- **WHEN** een bestelling een waarde voor Helpende hand heeft
- **THEN** heeft ze na de omzetting een antwoord met het label van die optie in beide talen

#### Scenario: Omzetting opnieuw uitgevoerd
- **WHEN** de omzetting een tweede keer draait
- **THEN** ontstaan er geen extra vragen of antwoorden

#### Scenario: Nieuwe bestelling na de omzetting
- **WHEN** een klant bestelt
- **THEN** wordt Helpende hand als gewone vraag gesteld en niet meer in de vaste kolom bewaard

#### Scenario: Geen dubbele weergave
- **WHEN** een bestelling een omgezet antwoord heeft
- **THEN** toont geen enkele e-mail of scherm de waarde tweemaal
