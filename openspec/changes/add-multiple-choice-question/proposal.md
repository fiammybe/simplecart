## Why

Een vraag laat de klant nu hoogstens één optie kiezen (radio of keuzelijst). Voor vragen als "welke allergieën?" of "welke extra's wil je?" moet een klant meerdere opties tegelijk kunnen aanvinken, zonder dat de beheerder daarvoor een vraag per optie moet aanmaken.

## What Changes

- Nieuw formaat **meerkeuze** (selectievakjes): de beheerder definieert een lijst opties zoals bij radio en keuzelijst; de klant vinkt er nul, één of meer aan.
- Het antwoord blijft één antwoord per vraag, met alle gekozen opties (label met taaltags) in de volgorde van de optielijst, als momentopname bij de bestelling.
- Verplicht betekent minstens één optie; aanvullend laat nul opties toe zonder leeg antwoord te bewaren.
- Antwoorden worden in e-mails, beheer en scanpagina getoond als een opsomming, elke optie in de taal van de e-mail of de bekijker.
- Geen minimum of maximum aantal keuzes (bewust buiten scope).

## Capabilities

### New Capabilities
<!-- geen -->

### Modified Capabilities
- `order-questions`: het formaat meerkeuze komt erbij en de eisen voor formaten, keuzevragen en weergave van antwoorden gelden ook voor meerdere gekozen opties.

## Impact

- `src/class/SimplecartQuestionFormat.php` (nieuw formaat, `isChoice`, `formKind`), `SimplecartAnswerValidator.php` (reeks waarden valideren), `orderanswer.php` (meerdere waarden weergeven), `question.php` (formulierrijen met aangevinkte opties).
- `src/checkout.php` (lijstwaarden `q_<id>[]` accepteren) en `simplecart_checkout.html.tpl` (selectievakjes, Bulma `checkbox`).
- Taalbestanden `english` en `nederlands` (`admin.php`), `docs/ORDER_QUESTIONS.md`.
- Geen schemawijziging: `question_format` en `answer_value` bestaan al; geen nieuwe tabellen of kolommen.
- Bouwt voort op de nog actieve wijziging `add-order-questions` (de main spec `order-questions` is daaruit gesynchroniseerd).
