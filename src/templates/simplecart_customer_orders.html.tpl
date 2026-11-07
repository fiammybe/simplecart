<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/simplecart.css">

<section class="section">
    <div class="container">
        <h1 class="title is-4"><{$smarty.const._MD_SIMPLECART_ACCESS_TITLE}></h1>

        <{if isset($simplecart_orders)}>
            <div class="level">
                <div class="level-left">
                    <p class="subtitle is-6"><{$simplecart_access_heading|escape}></p>
                </div>
                <div class="level-right">
                    <form method="post" action="<{$simplecart_module_url}>orders.php">
                        <input type="hidden" name="op" value="destroy">
                        <button type="submit" class="button is-small"><{$smarty.const._MD_SIMPLECART_ACCESS_LOGOUT}></button>
                    </form>
                </div>
            </div>

            <{foreach from=$simplecart_orders item=order}>
                <div class="box">
                    <p class="title is-5"><{$smarty.const._MD_SIMPLECART_ORDER_ID}> #<{$order.order_id}></p>
                    <table class="table is-fullwidth">
                        <tbody>
                            <tr>
                                <th><{$smarty.const._MD_SIMPLECART_EMAIL_ORDER_DATE}></th>
                                <td><{$order.date}></td>
                            </tr>
                            <tr>
                                <th><{$smarty.const._MD_SIMPLECART_SCAN_STATUS}></th>
                                <td><{$order.status|escape}></td>
                            </tr>
                            <{if $order.customer_name}>
                                <tr>
                                    <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
                                    <td><{$order.customer_name|escape}></td>
                                </tr>
                            <{/if}>
                        </tbody>
                    </table>

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
                                <{foreach from=$order.items item=row}>
                                    <tr>
                                        <td><{$row.product_name}></td>
                                        <td class="has-text-right"><{$row.product_price_fmt}> <{$simplecart_currency}></td>
                                        <td class="has-text-right"><{$row.quantity}></td>
                                        <td class="has-text-right"><{$row.subtotal_fmt}> <{$simplecart_currency}></td>
                                    </tr>
                                <{/foreach}>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="has-text-right"><{$smarty.const._MD_SIMPLECART_TOTAL}></th>
                                    <th class="has-text-right"><{$order.grand_total_fmt}> <{$simplecart_currency}></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <{foreachelse}>
                <div class="notification is-info is-light"><{$smarty.const._MD_SIMPLECART_ACCESS_NO_ORDERS}></div>
            <{/foreach}>
        <{else}>
            <div class="columns">
                <div class="column is-6-tablet is-5-desktop">
                    <p class="mb-4"><{$smarty.const._MD_SIMPLECART_ACCESS_INTRO}></p>

                    <{if $simplecart_access_error}>
                        <div class="notification is-danger is-light"><{$simplecart_access_error|escape}></div>
                    <{/if}>

                    <form method="post" action="<{$simplecart_module_url}>orders.php" class="box">
                        <input type="hidden" name="op" value="store">
                        <input type="hidden" name="token" value="<{$simplecart_access_token|escape}>">

                        <div class="field">
                            <label class="label" for="simplecart_access_email"><{$smarty.const._MD_SIMPLECART_EMAIL}></label>
                            <div class="control">
                                <input class="input" type="email" id="simplecart_access_email" name="email" value="<{$simplecart_access_email|escape}>" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="simplecart_access_code"><{$smarty.const._MD_SIMPLECART_ACCESS_CODE}></label>
                            <div class="control">
                                <input class="input is-family-monospace" type="text" id="simplecart_access_code" name="access_code" placeholder="XXXXX-XXXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
                            </div>
                        </div>

                        <button type="submit" class="button is-primary"><{$smarty.const._MD_SIMPLECART_ACCESS_SUBMIT}></button>
                    </form>
                </div>
            </div>
        <{/if}>
    </div>
</section>
