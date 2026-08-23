<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/simplecart.css">

<section class="section">
  <div class="container">
    <h1 class="title is-4"><{$smarty.const._MD_SIMPLECART_SHOP_TITLE}></h1>

    <{if $simplecart_message}>
      <div class="notification is-info"><{$simplecart_message}></div>
    <{/if}>

    <div class="columns is-multiline">
      <{foreach from=$simplecart_products item=product}>
      <div class="column is-12-mobile is-6-tablet is-4-desktop">
        <div class="card">
          <div class="card-content">
            <p class="title is-5"><{$product.name}></p>
            <p class="subtitle is-6 sc-card-price"><{$product.price_formatted}></p>
            <{if $product.description}>
              <p class="content"><{$product.description}></p>
            <{/if}>
            <form method="post" action="<{$simplecart_module_url}>index.php">
              <input type="hidden" name="action" value="add_to_cart">
              <input type="hidden" name="product_id" value="<{$product.product_id}>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="button is-primary is-small"><{$smarty.const._MD_SIMPLECART_ADD_TO_CART}></button>
            </form>
          </div>
        </div>
      </div>
      <{/foreach}>
    </div>

    <{if $simplecart_cart_items|@count}>
      <div class="box">
        <h2 class="title is-5"><{$smarty.const._MD_SIMPLECART_YOUR_CART}></h2>
        <table class="table is-fullwidth is-striped is-hoverable">
          <thead>
            <tr>
              <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
              <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_TOTAL}></th>
              <th class="has-text-centered">Qty</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <{foreach from=$simplecart_cart_items item=item}>
            <tr>
              <td><{$item.name}> <span class="tag is-light"><{$item.price_formatted}></span></td>
              <td class="has-text-right"><{$item.subtotal_formatted}></td>
              <td class="has-text-centered">
                <form method="post" action="<{$simplecart_module_url}>index.php" class="is-inline-block">
                  <input type="hidden" name="action" value="update_quantity">
                  <input type="hidden" name="product_id" value="<{$item.product_id}>">
                  <input class="input is-small" type="number" min="1" max="1000" name="quantity" value="<{$item.quantity}>" style="width: 72px;" onchange="this.form.submit();">
                  <button type="submit" class="button is-small is-light">Update</button>
                </form>
              </td>
              <td class="has-text-right">
                <form method="post" action="<{$simplecart_module_url}>index.php">
                  <input type="hidden" name="action" value="remove_from_cart">
                  <input type="hidden" name="product_id" value="<{$item.product_id}>">
                  <button type="submit" class="button is-text is-small"><{$smarty.const._MD_SIMPLECART_REMOVE}></button>
                </form>
              </td>
            </tr>
            <{/foreach}>
          </tbody>
        </table>
        <div class="has-text-right is-size-5 has-text-weight-semibold sc-total">
          <{$smarty.const._MD_SIMPLECART_TOTAL}>: <{$simplecart_cart_total_formatted}>
        </div>
        <div class="buttons is-justify-content-flex-end">
          <form method="post" action="<{$simplecart_module_url}>index.php">
            <input type="hidden" name="action" value="empty_cart">
            <button type="submit" class="button is-light"><{$smarty.const._MD_SIMPLECART_EMPTY_CART}></button>
          </form>
          <a href="<{$simplecart_module_url}>checkout.php" class="button is-link"><{$smarty.const._MD_SIMPLECART_CHECKOUT}></a>
        </div>
      </div>
    <{else}>
      <div class="notification is-light"><{$smarty.const._MD_SIMPLECART_EMPTY_CART}></div>
    <{/if}>
  </div>
</section>
