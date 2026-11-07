## Purpose

Zorgt dat een klant e-mails krijgt in de taal waarin die besteld heeft, ook wanneer een beheerder in een andere taal werkt, en lost meertalige teksten met taaltags op in die taal.

## ADDED Requirements

### Requirement: Taal van de klant vastleggen
Het systeem SHALL bij het plaatsen van een bestelling de actieve taal van het verzoek bewaren bij de bestelling, enkel wanneer de module voor die taal teksten heeft, en anders de standaardtaal van de site.

#### Scenario: Klant bestelt in het Nederlands
- **WHEN** een klant afrekent terwijl de siteweergave Nederlands is
- **THEN** bewaart de bestelling Nederlands als taal van de klant

#### Scenario: Taal zonder teksten
- **WHEN** de actieve taal geen taalmap in de module heeft
- **THEN** bewaart de bestelling de standaardtaal van de site

### Requirement: Oude bestellingen
Het systeem SHALL een bestelling zonder bewaarde taal behandelen zoals nu: de teksten volgen de actieve taal van het verzoek.

#### Scenario: Bestelling van voor deze wijziging
- **WHEN** voor een bestelling zonder taal een e-mail wordt verstuurd
- **THEN** is de taal van de e-mail de actieve taal van het verzoek

### Requirement: E-mails in de taal van de klant
Het systeem SHALL de e-mails "bevestiging" en "betaling ontvangen" volledig opmaken in de taal van de klant, onafhankelijk van de taal van de beheerder die de e-mail veroorzaakt, inclusief de onderwerpregel, de vaste teksten, de labels van vragen en de opties van keuzevragen.

#### Scenario: Beheerder markeert als betaald in een andere taal
- **WHEN** een Engelstalige beheerder een Nederlandstalige bestelling als betaald markeert
- **THEN** is de e-mail "betaling ontvangen" volledig in het Nederlands

#### Scenario: Zelfde bestelling in twee talen
- **WHEN** dezelfde bestelling met een Nederlandse en met een Engelse taal wordt opgemaakt
- **THEN** verschillen de vaste teksten en de labels van de vragen overeenkomstig de taal

### Requirement: Taalcatalogus voor e-mails
Het systeem SHALL de e-mailteksten per taal uit een eigen catalogus halen die los staat van de constanten voor de webpagina's, en SHALL bij een ontbrekende tekst terugvallen op de standaardtaal van de site en dan op Engels, zonder een fout te veroorzaken.

#### Scenario: Ontbrekende tekst
- **WHEN** een catalogus voor een taal een tekst mist
- **THEN** gebruikt de e-mail de tekst van de terugvaltaal

#### Scenario: Taal zonder catalogus
- **WHEN** de bestelling een taal heeft zonder catalogus
- **THEN** wordt de e-mail in de terugvaltaal verstuurd

### Requirement: Meertalige teksten oplossen
Het systeem SHALL teksten met taaltags (`[en]…[/en][nl]…[/nl]`) in een gevraagde taal oplossen, de tags gebruiken die in de systeeminstellingen staan, en SHALL ook werken wanneer de multilanguage-instelling van het systeem uitstaat.

#### Scenario: Tekst in de gevraagde taal
- **WHEN** een tekst `[en]Yes[/en][nl]Ja[/nl]` wordt opgelost voor Nederlands
- **THEN** is het resultaat "Ja"

#### Scenario: Tekst zonder blok voor de taal
- **WHEN** een tekst geen blok voor de gevraagde taal heeft
- **THEN** is het resultaat het eerste blok van de tekst

#### Scenario: Tekst zonder tags
- **WHEN** een tekst geen taaltags bevat
- **THEN** blijft ze ongewijzigd

#### Scenario: Multilanguage staat uit
- **WHEN** de multilanguage-instelling van het systeem uitstaat
- **THEN** toont geen enkel scherm of e-mail ruwe taaltags

#### Scenario: Onbekende taal
- **WHEN** voor de taal geen overeenkomende tag in de instellingen staat
- **THEN** valt het systeem terug op het eerste blok

### Requirement: Taal in de extension-API
Het systeem SHALL aan de payload van het event `order.render` met context `mail` het veld `language` toevoegen, en SHALL de API-revisie verhogen, zonder de bestaande API-versie te wijzigen.

#### Scenario: Add-on bouwt mailtekst
- **WHEN** een add-on bij `order.render` met context `mail` wordt aangeroepen
- **THEN** bevat de payload de taal van de klant

#### Scenario: Oudere add-on
- **WHEN** een add-on het veld `language` negeert
- **THEN** werkt ze zoals voorheen

### Requirement: Taal zichtbaar in het beheer
Het systeem SHALL de taal van de klant tonen in het beheer van de bestelling.

#### Scenario: Beheerder opent een bestelling
- **WHEN** een beheerder een bestelling met taal bekijkt
- **THEN** ziet die de taal van de klant
