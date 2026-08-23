<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/simplecart.css">

<section class="section">
  <div class="container">
    <h1 class="title is-4"><{$smarty.const._MD_SIMPLECART_CHECKOUT}></h1>

    <{if $simplecart_order_success}>
      <div class="notification is-success"><strong><{$simplecart_order_success_message}></strong></div>
      <{if $simplecart_payment.qr_image}>
        <div class="box has-text-centered mt-4">
          <h3 class="title is-5"><{$smarty.const._MD_SIMPLECART_PAY_WITH_SEPA}></h3>
          <img src="<{$simplecart_payment.qr_image}>" alt="SEPA QR Code" style="max-width: 280px;">
          <p class="help"><{$smarty.const._MD_SIMPLECART_SCAN_TO_PAY}></p>
        </div>
      <{/if}>
    <{elseif $simplecart_error_message}>
      <div class="notification is-danger"><{$simplecart_error_message}></div>
    <{/if}>

    <{if !$simplecart_order_success && $simplecart_cart_items|@count}>
      <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
          <tr>
            <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
            <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_TOTAL}></th>
          </tr>
        </thead>
        <tbody>
          <{foreach from=$simplecart_cart_items item=item}>
          <tr>
            <td><{$item.name}> × <{$item.quantity}></td>
            <td class="has-text-right"><{$item.subtotal_formatted}></td>
          </tr>
          <{/foreach}>
        </tbody>
      </table>

      <div class="has-text-right is-size-5 has-text-weight-semibold sc-total">
        <{$smarty.const._MD_SIMPLECART_TOTAL}>: <{$simplecart_cart_total_formatted}>
      </div>

      <form method="post" action="<{$simplecart_module_url}>checkout.php">
        <input type="hidden" name="action" value="place_order">
        <input type="hidden" name="simplecart_token" value="<{$simplecart_order_token}>">

        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_NAME}></label>
          <div class="control">
            <input class="input" type="text" name="customer_name" value="<{$simplecart_customer.name}>" required>
          </div>
        </div>

        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_EMAIL}></label>
          <div class="control">
            <input class="input" type="email" name="customer_email" value="<{$simplecart_customer.email}>" required>
          </div>
        </div>

        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_PHONE}></label>
          <div class="control">
            <input class="input" type="tel" name="customer_phone" value="<{$simplecart_customer.phone}>">
          </div>
        </div>

        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_ADDRESS}></label>
          <div class="control">
            <textarea class="textarea" name="customer_address"><{$simplecart_customer.address}></textarea>
          </div>
        </div>

        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_HELP}></label>
          <div class="control">
            <label class="radio">
              <input type="radio" name="helpendehanden" value="help_1" <{if $simplecart_customer.helpendehanden == 'help_1'}>checked="checked"<{/if}> required>
              <{$smarty.const._MD_SIMPLECART_HELP_1}>
            </label>
            <label class="radio">
              <input type="radio" name="helpendehanden" value="help_2" <{if $simplecart_customer.helpendehanden == 'help_2'}>checked="checked"<{/if}> required>
              <{$smarty.const._MD_SIMPLECART_HELP_2}>
            </label>
            <label class="radio">
              <input type="radio" name="helpendehanden" value="help_3" <{if $simplecart_customer.helpendehanden == 'help_3'}>checked="checked"<{/if}> required>
              <{$smarty.const._MD_SIMPLECART_HELP_3}>
            </label>
            <label class="radio">
              <input type="radio" name="helpendehanden" value="help_4" <{if $simplecart_customer.helpendehanden == 'help_4'}>checked="checked"<{/if}> required>
              <{$smarty.const._MD_SIMPLECART_HELP_4}>
            </label>
          </div>
        </div>

        <div class="field">
          <div class="control">
            <button type="submit" class="button is-primary"><{$smarty.const._MD_SIMPLECART_PLACE_ORDER}></button>
          </div>
        </div>
      </form>
    <{elseif !$simplecart_order_success}>
      <div class="notification is-light"><{$smarty.const._MD_SIMPLECART_EMPTY_CART}></div>
    <{/if}>
  </div>
</section>
