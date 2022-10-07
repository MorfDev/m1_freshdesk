<?php

class Morfdev_Freshdesk_Model_Customer extends Mage_Core_Model_Abstract
{
    const GUEST_GROUP_LABEL = 'Guest';

    /**
     * @param string $email
     * @param integer|Mage_Core_Model_Website|Mage_Core_Model_Store $scope
     * @return array
     */
    public function getInfo($email, $scope)
    {
        /** @var Mage_Customer_Model_Resource_Customer_Collection $customerCollection */
        $customerCollection = Mage::getModel('customer/customer')->getCollection();
        $customerCollection->addFilter('email', array('eq' => $email));


        if ($scope instanceof Mage_Core_Model_Website) {
            $customerCollection->addFilter('website_id', array('eq' => $scope->getId()));
        }
        if ($scope instanceof Mage_Core_Model_Store) {
            $customerCollection->addFilter('website_id', array('eq' => $scope->getWebsiteId()));
        }
        $customerCollection->load();
        $customerInfo = array();

        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($customerCollection->getItems() as $item) {
            $customer = Mage::getModel('customer/customer')->load($item->getEntityId());
            /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
            $orderCollection = Mage::getModel('sales/order')->getCollection();
            $emailCondition = $orderCollection->getConnection()->prepareSqlCondition('customer_email',
                array('eq' => $customer->getEmail()));
            $idCondition = $orderCollection->getConnection()->prepareSqlCondition('customer_id',
                array('eq' => $customer->getId()));
            $orderCollection->getSelect()->where("({$emailCondition} OR {$idCondition})");

            try {
                $website = Mage::app()->getWebsite($customer->getWebsiteId());
            } catch (Exception $e) {
                continue;
            }

            if ($scope instanceof Mage_Core_Model_Store) {
                $orderCollection->addFilter('store_id', array('eq' => $scope->getId()), 'public');
            } else {
                $orderCollection->addFilter('store_id', array('in' => $website->getStoreIds()), 'public');
            }

            $orderCollection->addFilter('state', array('eq' => 'complete'));
            $orderCollection->getSelect()->reset(Zend_Db_Select::COLUMNS);
            $orderCollection->getSelect()->columns(
                array(
                    'total_sales' => 'SUM(main_table.base_grand_total)'
                )
            );
            
            $totalSales = $orderCollection->getFirstItem()->getTotalSales();
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
            $currency = Mage::app()->getLocale()->currency($baseCurrencyCode);

            /** @var Mage_Customer_Model_Address $addressModel */
            $addressModel = Mage::getModel('customer/address');
            $addressRendererType = $addressModel->getConfig()->getFormatByCode('default');
            $addressRenderer = Mage::getBlockSingleton('customer/address_renderer_default')->setType($addressRendererType);


            if ($customer->getDefaultBilling()) {
                $address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());

                //get default billing address
                $billingAddressFormatted = $addressRenderer->render($address);

                //get country name
                $country = Mage::getModel('directory/country')->load($address->getCountryId());
                $countryName = $country->getName();
            } else {
                $countryName = '';
                $billingAddressFormatted = null;
            }

            //get default shipping address
            if ($customer->getDefaultShipping()) {
                $shippingAddress = Mage::getModel('customer/address')->load($customer->getDefaultShipping());
                $shippingAddressFormatted = $addressRenderer->render($shippingAddress);
            } else {
                $shippingAddressFormatted = null;
            }

            //get customer group code
            try {
                $groupCode = Mage::getModel('customer/group')->load($customer->getGroupId())->getCode();
            } catch (\Exception $e) {
                $groupCode = '';
            }

            $customerInfo[] = array(
                'url' => Mage::helper('adminhtml')->getUrl('adminhtml/morfdev_freshdesk_index/redirect',
                    array(
                        'id' => $customer->getId(),
                        'type' => Morfdev_Freshdesk_Model_Source_RedirectType::CUSTOMER_TYPE)
                ),
                'customer_id' => $customer->getId(),
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'email' => $customer->getEmail(),
                'website_id' => $customer->getWebsiteId(),
                'group' => $groupCode,
                'country' => $countryName,
                'total_sales' => $currency->toCurrency($totalSales),
                'created_at' => Mage::helper('core')->formatDate($customer->getCreatedAt()),
                'billing_address' => $billingAddressFormatted,
                'shipping_address' => $shippingAddressFormatted,
            );
        }
        return $customerInfo;
    }

    /**
     * @param string $incrementId
     * @param integer|Mage_Core_Model_Website|Mage_Core_Model_Store $scope
     * @return array
     */
    public function getInfoFromOrder($incrementId, $scope)
    {

        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $orderCollection->addFilter('increment_id', array('eq' => $incrementId), 'public');

        if ($scope instanceof Mage_Core_Model_Store) {
            $orderCollection->addFilter('store_id', array('eq' => $scope->getId()), 'public');
        } elseif ($scope instanceof Mage_Core_Model_Website) {
            $orderCollection->addFilter('store_id', array('in' => $scope->getStoreIds()), 'public');
        }
        $orderCollection->load();

        $customerInfo = array();
        foreach ($orderCollection->getItems() as $order) {
            $customer = null;
            if ($customerId = $order->getCustomerId()) {
                $customer = Mage::getModel('customer/customer')->load($customerId);
            }
            if (null !== $customer && $customer->getEmail()) {
                return $this->getInfo($customer->getEmail(), $scope);
            }

            $billingAddress = $order->getBillingAddress();

            //get country name
            try {
                $address = Mage::getModel('customer/address')->load($billingAddress->getId());
                $country = Mage::getModel('directory/country')->load($address->getCountryId());
                $countryName = $country->getName();
            } catch (\Exception $e) {
                $countryName = '';
            }
            $customerInfo[] = array(
                'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                'email' => $billingAddress->getEmail(),
                'group' => self::GUEST_GROUP_LABEL,
                'country' => $countryName
            );
        }
        return $customerInfo;
    }
}
