## ADDED Requirements

### Requirement: Aparte optionele module
De plaatskeuze SHALL geleverd worden als aparte ImpressCMS-module (`simplecartseats`) met eigen repository, versie, installatie, configuratie, tabellen en release-pakket, los van simplecart.

#### Scenario: Zonder de add-on
- **WHEN** enkel simplecart geïnstalleerd is
- **THEN** zijn geen zaal-, optreden- of plaatsfuncties zichtbaar en bestaan geen add-on-tabellen

### Requirement: Afhankelijkheidscontrole
De add-on SHALL bij installatie controleren dat simplecart actief is met minstens de vereiste versie en extension-API, en SHALL de installatie weigeren met een duidelijke melding als dat niet zo is.

#### Scenario: Simplecart ontbreekt
- **WHEN** een beheerder de add-on installeert zonder actieve simplecart
- **THEN** weigert de installatie met de melding welke versie vereist is

#### Scenario: Te oude simplecart
- **WHEN** simplecart ouder is dan 2.3.0
- **THEN** weigert de installatie

### Requirement: Vereisten voor de database
De add-on SHALL bij installatie controleren dat de tabelengine transacties ondersteunt (InnoDB) en de unieke index op `(event_id, seat_id)` aanmaken.

#### Scenario: Geen InnoDB
- **WHEN** de database geen InnoDB biedt voor de nieuwe tabellen
- **THEN** weigert de installatie met uitleg

### Requirement: Uitschakelen en deïnstalleren
De add-on SHALL uitgeschakeld kunnen worden zonder verlies van bestellingen en SHALL bij deïnstallatie enkel eigen tabellen verwijderen, na bevestiging.

#### Scenario: Add-on uitgeschakeld
- **WHEN** de module wordt gedeactiveerd
- **THEN** blijven bestaande bestellingen en orderitems in simplecart zichtbaar en kloppend
- **AND** wordt geen plaats meer verkocht

#### Scenario: Deïnstalleren
- **WHEN** een beheerder de module deïnstalleert en bevestigt
- **THEN** worden enkel de add-on-tabellen verwijderd en blijven simplecart-bestellingen intact

### Requirement: Compatibiliteitscontrole bij gebruik
De add-on SHALL bij elke aanvraag controleren of simplecart actief is en de vereiste API biedt, en SHALL anders inactief blijven met een melding aan beheerders.

#### Scenario: Simplecart later uitgeschakeld
- **WHEN** simplecart wordt gedeactiveerd terwijl de add-on actief blijft
- **THEN** toont de add-on geen verkoopfuncties en meldt beheerders de oorzaak

### Requirement: Versiecontract tussen repositories
De add-on SHALL declareren welke minimale simplecart-versie en extension-API-versie vereist zijn, en SHALL bij een hoger hoofdnummer van de extension-API dan ondersteund inactief blijven met een melding.

#### Scenario: Niet-ondersteunde API
- **WHEN** simplecart een extension-API met een hoger hoofdnummer aanbiedt dan de add-on kent
- **THEN** blijft de add-on inactief en meldt beheerders welke versie van de add-on nodig is
