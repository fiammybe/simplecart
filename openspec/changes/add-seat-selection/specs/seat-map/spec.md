## ADDED Requirements

### Requirement: Zaalkaart tonen
Het systeem SHALL op de optredenpagina een zaalkaart als SVG tonen met podium, secties, rijen en plaatsen, gekleurd per zone en status (beschikbaar, gehouden, verkocht, geblokkeerd, geselecteerd), met een legenda.

#### Scenario: Kaart laden
- **WHEN** een bezoeker de pagina van een gepubliceerd optreden opent
- **THEN** toont de kaart elke plaats met de actuele status
- **AND** toont de legenda de zones met prijs

### Requirement: Plaatsen selecteren
Het systeem SHALL bezoekers beschikbare plaatsen laten selecteren en deselecteren door te klikken of te tikken, met een lopend totaal van de gekozen plaatsen en prijs.

#### Scenario: Plaats selecteren
- **WHEN** een bezoeker op een beschikbare plaats klikt
- **THEN** wordt de plaats gemarkeerd als geselecteerd en stijgt het totaal met de zoneprijs

#### Scenario: Bezette plaats
- **WHEN** een bezoeker op een verkochte of geblokkeerde plaats klikt
- **THEN** gebeurt er geen selectie

#### Scenario: Maximum bereikt
- **WHEN** een bezoeker meer plaatsen probeert te selecteren dan het toegelaten maximum
- **THEN** weigert het systeem de selectie en toont de limiet

### Requirement: Actuele beschikbaarheid
Het systeem SHALL de kaart periodiek verversen en alleen gewijzigde plaatsstatussen ophalen.

#### Scenario: Plaats elders verkocht
- **WHEN** een andere bezoeker een plaats vasthoudt terwijl de kaart open staat
- **THEN** toont de kaart die plaats binnen de pollinginterval als niet beschikbaar
- **AND** wordt een eventuele selectie van die plaats opgeheven met een melding

### Requirement: Toegankelijkheid en mobiel gebruik
Het systeem SHALL elke plaats focusbaar maken met een beschrijvend label (rij, nummer, prijs, status), toetsenbordselectie ondersteunen en op telefoons zoom en pan met pinch toelaten.

#### Scenario: Toetsenbord
- **WHEN** een bezoeker met het toetsenbord naar een beschikbare plaats navigeert en Enter drukt
- **THEN** wordt de plaats geselecteerd en leest een schermlezer het label voor

### Requirement: Werking zonder JavaScript
Het systeem SHALL zonder JavaScript een formulier bieden om per zone een aantal plaatsen te kiezen, waarbij de server de beste beschikbare plaatsen toewijst.

#### Scenario: Zonder JavaScript bestellen
- **WHEN** JavaScript uitgeschakeld is en een bezoeker 2 plaatsen in zone "Parterre" kiest
- **THEN** houdt het systeem 2 beschikbare plaatsen in die zone vast, bij voorkeur naast elkaar
