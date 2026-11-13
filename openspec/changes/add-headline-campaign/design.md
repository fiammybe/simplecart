## Context

De titel stond als `_MD_SIMPLECART_SHOP_TITLE` in de taalbestanden en werd rechtstreeks in `simplecart_index.html.tpl` gebruikt. Het mailonderwerp kwam uit de taalcatalogus (`EMAIL_SUBJECT` in `language/<taal>/email.php`, opgelost door `SimplecartEmailText` in de taal van de klant). Campagnes hebben `name`, `start_date` en `end_date` en een methode `isActive()`.

## Goals / Non-Goals

**Goals:**
- Eén plaats die bepaalt welke campagne "de" campagne is, gebruikt door pagina en mail.
- Geen campagnenaam in taalbestanden of code.

**Non-Goals:**
- Een apart titelveld op de campagne, of een vertaalbare titel per taal.
- Campagnekoppen boven de producten.
- De naam in het onderwerp van "betaling ontvangen" of in de body van de mails.

## Decisions

**`SimplecartCampaignHandler::getHeadlineCampaign(): ?SimplecartCampaign`.** Laadt de campagnes, filtert met `isActive()` en sorteert in PHP op `[start_date, mb_strtolower(name), campaign_id]`. Gebruikt `isActive()` in plaats van de SQL-criteria van `getActiveIds()`, zodat er één definitie van "actief" is in PHP en de logica testbaar is met de bestaande teststubs (die geen `<=` of OR kennen). Het aantal campagnes is klein. `campaign_id` is de laatste sleutel voor een vaste uitkomst bij gelijke namen.

**Titel via een sjabloonvariabele.** `index.php` geeft `simplecart_shop_title` (lege tekst zonder kopcampagne) door. De template toont de `<h1>` alleen bij een niet-lege waarde, geëscaped met `escape:html`.

**Mailonderwerp met twee sleutels.** `EMAIL_SUBJECT` (zonder naam) en `EMAIL_SUBJECT_CAMPAIGN` (met naam) met positionele argumenten (`%1$d` voor het nummer, `%2$s` voor de naam), zodat elke taal de naam op een eigen plaats kan zetten. Alternatief: de naam achteraan plakken in code; verworpen omdat de zinsvolgorde dan niet vertaalbaar is.

**Moment van de keuze.** De kopcampagne wordt gekozen bij het opstellen van de mail, niet bewaard bij de bestelling. Dat is eenvoudig en volgt de wens ("de actieve campagne"), maar zie de risico's.

**Opruimen.** De constanten `_MD_SIMPLECART_SHOP_TITLE` en `_MD_SIMPLECART_EMAIL_SUBJECT` worden verwijderd; niets gebruikt ze nog.

## Risks / Trade-offs

- [Mail wordt verzonden nadat de actie is afgelopen] Het onderwerp heeft dan geen naam, of die van een andere campagne → aanvaard; bestellen kan enkel binnen een actieve campagne, dus de kans is klein. Alternatief indien nodig: de campagne van de bestelde producten gebruiken of de naam bij de bestelling bewaren.
- [Campagnenaam met taaltags of HTML] De titel escapet HTML. Het onderwerp is gewone tekst en toont de naam zoals ingevoerd; taaltags worden niet opgelost.
- [Een bestaande installatie verliest haar titel] De site toont na de update de naam van de actieve campagne in plaats van de oude vaste tekst → de beheerder moet een campagne hebben met de gewenste naam (zoals "Tickets ColoMAenPA Vredestraat Fuif op 11 september 2026"); vermelden in de release notes.

## Assumptions

- "Actief" volgt `isActive()` (gestart en niet afgelopen).
- Het onderwerp "Betaling ontvangen" blijft zoals het was.
- De tekst "Tickets ColoMAenPA …" wordt de naam van de campagne; de module voegt zelf geen voor- of achtervoegsel toe.

## Open Questions

- Moet de naam bij de bestelling worden bewaard zodat latere mails (bv. na afloop van de actie) hetzelfde onderwerp krijgen?
