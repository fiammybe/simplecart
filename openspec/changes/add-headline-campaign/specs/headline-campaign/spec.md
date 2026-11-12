## Purpose

Laat de winkel de naam van de lopende campagne tonen als titel van de startpagina en in het onderwerp van de bevestigingsmail, zonder dat die tekst in code of taalbestanden staat.

## ADDED Requirements

### Requirement: Kopcampagne
Het systeem SHALL als kopcampagne de actieve campagne kiezen met de vroegste startdatum. Hebben meerdere actieve campagnes dezelfde startdatum, dan SHALL de campagne gekozen worden waarvan de naam alfabetisch (zonder onderscheid tussen hoofd- en kleine letters) eerst komt. Is er geen actieve campagne, dan is er geen kopcampagne.

#### Scenario: Vroegste start
- **WHEN** twee campagnes actief zijn en de ene eerder is gestart
- **THEN** is de eerder gestarte campagne de kopcampagne

#### Scenario: Zelfde startdatum
- **WHEN** twee actieve campagnes dezelfde startdatum hebben
- **THEN** is de campagne met de alfabetisch eerste naam de kopcampagne

#### Scenario: Niet-actieve campagnes tellen niet mee
- **WHEN** een campagne nog niet is gestart of al is afgelopen
- **THEN** kan ze de kopcampagne niet zijn, ook niet met een vroegere startdatum

#### Scenario: Geen actieve campagne
- **WHEN** geen enkele campagne actief is
- **THEN** is er geen kopcampagne

### Requirement: Titel van de startpagina
Het systeem SHALL op de startpagina van de winkel de naam van de kopcampagne als titel tonen, in elke taal, en SHALL geen titel tonen wanneer er geen kopcampagne is.

#### Scenario: Titel uit de campagne
- **WHEN** er een kopcampagne is
- **THEN** toont de startpagina haar naam als titel

#### Scenario: Geen titel
- **WHEN** er geen kopcampagne is
- **THEN** toont de startpagina geen titel en ook geen lege kop

#### Scenario: Naam met speciale tekens
- **WHEN** de naam van de kopcampagne HTML-tekens bevat
- **THEN** toont de startpagina ze als gewone tekst

### Requirement: Onderwerp van de bevestigingsmail
Het systeem SHALL in het onderwerp van de bevestigingsmail de naam van de kopcampagne opnemen, in de taal van de klant, en SHALL die naam weglaten wanneer er geen kopcampagne is. Het onderwerp bevat altijd het bestelnummer.

#### Scenario: Met kopcampagne
- **WHEN** een bevestigingsmail wordt opgesteld terwijl er een kopcampagne is
- **THEN** bevat het onderwerp het woord "Bestellingsbevestiging" (of de vertaling in de taal van de klant), de naam van de kopcampagne en het bestelnummer

#### Scenario: Zonder kopcampagne
- **WHEN** een bevestigingsmail wordt opgesteld terwijl er geen kopcampagne is
- **THEN** bevat het onderwerp enkel het woord "Bestellingsbevestiging" (of de vertaling) en het bestelnummer

#### Scenario: Onderwerp van betaling ontvangen
- **WHEN** een mail "betaling ontvangen" wordt opgesteld
- **THEN** is het onderwerp ongewijzigd en bevat het geen campagnenaam
