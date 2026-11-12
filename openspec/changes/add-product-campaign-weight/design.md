## Context

`SimplecartProductHandler::getProducts()` sorteert in SQL op `name`; `simplecart_getProductList()` en de winkelpagina nemen die volgorde over. Vragen hebben al een `weight` (`SimplecartQuestion`, IPF-veld met `XOBJ_DTYPE_INT`, besturingselement `text`, beheerlijst met `setDefaultSort('weight')`) en worden in PHP gesorteerd via `usort`. Campagnes en producten staan in aparte tabellen, dus één SQL-`ORDER BY` kan de samengestelde volgorde niet zonder join uitdrukken.

## Goals / Non-Goals

**Goals:**
- Winkelvolgorde: campagnegewicht, dan productgewicht, producten zonder campagne als laatste groep.
- Zelfde patroon als bij vragen, zodat beheerders één begrip van "gewicht" hebben.
- Ongewijzigde volgorde zolang niemand een gewicht instelt.

**Non-Goals:**
- Slepen-en-neerzetten in het beheer.
- Volgorde van het winkelmandje, de bestellijst of de vragen wijzigen.
- Wijzigingen aan de extension-API.

## Decisions

**`weight` als IPF-veld op beide objecten.** Zelfde definitie als bij de vraag: `initVar('weight', XOBJ_DTYPE_INT, 0, false, ...)`, `setControl('weight', 'text')`, label via nieuwe `_MI_…_WEIGHT`-constanten. Het schema volgt uit de objectdefinitie en de IPF-upgrade (geen `sql/mysql.sql`). Alternatief: een aparte ordertabel; verworpen, te zwaar voor een getal.

**Sorteren in PHP in `getProducts()`.** De handler haalt de beschikbare producten op (zoals nu) en laadt de campagnes één keer (`getObjects(null, true)`, geen N+1). Daarna `usort` op de sleutel `[campagnegroep, campagnegewicht, campagnenaam, productgewicht, productnaam, product_id]`. Voor een product zonder campagne is de groep 1 (alle echte campagnes 0), zodat de virtuele campagne altijd laatst komt zonder een magisch groot gewicht te gebruiken. Alternatief: SQL met `LEFT JOIN` en `ORDER BY`; verworpen omdat IPF-handlers geen joins in `getObjects` ondersteunen en de rest van de module ook in PHP sorteert. De productenlijst is klein.

**Tie-breaks op naam.** Dit behoudt het huidige gedrag bij gelijke (of nog niet ingestelde) gewichten. `product_id` is de laatste sleutel voor een deterministische volgorde bij gelijke namen.

**Beheerlijsten.** `weight`-kolom in beide lijsten en `setDefaultSort('weight')`, zoals in `admin/question.php`. De lijst van producten sorteert dus op productgewicht alleen; de winkelvolgorde (eerst campagne) staat bewust niet in het beheer, om het eenvoudig te houden. Het label legt uit dat het gewicht van een product binnen zijn campagne geldt.

**Validatie.** Gewicht wordt in `beforeInsert` naar een geheel getal gecast (zoals de vraag) zodat tekst als 0 wordt opgeslagen.

**Moduleversie.** Verhoog de versie in `icms_version.php` zodat de IPF-upgrade de kolommen toevoegt. `onupdate.inc.php` hoeft niet aangepast: nieuwe kolommen met standaardwaarde 0 vallen niet onder de NULL-problemen die daar worden opgevangen.

## Risks / Trade-offs

- [Bestaande rijen en de IPF-upgrade] Een upgrade die faalt laat de kolom ontbreken → test de update op een kopie van een bestaande installatie en controleer dat `getProducts()` dan niet breekt.
- [Productgewicht lijkt globaal in het beheer] Een beheerder ziet de productlijst op productgewicht en verwacht mogelijk dezelfde volgorde in de winkel → label en hulptekst benoemen dat het gewicht binnen de campagne geldt.
- [Negatieve of zeer grote gewichten] Gehele getallen worden aanvaard zoals bij vragen; geen bovengrens, de PHP-int volstaat.

## Assumptions

- Alleen de winkelpagina verandert van volgorde; het winkelmandje blijft in de volgorde waarin producten zijn toegevoegd.
- Beschikbaarheid (actief product, actieve campagne) blijft zoals nu; gewicht beïnvloedt die niet.
- "Laagste eerst" geldt ook voor campagnes, in lijn met het label bij vragen.

## Open Questions

- Moet de winkelpagina de campagne zichtbaar als kop tonen? Buiten scope hier; de volgorde geldt ook zonder koppen.
