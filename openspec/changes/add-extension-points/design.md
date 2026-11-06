## Context

Simplecart 2.x heeft een sessiewinkelwagen (`$_SESSION['simplecart_cart']`), `SimplecartOrderHandler::placeOrder()`, een bevestigingsmail, een succespagina in `checkout.php` en een scanpagina. De kern kent alleen producten met een aantal. De add-on `simplecartseats` (eigen repository) moet plaatsen kunnen toevoegen zonder dat de kern daarvan weet. De volledige add-on-ontwerpkeuzes staan in `fiammybe/simplecart-seats`.

## Goals / Non-Goals

**Goals**
- Kleine, generieke, gedocumenteerde en versieerde extension-API.
- Geen gedragswijziging en geen databasewijziging zonder add-on.
- Foutieve add-on mag een bestelling nooit half laten aanmaken (transactie).

**Non-Goals**
- Een volledig plug-inframework of dynamische hooks voor alle onderdelen.
- Beheer-UI voor add-ons.
- Wijzigingen aan het datamodel (`orderitem` blijft ongewijzigd).

## Decisions

### D1. Eigen listener-registratie, ontdekt via `simplecart_extension.php`
De kern biedt `SimplecartExtension::listen($event, $callable)`. Add-ons leveren in hun modulemap een bestand `simplecart_extension.php`; de kern laadt dat per aanvraag één keer voor elke actieve module (lui, bij het eerste gebruik van een event) en het bestand registreert de luisteraars.
- **Verificatie (taak 0.1) in ImpressCMS 2.0.4:** de preload-handler laadt enkel bestanden uit `plugins/preloads/`, detecteert geen preloads per module (`$icmsOnDemandPreload` wordt nergens gevuld) en events leveren geen resultaten op. Daarom is het geplande gebruik van `icms::$preload->triggerEvent` verlaten.
- Alternatief verworpen: preload-bestand van de add-on naar `plugins/preloads/` kopiëren — vereist handmatige stappen en schrijfrechten.
- Alternatief verworpen: eigen registratietabel — extra database en beheer.
- Listeners krijgen een `SimplecartExtensionEvent` met payload en `addResult()`, zodat resultaten verzameld worden zonder referenties.
- Blokkerende events (`order.collectLines`, `order.beforePlace`, `order.afterPlace`) laten uitzonderingen door; de overige worden gelogd en genegeerd.

### D2. Events en payloads (API-versie 1)
| Event | Payload | Resultaat van luisteraar |
|---|---|---|
| `cart.summary` | huidige cart-samenvatting | extra regels `[name, price, quantity, html]` |
| `cart.emptied` | reden (`manual`, `order_placed`) | geen |
| `order.collectLines` | sessie, klantgegevens | lijst bestelregels `[name, price, quantity, meta]` |
| `order.beforePlace` | klantgegevens, alle regels | geen, of een uitzondering met foutmelding |
| `order.afterPlace` | order, orderitems per regel (met meta) | geen, of een uitzondering (rolt alles terug) |
| `order.cancelled` | order, reden (`status`, `deleted`) | geen |
| `order.render` | order, context (`mail`, `page`, `scan`) | `text` (mail) of `html` (page, scan) |
| `scan.resolve` | `addon`, GET-parameters, gebruiker (alleen bij parameter `addon`, na login- en beheerderscontrole) | `title`, `message`, `status`, `html` |

Regels van add-ons worden in de kern als gewone `orderitem` aangemaakt (`product_name`, `product_price`, `quantity`); de `meta` blijft in het geheugen en gaat enkel naar `afterPlace`.

### D3. Transactie in `placeOrder()`
`placeOrder()` start een transactie (indien de tabelengine dat toelaat), laat `afterPlace` binnen die transactie lopen en doet bij een fout rollback. De transactie wordt alleen gestart als er add-onregels of een `afterPlace`-luisteraar zijn; anders is het gedrag ongewijzigd. Bestaan er add-onregels en gebruiken `simplecart_order`/`simplecart_orderitem` geen InnoDB, dan weigert de kern de bestelling met een duidelijke melding.

### D4. Bestellingen met enkel add-onregels
De huidige controles ("geen geldige items", "totaal > 0") worden aangepast: een bestelling is geldig als er minstens één regel is (product of add-on) en het totaal groter dan nul is.

### D5. Versiebeheer van de API
`SIMPLECART_EXTENSION_API` (integer, nu 1). Semver-regels: toevoegen van events of optionele payloadvelden is compatibel; wijzigen/verwijderen verhoogt het hoofdnummer. Add-ons controleren de waarde bij installatie en gebruik.

### D7. Succespagina enkel voor de eigen sessie
`checkout.php?success=1&order_id=N` toont orderdetails zonder authenticatie (bestaand gedrag, niet gewijzigd). Add-on-uitvoer (`order.render`, context `page`) wordt daarom enkel getoond aan de sessie die de bestelling net plaatste.

### D6. Annulatie
`order.cancelled` wordt uitgestuurd bij statuswijziging naar `cancelled` en bij verwijderen van een bestelling (`beforeDelete`).

## Risks / Trade-offs

- **Te beperkte of instabiele API** → klein houden, documenteren, contracttests, semver.
- **ImpressCMS-preloads ongeschikt** → opgelost met eigen registratie (D1); module-detectie kost één gecachte modulequery per aanvraag, lui en enkel op pagina's die een event gebruiken.
- **Add-onregels in de kern** → ze zijn tijdelijk (sessie/aanvraag) en worden gewone orderitems; geen parallelle opslag in de kern.
- **MyISAM zonder transacties** → documenteren, add-on weigert installatie.
- **Regressies in de productflow** → regressietests dat zonder luisteraars alles identiek werkt.

## Migration Plan

1. Kern 2.3.0 uitbrengen met extension points, zonder databasewijzigingen.
2. Add-on 1.0.0 volgt in `fiammybe/simplecart-seats` en vereist 2.3.0.
3. Rollback van 2.3.0 naar 2.2.0 is zonder dataverlies mogelijk, behalve add-on-specifieke gegevens die de add-on zelf beheert.

## Open Questions

1. ~~Preload-events of eigen listener-registratie?~~ Opgelost: eigen registratie (D1).
2. Moet de kern vervallen onbetaalde bestellingen zelf afhandelen (algemene betalingstermijn), of blijft dat voor de add-on?
