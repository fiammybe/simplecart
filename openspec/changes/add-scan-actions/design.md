## Context

`scan.php` handelt twee soorten scans af: een bestelscan (`order_id` en `token`) en, sinds extension points 2.3.0, add-on-scans (`?addon=...`). Na PR 38 sluit de bestelscan af met een POST-knop. De add-on-scan kent nog alleen GET.

## Decisions

### D1. De kern toont de knop en het CSRF-token, de add-on beslist
Een add-on geeft `actions` terug; de kern bouwt het formulier (zelfde sticky knop als PR 38), maakt het token (naam `simplecart_scan_<addon>`) en controleert het bij de POST. De add-on voert een mutatie uitsluitend uit bij `method = POST` en een actienaam die hij zelf kent.
- Alternatief verworpen: de add-on bouwt zelf formulier en token. Dubbel werk en elke add-on zou het anders doen.
- Alternatief verworpen: de scan idempotent maken. Een linkvoorbeeld zou het ticket nog steeds "gebruiken".

### D2. Payload en resultaat (additief, API-versie blijft 1)
| Veld | Richting | Betekenis |
|---|---|---|
| `method` | kern → add-on | `GET` of `POST` |
| `action` | kern → add-on | alleen bij POST; alleen `[a-z_]`, maximaal 32 tekens |
| `actions[]` | add-on → kern | `name`, `label`, optioneel `confirm`, optioneel `style` (`primary`, `warning`) |

De kern negeert acties met een ongeldige naam en toont `label` en `confirm` geëscaped.

### D3. Bevestiging voor risicovolle acties
Een actie met `confirm` krijgt een bevestigingsdialoog in de browser (`onsubmit`). De add-on kiest wanneer: bijvoorbeeld "Toch innemen" bij een nog niet betaald ticket. De dialoog is een gemak, geen beveiliging: de server voert de actie ook zonder dialoog uit zodra de POST geldig is.

### D4. PRG na een POST
Na een POST stuurt de kern de melding van het resultaat mee in `redirect_header` naar de GET-URL, net als PR 38. Zo kan een herladen pagina de actie niet opnieuw uitvoeren.

### D5. Afsluiten onderdrukken
`order.render` (context `scan`) mag `allow_close = false` teruggeven. De kern controleert dat bij het tonen en opnieuw bij de POST van "Bestelling afsluiten". Voor bestellingen met plaatsen is afhalen niet van toepassing.

### D6. Feature-detectie
`SimplecartExtension::API_REVISION` is een geheel getal dat bij additieve uitbreidingen stijgt (2 bij deze wijziging, 1 voor 2.3.0). Een add-on die acties nodig heeft controleert `API_REVISION >= 2`. Zo niet, dan toont hij alleen de gegevens en voert hij geen scan door (faalt veilig).

### D7. Testbaarheid
`scan.php` is een paginascript. De beslislogica (payload bouwen, acties valideren, `allow_close` bepalen) komt in `SimplecartExtension::resolveScan()` en `SimplecartExtension::scanAllowsClose()`, die de tests rechtstreeks aanroepen.

## Risks / Trade-offs

- **Oudere add-ons** registreren nog bij een GET → niet te voorkomen; gedocumenteerd.
- **Dialoog is geen beveiliging** → server controleert alles opnieuw.
- **Afhankelijkheid van PR 38** → eerst mergen, anders dubbele conflicten in `scan.php` en het template.

## Open Questions

Geen: de keuzes voor niet-betaalde tickets (knop met bevestiging), het verbergen van afsluiten bij bestellingen met plaatsen en de naam `API_REVISION` zijn bevestigd.
