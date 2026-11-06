## ADDED Requirements

### Requirement: Atomaire vasthouding
De add-on SHALL plaatsen vasthouden met een voorwaardelijke databaseupdate zodat een plaats nooit aan twee sessies tegelijk wordt toegewezen. Een aanvraag voor meerdere plaatsen SHALL alles of niets slagen.

#### Scenario: Gelijktijdige aanvraag
- **WHEN** twee sessies op hetzelfde moment dezelfde beschikbare plaats vasthouden
- **THEN** slaagt exact één aanvraag
- **AND** krijgt de andere een melding dat de plaats niet meer beschikbaar is

#### Scenario: Gedeeltelijk bezette selectie
- **WHEN** een bezoeker 4 plaatsen aanvraagt en één ervan is bezet
- **THEN** wordt geen enkele van de 4 vastgehouden
- **AND** meldt het systeem welke plaats niet beschikbaar is

### Requirement: Vasthoudduur
De add-on SHALL vastgehouden plaatsen standaard 15 minuten reserveren (instelbaar) en de resterende tijd tonen met een timer.

#### Scenario: Hold verloopt
- **WHEN** de vasthoudtijd verstrijkt zonder afgeronde bestelling
- **THEN** zijn de plaatsen opnieuw beschikbaar voor andere bezoekers
- **AND** verwijdert de winkelwagen de plaatsen met een melding bij de volgende actie

#### Scenario: Verlengen bij checkout
- **WHEN** een bezoeker met geldige holds de checkout opent
- **THEN** wordt de vasthoudtijd eenmalig verlengd

### Requirement: Vrijgeven
De add-on SHALL vastgehouden plaatsen vrijgeven wanneer de bezoeker ze uit de winkelwagen verwijdert of de winkelwagen leegt.

#### Scenario: Plaats verwijderen
- **WHEN** een bezoeker een plaats uit de winkelwagen verwijdert
- **THEN** wordt de plaats onmiddellijk `available`

### Requirement: Beveiliging van reservatie-eindpunten
De add-on SHALL de reservatie-eindpunten beschermen met een CSRF-token, ids valideren tegen het optreden en het aantal aanvragen per sessie beperken.

#### Scenario: Ongeldig token
- **WHEN** een aanvraag zonder geldig CSRF-token wordt verstuurd
- **THEN** wijst het systeem de aanvraag af en wijzigt geen plaats

#### Scenario: Te veel aanvragen
- **WHEN** een sessie de toegelaten aanvragen per minuut overschrijdt
- **THEN** weigert het systeem verdere aanvragen tijdelijk
