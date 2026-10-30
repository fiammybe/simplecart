<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

class SimplecartCampaign extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);

        $this->initVar('campaign_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('name', XOBJ_DTYPE_TXTBOX, '', true, 255, '', false, _MI_SIMPLECART_CAMPAIGN_NAME);
        $this->initVar('start_date', XOBJ_DTYPE_LTIME, time(), true, null, '', false, _MI_SIMPLECART_CAMPAIGN_START);
        $this->initVar('end_date', XOBJ_DTYPE_LTIME, 0, false, null, '', false, _MI_SIMPLECART_CAMPAIGN_END);

        $this->hideFieldFromForm('campaign_id');

        $this->handler->identifierName = 'name';
    }

    public function hasEnd(): bool
    {
        return (int)$this->getVar('end_date', 'n') > 0;
    }

    public function isActive(?int $now = null): bool
    {
        $now ??= time();

        if ((int)$this->getVar('start_date', 'n') > $now) {
            return false;
        }

        if (!$this->hasEnd()) {
            return true;
        }

        return (int)$this->getVar('end_date', 'n') >= $now;
    }

    public function getStartFormatted(): string
    {
        return formatTimestamp((int)$this->getVar('start_date', 'n'), 'm');
    }

    public function getEndFormatted(): string
    {
        if (!$this->hasEnd()) {
            return '-';
        }

        return formatTimestamp((int)$this->getVar('end_date', 'n'), 'm');
    }

    public function getStatusLabel(): string
    {
        return $this->isActive() ? _AM_SIMPLECART_CAMPAIGN_STATUS_ACTIVE : _AM_SIMPLECART_CAMPAIGN_STATUS_INACTIVE;
    }
}

class SimplecartCampaignHandler extends icms_ipf_Handler {
    // an empty date in the datetime control is stored as a time of day, never as a real timestamp
    private const MIN_TIMESTAMP = 86400;

    public function __construct(&$db) {
        parent::__construct($db, 'campaign', 'campaign_id', 'name', 'name', 'simplecart');
    }

    public function beforeInsert(&$obj) {
        $start = (int)$obj->getVar('start_date', 'n');
        $end = (int)$obj->getVar('end_date', 'n');

        if ($start < self::MIN_TIMESTAMP) {
            $obj->setErrors(_AM_SIMPLECART_CAMPAIGN_START_REQUIRED);

            return false;
        }

        if ($end < self::MIN_TIMESTAMP) {
            $end = 0;
        }

        if ($end > 0 && $end <= $start) {
            $obj->setErrors(_AM_SIMPLECART_CAMPAIGN_END_BEFORE_START);

            return false;
        }

        $obj->setVar('end_date', $end);

        return true;
    }

    public function beforeUpdate(&$obj) {
        return $this->beforeInsert($obj);
    }

    public function beforeDelete(&$obj) {
        $productHandler = simplecart_getHandler('product');
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('campaign_id', (int)$obj->getVar('campaign_id')));
        $productHandler->updateAll('campaign_id', 0, $criteria, true);

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function getCampaignArray(): array
    {
        $options = [0 => _AM_SIMPLECART_CAMPAIGN_NONE];

        $criteria = new icms_db_criteria_Compo();
        $criteria->setSort('name');
        $criteria->setOrder('ASC');

        foreach ($this->getObjects($criteria, true, true) as $id => $campaign) {
            $options[(int)$id] = (string)$campaign->getVar('name');
        }

        return $options;
    }

    /**
     * @return array<int, int>
     */
    public function getActiveIds(): array
    {
        $now = time();
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('start_date', $now, '<='));

        $end = new icms_db_criteria_Compo(new icms_db_criteria_Item('end_date', 0));
        $end->add(new icms_db_criteria_Item('end_date', $now, '>='), 'OR');
        $criteria->add($end);

        $ids = [];
        foreach ($this->getObjects($criteria, true, true) as $id => $campaign) {
            $ids[] = (int)$id;
        }

        return $ids;
    }
}
?>
