## Purpose

Laat beheerders bepalen in welke volgorde de winkel de producten toont, via een gewicht op campagnes en op producten, met producten zonder campagne als laatste groep.

## ADDED Requirements

### Requirement: Gewicht op campagnes en producten
Het systeem SHALL beheerders toelaten een gewicht (geheel getal, laagste eerst) in te stellen op elke campagne en op elk product. Het gewicht is optioneel en is standaard 0.

#### Scenario: Gewicht instellen
- **WHEN** een beheerder een campagne of product opslaat met een gewicht
- **THEN** wordt dat gewicht bewaard en bij het volgende bewerken opnieuw getoond

#### Scenario: Geen gewicht ingevuld
- **WHEN** een beheerder een campagne of product aanmaakt zonder gewicht
- **THEN** is het gewicht 0

#### Scenario: Bestaande gegevens
- **WHEN** de module wordt bijgewerkt naar de versie met gewichten
- **THEN** krijgen bestaande campagnes en producten gewicht 0 en gaat er geen ander gegeven verloren

### Requirement: Volgorde van producten in de winkel
Het systeem SHALL de producten in de winkel tonen gegroepeerd per campagne: eerst op het gewicht van de campagne, daarbinnen op het gewicht van het product. Producten zonder campagne SHALL een virtuele campagne vormen die altijd na alle echte campagnes komt, ongeacht hun gewicht. Bij gelijk gewicht SHALL de volgorde op naam gelden.

#### Scenario: Campagnes op gewicht
- **WHEN** campagne A gewicht 20 heeft en campagne B gewicht 10, en beide een beschikbaar product bevatten
- **THEN** staan de producten van B vóór die van A

#### Scenario: Producten binnen een campagne
- **WHEN** twee producten van dezelfde campagne gewicht 5 en 1 hebben
- **THEN** staat het product met gewicht 1 eerst

#### Scenario: Producten zonder campagne als laatste
- **WHEN** een product geen campagne heeft en een campagne gewicht 999 heeft
- **THEN** staat het product zonder campagne na de producten van die campagne

#### Scenario: Gewicht van een product telt niet over campagnes heen
- **WHEN** een product in campagne B (gewicht 10) gewicht 1 heeft en een product in campagne A (gewicht 20) gewicht -5
- **THEN** staat het product van B toch eerst, omdat eerst de campagne telt

#### Scenario: Gelijke gewichten
- **WHEN** campagnes of producten hetzelfde gewicht hebben, bijvoorbeeld omdat nog nergens een gewicht is ingesteld
- **THEN** worden ze op naam gesorteerd, zoals vóór deze wijziging

#### Scenario: Niet-beschikbare producten
- **WHEN** een product inactief is of bij een inactieve campagne hoort
- **THEN** verschijnt het niet in de winkel, en verandert de volgorde van de overige producten niet

### Requirement: Gewicht in het beheer
Het systeem SHALL in de beheerlijsten van campagnes en producten het gewicht tonen en de lijsten standaard op gewicht sorteren.

#### Scenario: Beheerlijst
- **WHEN** een beheerder de lijst van campagnes of producten opent
- **THEN** toont elke rij haar gewicht en staan de rijen met het laagste gewicht bovenaan
