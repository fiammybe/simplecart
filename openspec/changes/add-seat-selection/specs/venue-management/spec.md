## ADDED Requirements

### Requirement: Zalen beheren
Het systeem SHALL beheerders toelaten zalen aan te maken, te bewerken, te kopiëren en te verwijderen, elk met een zaalkaart gedefinieerd door rijen en secties.

#### Scenario: Zaal aanmaken met rijengenerator
- **WHEN** een beheerder een zaal opslaat met een sectie van 10 rijen van 20 plaatsen
- **THEN** bestaan er 200 plaatsen voor die zaal met rij- en plaatsnummer
- **AND** toont de preview een SVG met alle plaatsen

#### Scenario: Zaal met bestaande verkoop verwijderen
- **WHEN** een beheerder een zaal verwijdert waaraan een optreden met verkochte plaatsen hangt
- **THEN** weigert het systeem de verwijdering met een melding

### Requirement: Optredens met zones en prijzen
Het systeem SHALL een optreden aan één zaal koppelen, met een startmoment, verkoopvenster en minstens één prijszone; elke plaats SHALL aan precies één zone toegewezen worden.

#### Scenario: Publiceren zonder volledige zone-toewijzing
- **WHEN** een beheerder een optreden publiceert waarbij plaatsen geen zone hebben
- **THEN** weigert het systeem de publicatie en toont welke plaatsen ontbreken

#### Scenario: Publiceren
- **WHEN** een beheerder een volledig geconfigureerd optreden publiceert
- **THEN** wordt voor elke plaats een `eventseat`-rij met status `available` aangemaakt
- **AND** is het optreden zichtbaar op de frontend binnen het verkoopvenster

### Requirement: Plaatsen blokkeren en vrijgeven
Het systeem SHALL beheerders toelaten plaatsen per optreden te blokkeren en geblokkeerde of gehouden plaatsen handmatig vrij te geven.

#### Scenario: Geblokkeerde plaats
- **WHEN** een beheerder een plaats blokkeert
- **THEN** kan geen bezoeker die plaats selecteren
- **AND** toont de kaart de plaats als niet beschikbaar

### Requirement: Bezetting en export
Het systeem SHALL per optreden een bezettingsoverzicht, dashboardcijfers en een CSV-export van bezette plaatsen bieden.

#### Scenario: CSV-export
- **WHEN** een beheerder de export van een optreden vraagt
- **THEN** bevat de CSV per verkochte plaats rij, nummer, zone, bestelnummer en klantnaam
