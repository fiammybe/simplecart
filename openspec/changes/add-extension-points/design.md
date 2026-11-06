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

### D1. Events via de ImpressCMS preload-handler
De kern dispatcht via `icms::$preload->triggerEvent(...)`; add-ons luisteren met een preload-klasse in hun `preload/`-map. `SimplecartExtension` verpakt de dispatch zodat namen en payloads op één plek staan en getest zijn. Zijn er geen luisteraars, dan gebeurt er niets.
- Alternatief verworpen: eigen registratietabel — extra database en beheer.
- Te verifiëren in de ondersteunde ImpressCMS-versie: custom events en luisteraars per module. Terugval: eigen registratie van luisteraars in `SimplecartExtension`.

### D2. Events en payloads (API-versie 1)
| Event | Payload | Resultaat van luisteraar |
|---|---|---|
| `cart.summary` | huidige cart-samenvatting | extra regels `[label, prijs, aantal, meta]` en eigen totaal |
| `order.collectLines` | sessie, klantgegevens | lijst bestelregels `[name, price, quantity, meta]` |
| `order.beforePlace` | klantgegevens, alle regels | geen, of een uitzondering met foutmelding |
| `order.afterPlace` | order, orderitems per regel (met meta) | geen, of een uitzondering (rolt alles terug) |
| `order.cancelled` | order | geen |
| `order.render` | order, context (`mail` of `page`) | extra tekst/regels |
| `scan.resolve` | GET-parameters, gebruiker | resultaat (titel, boodschap, status) of null |

Regels van add-ons worden in de kern als gewone `orderitem` aangemaakt (`product_name`, `product_price`, `quantity`); de `meta` blijft in het geheugen en gaat enkel naar `afterPlace`.

### D3. Transactie in `placeOrder()`
`placeOrder()` start een transactie (indien de tabelengine dat toelaat), laat `afterPlace` binnen die transactie lopen en doet bij een fout rollback. Zonder transactie-ondersteuning valt het terug op het huidige gedrag en schakelt het alle add-onregels uit met een duidelijke melding; add-ons moeten InnoDB controleren.

### D4. Bestellingen met enkel add-onregels
De huidige controles ("geen geldige items", "totaal > 0") worden aangepast: een bestelling is geldig als er minstens één regel is (product of add-on) en het totaal groter dan nul is.

### D5. Versiebeheer van de API
`SIMPLECART_EXTENSION_API` (integer, nu 1). Semver-regels: toevoegen van events of optionele payloadvelden is compatibel; wijzigen/verwijderen verhoogt het hoofdnummer. Add-ons controleren de waarde bij installatie en gebruik.

### D6. Annulatie
`order.cancelled` wordt uitgestuurd bij statuswijziging naar `cancelled`, bij verwijderen van een bestelling (`beforeDelete`) en na het vervallen van onbetaalde bestellingen indien de kern dat later introduceert.

## Risks / Trade-offs

- **Te beperkte of instabiele API** → klein houden, documenteren, contracttests, semver.
- **Preload-API verschilt per ImpressCMS-versie** → vroeg verifiëren (taak 0.1), terugval op eigen registratie.
- **Tweede systeem van cart/order-regels** → add-onregels zijn tijdelijk (sessie) en worden gewone orderitems; geen parallelle opslag in de kern.
- **MyISAM zonder transacties** → documenteren, add-on weigert installatie.
- **Regressies in de productflow** → regressietests dat zonder luisteraars alles identiek werkt.

## Migration Plan

1. Kern 2.3.0 uitbrengen met extension points, zonder databasewijzigingen.
2. Add-on 1.0.0 volgt in `fiammybe/simplecart-seats` en vereist 2.3.0.
3. Rollback van 2.3.0 naar 2.2.0 is zonder dataverlies mogelijk, behalve add-on-specifieke gegevens die de add-on zelf beheert.

## Open Questions

1. Preload-events of eigen listener-registratie (resultaat van verificatie)?
2. Moet de kern vervallen onbetaalde bestellingen zelf afhandelen (algemene betalingstermijn), of blijft dat voor de add-on?
