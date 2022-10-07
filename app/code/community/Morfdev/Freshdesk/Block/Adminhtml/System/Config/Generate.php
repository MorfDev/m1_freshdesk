<?php

class Morfdev_Freshdesk_Block_Adminhtml_System_Config_Generate
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        $url = $this->getUrl('adminhtml/morfdev_freshdesk_system/generate');
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel($this->__('Generate new token'))
            ->setOnClick("setLocation('" . $url . "')")
            ->toHtml()
        ;
        return '';
    }
}
