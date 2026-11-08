<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/simplecart.css">

<section class="section">
  <div class="container">
    <h1 class="title is-4"><{$smarty.const._MD_SIMPLECART_CHECKOUT}></h1>

    <{if $simplecart_removed_items && !$simplecart_error_message}>
      <div class="notification is-warning">
        <{$smarty.const._MD_SIMPLECART_ITEMS_REMOVED}>
        <{foreach from=$simplecart_removed_items item=removed_name name=removed}><{$removed_name|escape:html}><{if !$smarty.foreach.removed.last}>, <{/if}><{/foreach}>
      </div>
    <{/if}>

    <{if $simplecart_order_success}>
      <div class="notification is-success"><strong><{$simplecart_order_success_message|escape:html}></strong></div>

      <{if $simplecart_payment.qr_image}>
        <div class="box has-text-centered mt-4">
          <h3 class="title is-5"><{$smarty.const._MD_SIMPLECART_PAY_WITH_SEPA}></h3>
          <img src="<{$simplecart_payment.qr_image}>" alt="SEPA QR Code" style="max-width: 280px;">
          <p class="help"><{$smarty.const._MD_SIMPLECART_SCAN_TO_PAY}></p>
        </div>
      <{/if}>

      <{if $simplecart_order_id || $simplecart_payment.beneficiary_name || $simplecart_payment.beneficiary_iban || isset($simplecart_payment.amount)}>
        <div class="box mt-4">
          <h3 class="title is-5"><{$smarty.const._MD_SIMPLECART_PAYMENT_INFO}></h3>
          <div class="content">
            <p><strong><{$smarty.const._MD_SIMPLECART_ORDER_ID}></strong>: #<{$simplecart_order_id|default:0}></p>
            <{if $simplecart_payment.beneficiary_name}>
              <p><strong><{$smarty.const._MD_SIMPLECART_BENEFICIARY}></strong>: <{$simplecart_payment.beneficiary_name|escape:html}></p>
            <{/if}>
            <{if $simplecart_payment.beneficiary_iban}>
              <p><strong><{$smarty.const._MD_SIMPLECART_IBAN}></strong>: <span class="is-family-monospace"><{$simplecart_payment.beneficiary_iban|escape:html}></span></p>
            <{/if}>
            <{if $simplecart_order_total_formatted}>
              <p><strong><{$smarty.const._MD_SIMPLECART_AMOUNT}></strong>: <{$simplecart_order_total_formatted}></p>
            <{/if}>
            <{if $simplecart_order_id}>
              <p><strong><{$smarty.const._MD_SIMPLECART_MSG}></strong>: <{$smarty.const._MD_SIMPLECART_ORDER_ID}> #<{$simplecart_order_id}></p>
            <{/if}>
          </div>
        </div>

        <div class="box mt-4">
          <h3 class="title is-5"><{$smarty.const._MD_SIMPLECART_ORDER_SUMMARY}></h3>

          <{if $simplecart_order_details.customer.name || $simplecart_order_details.customer.email || $simplecart_order_details.customer.phone || $simplecart_order_details.customer.address}>
            <div class="mb-4">
              <p><strong><{$smarty.const._MD_SIMPLECART_NAME}></strong>: <{$simplecart_order_details.customer.name|default:''|escape:html}></p>
              <p><strong><{$smarty.const._MD_SIMPLECART_EMAIL}></strong>: <{$simplecart_order_details.customer.email|default:''|escape:html}></p>
              <{if $simplecart_order_details.customer.phone}>
                <p><strong><{$smarty.const._MD_SIMPLECART_PHONE}></strong>: <{$simplecart_order_details.customer.phone|escape:html}></p>
              <{/if}>
              <{if $simplecart_order_details.customer.address}>
                <p><strong><{$smarty.const._MD_SIMPLECART_ADDRESS}></strong>: <{$simplecart_order_details.customer.address|escape:html}></p>
              <{/if}>
            </div>
          <{/if}>

          <{if $simplecart_order_details.answers|@count}>
            <div class="mb-4">
              <p class="has-text-weight-semibold"><{$smarty.const._MD_SIMPLECART_ANSWERS_HEADING}></p>
              <{foreach from=$simplecart_order_details.answers item=answer}>
                <p><strong><{$answer.label}></strong>: <{$answer.value}></p>
              <{/foreach}>
            </div>
          <{/if}>

          <{if $simplecart_order_details.items|@count}>
            <table class="table is-fullwidth is-striped is-hoverable">
              <thead>
                <tr>
                  <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
                  <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_QUANTITY}></th>
                  <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_UNIT_PRICE}></th>
                  <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_SUBTOTAL}></th>
                </tr>
              </thead>
              <tbody>
                <{foreach from=$simplecart_order_details.items item=item}>
                  <tr>
                    <td><{$item.name|escape:html}></td>
                    <td class="has-text-right"><{$item.quantity}></td>
                    <td class="has-text-right"><{$item.price_formatted}></td>
                    <td class="has-text-right"><{$item.subtotal_formatted}></td>
                  </tr>
                <{/foreach}>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="has-text-right"><strong><{$smarty.const._MD_SIMPLECART_TOTAL}></strong></td>
                  <td class="has-text-right"><strong><{$simplecart_order_details.total_formatted|default:'-'}></strong></td>
                </tr>
              </tfoot>
            </table>
          <{else}>
            <p><strong><{$smarty.const._MD_SIMPLECART_ORDER_ID}></strong>: #<{$simplecart_order_id|default:0}></p>
            <{if $simplecart_order_total_formatted}>
              <p><strong><{$smarty.const._MD_SIMPLECART_TOTAL}></strong>: <{$simplecart_order_total_formatted}></p>
            <{/if}>
          <{/if}>

          <{foreach from=$simplecart_extension_blocks item=block}>
            <div class="box"><{$block}></div>
          <{/foreach}>
        </div>
      <{/if}>
    <{elseif $simplecart_error_message}>
      <div class="notification is-danger"><{$simplecart_error_message|escape:html}></div>
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
            <td><{$item.name|escape:html}> × <{$item.quantity}></td>
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
          <label class="label"><{$smarty.const._MD_SIMPLECART_NAME}> <span class="tag is-info is-light">verplicht</span></label>
          <div class="control">
            <input class="input" type="text" name="customer_name" value="<{$simplecart_customer.name|escape:html}>" required>
          </div>
        </div>

        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_EMAIL}> <span class="tag is-info is-light">verplicht</span></label>
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

        <{if $simplecart_questions|@count}>
          <h2 class="title is-5 mt-5"><{$smarty.const._MD_SIMPLECART_QUESTIONS_HEADING}></h2>
        <{/if}>

        <{foreach from=$simplecart_questions item=question}>
          <div class="field">
            <label class="label">
              <{$question.label}>
              <{if $question.required}><span class="tag is-info is-light"><{$smarty.const._MD_SIMPLECART_QUESTION_REQUIRED_TAG}></span><{/if}>
            </label>
            <{if $question.help}><p class="help mb-2"><{$question.help}></p><{/if}>
            <div class="control">
              <{if $question.kind == 'textarea'}>
                <textarea class="textarea" name="<{$question.field}>" <{if $question.required}>required<{/if}>><{$question.value}></textarea>
              <{elseif $question.kind == 'radio'}>
                <{foreach from=$question.options item=option}>
                  <label class="radio">
                    <input type="radio" name="<{$question.field}>" value="<{$option.value}>" <{if $option.selected}>checked="checked"<{/if}> <{if $question.required}>required<{/if}>>
                    <{$option.label}>
                  </label>
                <{/foreach}>
              <{elseif $question.kind == 'select'}>
                <div class="select">
                  <select name="<{$question.field}>" <{if $question.required}>required<{/if}>>
                    <option value=""><{$smarty.const._MD_SIMPLECART_QUESTION_CHOOSE}></option>
                    <{foreach from=$question.options item=option}>
                      <option value="<{$option.value}>" <{if $option.selected}>selected="selected"<{/if}>><{$option.label}></option>
                    <{/foreach}>
                  </select>
                </div>
              <{else}>
                <input class="input" type="<{$question.input_type}>" name="<{$question.field}>" value="<{$question.value}>" <{if $question.required}>required<{/if}>>
              <{/if}>
            </div>
            <{if $question.error}><p class="help is-danger"><{$question.error}></p><{/if}>
          </div>
        <{/foreach}>

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
