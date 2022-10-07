<?php

class Morfdev_Freshdesk_Adminhtml_Morfdev_Freshdesk_SystemController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return void
     */
    public function generateAction()
    {
        try {
            /** @var Morfdev_Freshdesk_Helper_Config $configHelper */
            $configHelper = Mage::helper('md_freshdesk/config');
            Mage::getModel('core/config')->saveConfig($configHelper::FRESHDESK_CONFIG_API_TOKEN_PATH, md5(time()));
            Mage::getConfig()->reinit();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('md_freshdesk')->__('Token successfully created.')
            );
        } catch (\Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }
}
