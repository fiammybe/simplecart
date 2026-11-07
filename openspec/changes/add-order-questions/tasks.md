## 0. Voorbereiding
- [x] 0.1 De campagnes (`llm/simplecart-campaigns-b86957`, commit `e49f70c`) zijn naar `main` gerebased, gepusht en gemerged; deze tak is daarna op `main` gerebased
- [x] 0.2 Test: `tests/bootstrap.php` krijgt een echte kopie van `cleanVars()`-gedrag (of laadt IPF) zodat de validatie tegen IPF getest wordt

## 1. Taal van de klant
- [x] 1.1 `SimplecartLanguage`: `normalize()`, `tagFor()` (via `ml_tags`/`ml_names`), `resolve()` met herstel van `$easiestml_lang` en terugval op het eerste blok
- [x] 1.2 Variabele `customer_language` op `order` (standaard leeg), verborgen in het formulier, getoond in het beheer
- [x] 1.3 `checkout.php` geeft de actieve taal mee; `placeOrder()` valideert en bewaart ze
- [x] 1.4 `SimplecartEmailText` met terugvalketen (taal, standaardtaal, Engels, sleutel)
- [x] 1.5 `language/english/email.php` en `language/nederlands/email.php` met alle e-mailteksten, gedeelde labels en de tijdelijke legacy-labels `help_1`–`help_4`
- [x] 1.6 `OrderConfirmationEmail` en `PaymentReceivedEmail` gebruiken de catalogus en de taal van de bestelling (tekst en HTML), met de actieve taal als terugval voor oude bestellingen
- [x] 1.7 `order.render` (context `mail`) krijgt `language`; `API_REVISION` verhogen; `docs/EXTENSION_POINTS.md` bijwerken
- [x] 1.8 Nakijken of bedragen en datums in e-mails de taal volgen (Open Question 1) en beslissen

## 2. Vragen: model en beheer
- [x] 2.1 `question`-klasse en -handler (IPF, `object_items`), met de velden uit design D1 en tekstvakken voor label, hulptekst en opties
- [x] 2.2 `orderanswer`-klasse en -handler; `beforeDelete` van de bestelling verwijdert de antwoorden
- [x] 2.3 Verwijderen van een campagne verwijdert de vragen, niet de antwoorden
- [x] 2.4 Beheerpagina voor vragen: lijst per campagne (filter), volgorde, actief, formaat, verplicht, opties; uitleg over de taaltags in de hulptekst van het veld
- [x] 2.5 Beheermenu en taalstrings (`english`, `nederlands`)
- [x] 2.6 Beheer van de bestelling toont de antwoorden en de taal

## 3. Vragen: afrekenen en validatie
- [x] 3.1 Resolver `getQuestionsForCart()`: algemeen + campagnes van de producten, ontdubbeld, op volgorde (design D2)
- [x] 3.2 Validator met een tijdelijk IPF-object en de eigen strikte laag voor verplicht, geheel getal, getal, datum, url en opties (design D3), met foutmeldingen op label
- [x] 3.3 Keuzevragen: plaats posten, omzetten naar de optie, label bewaren (design D4)
- [x] 3.4 `checkout.php` en de template: vragen tonen met hulptekst, verplicht-markering en foutmeldingen, ingevulde waarden bij een fout behouden
- [x] 3.5 `placeOrder()`: antwoorden valideren vóór de bestelling, schrijven binnen de transactie, compensatie zonder InnoDB (design D5)
- [x] 3.6 Veilige uitvoer van antwoorden, inclusief `[` → `&#91;` op HTML-pagina's (design D9)
- [x] 3.7 Antwoorden in beide e-mails (tekst en HTML), op de scanpagina en op de succespagina, zonder leeg onderdeel

## 4. Omzetting van "Helpende hand"
- [x] 4.1 `onUpdate`-stap: vraag met `system_key = 'legacy_helpende_hand'` aanmaken met opties in alle aanwezige talen (design D8)
- [x] 4.2 `onUpdate`-stap: antwoorden aanmaken voor bestellingen met een waarde, herhaalbaar
- [x] 4.3 Vaste vraag uit de template, `checkout.php`, `placeOrder()` en de e-mails halen; de kolom blijft gedeclareerd maar wordt niet meer geschreven of getoond
- [x] 4.4 `NULL`-opruiming in `onupdate.inc.php` bijwerken voor de nieuwe kolommen
- [ ] 4.5 Versie N+1 (aparte release): kolom, variabele, legacy-teksten en vaste code verwijderen

## 5. Tests
- [x] 5.1 Formaten: e-mailadres, geheel getal, getal, datum, url en maximale lengte geven de verwachte fouten en genormaliseerde waarden
- [x] 5.2 Verplicht, aanvullend leeg, aanvullend ongeldig
- [x] 5.3 Keuzevragen: geldige keuze, waarde buiten de lijst, optie gewijzigd na het bestellen
- [x] 5.4 Gemengde winkelwagen, dezelfde campagne tweemaal, algemene vraag, inactieve vraag, formulier met verouderde vragen
- [x] 5.5 Momentopname: vraag gewijzigd en verwijderd; bestelling verwijderd; campagne verwijderd
- [x] 5.6 Atomair opslaan: een mislukt antwoord laat geen bestelling achter
- [x] 5.7 Taalcatalogus: dezelfde bestelling in twee talen, ontbrekende sleutel, taal zonder catalogus, oude bestelling zonder taal
- [x] 5.8 `payment received` door een beheerder in een andere taal geeft een volledig correcte e-mail
- [x] 5.9 Taaltags: gevraagde taal, ontbrekend blok, geen tags, multilanguage uit, onbekende tag; vrij antwoord met tags en HTML
- [x] 5.10 Omzetting: eerste uitvoering, herhaling, bestelling zonder waarde, geen dubbele weergave
- [x] 5.11 Regressie: bestelling zonder vragen werkt als voorheen

## 6. Afronding
- [x] 6.1 README en `docs/`: vragen beheren, taaltags, een taalcatalogus toevoegen
- [x] 6.2 Release-notes en versie van simplecart verhogen volgens de volgorde van de PR's
- [ ] 6.3 Controle op een kopie van productiegegevens (design Migration Plan, stap 3)
