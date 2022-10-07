<?php

/**
 * Class Morfdev_Freshdesk_Model_Authorization
 */
class Morfdev_Freshdesk_Model_Authorization extends Mage_Core_Model_Abstract
{
    /**
     * @param null|array $postData
     * @return null|integer|Mage_Core_Model_Store|Mage_Core_Model_Website
     */
    public function isAuth($postData)
    {
        $result = null;
        if(null === $postData || !isset($postData['token'])) {
            return $result;
        }
        /** @var Morfdev_Freshdesk_Helper_Config $helperConfig */
        $helperConfig = Mage::helper('md_freshdesk/config');
        //check is default token
        $storeToken =  $helperConfig->getApiTokenForDefault();
        if($postData['token'] == $storeToken) {
            $result = Mage_Core_Model_App::ADMIN_STORE_ID;
            return $result;
        }
        return $result;
    }
}
