## 1. Formaat en validatie

- [x] 1.1 `SimplecartQuestionFormat`: constante `CHECKBOX`, label, `isChoice()`, `isMultiple()`, `formKind()`
- [x] 1.2 Taalstrings voor het formaat in `language/english/admin.php` en `language/nederlands/admin.php`
- [x] 1.3 `SimplecartAnswerValidator`: lijst met plaatsen valideren voor meerkeuze, ontdubbelen en sorteren, meerdere waarden weigeren bij radio en keuzelijst, lege lijst als geen antwoord
- [x] 1.4 `SimplecartAnswerResult::$values` en de foutmeldingen laten overweg met een lijst

## 2. Opslag en weergave

- [x] 2.1 Antwoorden bewaren als labels gescheiden door een regeleinde (design D2)
- [x] 2.2 `SimplecartOrderanswer::getAnswerFor()` lost elke gekozen optie apart op en voegt ze samen
- [x] 2.3 Controleren dat e-mails (tekst en HTML), beheer van de bestelling, scanpagina en succespagina elke optie op een eigen regel tonen

## 3. Afrekenformulier

- [x] 3.1 `checkout.php` accepteert `q_<id>[]` (lijst) naast scalaire waarden en geeft ze door aan de validator en het formulier
- [x] 3.2 `SimplecartQuestionHandler::getFormRows()` markeert meerdere opties als geselecteerd
- [x] 3.3 `simplecart_checkout.html.tpl`: tak voor selectievakjes (Bulma `checkbox`), aangevinkte opties blijven na een fout

## 4. Tests en documentatie

- [x] 4.1 `tests/QuestionTest.php`: meerdere opties, volgorde, dubbele waarde, ongeldige optie, verplicht en aanvullend leeg, meerdere waarden bij radio, onveranderd gedrag van radio en keuzelijst
- [x] 4.2 `php -l` op alle gewijzigde bestanden, `composer test`, en handmatig testen in ImpressCMS (bestellen, foutherstel, e-mail, beheer)
- [x] 4.3 `docs/ORDER_QUESTIONS.md` bijwerken met het formaat meerkeuze
