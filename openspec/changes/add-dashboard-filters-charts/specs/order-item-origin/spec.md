## Purpose

Bewaart bij elk orderitem uit welk product en welke campagne het kwam op het moment van bestellen, zodat bestellingen later betrouwbaar per product of campagne bekeken kunnen worden, ook als het product hernoemd, verplaatst of verwijderd wordt.

## ADDED Requirements

### Requirement: Herkomst bij het plaatsen
Het systeem SHALL bij het plaatsen van een bestelling per productregel het product en de campagne van dat product op dat moment bewaren bij het orderitem. Een product zonder campagne SHALL als geen campagne bewaard worden.

#### Scenario: Product in een campagne
- **WHEN** een klant een product bestelt dat bij campagne A hoort
- **THEN** bewaart het orderitem het product en campagne A

#### Scenario: Product zonder campagne
- **WHEN** een klant een product bestelt dat bij geen campagne hoort
- **THEN** bewaart het orderitem het product en geen campagne

#### Scenario: Add-onregel
- **WHEN** een extensie een regel aan de bestelling toevoegt die niet uit een product komt
- **THEN** bewaart het orderitem geen product en geen campagne

### Requirement: Onveranderlijke momentopname
Het systeem SHALL de bewaarde herkomst van een orderitem ongewijzigd laten wanneer het product later hernoemd, naar een andere campagne verplaatst of verwijderd wordt, of wanneer de campagne verwijderd wordt.

#### Scenario: Product verhuist
- **WHEN** een product na een bestelling naar een andere campagne verhuist
- **THEN** blijft het orderitem van die bestelling bij de oorspronkelijke campagne horen

#### Scenario: Product of campagne verwijderd
- **WHEN** het product of de campagne van een bestaand orderitem wordt verwijderd
- **THEN** blijven de bestelling en het orderitem bewaard, met naam en prijs zoals besteld

### Requirement: Aanvullen van bestaande orderitems
Het systeem SHALL bij de update van de module orderitems zonder herkomst eenmalig aanvullen met het product en de campagne van het product waarvan de naam exact overeenkomt, en SHALL een orderitem ongewijzigd laten wanneer de naam bij geen of bij meer dan één product past. Een tweede uitvoering SHALL niets wijzigen.

#### Scenario: Eenduidige naam
- **WHEN** de update een orderitem zonder herkomst vindt waarvan de naam bij precies één product past
- **THEN** krijgt dat orderitem dat product en de huidige campagne van dat product

#### Scenario: Niet te koppelen
- **WHEN** de naam van een orderitem bij geen product of bij meerdere producten past
- **THEN** blijft de herkomst van dat orderitem leeg

#### Scenario: Herhaalde update
- **WHEN** de update opnieuw wordt uitgevoerd
- **THEN** wijzigt ze geen orderitem dat al een herkomst heeft
