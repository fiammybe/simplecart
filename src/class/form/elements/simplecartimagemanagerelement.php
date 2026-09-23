<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

class SimplecartImagemanagerElement extends icms_form_elements_select_Image
{
    public function __construct(icms_ipf_Object $object, string $key)
    {
        parent::__construct($object->vars[$key]['form_caption'], $key, $object->getVar($key, 'e'));
    }
}
