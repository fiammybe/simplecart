## ADDED Requirements

### Requirement: Productflow blijft werken
Het systeem SHALL de bestaande flow voor gewone producten (toevoegen, aanpassen, verwijderen, afrekenen met SEPA) ongewijzigd laten werken, ook voor bestaande bestellingen.

#### Scenario: Bestaande bestelling
- **WHEN** na de update een bestelling van vóór de update wordt geopend
- **THEN** worden de regels getoond als producten met hun oorspronkelijke bedragen

### Requirement: Plaatsen in winkelwagen
Het systeem SHALL plaatsen van één optreden in de winkelwagen opnemen, met optreden, rij, nummer, zone en prijs per plaats, naast eventuele producten.

#### Scenario: Plaatsen tonen
- **WHEN** een bezoeker 3 geldig vastgehouden plaatsen heeft
- **THEN** toont de winkelwagen 3 regels met plaatsdetails, de resterende tijd en het totaal

#### Scenario: Ander optreden
- **WHEN** een bezoeker plaatsen van een tweede optreden toevoegt terwijl de winkelwagen er al een heeft
- **THEN** weigert het systeem en vraagt eerst af te rekenen of de winkelwagen te legen

### Requirement: Bestelling plaatsen
Het systeem SHALL bij het plaatsen van de bestelling alle plaatsen in één transactie op `sold` zetten voor de huidige sessie, de prijs server-side uit de zone bepalen en per plaats een orderitem met snapshot (titel, plaatslabel, prijs, aantal 1) bewaren.

#### Scenario: Succesvolle bestelling
- **WHEN** een bezoeker met geldige holds de checkout bevestigt
- **THEN** worden de plaatsen `sold` en gekoppeld aan de bestelling
- **AND** ontvangt de klant een bevestigingsmail met optreden, datum, plaatsen en SEPA-betaalgegevens

#### Scenario: Hold verlopen tijdens checkout
- **WHEN** minstens één hold is verlopen op het moment van bevestigen
- **THEN** wordt geen bestelling aangemaakt en blijft geen plaats `sold`
- **AND** toont het systeem welke plaatsen ontbreken

#### Scenario: Geknoeide prijs
- **WHEN** de client een andere prijs of zone meestuurt dan in de database
- **THEN** negeert het systeem die waarden en gebruikt de zoneprijs

### Requirement: Betalingstermijn en vervallen bestellingen
Het systeem SHALL bestellingen met plaatsen die binnen de betalingstermijn (standaard 7 dagen, instelbaar) niet betaald zijn laten vervallen en hun plaatsen vrijgeven.

#### Scenario: Bestelling vervalt
- **WHEN** een bestelling in status `pending` of `awaiting_payment` de termijn overschrijdt
- **THEN** krijgt ze status `cancelled` en worden haar plaatsen `available`

#### Scenario: Handmatige annulatie
- **WHEN** een beheerder een bestelling annuleert of terugbetaalt
- **THEN** biedt het systeem aan de plaatsen vrij te geven en doet dat bij bevestiging

### Requirement: Verkoopgrenzen
Het systeem SHALL geen plaatsen verkopen buiten het verkoopvenster of na het begin van het optreden, en niet meer dan het ingestelde maximum per bestelling.

#### Scenario: Verkoop gesloten
- **WHEN** een bezoeker een plaats probeert vast te houden na `sales_close_at`
- **THEN** weigert het systeem de aanvraag
