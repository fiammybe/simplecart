<{* Order Detail Template for SimpleCart Admin *}

<div class="icms-admin-content">
  <h2 class="title is-2"><{$smarty.const._AM_SIMPLECART_ORDER_ADMIN}></h2>

  <{if isset($simplecart_order_heading)}>
    <h3 class="title is-3"><{$simplecart_order_heading|default:''}></h3>
  <{/if}>

  <{if isset($simplecart_order_error)}>
    <div class="notification is-danger"><{$simplecart_order_error}></div>
  <{/if}>

  <!-- Main Order Information -->
  <{if isset($simplecart_order_single)}>
    <div class="box">
      <{$simplecart_order_single}>
    </div>
  <{/if}>

  <!-- Order Items Section -->
  <{if isset($simplecart_order_items) && isset($simplecart_order_grand_total_fmt)}>
    <div class="box mt-5">
      <h3 class="title is-4">Order Items</h3>

      <div class="table-container">
        <table class="table is-fullwidth is-striped">
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <{foreach from=$simplecart_order_items item=row name=orderitem}>
            <tr>
              <td><{$row.product_name|escape}></td>
              <td>$<{$row.product_price_fmt}></td>
              <td><{$row.quantity}></td>
              <td>$<{$row.subtotal_fmt}></td>
            </tr>
            <{/foreach}>
          </tbody>
          <tfoot>
            <tr class="has-background-info-light">
              <td colspan="3" class="has-text-right"><strong>Grand Total:</strong></td>
              <td><strong>$<{$simplecart_order_grand_total_fmt|default:'0.00'}></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  <{/if}>

  <!-- Action Buttons -->
  <div class="field is-grouped mt-4">
    <p class="control">
      <a href="order.php?op=list" class="button is-link is-light">
        <span>Back to Orders</span>
      </a>
    </p>
    <{if isset($simplecart_can_edit_order) && $simplecart_can_edit_order}>
      <p class="control">
        <a href="order.php?order_id=<{$smarty.get.order_id|default:0}>&op=edit" class="button is-primary">
          <span>Edit Order</span>
        </a>
      </p>
    <{/if}>
  </div>
</div>
