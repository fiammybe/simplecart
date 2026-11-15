<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/dashboard.css?v=<{$simplecart_asset_version}>">

<div class="simplecart-admin-dashboard">
    <div id="simplecart-dashboard">
        <noscript>
            <p><{$smarty.const._AM_SIMPLECART_DASH_NOSCRIPT}></p>
        </noscript>
    </div>

    <script type="application/json" id="simplecart-dashboard-data"><{$simplecart_dashboard_json}></script>
    <script type="application/json" id="simplecart-dashboard-strings"><{$simplecart_dashboard_strings}></script>
    <script src="<{$simplecart_module_url}>assets/js/vendor/chart.umd.min.js?v=4.6.1"></script>
    <script src="<{$simplecart_module_url}>assets/js/dashboard.js?v=<{$simplecart_asset_version}>"></script>
</div>
