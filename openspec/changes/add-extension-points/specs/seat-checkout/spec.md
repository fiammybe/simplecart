## ADDED Requirements

### Requirement: Productflow blijft werken
De add-on SHALL de bestaande productflow van simplecart (toevoegen, aanpassen, verwijderen, afrekenen met SEPA) niet verstoren, ook niet voor bestaande bestellingen of gemengde winkelwagens met producten en plaatsen.

#### Scenario: Gemengde winkelwagen
- **WHEN** een bezoeker producten en plaatsen in de winkelwagen heeft en afrekent
- **THEN** bevat de bestelling beide soorten regels en klopt het totaal

#### Scenario: Bestaande bestelling
- **WHEN** een bestelling van vóór de installatie van de add-on wordt geopend
- **THEN** worden de regels getoond als producten met hun oorspronkelijke bedragen

### Requirement: Plaatsen in winkelwagen
De add-on SHALL via het event `cart.summary` plaatsen van één optreden in de winkelwagen opnemen, met optreden, rij, nummer, zone en prijs per plaats, naast eventuele producten.

#### Scenario: Plaatsen tonen
- **WHEN** een bezoeker 3 geldig vastgehouden plaatsen heeft
- **THEN** toont de winkelwagen 3 regels met plaatsdetails, de resterende tijd en het totaal

#### Scenario: Ander optreden
- **WHEN** een bezoeker plaatsen van een tweede optreden toevoegt terwijl de winkelwagen er al een heeft
- **THEN** weigert het systeem en vraagt eerst af te rekenen of de winkelwagen te legen

### Requirement: Bestelling plaatsen
De add-on SHALL via `order.collectLines`, `order.beforePlace` en `order.afterPlace` bij het plaatsen van de bestelling alle plaatsen in dezelfde transactie op `sold` zetten voor de huidige sessie, de prijs server-side uit de zone bepalen en per plaats een orderitem (titel + plaatslabel, prijs, aantal 1) laten aanmaken, gekoppeld via `orderseat`.

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
De add-on SHALL bestellingen met plaatsen die binnen de betalingstermijn (standaard 7 dagen, instelbaar) niet betaald zijn laten vervallen en hun plaatsen vrijgeven.

#### Scenario: Bestelling vervalt
- **WHEN** een bestelling in status `pending` of `awaiting_payment` de termijn overschrijdt
- **THEN** krijgt ze status `cancelled` en worden haar plaatsen `available`

#### Scenario: Handmatige annulatie
- **WHEN** een bestelling met plaatsen wordt geannuleerd (event `order.cancelled`)
- **THEN** geeft de add-on de plaatsen automatisch vrij

### Requirement: Verkoopgrenzen
De add-on SHALL geen plaatsen verkopen buiten het verkoopvenster of na het begin van het optreden, en niet meer dan het ingestelde maximum per bestelling.

#### Scenario: Verkoop gesloten
- **WHEN** een bezoeker een plaats probeert vast te houden na `sales_close_at`
- **THEN** weigert het systeem de aanvraag
