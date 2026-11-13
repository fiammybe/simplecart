## 1. Kopcampagne

- [x] 1.1 `getHeadlineCampaign()` toevoegen aan `SimplecartCampaignHandler`: actieve campagnes, vroegste start, daarna naam zonder hoofdlettergevoeligheid
- [x] 1.2 Tests voor vroegste start, geen actieve campagne, gelijke start op naam en een afgelopen campagne met vroegere start

## 2. Titel van de startpagina

- [x] 2.1 `index.php` geeft `simplecart_shop_title` door (lege tekst zonder kopcampagne)
- [x] 2.2 Template toont de `<h1>` enkel bij een naam, met `escape:html`
- [x] 2.3 Constante `_MD_SIMPLECART_SHOP_TITLE` uit beide taalbestanden verwijderen

## 3. Onderwerp van de bevestigingsmail

- [x] 3.1 Sleutel `EMAIL_SUBJECT_CAMPAIGN` in `language/english/email.php` en `language/nederlands/email.php`; hardcoded naam uit `EMAIL_SUBJECT`
- [x] 3.2 `OrderConfirmationEmail::getSubject()` kiest de sleutel op basis van de kopcampagne
- [x] 3.3 Ongebruikte constante `_MD_SIMPLECART_EMAIL_SUBJECT` verwijderen
- [x] 3.4 Tests voor het onderwerp met en zonder kopcampagne in de taal van de klant

## 4. Controle

- [x] 4.1 `php -l` (php74) op elk gewijzigd PHP-bestand en de testsuite op PHP 8.2
- [ ] 4.2 Startpagina en bevestigingsmail nakijken op een testsite, met een lopende en zonder lopende campagne
- [ ] 4.3 Release notes: de oude vaste titel wordt de naam van de campagne
