<?php
include_once __DIR__ . '/header.php';

$icms_question_handler = simplecart_getHandler('question');
$clean_op = isset($_REQUEST['op']) ? preg_replace('/[^a-z_]/', '', $_REQUEST['op']) : 'list';
$question_id = isset($_REQUEST['question_id']) ? (int)$_REQUEST['question_id'] : 0;

global $icmsAdminTpl;

icms_cp_header();
icms::$module->displayAdminMenu(4, 'SimpleCart');

switch ($clean_op) {
    case 'mod':
        $obj = $question_id ? $icms_question_handler->get($question_id) : $icms_question_handler->create();
        $icmsAdminTpl->assign('simplecart_question_form', $obj->getForm(_AM_SIMPLECART_QUESTION_FORM, 'addquestion', 'question.php', _SUBMIT)->render());
        $icmsAdminTpl->display('db:simplecart_admin_question.html.tpl');
        break;

    case 'addquestion':
        $controller = new icms_ipf_Controller($icms_question_handler);
        $controller->storeFromDefaultForm(_AM_SIMPLECART_QUESTION_CREATED, _AM_SIMPLECART_QUESTION_UPDATED, 'question.php');
        break;

    case 'del':
        $controller = new icms_ipf_Controller($icms_question_handler);
        $controller->handleObjectDeletion(_AM_SIMPLECART_QUESTION_DELETE_CONFIRM);
        break;

    default:
        $objectTable = new icms_ipf_view_Table($icms_question_handler);
        $objectTable->addColumn(new icms_ipf_view_Column('label', _GLOBAL_LEFT, false, 'getLabelPreview'));
        $objectTable->addColumn(new icms_ipf_view_Column('campaign_id', 'center', 160, 'getCampaignName', false, _AM_SIMPLECART_QUESTION_COLUMN_CAMPAIGN, false));
        $objectTable->addColumn(new icms_ipf_view_Column('format', 'center', 180, 'getFormatLabel', false, _AM_SIMPLECART_QUESTION_COLUMN_FORMAT, false));
        $objectTable->addColumn(new icms_ipf_view_Column('is_required', 'center', 110, 'getRequiredLabel', false, _AM_SIMPLECART_QUESTION_COLUMN_ANSWER, false));
        $objectTable->addColumn(new icms_ipf_view_Column('weight', 'center', 80));
        $objectTable->addColumn(new icms_ipf_view_Column('active', 'center', 80));
        $objectTable->addFilter('campaign_id', 'getCampaignFilter');
        $objectTable->setDefaultSort('weight');
        $objectTable->addIntroButton('addquestion', 'question.php?op=mod', _AM_SIMPLECART_QUESTION_CREATE);
        $objectTable->addQuickSearch(array('label'));
        $icmsAdminTpl->assign('simplecart_question_table', $objectTable->fetch());
        $icmsAdminTpl->display('db:simplecart_admin_question.html.tpl');
        break;
}

icms_cp_footer();
