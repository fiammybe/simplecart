<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">

<section class="section">
    <div class="container">
        <h1 class="title is-4"><{$smarty.const._MD_SIMPLECART_SCAN_TITLE}></h1>

        <{if isset($simplecart_scan_error)}>
            <div class="notification is-danger"><{$simplecart_scan_error|escape}></div>
        <{else}>
            <div class="notification <{if $simplecart_scan_first}>is-success<{else}>is-warning<{/if}>">
                <strong><{$simplecart_scan_message|escape}></strong>
            </div>

            <{if isset($simplecart_scan_status_warning)}>
                <div class="notification is-danger is-light"><{$simplecart_scan_status_warning|escape}></div>
            <{/if}>

            <div class="box">
                <p class="title is-5"><{$smarty.const._MD_SIMPLECART_ORDER_ID}> #<{$simplecart_scan_order.order_id}></p>
                <table class="table is-fullwidth">
                    <tbody>
                        <tr>
                            <th><{$smarty.const._MD_SIMPLECART_EMAIL_ORDER_DATE}></th>
                            <td><{$simplecart_scan_order.date}></td>
                        </tr>
                        <tr>
                            <th><{$smarty.const._MD_SIMPLECART_SCAN_STATUS}></th>
                            <td><{$simplecart_scan_order.status|escape}></td>
                        </tr>
                        <{if $simplecart_scan_order.customer_name}>
                            <tr>
                                <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
                                <td><{$simplecart_scan_order.customer_name|escape}></td>
                            </tr>
                        <{/if}>
                        <{if $simplecart_scan_order.customer_email}>
                            <tr>
                                <th><{$smarty.const._MD_SIMPLECART_EMAIL}></th>
                                <td><{$simplecart_scan_order.customer_email|escape}></td>
                            </tr>
                        <{/if}>
                        <{if $simplecart_scan_order.customer_phone}>
                            <tr>
                                <th><{$smarty.const._MD_SIMPLECART_PHONE}></th>
                                <td><{$simplecart_scan_order.customer_phone|escape}></td>
                            </tr>
                        <{/if}>
                    </tbody>
                </table>
            </div>

            <div class="box">
                <p class="title is-5"><{$smarty.const._MD_SIMPLECART_EMAIL_ITEMS}></p>
                <div class="table-container">
                    <table class="table is-fullwidth is-striped">
                        <thead>
                            <tr>
                                <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
                                <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_EMAIL_UNIT_PRICE}></th>
                                <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_EMAIL_QUANTITY}></th>
                                <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_EMAIL_SUBTOTAL}></th>
                            </tr>
                        </thead>
                        <tbody>
                            <{foreach from=$simplecart_scan_order.items item=row}>
                                <tr>
                                    <td><{$row.product_name}></td>
                                    <td class="has-text-right"><{$row.product_price_fmt}> <{$simplecart_scan_order.currency}></td>
                                    <td class="has-text-right"><{$row.quantity}></td>
                                    <td class="has-text-right"><{$row.subtotal_fmt}> <{$simplecart_scan_order.currency}></td>
                                </tr>
                            <{/foreach}>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="has-text-right"><{$smarty.const._MD_SIMPLECART_TOTAL}></th>
                                <th class="has-text-right"><{$simplecart_scan_order.grand_total_fmt}> <{$simplecart_scan_order.currency}></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <a class="button is-link is-light" href="<{$simplecart_scan_order.admin_url}>"><{$smarty.const._MD_SIMPLECART_SCAN_ADMIN_LINK}></a>
        <{/if}>
    </div>
</section>
