<?php

class Morfdev_Freshdesk_Helper_Config extends Mage_Core_Helper_Abstract
{
    const FRESHDESK_CONFIG_API_TOKEN_PATH = 'md_freshdesk/general/token';

    /**
     * @return string
     */
    public function getApiTokenForDefault()
    {
        return Mage::getStoreConfig(self::FRESHDESK_CONFIG_API_TOKEN_PATH, Mage_Core_Model_App::ADMIN_STORE_ID);
    }

}
