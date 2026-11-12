## Why

De titel op de startpagina ("Tickets ColoMAenPA Vredestraat Fuif op 11 september 2026") en het onderwerp van de bevestigingsmail ("Bestellingsbevestiging Vredestraat Fuif #%d") stonden hardcoded in het Nederlandse taalbestand. Een volgende actie of een andere organisator vraagt dan een codewijziging, en de Engelse versie toonde iets anders ("Products"). De campagnes bevatten al wat die teksten moeten zeggen: de naam van de lopende actie.

## What Changes

- Er komt één begrip, de **kopcampagne**: de actieve campagne met de vroegste startdatum. Bij dezelfde startdatum wint de naam die alfabetisch eerst komt.
- De titel op de startpagina is de naam van de kopcampagne. Is er geen actieve campagne, dan staat er geen titel.
- Het onderwerp van de bevestigingsmail bevat de naam van de kopcampagne ("Bestellingsbevestiging <naam> #<nummer>"). Is er geen actieve campagne, dan blijft "Bestellingsbevestiging #<nummer>".
- De hardcoded teksten en de ongebruikte constanten `_MD_SIMPLECART_SHOP_TITLE` en `_MD_SIMPLECART_EMAIL_SUBJECT` verdwijnen.
- Het onderwerp "Betaling ontvangen" bevatte geen naam en blijft ongewijzigd.

## Capabilities

### New Capabilities
- `headline-campaign`: het kiezen van de kopcampagne en het gebruik van haar naam als titel van de startpagina en in het onderwerp van de bevestigingsmail.

### Modified Capabilities
Geen: er bestaan nog geen gearchiveerde specs voor de huidige functionaliteit.

## Impact

- Code: `src/class/campaign.php` (`getHeadlineCampaign()`), `src/index.php`, `src/templates/simplecart_index.html.tpl`, `src/class/OrderConfirmationEmail.php`, `src/language/<taal>/main.php` en `email.php` (nieuwe sleutel `EMAIL_SUBJECT_CAMPAIGN`).
- Geen databasewijziging en geen wijziging aan de extension-API.
- Sluit aan bij de change `add-product-campaign-weight` (zelfde campagnes) maar hangt er niet van af.
- Tests: kiezen van de kopcampagne en het mailonderwerp in twee talen.
