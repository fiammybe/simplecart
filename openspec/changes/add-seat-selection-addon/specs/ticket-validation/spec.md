## ADDED Requirements

### Requirement: Ticket per plaats
De add-on SHALL voor elke verkochte plaats een uniek ticket-token genereren en een QR-code aanbieden die naar de scanpagina verwijst.

#### Scenario: Token aanmaken
- **WHEN** een plaats `sold` wordt
- **THEN** bestaat er een niet-raadbaar `ticket_token` voor die plaats

### Requirement: Scannen van een plaats
De add-on SHALL via `scan.resolve` enkel ingelogde beheerders van de module toelaten een ticket te scannen en SHALL de eerste scan atomair registreren met tijdstip en gebruiker.

#### Scenario: Eerste scan
- **WHEN** een beheerder een geldig ticket voor het eerst scant
- **THEN** meldt het systeem toegang toegestaan met plaatsdetails
- **AND** wordt `scanned_at` en `scanned_by` bewaard

#### Scenario: Tweede scan
- **WHEN** hetzelfde ticket opnieuw gescand wordt
- **THEN** meldt het systeem dat het al gescand is, met tijdstip en scanner

#### Scenario: Gelijktijdige scans
- **WHEN** twee beheerders hetzelfde ticket tegelijk scannen
- **THEN** wordt exact één scan als eerste geregistreerd

#### Scenario: Ongeldig of foutief token
- **WHEN** een scan een onbekende plaats of een ongeldig token bevat
- **THEN** weigert het systeem met een duidelijke melding

### Requirement: Bestelscan blijft werken
De kern SHALL de bestaande QR per bestelling blijven ondersteunen en de add-on SHALL via `order.render` daarbij de scanstatus van alle plaatsen van de bestelling tonen.

#### Scenario: Bestelling scannen
- **WHEN** een beheerder de QR van een bestelling met 3 plaatsen scant
- **THEN** toont het systeem de 3 plaatsen met per plaats of ze al gescand zijn
