## MODIFIED Requirements

### Requirement: Formaten
Het systeem SHALL de volgende formaten aanbieden: tekst, tekstvak, e-mailadres, url, geheel getal, getal, datum, radio, keuzelijst en meerkeuze. De formaten tekst, tekstvak, e-mailadres, url en getal volgen de datatypes van IPF en gebruiken de validatieregels van IPF waar die bestaan.

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

#### Scenario: Meerkeuze aanmaken
- **WHEN** een beheerder een vraag aanmaakt met formaat meerkeuze zonder opties
- **THEN** weigert het systeem de vraag, net als bij radio en keuzelijst

### Requirement: Keuzevragen
Het systeem SHALL bij een vraag met formaat radio, keuzelijst of meerkeuze een lijst opties bijhouden, de opties tonen in de volgorde van die lijst, en een antwoord alleen aanvaarden als elke gekozen waarde overeenkomt met een optie van de lijst op het moment van bestellen. Bij radio en keuzelijst is er hoogstens één gekozen optie.

#### Scenario: Geldige keuze
- **WHEN** een klant een optie kiest
- **THEN** wordt het label van die optie bij de bestelling bewaard

#### Scenario: Waarde buiten de lijst
- **WHEN** een verzoek een waarde meestuurt die niet overeenkomt met een optie
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Meerdere waarden bij een enkelvoudige keuze
- **WHEN** een verzoek bij een vraag met formaat radio of keuzelijst meer dan één waarde meestuurt
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

## ADDED Requirements

### Requirement: Meerdere opties kiezen
Het systeem SHALL een klant bij een vraag met formaat meerkeuze nul, één of meer opties laten aanvinken, en SHALL die keuzes als één antwoord bij de vraag bewaren, in de volgorde van de optielijst en elke optie hoogstens één keer, zonder een minimum of maximum aantal keuzes af te dwingen.

#### Scenario: Meerdere opties gekozen
- **WHEN** een klant bij een meerkeuzevraag twee van vier opties aanvinkt
- **THEN** bewaart de bestelling één antwoord met de labels van die twee opties in de volgorde van de optielijst

#### Scenario: Volgorde van aanvinken
- **WHEN** een klant de derde optie vóór de eerste aanvinkt
- **THEN** staat in het antwoord de eerste optie vóór de derde

#### Scenario: Dubbele waarde
- **WHEN** een verzoek dezelfde optie twee keer meestuurt
- **THEN** wordt die optie één keer bewaard

#### Scenario: Verplichte meerkeuzevraag zonder keuze
- **WHEN** een klant een verplichte meerkeuzevraag zonder aangevinkte optie verstuurt
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Aanvullende meerkeuzevraag zonder keuze
- **WHEN** een klant een aanvullende meerkeuzevraag zonder aangevinkte optie verstuurt
- **THEN** wordt de bestelling geplaatst zonder antwoord op die vraag

#### Scenario: Ongeldige optie tussen geldige
- **WHEN** een verzoek bij een meerkeuzevraag twee geldige opties en één waarde buiten de lijst meestuurt
- **THEN** wordt de bestelling niet geplaatst en toont het systeem een foutmelding bij die vraag

#### Scenario: Aangevinkte opties blijven na een fout
- **WHEN** het formulier na een weigering opnieuw wordt getoond
- **THEN** zijn de eerder aangevinkte opties van meerkeuzevragen nog aangevinkt

### Requirement: Weergave van meerdere gekozen opties
Het systeem SHALL de gekozen opties van een meerkeuzeantwoord in e-mails, beheer van de bestelling, scanpagina en succespagina tonen als een opsomming met één optie per regel, elke optie in de taal van de e-mail of van de bekijker en met escaping van de tekst.

#### Scenario: Bevestigingsmail met meerdere opties
- **WHEN** een bestelling een meerkeuzeantwoord met drie opties heeft
- **THEN** bevat de bevestigingsmail die drie opties onder het label van de vraag, elk op een eigen regel

#### Scenario: Opties in de taal van de klant
- **WHEN** de opties taaltags bevatten en de klant Nederlands bestelde
- **THEN** toont de e-mail elke optie in het Nederlands

#### Scenario: Optie wijzigt achteraf
- **WHEN** een beheerder de optielijst wijzigt nadat een bestelling is geplaatst
- **THEN** toont die bestelling nog steeds de opties zoals bij het bestellen
