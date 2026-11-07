<?php
include_once __DIR__ . '/header.php';

$icms_campaign_handler = simplecart_getHandler('campaign');
$clean_op = isset($_REQUEST['op']) ? preg_replace('/[^a-z_]/', '', $_REQUEST['op']) : 'list';
$campaign_id = isset($_REQUEST['campaign_id']) ? (int)$_REQUEST['campaign_id'] : 0;

function simplecart_admin_edit_campaign($campaign_id = 0) {
    global $icms_campaign_handler, $icmsAdminTpl;
    $obj = $campaign_id ? $icms_campaign_handler->get($campaign_id) : $icms_campaign_handler->create();
    icms_cp_header();
    $icmsAdminTpl->assign('simplecart_campaign_form', $obj->getForm(_AM_SIMPLECART_CAMPAIGN_FORM, 'addcampaign', 'campaign.php', _SUBMIT)->render());
    $icmsAdminTpl->display('db:simplecart_admin_campaign.html.tpl');
    icms_cp_footer();
}

switch ($clean_op) {
    case 'mod':
        icms_cp_header();
        icms::$module->displayAdminMenu(3, 'SimpleCart');
        simplecart_admin_edit_campaign($campaign_id);
        icms_cp_footer();
        break;

    case 'addcampaign':
        icms_cp_header();
        icms::$module->displayAdminMenu(3, 'SimpleCart');
        $controller = new icms_ipf_Controller($icms_campaign_handler);
        $controller->storeFromDefaultForm(_AM_SIMPLECART_CAMPAIGN_CREATED, _AM_SIMPLECART_CAMPAIGN_UPDATED, 'campaign.php');
        icms_cp_footer();
        break;

    case 'del':
        icms_cp_header();
        icms::$module->displayAdminMenu(3, 'SimpleCart');
        $controller = new icms_ipf_Controller($icms_campaign_handler);
        $controller->handleObjectDeletion(_AM_SIMPLECART_CAMPAIGN_DELETE_CONFIRM);
        icms_cp_footer();
        break;

    default:
        icms_cp_header();
        icms::$module->displayAdminMenu(3, 'SimpleCart');
        global $icmsAdminTpl;
        $objectTable = new icms_ipf_view_Table($icms_campaign_handler);
        $objectTable->addColumn(new icms_ipf_view_Column('name', _GLOBAL_LEFT, 250));
        $objectTable->addColumn(new icms_ipf_view_Column('start_date', 'center', 160, 'getStartFormatted', false, false, false));
        $objectTable->addColumn(new icms_ipf_view_Column('end_date', 'center', 160, 'getEndFormatted', false, false, false));
        $objectTable->addColumn(new icms_ipf_view_Column('campaign_id', 'center', 100, 'getStatusLabel', false, _AM_SIMPLECART_CAMPAIGN_STATUS, false));
        $objectTable->addIntroButton('addcampaign', 'campaign.php?op=mod', _AM_SIMPLECART_CAMPAIGN_CREATE);
        $objectTable->addQuickSearch(array('name'));
        $icmsAdminTpl->assign('simplecart_campaign_table', $objectTable->fetch());
        $icmsAdminTpl->display('db:simplecart_admin_campaign.html.tpl');
        icms_cp_footer();
        break;
}
