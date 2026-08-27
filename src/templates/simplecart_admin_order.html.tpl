<div class="icms-admin-content">
  <h2><{$smarty.const._AM_PLAYLISTBUILDER_ORDER_ADMIN}></h2>
  <{if isset($simplecart_order_heading)}>
    <h3><{$simplecart_order_heading|default:''}></h3>
  <{/if}>
  <{if isset($simplecart_order_error)}>
    <div class="error"><{$simplecart_order_error}></div>
  <{/if}>
  <{if isset($simplecart_order_single)}>
    <{$simplecart_order_single}>
  <{/if}>
  <{if isset($simplecart_order_table)}>
    <{$simplecart_order_table}>
  <{/if}>
  <{if isset($simplecart_order_items)}>
    <h3>Order Items</h3>
    <{if $simplecart_order_items|@count > 0}>
      <table class="outer" cellspacing="1">
        <thead>
          <tr class="head">
            <th>Product</th>
            <th style="text-align:right;">Eenheidsprijs</th>
            <th style="text-align:right;">Aantal</th>
            <th style="text-align:right;">Subtotaal</th>
          </tr>
        </thead>
        <tbody>
          <{foreach from=$simplecart_order_items item=row}>
            <tr class="<{cycle values='even,odd'}>">
              <td><{$row.product_name|escape}></td>
              <td style="text-align:right;">$<{$row.product_price_fmt}></td>
              <td style="text-align:right;"><{$row.quantity}></td>
              <td style="text-align:right;">$<{$row.subtotal_fmt}></td>
            </tr>
          <{/foreach}>
        </tbody>
        <tfoot>
          <tr class="foot">
            <td colspan="3" style="text-align:right;"><strong>Totaal</strong></td>
            <td style="text-align:right;"><strong>$<{$simplecart_order_grand_total_fmt|default:'0.00'}></strong></td>
          </tr>
        </tfoot>
      </table>
    <{else}>
      <div class="resultMsg warnMsg">Geen elementen gevonden in deze bestelling.</div>
    <{/if}>
  <{/if}>

</div>
