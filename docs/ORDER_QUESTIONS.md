# Vragen bij een bestelling

Een beheerder definieert vragen die de klant bij het afrekenen beantwoordt. De antwoorden blijven bij de bestelling bewaard en staan in de e-mails, in het beheer van de bestelling en op de scanpagina.

## Vragen beheren

Beheer → **Vragen**. Per vraag:

| Veld | Betekenis |
|---|---|
| Campagne | Een campagne, of *Elke bestelling*. Een vraag voor een campagne wordt gesteld zodra de winkelwagen een product van die campagne bevat. |
| Vraag | De tekst, eventueel met taaltags (zie hieronder). |
| Uitleg | Optionele hulptekst onder de vraag. |
| Formaat | Zie de tabel hieronder. |
| Opties | Enkel voor keuzevragen: één optie per regel. |
| Antwoord | *Verplicht* blokkeert de bestelling zolang het antwoord ontbreekt. *Aanvullend* mag leeg blijven. |
| Volgorde | Laagste eerst. Vragen voor elke bestelling komen vóór die van campagnes. |
| Actief | Een inactieve vraag wordt niet gesteld; bestaande antwoorden blijven bewaard. |

Een winkelwagen met producten van twee campagnes krijgt de vragen voor elke bestelling plus die van beide campagnes, elke vraag één keer.

## Formaten en validatie

De formaten volgen de datatypes van IPF; de controles van IPF gelden waar ze bestaan. IPF zegt niets over lege waarden, getallen en datums, en zet bij een url enkel `http://` voor. Die controles doet de module zelf.

| Formaat | IPF-datatype | Wat wordt gecontroleerd |
|---|---|---|
| Tekst (één regel) | tekst | maximaal 255 tekens |
| Tekst (meerdere regels) | tekstvak | maximaal 5000 tekens |
| E-mailadres | e-mail | geldig e-mailadres (IPF) |
| Webadres | url | `http://` wordt toegevoegd (IPF), daarna een geldig adres |
| Geheel getal | geheel getal | enkel cijfers, met eventueel een min-teken |
| Getal | kommagetal | komma of punt als decimaalteken, bewaard met een punt |
| Datum | tekst | `jjjj-mm-dd`, `dd/mm/jjjj` of `dd-mm-jjjj`; bewaard als `jjjj-mm-dd` |
| Keuze (radio of lijst) | tekst | moet een optie van de lijst zijn |

Een aanvullende vraag mag leeg blijven, maar een ingevuld antwoord moet wel geldig zijn.

## Meerdere talen

Labels, uitleg en opties gebruiken de standaard taaltags van ImpressCMS:

```
[en]Do you have allergies?[/en][nl]Heb je allergieën?[/nl]
```

De tags komen uit de systeeminstellingen (`ml_tags`, `ml_names`). Een tekst zonder blok voor de gevraagde taal toont het eerste blok; een tekst zonder tags blijft zoals ze is. Dit werkt ook als multilanguage van het systeem uitstaat, dan is er nooit een ruwe tag te zien.

## Wat er bij de bestelling bewaard wordt

Per antwoord bewaart de bestelling het label (met taaltags), het formaat, de volgorde en het antwoord, los van de vraag. Een vraag wijzigen of verwijderen verandert oude bestellingen dus niet. Een campagne verwijderen verwijdert haar vragen, niet de antwoorden.

Bij keuzevragen bewaart de bestelling de optie met haar taaltags, zodat ze in elke taal kan worden getoond. Een vrij antwoord wordt altijd letterlijk getoond en geëscaped, ook wanneer het `[en]…[/en]` bevat.

## Taal van de klant

De bestelling onthoudt de taal waarin de klant bestelde (de naam van de taalmap, bijvoorbeeld `nederlands`). De bevestigingsmail en de mail "betaling ontvangen" gebruiken die taal, ook wanneer een beheerder in een andere taal de bestelling als betaald markeert. Bestellingen van vóór deze functie volgen de actieve taal, zoals voorheen.

De e-mailteksten staan per taal in `language/<taal>/email.php`, los van de constanten voor de webpagina's. Een taal toevoegen:

1. Kopieer `language/english/email.php` naar `language/<taal>/email.php` en vertaal de waarden. De sleutels blijven gelijk.
2. Ontbreekt een sleutel, dan valt de module terug op de standaardtaal van de site en dan op Engels.

De test `every e-mail text exists in every language` controleert dat alle catalogi dezelfde sleutels hebben.

## Van "Helpende hand" naar een gewone vraag

Voorheen was "Helpende hand" een vaste vraag met een eigen kolom. Bij het bijwerken naar deze versie maakt de module er een algemene, verplichte radiovraag van met dezelfde vier opties in alle aanwezige talen, en zet ze de bewaarde waarde van elke bestaande bestelling om naar een antwoord. De omzetting mag herhaald worden zonder dubbele vragen of antwoorden.

De kolom `helpende_hand` blijft in deze versie bestaan maar wordt niet meer geschreven of getoond. De volgende versie verwijdert de kolom, de vaste code en de tijdelijke `LEGACY_HELP_*`-teksten uit de e-mailcatalogi. Terugkeren naar de vorige versie kan zonder gegevensverlies; nieuwe bestellingen missen dan de oude kolomwaarde.
