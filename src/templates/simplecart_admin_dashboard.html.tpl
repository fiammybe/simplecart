<div class="simplecart-admin-dashboard">
    <{if !$dashboard}>
    <p>No dashboard data.</p>
    <{else}>
    <table class="table">
        <thead>
        <tr>
            <th>Shift</th>
                <th>Total Orders</th>
                <th>Total Amount</th>
                <th>Paid Orders</th>
                <th>Paid Amount</th>
                <th>Pending Orders</th>
                <th>Pending Amount</th>
            </tr>
        </thead>
        <tbody>
        <{foreach from=$dashboard item=row}>
            <tr>
                <td><{$row.shift_name|default:''|escape:'html'}></td>

                <td>
                    <a href="order.php?shift=<{$row.shift_key|escape:'url'}>">
                        <{$row.total_orders|default:0|escape:'html'}>
                    </a>
                </td>

                <td>
                    <{$row.total_amount|default:0|number_format:2:'.':','|escape:'html'}>
                </td>

                <td>
                    <a href="order.php?shift=<{$row.shift_key|escape:'url'}>&amp;status=paid">
                        <{$row.paid_orders|default:0|escape:'html'}>
                    </a>
                </td>

                <td>
                    <{$row.paid_amount|default:0|number_format:2:'.':','|escape:'html'}>
                </td>

                <td>
                    <a href="order.php?shift=<{$row.shift_key|escape:'url'}>&amp;status=pending">
                        <{$row.pending_orders|default:0|escape:'html'}>
                    </a>
                </td>

                <td>
                    <{$row.pending_amount|default:0|number_format:2:'.':','|escape:'html'}>
                </td>
            </tr>
        <{/foreach}>
        </tbody>
    </table>
    <{/if}>

    <!-- Product Sales Breakdown by Shift -->
    <{if $productSalesBreakdown}>
    <h2 class="title">Product Sales Breakdown by Shift</h2>
    <{foreach from=$productSalesBreakdown item=shiftData}>
        <h3 class="subtitle">Shift: <{$shiftData.shift_name|default:''|escape:'html'}></h3>
        <{if $shiftData.products|@count > 0}>
        <table class="table">
            <thead>
            <tr>
                <th>Product Name</th>
                <th style="text-align: right;">Total Quantity</th>
                <th style="text-align: right;">Total Revenue</th>
            </tr>
            </thead>
            <tbody>
            <{foreach from=$shiftData.products item=product}>
                <tr>
                    <td><{$product.product_name|default:''|escape:'html'}></td>
                    <td style="text-align: right;"><{$product.total_quantity|default:0|escape:'html'}></td>
                    <td style="text-align: right;"><{$product.total_revenue|default:0|number_format:2:'.':','|escape:'html'}></td>
                </tr>
            <{/foreach}>
            </tbody>
        </table>
        <{else}>
        <p>No products sold in this shift.</p>
        <{/if}>
    <{/foreach}>
    <{/if}>
</div>
