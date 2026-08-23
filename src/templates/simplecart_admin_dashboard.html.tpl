<div class="simplecart-admin-dashboard">
    <{if !$dashboard}>
    <p>No dashboard data.</p>
    <{else}>
    <table class="table">
        <thead>
        <tr>
            <th>Group</th>
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
                <td><{$row.group_name|default:'Overview'|escape:'html'}></td>
                <td><{$row.total_orders|default:0|escape:'html'}></td>
                <td><{$row.total_amount|default:0|number_format:2:'.':','|escape:'html'}></td>
                <td><{$row.paid_orders|default:0|escape:'html'}></td>
                <td><{$row.paid_amount|default:0|number_format:2:'.':','|escape:'html'}></td>
                <td><{$row.pending_orders|default:0|escape:'html'}></td>
                <td><{$row.pending_amount|default:0|number_format:2:'.':','|escape:'html'}></td>
            </tr>
        <{/foreach}>
        </tbody>
    </table>
    <{/if}>

    <{if $productSalesBreakdown}>
    <h2 class="title">Product Sales Breakdown</h2>
    <{foreach from=$productSalesBreakdown item=groupData}>
        <h3 class="subtitle"><{$groupData.group_name|default:'Overview'|escape:'html'}></h3>
        <{if $groupData.products|@count > 0}>
        <table class="table">
            <thead>
            <tr>
                <th>Product Name</th>
                <th style="text-align: right;">Total Quantity</th>
                <th style="text-align: right;">Total Revenue</th>
            </tr>
            </thead>
            <tbody>
            <{foreach from=$groupData.products item=product}>
                <tr>
                    <td><{$product.product_name|default:''|escape:'html'}></td>
                    <td style="text-align: right;"><{$product.total_quantity|default:0|escape:'html'}></td>
                    <td style="text-align: right;"><{$product.total_revenue|default:0|number_format:2:'.':','|escape:'html'}></td>
                </tr>
            <{/foreach}>
            </tbody>
        </table>
        <{else}>
        <p>No products sold yet.</p>
        <{/if}>
    <{/foreach}>
    <{/if}>
</div>
