<?php

class Morfdev_Freshdesk_Adminhtml_Morfdev_Freshdesk_IndexController
    extends Mage_Adminhtml_Controller_Action
{

    /** @var array  */
    protected $_publicActions = array('redirect');

    /**
     * @return void
     */
    public function redirectAction()
    {
        $type = $this->getRequest()->getParam('type');
        $id = $this->getRequest()->getParam('id');

        if (null === $type || null === $id) {
            $this->_forward('noroute');
            return;
        }

        switch ($type) {
            case Morfdev_Freshdesk_Model_Source_RedirectType::CUSTOMER_TYPE:
                $this->_redirect('adminhtml/customer/edit', array('id' => $id));
                break;
            case Morfdev_Freshdesk_Model_Source_RedirectType::PRODUCT_TYPE:
                $this->_redirect('adminhtml/catalog_product/edit', array('id' => $id));
                break;
            case Morfdev_Freshdesk_Model_Source_RedirectType::ORDER_TYPE:
                $this->_redirect('adminhtml/sales_order/view', array('order_id' => $id));
                break;
            default:
                $this->_forward('noroute');
        }
    }
}
