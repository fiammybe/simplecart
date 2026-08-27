<div class="icms-admin-content">
    <h2 class="title is-2"><{$smarty.const._AM_PLAYLISTBUILDER_ORDER_ADMIN}></h2>
    <{if isset($simplecart_order_heading)}>
      <h3 class="title is-3"><{$simplecart_order_heading|default:''}></h3>
    <{/if}>
    <{if isset($simplecart_order_error)}>
      <div class="notification is-danger"><{$simplecart_order_error}></div>
    <{/if}>
    <{if isset($simplecart_order_single)}>
      <{$simplecart_order_single}>
    <{/if}>
    <{if isset($simplecart_order_table)}>
      <{$simplecart_order_table}>
    <{/if}>

    <{if isset($simplecart_order_items)}>
      <h3 class="title is-3 mt-5">Order Items</h3>
      <{if $simplecart_order_items|@count > 0}>
        <!-- Responsive Grid View -->
        <div class="box">
          <!-- Grid Header -->
          <div class="columns is-hidden-touch is-mobile has-background-light p-3 mb-3">
            <div class="column is-5"><strong>Product</strong></div>
            <div class="column is-2 has-text-right"><strong>Unit Price</strong></div>
            <div class="column is-2 has-text-right"><strong>Qty</strong></div>
            <div class="column is-3 has-text-right"><strong>Subtotal</strong></div>
          </div>

          <!-- Grid Items -->
          <{foreach from=$simplecart_order_items item=row name=orderitem}>
            <div class="columns is-mobile border-bottom pb-2 pt-2">
              <!-- Mobile header for collapsed view -->
              <div class="is-hidden-desktop">
                <div class="has-text-weight-bold"><{$row.product_name|escape}></div>
              </div>

              <div class="column is-5">
                <div class="is-hidden-touch"><{$row.product_name|escape}></div>
                <div class="is-hidden-desktop"><span class="has-text-weight-semibold">Product:</span> <{$row.product_name|escape}></div>
              </div>
              <div class="column is-2 has-text-right-mobile">
                <div class="is-hidden-touch">$<{$row.product_price_fmt}></div>
                <div class="is-hidden-desktop"><span class="has-text-weight-semibold">Unit Price:</span> $<{$row.product_price_fmt}></div>
              </div>
              <div class="column is-2 has-text-right-mobile">
                <div class="is-hidden-touch"><{$row.quantity}></div>
                <div class="is-hidden-desktop"><span class="has-text-weight-semibold">Qty:</span> <{$row.quantity}></div>
              </div>
              <div class="column is-3 has-text-right-mobile">
                <div class="is-hidden-touch">$<{$row.subtotal_fmt}></div>
                <div class="is-hidden-desktop"><span class="has-text-weight-semibold">Subtotal:</span> $<{$row.subtotal_fmt}></div>
              </div>
            </div>
          <{/foreach}>

          <!-- Grand Total -->
          <div class="columns is-mobile has-background-info-light p-3 mt-3">
            <div class="column is-9 has-text-right-touch">
              <strong>Grand total</strong>
            </div>
            <div class="column is-3 has-text-right-touch">
              <strong>$<{$simplecart_order_grand_total_fmt|default:'0.00'}></strong>
            </div>
          </div>
        </div>
      <{else}>
        <div class="notification is-warning">No items found for this order.</div>
      <{/if}>
    <{/if}>
  </div>
