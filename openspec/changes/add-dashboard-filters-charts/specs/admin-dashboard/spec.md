## Purpose

Geeft beheerders een dashboard waarmee ze bestellingen kunnen bekijken per campagne, product, status en antwoord op een vraag, en met grafieken van de evolutie van bestellingen en omzet in de tijd.

## ADDED Requirements

### Requirement: Samenvatting
Het systeem SHALL op het dashboard het aantal bestellingen, het totale bedrag, het aantal en bedrag van betaalde bestellingen (status betaald of afgesloten) en het aantal en bedrag van openstaande bestellingen tonen voor de bestellingen die bij de actieve filters horen, samen met het aantal verkochte stuks en de omzet per product.

#### Scenario: Zonder filters
- **WHEN** een beheerder het dashboard opent zonder filters
- **THEN** omvatten alle cijfers alle bestellingen

#### Scenario: Geen bestellingen
- **WHEN** er geen bestelling bij de filters hoort
- **THEN** toont het dashboard nullen en een melding dat er geen bestellingen zijn, en geen foutmelding

### Requirement: Filteren op campagne, product, status en antwoord
Het systeem SHALL de bestellingen laten filteren op campagne, product, status en het antwoord op een keuzevraag (radio, keuzelijst, meerkeuze), en op elke combinatie daarvan. Binnen één filter SHALL meerdere gekozen waarden als of gelden; tussen verschillende filters SHALL ze als en gelden. Een bestelling hoort bij een filter op campagne of product wanneer minstens één van haar items erbij hoort.

#### Scenario: Eén filter
- **WHEN** een beheerder campagne A kiest
- **THEN** tonen alle cijfers en grafieken enkel de bestellingen met minstens één item uit campagne A

#### Scenario: Combinatie
- **WHEN** een beheerder campagne A en status betaald kiest
- **THEN** tonen de cijfers enkel de betaalde bestellingen met minstens één item uit campagne A

#### Scenario: Meerdere waarden van één filter
- **WHEN** een beheerder twee producten kiest
- **THEN** tonen de cijfers de bestellingen met minstens één item van één van die twee producten

#### Scenario: Antwoord op een vraag
- **WHEN** een beheerder bij een keuzevraag een optie kiest
- **THEN** tonen de cijfers enkel de bestellingen waarbij die optie het antwoord is, of bij meerkeuze een van de gekozen antwoorden

#### Scenario: Vrije tekst
- **WHEN** een vraag een formaat heeft dat geen keuzevraag is (tekst, e-mailadres, getal, datum, ...)
- **THEN** biedt het dashboard geen filter op die vraag aan

#### Scenario: Geen resultaat
- **WHEN** de gekozen combinatie geen bestelling oplevert
- **THEN** kan de beheerder de filters met één handeling wissen

### Requirement: Bedragen volgen de filters op items
Het systeem SHALL zodra een filter op campagne of product actief is, bij stuks en bedragen enkel de items tellen die aan die filter voldoen, en SHALL het aantal bestellingen het aantal bestellingen laten blijven. Zonder filter op campagne of product SHALL het bedrag van een bestelling het totaalbedrag van de bestelling zijn.

#### Scenario: Bestelling met twee campagnes
- **WHEN** een bestelling een item van campagne A (20,00) en een item van campagne B (10,00) bevat en de beheerder campagne A kiest
- **THEN** telt de bestelling als één bestelling met een bedrag van 20,00

#### Scenario: Zonder filter op items
- **WHEN** dezelfde bestelling wordt bekeken met enkel een statusfilter
- **THEN** telt ze als één bestelling met een bedrag van 30,00

### Requirement: Items zonder bekende herkomst
Het systeem SHALL orderitems zonder product of zonder campagne zichtbaar houden als "Overig/onbekend" en SHALL ze als keuze in de filters op product en campagne aanbieden, zodat ze nooit stilzwijgend uit de cijfers verdwijnen.

#### Scenario: Add-onregel
- **WHEN** een bestelling een add-onregel bevat
- **THEN** valt die regel onder "Overig/onbekend" en telt ze mee in het bedrag van de bestelling

#### Scenario: Verwijderd product
- **WHEN** het product van een item is verwijderd
- **THEN** toont het dashboard het item met de naam zoals besteld

### Requirement: Evolutie in de tijd
Het systeem SHALL grafieken tonen van het aantal bestellingen en de waarde van de bestellingen per periode, voor de bestellingen die bij de filters horen, met de periode dag, week of maand naar keuze en met een weergave per periode of cumulatief. Perioden zonder bestelling SHALL als 0 getoond worden, van de eerste tot de laatste bestelling.

#### Scenario: Per dag
- **WHEN** een beheerder de periode dag kiest
- **THEN** toont de grafiek voor elke dag tussen de eerste en laatste bestelling het aantal en de waarde, ook voor dagen zonder bestelling

#### Scenario: Cumulatief
- **WHEN** een beheerder cumulatief kiest
- **THEN** toont de grafiek per periode het totaal tot en met die periode

#### Scenario: Filters op de grafieken
- **WHEN** een beheerder een filter wijzigt
- **THEN** passen de grafieken zich zonder pagina te herladen aan en gebruiken ze dezelfde regels voor bedragen als de samenvatting

#### Scenario: Toegankelijke weergave
- **WHEN** een beheerder geen grafiek kan bekijken
- **THEN** zijn dezelfde cijfers als tabel beschikbaar

### Requirement: Filterstatus in de URL
Het systeem SHALL de actieve filters en de gekozen weergave in de URL van het dashboard bewaren, zodat een gefilterd overzicht opnieuw geopend of gedeeld kan worden, en SHALL onbekende of ongeldige waarden in de URL negeren.

#### Scenario: Teruggaan naar een overzicht
- **WHEN** een beheerder een bewaarde URL met filters opent
- **THEN** staan dezelfde filters actief en tonen cijfers en grafieken dezelfde selectie

#### Scenario: Ongeldige waarde
- **WHEN** de URL een filterwaarde bevat die niet bestaat
- **THEN** wordt die waarde genegeerd en toont het dashboard geen foutmelding

### Requirement: Geen persoonsgegevens in de dashboardgegevens
Het systeem SHALL aan de dashboardpagina enkel gegevens meegeven die nodig zijn voor de cijfers, filters en grafieken (bestellingsnummer, datum, status, bedragen, items en keuzeantwoorden), en SHALL geen naam, e-mailadres, telefoonnummer, adres of vrije-tekstantwoord meegeven.

#### Scenario: Gegevens in de pagina
- **WHEN** het dashboard wordt geladen
- **THEN** bevat de pagina geen persoonsgegevens van klanten

### Requirement: Alleen voor beheerders
Het systeem SHALL het dashboard enkel tonen binnen de beheerinterface van de module.

#### Scenario: Niet-beheerder
- **WHEN** een gebruiker zonder beheerrechten het dashboard probeert te openen
- **THEN** toont het systeem de gegevens niet
