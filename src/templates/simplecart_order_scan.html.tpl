<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/simplecart.css">

<section class="sc-scan">
    <{if isset($simplecart_scan_error)}>
        <h1 class="title is-5"><{$smarty.const._MD_SIMPLECART_SCAN_TITLE}></h1>
        <div class="notification is-danger"><{$simplecart_scan_error|escape}></div>
    <{else}>
        <header class="sc-scan-header">
            <div>
                <h1 class="title is-4 mb-1">#<{$simplecart_scan_order.order_id}></h1>
                <p class="is-size-7 has-text-grey"><{$simplecart_scan_order.date}></p>
            </div>
            <span class="tag is-medium <{if $simplecart_scan_order.status_key == 'paid'}>is-success<{elseif $simplecart_scan_order.status_key == 'closed'}>is-dark<{else}>is-danger<{/if}>">
                <{$simplecart_scan_order.status|escape}>
            </span>
        </header>

        <{if isset($simplecart_scan_closed_message)}>
            <div class="notification is-warning sc-scan-notice">
                <strong><{$simplecart_scan_closed_message|escape}></strong>
            </div>
        <{/if}>

        <{if isset($simplecart_scan_status_warning)}>
            <div class="notification is-danger sc-scan-notice">
                <strong><{$simplecart_scan_status_warning|escape}></strong>
            </div>
        <{/if}>

        <div class="sc-scan-total">
            <span class="is-size-7 has-text-grey"><{$smarty.const._MD_SIMPLECART_TOTAL}>&nbsp;&middot;&nbsp;<{$simplecart_scan_order.item_count}>&times;</span>
            <span class="is-size-3 has-text-weight-bold"><{$simplecart_scan_order.grand_total_fmt}> <{$simplecart_scan_order.currency}></span>
        </div>

        <div class="box sc-scan-box">
            <p class="has-text-weight-semibold mb-2"><{$smarty.const._MD_SIMPLECART_EMAIL_ITEMS}></p>
            <ul class="sc-scan-items">
                <{foreach from=$simplecart_scan_order.items item=row}>
                    <li>
                        <span class="sc-scan-qty"><{$row.quantity}>&times;</span>
                        <span class="sc-scan-name">
                            <{$row.product_name}>
                            <span class="is-block is-size-7 has-text-grey"><{$row.product_price_fmt}> <{$simplecart_scan_order.currency}></span>
                        </span>
                        <span class="sc-scan-subtotal"><{$row.subtotal_fmt}></span>
                    </li>
                <{/foreach}>
            </ul>
        </div>

        <{if $simplecart_scan_order.customer_name || $simplecart_scan_order.customer_phone || $simplecart_scan_order.customer_email}>
            <div class="box sc-scan-box">
                <{if $simplecart_scan_order.customer_name}>
                    <p class="has-text-weight-semibold mb-2"><{$simplecart_scan_order.customer_name|escape}></p>
                <{/if}>
                <div class="buttons">
                    <{if $simplecart_scan_order.customer_phone}>
                        <a class="button is-light" href="tel:<{$simplecart_scan_order.customer_phone_href|escape}>" title="<{$smarty.const._MD_SIMPLECART_PHONE}>">
                            <{$simplecart_scan_order.customer_phone|escape}>
                        </a>
                    <{/if}>
                    <{if $simplecart_scan_order.customer_email}>
                        <a class="button is-light sc-scan-email" href="mailto:<{$simplecart_scan_order.customer_email|escape}>" title="<{$smarty.const._MD_SIMPLECART_EMAIL}>">
                            <{$simplecart_scan_order.customer_email|escape}>
                        </a>
                    <{/if}>
                </div>
            </div>
        <{/if}>

        <p class="has-text-centered mb-4">
            <a class="is-size-7" href="<{$simplecart_scan_order.admin_url}>"><{$smarty.const._MD_SIMPLECART_SCAN_ADMIN_LINK}></a>
        </p>

        <{if isset($simplecart_scan_close_form)}>
            <form method="post" action="<{$simplecart_scan_close_form.action|escape}>" class="sc-scan-actions" onsubmit="this.querySelector('button').classList.add('is-loading'); this.querySelector('button').disabled = true;">
                <input type="hidden" name="close_token" value="<{$simplecart_scan_close_form.close_token|escape}>">
                <button type="submit" class="button is-success is-medium is-fullwidth"><{$smarty.const._MD_SIMPLECART_SCAN_CLOSE_BUTTON}></button>
            </form>
        <{/if}>
    <{/if}>
</section>
