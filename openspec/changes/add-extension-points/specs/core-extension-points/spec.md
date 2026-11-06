## ADDED Requirements

### Requirement: Geen gedragswijziging zonder add-ons
Het systeem SHALL zich zonder geïnstalleerde add-ons identiek gedragen aan versie 2.2.0, inclusief bestaande bestellingen, e-mails, SEPA-betaling en scan, en SHALL geen databasewijzigingen voor extension points vereisen.

#### Scenario: Update zonder add-on
- **WHEN** simplecart van 2.2.0 naar 2.3.0 wordt bijgewerkt en geen add-on is geïnstalleerd
- **THEN** werken producten, winkelwagen, checkout en scan ongewijzigd
- **AND** worden geen tabellen of kolommen toegevoegd of gewijzigd

### Requirement: Extension-eventsysteem
Het systeem SHALL events uitsturen met een vaste naam en gedocumenteerde payload (`cart.summary`, `order.collectLines`, `order.beforePlace`, `order.afterPlace`, `order.cancelled`, `order.render`, `scan.resolve`) en SHALL een API-versie (`SIMPLECART_EXTENSION_API`) publiceren.

#### Scenario: Geen luisteraars
- **WHEN** een event wordt uitgestuurd en er zijn geen luisteraars
- **THEN** gaat de verwerking zonder fout verder

#### Scenario: API-versie
- **WHEN** een add-on de API-versie opvraagt
- **THEN** krijgt ze de huidige versie zodat ze compatibiliteit kan controleren

### Requirement: Regels van add-ons in winkelwagen en bestelling
Het systeem SHALL regels van add-ons (naam, prijs, aantal) in de winkelwageninhoud, het totaal en de bestelling opnemen, en SHALL een bestelling aanvaarden die uitsluitend uit add-onregels bestaat.

#### Scenario: Bestelling met enkel add-onregels
- **WHEN** de winkelwagen geen producten maar wel add-onregels bevat en de klant bestelt
- **THEN** wordt een bestelling aangemaakt met een orderitem per add-onregel
- **AND** is het totaal de som van die regels

#### Scenario: Gemengde bestelling
- **WHEN** de winkelwagen producten en add-onregels bevat
- **THEN** bevat de bestelling beide soorten regels en het juiste totaal

### Requirement: Validatie en atomaire afronding door add-ons
Het systeem SHALL add-ons toelaten de bestelling vóór aanmaak te weigeren met een foutmelding en SHALL hun afronding binnen dezelfde databasetransactie uitvoeren, zodat een fout in de add-on de hele bestelling terugdraait.

#### Scenario: Add-on weigert
- **WHEN** een luisteraar van `order.beforePlace` een fout teruggeeft
- **THEN** wordt geen bestelling aangemaakt en toont het systeem de foutmelding

#### Scenario: Afronding mislukt
- **WHEN** een luisteraar van `order.afterPlace` faalt
- **THEN** wordt de bestelling met haar orderitems teruggedraaid

### Requirement: Annulatie
Het systeem SHALL `order.cancelled` uitsturen wanneer een bestelling geannuleerd, verwijderd of naar status `cancelled` gezet wordt.

#### Scenario: Bestelling geannuleerd
- **WHEN** een beheerder een bestelling annuleert
- **THEN** ontvangen luisteraars de bestelling zodat ze resources kunnen vrijgeven

### Requirement: Extra inhoud in mail en succespagina
Het systeem SHALL luisteraars van `order.render` toelaten extra tekst of regels aan de bevestigingsmail en de succespagina toe te voegen.

#### Scenario: Extra regels
- **WHEN** een luisteraar extra regels teruggeeft
- **THEN** verschijnen die in mail en succespagina onder de bestelregels

### Requirement: Scan-uitbreiding
Het systeem SHALL `scan.resolve` uitsturen voor scanverzoeken die niet bij een eigen bestelling horen, en het resultaat van een luisteraar tonen.

#### Scenario: Onbekend verzoek
- **WHEN** `scan.php` een verzoek krijgt dat de kern niet herkent en een luisteraar het afhandelt
- **THEN** toont de pagina het resultaat van die luisteraar

### Requirement: Versiecontract
Het systeem SHALL de extension-API semantisch versioneren: het toevoegen van events of optionele payloadvelden verhoogt het hoofdnummer niet; het wijzigen of verwijderen van events of verplichte velden wel.

#### Scenario: Compatibele uitbreiding
- **WHEN** een nieuwe versie van simplecart een extra event toevoegt
- **THEN** blijft `SIMPLECART_EXTENSION_API` gelijk en blijven bestaande add-ons werken
