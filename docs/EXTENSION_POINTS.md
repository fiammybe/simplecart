# Extension points voor add-ons (API-versie 1)

Simplecart kan uitgebreid worden door een andere ImpressCMS-module (een *add-on*), bijvoorbeeld [simplecart-seats](https://github.com/fiammybe/simplecart-seats). Zonder add-on gedraagt simplecart zich als 2.2.0: er worden geen tabellen of kolommen toegevoegd en er verandert niets voor bezoekers of beheerders.

Beschikbaar vanaf simplecart **2.3.0**. De API-versie staat in de constante `SIMPLECART_EXTENSION_API` (nu `1`) en in `SimplecartExtension::API_VERSION`.

## Registreren

Een add-on levert in de root van zijn module een bestand `simplecart_extension.php`. Simplecart laadt dat bestand (met `require_once`) één keer per aanvraag, voor elke **actieve** module, zodra een extension point voor het eerst gebruikt wordt. In dat bestand registreert de add-on zijn luisteraars:

```php
<?php
// modules/simplecartseats/simplecart_extension.php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

if (!defined('SIMPLECART_EXTENSION_API') || SIMPLECART_EXTENSION_API < 1) {
    return; // te oude simplecart: niets registreren
}

SimplecartExtension::listen(SimplecartExtension::EVENT_CART_SUMMARY, function (SimplecartExtensionEvent $event) {
    $event->addResult([
        'name' => 'Optreden X - rij A, plaats 12',
        'price' => 25.0,
        'quantity' => 1,
        'html' => '<small>Nog 12:30 minuten gereserveerd</small>',
    ]);
});
```

Waarom geen ImpressCMS-preloads? De preloads van ImpressCMS 2.0 laden alleen vanuit `plugins/preloads/`, kennen geen detectie per module en geven geen resultaten terug. Een eigen, kleine registratie is eenvoudiger en voorspelbaarder.

## Gedrag van luisteraars

- Een luisteraar is een `callable` die een `SimplecartExtensionEvent` ontvangt: `getName()`, `getPayload()`, `get($key, $default)` en `addResult(array $result)`.
- Luisteraars lopen in volgorde van registratie. Resultaten van alle luisteraars worden verzameld.
- **Blokkerende events** (`order.collectLines`, `order.beforePlace`, `order.afterPlace`): een uitzondering van een luisteraar breekt de bestelling af. Het bericht van een `Exception` wordt aan de klant getoond, houd het dus leesbaar en vertaald.
- **Niet-blokkerende events** (alle andere): een uitzondering wordt gelogd (`error_log`) en genegeerd, zodat een defecte add-on de winkel niet stuk maakt.
- Zonder luisteraars gebeurt er niets.

## Events

| Event | Payload | Resultaat (`addResult`) | Blokkerend |
|---|---|---|---|
| `cart.summary` | `items`, `total`, `count` van de productregels | regel (zie *Regels*) | nee |
| `cart.emptied` | `reason`: `manual` of `order_placed` | niets | nee |
| `order.collectLines` | `customer`, `items` (productregels van de sessie) | regel (zie *Regels*) | ja |
| `order.beforePlace` | `customer`, `items`, `lines` (genormaliseerd) | niets; gooi een uitzondering om te weigeren | ja |
| `order.afterPlace` | `order`, `lines` (met `orderitem_id`, `meta`), `customer` | niets; gooi een uitzondering om alles terug te draaien | ja |
| `order.cancelled` | `order`, `reason`: `status` of `deleted` | niets | nee |
| `order.render` | `order`, `context`: `mail`, `page` of `scan` | `text` (mail) of `html` (page, scan) | nee |
| `scan.resolve` | `addon`, `params` (`$_GET`), `user` | `title`, `message`, `status` (`success`/`warning`/`error`), `html` | nee |

### Regels

Een regel is een array:

| Sleutel | Verplicht | Betekenis |
|---|---|---|
| `name` | ja | naam (max. 255 tekens); wordt de `product_name` van het orderitem |
| `price` | ja | prijs per stuk, `>= 0` |
| `quantity` | nee (1) | `1` tot `1000` |
| `meta` | nee | vrije data die enkel in het geheugen blijft en terugkomt in `order.afterPlace` |
| `source` | nee | herkomst, vrij te kiezen |
| `html` | nee | extra HTML onder de regel in het winkelwagenoverzicht. **De add-on moet dit zelf escapen.** |

Ongeldige regels in `cart.summary` worden stilzwijgend overgeslagen. Ongeldige regels in `order.collectLines` breken de bestelling af.

## Levenscyclus van een bestelling

1. `cart.summary` — bij elke weergave van winkelwagen en checkout: de add-on levert zijn regels.
2. `order.collectLines` — bij het plaatsen: de add-on levert de definitieve regels (prijzen opnieuw uit de eigen bron, nooit uit de client).
3. `order.beforePlace` — valideren; uitzondering = geen bestelling, geen databasewijziging.
4. Simplecart start een databasetransactie, maakt de order en per regel een gewoon `orderitem` (productregels en add-onregels).
5. `order.afterPlace` — binnen dezelfde transactie vastleggen (bv. plaatsen op *verkocht*). Een uitzondering draait de **hele** bestelling terug.
6. Commit, bevestigingsmail (`order.render`, context `mail`) en `cart.emptied` met reden `order_placed`.

Een bestelling die uitsluitend uit add-onregels bestaat is geldig. Het totaal moet groter dan nul zijn.

### Transacties

Zodra er add-onregels zijn (of een luisteraar voor `order.afterPlace` bestaat) loopt het plaatsen in een transactie. Bestaan add-onregels, dan **eist** simplecart dat `simplecart_order` en `simplecart_orderitem` InnoDB gebruiken; anders wordt de bestelling geweigerd met een duidelijke melding. Add-ons die zelf tabellen gebruiken moeten die ook als InnoDB aanmaken.

### Annulatie

`order.cancelled` wordt gestuurd wanneer een bestelling naar status `cancelled` gaat (reden `status`) en wanneer ze verwijderd wordt zonder al geannuleerd te zijn (reden `deleted`, nog vóór de orderitems verdwijnen). De add-on geeft daar bijvoorbeeld plaatsen vrij.

### Weergave

- `order.render` met `context = mail`: resultaat `text` (platte tekst) komt na de bestelregels in de bevestigingsmail.
- `context = page`: resultaat `html` komt op de succespagina van de checkout, enkel zichtbaar voor de bezoeker die de bestelling net plaatste (sessie).
- `context = scan`: resultaat `html` komt op de scanpagina van een bestelling.
- `html` wordt niet gefilterd. **De add-on is verantwoordelijk voor het escapen van gebruikersdata.**

### Scannen

`scan.php` blijft de bestaande bestelscan (`order_id` en `token`) afhandelen. Bevat de URL een parameter `addon`, dan stuurt simplecart na de login- en beheerderscontrole het event `scan.resolve` en toont het eerste resultaat. De volledige query (`$_GET`) wordt meegegeven en moet door de add-on gevalideerd worden. Zonder resultaat toont simplecart "ongeldige link".

## Compatibiliteit en versiebeheer

- Het toevoegen van events of optionele payloadvelden verhoogt `SIMPLECART_EXTENSION_API` niet.
- Het wijzigen of verwijderen van events of verplichte velden verhoogt het hoofdnummer.
- Add-ons controleren `SIMPLECART_EXTENSION_API` bij installatie en gebruik.

| simplecart | extension-API |
|---|---|
| 2.3.x | 1 |

## Tests

De dispatcher en het bestelproces zijn getest met een zelfstandige runner die ImpressCMS en IPF stubt:

```
php tests/run.php
```

Add-ons kunnen `src/class/Extension.php` rechtstreeks in hun eigen tests laden (`SimplecartExtension::reset()` en `SimplecartExtension::setFileProvider()` zijn bedoeld voor tests).
