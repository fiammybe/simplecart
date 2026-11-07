## ADDED Requirements

### Requirement: Scans van add-ons veranderen niets bij een GET
Het systeem SHALL het event `scan.resolve` bij een GET uitsturen met `method = GET`, zodat een add-on de scan kan tonen zonder iets te registreren, en SHALL een scan van een add-on alleen laten uitvoeren via een POST met geldig token.

#### Scenario: Link geopend door een voorbeeldfunctie
- **WHEN** een URL met `?addon=...` met GET geopend wordt
- **THEN** ontvangt de add-on `method = GET` en geen `action`
- **AND** verandert er niets aan de gegevens van de add-on

### Requirement: Acties van een add-on
Het systeem SHALL een resultaat van `scan.resolve` met `actions` tonen als knoppen in een formulier met CSRF-token, en SHALL de naam en het label van een actie valideren en escapen.

#### Scenario: Knop tonen
- **WHEN** een add-on een actie `accept` met label "Ticket innemen" teruggeeft
- **THEN** toont de scanpagina een knop met dat label in een formulier met token

#### Scenario: Ongeldige actienaam
- **WHEN** een add-on een actie met een naam buiten `[a-z_]` of langer dan 32 tekens teruggeeft
- **THEN** toont het systeem die actie niet

### Requirement: Uitvoeren van een actie
Het systeem SHALL bij een POST eerst de beheerderrechten en het CSRF-token controleren en pas daarna `scan.resolve` uitsturen met `method = POST` en de gekozen `action`, en SHALL daarna terugverwijzen naar de GET-URL met de melding van de add-on.

#### Scenario: Geldige POST
- **WHEN** een module-beheerder de knop gebruikt met een geldig token
- **THEN** ontvangt de add-on `method = POST` en `action = accept`
- **AND** wordt de melding van de add-on getoond na de redirect

#### Scenario: Ongeldig token of geen rechten
- **WHEN** het token ongeldig is of de gebruiker geen module-beheerder is
- **THEN** wordt `scan.resolve` niet uitgestuurd en toont het systeem een melding

### Requirement: Bevestiging
Het systeem SHALL een actie met `confirm` pas laten indienen nadat de gebruiker de bevestigingstekst bevestigd heeft, en SHALL de actie op de server niet afhankelijk maken van die bevestiging.

#### Scenario: Niet-betaald ticket
- **WHEN** een add-on een actie met `confirm` teruggeeft
- **THEN** vraagt de browser om bevestiging voordat het formulier verzonden wordt

### Requirement: Afsluiten onderdrukken
Het systeem SHALL de knop "Bestelling afsluiten" verbergen en het afsluiten weigeren, bij GET en bij POST, wanneer een add-on bij `order.render` (context `scan`) `allow_close = false` teruggeeft.

#### Scenario: Bestelling met plaatsen
- **WHEN** een add-on voor een bestelling `allow_close = false` teruggeeft
- **THEN** toont de scanpagina geen afsluitknop
- **AND** weigert een rechtstreekse POST om af te sluiten

### Requirement: Feature-detectie
Het systeem SHALL een geheel getal `API_REVISION` publiceren dat bij elke additieve uitbreiding van de extension-API stijgt, zodat add-ons kunnen controleren welke mogelijkheden de kern heeft.

#### Scenario: Add-on op een oudere kern
- **WHEN** een add-on acties nodig heeft en `API_REVISION` lager is dan 2
- **THEN** voert de add-on geen scan door en toont hij alleen de gegevens
