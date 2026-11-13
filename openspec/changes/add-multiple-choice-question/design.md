## Context

Zie `proposal.md`. De vragen en antwoorden uit `add-order-questions` bestaan al: `question_format` (varchar 32) en `answer_value` (bronveld) op `simplecart_orderanswer`, de validator `SimplecartAnswerValidator` met veldnaam `q_<question_id>`, en een formulier dat voor radio en keuzelijst de plaats (1-gebaseerd) van de optie post, waarna de server het label met taaltags bewaart.

Alles gaat nu uit van één scalaire waarde per vraag: `checkout.php` neemt enkel scalaire `q_`-velden over, de validator leest `is_scalar`, en `SimplecartOrderanswer::getAnswerFor()` lost één label op.

## Goals / Non-Goals

**Goals:**
- Een formaat waarmee de klant meerdere opties van een vaste lijst kiest.
- Eén antwoord per vraag, als onveranderlijke momentopname, zonder schemawijziging.
- Dezelfde veiligheid en taalafhandeling als bij radio en keuzelijst.

**Non-Goals:**
- Minimum of maximum aantal keuzes (gekozen: geen limieten).
- Vrije tekstwaarden die de klant zelf toevoegt.
- Voorwaardelijke opties of gewichten per optie.
- Een tabel met één rij per gekozen optie.

## Decisions

### D1. Formaat `checkbox`, geen schemawijziging
Nieuwe constante `SimplecartQuestionFormat::CHECKBOX = 'checkbox'` met label "Meerkeuze (selectievakjes)". `isChoice()` geeft ook `true` voor dit formaat, zodat het aanmaken (minstens één optie), het oplossen van labels met taaltags en de optielijst meteen werken. Nieuwe hulpmethode `isMultiple()`. `formKind()` geeft `checkbox`; `ipfType()` blijft `TXTBOX` (de opties worden door de eigen laag gecontroleerd, niet door IPF).
- Alternatief verworpen: een aparte tabel met één rij per gekozen optie. Dat vraagt een schemawijziging, breekt het principe "één antwoord per vraag" en maakt weergave, verwijderen en de momentopname ingewikkelder.

- Alternatief verworpen: IPF-bouwstenen voor meerdere waarden (`XOBJ_DTYPE_SIMPLE_ARRAY`, `icms_form_elements_Checkbox`, `icms_ipf_form_elements_Selectmulti`, onderzocht in ImpressCMS 2.0.4). `SIMPLE_ARRAY` voegt enkel `|` samen en controleert niet of elke waarde een optie is (en slaat `required` over bij een lege lijst), dus de eigen validatie blijft nodig; `|` kan in een optielabel voorkomen; de `icms_form`-elementen passen niet in het Smarty/Bulma-afrekenformulier. De beheerzijde is al IPF.

### D2. Opslag: opties gescheiden door een regeleinde
`answer_value` bevat de gekozen optielabels (met taaltags), gescheiden door `\n`, in de volgorde van de optielijst. Een optie is één regel van het optieveld en bevat dus nooit een regeleinde, waardoor `\n` veilig scheidt. Eén antwoord blijft één rij; bestaande antwoorden en de weergave van andere formaten veranderen niet.
- Alternatief verworpen: JSON in `answer_value`. Breekt de bestaande weergave zonder winst; de regeleinde-vorm blijft altijd leesbaar.

### D3. Posten en valideren
Het formulier post `q_<id>[]` met de plaatsen (1-gebaseerd) van de aangevinkte opties, zoals bij radio. `checkout.php` accepteert voor velden `q_<id>` naast scalaire waarden ook een lijst van scalaire waarden. De validator:
1. radio en keuzelijst: een lijst met meer dan één waarde is ongeldig;
2. meerkeuze: elke waarde moet `ctype_digit` zijn en binnen de optielijst vallen, anders is de vraag ongeldig; dubbele waarden worden samengevoegd en de resultaten gesorteerd op plaats;
3. een lege lijst of enkel lege waarden telt als "geen antwoord": fout bij verplicht, anders overslaan zonder leeg antwoord.
`SimplecartAnswerResult::$values` houdt voor meerkeuze de lijst aangevinkte plaatsen bij, zodat het formulier ze opnieuw kan aanvinken. Een scalaire post voor een meerkeuzevraag (`q_5=2`) wordt als één gekozen plaats behandeld.

### D4. Weergave
`SimplecartOrderanswer::getAnswerFor()` splitst voor meerkeuze op `\n`, lost elk label op in de taal en voegt ze weer samen met `\n`. `toHtmlRow()` past al `escapeHtml` plus `nl2br` toe, waardoor elke optie op een eigen regel staat; de tekstmail krijgt de regeleinden rechtstreeks. Er komt geen opsommingsteken bij; één optie per regel is consistent met de bestaande rijen.

### D5. Formulier
`simplecart_checkout.html.tpl` krijgt een tak voor `kind == 'checkbox'`: per optie een `<label class="checkbox">` met `<input type="checkbox" name="<field>[]" value="<n>">`, Bulma-markup zoals bij de radio, `checked` volgens `$option.selected`. Geen `required` op de vakjes (HTML dwingt "minstens één" niet af); de server blijft de bron van waarheid. `SimplecartQuestionHandler::getFormRows()` markeert meerdere opties als geselecteerd op basis van de lijst plaatsen.

## Risks / Trade-offs

- Zonder `required` in HTML ziet de klant de fout pas na het versturen. Een JavaScript-controle valt buiten scope.
- Filteren of exporteren op één gekozen optie is lastiger dan met een rij per optie; filteren is al een non-goal van `add-order-questions`.
- `isChoice()` verruimen raakt bestaande aanroepers (`getAnswerFor`, aanmaakvalidatie); tests moeten radio en keuzelijst onveranderd bevestigen.
