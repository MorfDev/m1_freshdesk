<?php

/**
 * Class OrderRecent
 */
class Morfdev_Freshdesk_Model_OrderRecent extends Mage_Core_Model_Abstract
{
    /**
     * @param string $incrementId
     * @param integer|Mage_Core_Model_Website|Mage_Core_Model_Store $scope
     * @return array
     */
    public function getInfoFromOrder($incrementId, $scope)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $orderCollection->addFilter('increment_id', array('eq' => $incrementId));

        if ($scope instanceof Mage_Core_Model_Website) {
            $orderCollection->addFilter('store_id', array('in' => $scope->getStoreIds()), 'public');
        }
        if ($scope instanceof Mage_Core_Model_Store) {
            $orderCollection->addFilter('store_id', array('eq' => $scope->getId()));
        }
        $orderCollection->load();
        $orderInfo = array();
        /** @var Mage_Sales_Model_Order $order */
        foreach ($orderCollection->getItems() as $order) {
            $orderInfo = $this->getInfo($order->getCustomerEmail(), $scope);
            break;
        }
        return $orderInfo;
    }

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
        $customerIds = $customerCollection->getAllIds();

        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $emailCondition = $orderCollection->getConnection()->prepareSqlCondition('customer_email',
            array('eq' => $email));
        $idCondition = $orderCollection->getConnection()->prepareSqlCondition('customer_id',
            array('in' => $customerIds));
        $orderCollection->getSelect()->where("({$emailCondition} OR {$idCondition})");

        if ($scope instanceof Mage_Core_Model_Website) {
            $orderCollection->addFilter('store_id', array('in' => $scope->getStoreIds()), 'public');
        }
        if ($scope instanceof Mage_Core_Model_Store) {
            $orderCollection->addFilter('store_id', array('eq' => $scope->getId()));
        }
        $orderCollection->addOrder('created_at');
        $orderCollection->load();

        $orderInfo = array();
        /** @var Mage_Sales_Model_Order $order */
        foreach ($orderCollection->getItems() as $order) {
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress) {
                $shippingAddress = $billingAddress;
            }

            /** @var Mage_Sales_Model_Resource_Order_Item_Collection $orderItemsList */
            $orderItemsList = Mage::getModel('sales/order_item')->getCollection();
            $nullCondition = $orderItemsList->getConnection()->prepareSqlCondition('parent_item_id',
                array('null' => true));
            $orderItemsList->getSelect()->where($nullCondition);
            $orderItemsList
                ->addFilter('order_id', array('eq' => $order->getEntityId()));
            $orderItemsList->load();

            $currency = Mage::app()->getLocale()->currency($order->getBaseCurrencyCode());
            $orderItemInfo = array();
            /** @var Mage_Sales_Model_Order_Item $orderItem */
            foreach ($orderItemsList->getItems() as $orderItem) {
                $renderer = Morfdev_Freshdesk_Model_Source_RendererType::getProductRendererByType($orderItem->getProductType());
                $renderer->setItem($orderItem)->setArea('frontend');

                $orderItemInfo[] = array(
                    'url' => Mage::helper('adminhtml')->getUrl('adminhtml/morfdev_freshdesk_index/redirect',
                        array(
                            'id' => $orderItem->getProductId(),
                            'type' => Morfdev_Freshdesk_Model_Source_RedirectType::PRODUCT_TYPE)
                    ),
                    'product_id' => $orderItem->getProductId(),
                    'name' => $orderItem->getName(),
                    'product_html' => $renderer->toHtml(),
                    'sku' => $orderItem->getSku(),
                    'price' => $currency->toCurrency($orderItem->getBasePrice()),
                    'ordered_qty' => (int)$orderItem->getQtyOrdered(),
                    'invoiced_qty' => (int)$orderItem->getQtyInvoiced(),
                    'shipped_qty' => (int)$orderItem->getQtyShipped(),
                    'refunded_qty' => (int)$orderItem->getQtyRefunded(),
                    'row_total' => $currency->toCurrency($orderItem->getBaseRowTotal())
                );
            }
            /** @var Mage_Customer_Model_Address $addressModel */
            $addressModel = Mage::getModel('customer/address');
            $addressRendererType = $addressModel->getConfig()->getFormatByCode('default');
            $addressRenderer = Mage::getBlockSingleton('customer/address_renderer_default')->setType($addressRendererType);

            try {
                $billingAddressFormatted = $addressRenderer->render($order->getBillingAddress());
            } catch (\Exception $e) {
                $billingAddressFormatted = null;
            }

            //get default shipping address
            if ($order->getShippingAddress()) {
                $shippingAddressFormatted = $addressRenderer->render($order->getShippingAddress());
            } else {
                $shippingAddressFormatted = null;
            }

            //store name
            try {
                $storeName = Mage::app()->getStore($order->getStoreId())->getName();
            } catch (Exception $e) {
                $storeName = null;
            }

            $orderInfo[] = array(
                'url' => Mage::helper('adminhtml')->getUrl('adminhtml/morfdev_freshdesk_index/redirect',
                    array(
                        'id' => $order->getEntityId(),
                        'type' => Morfdev_Freshdesk_Model_Source_RedirectType::ORDER_TYPE
                    )
                ),
                'order_id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
                'store' => $storeName,
                'created_at' => Mage::helper('core')->formatDate($order->getCreatedAt()),
                'billing_address' => $billingAddressFormatted,
                'shipping_address' => $shippingAddressFormatted,
                'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
                'shipping_method' => $order->getShippingDescription(),
                'shipping_tracking' => $this->prepareShippingTrackingForOrder($order),
                'status' => $order->getStatusLabel(),
                'state' => $order->getState(),
                'totals' => array(
                    'subtotal' => $currency->toCurrency($order->getBaseSubtotal()),
                    'shipping' => $currency->toCurrency($order->getBaseShippingAmount()),
                    'discount' => $currency->toCurrency($order->getBaseDiscountAmount()),
                    'tax' => $currency->toCurrency($order->getBaseTaxAmount()),
                    'grand_total' => $currency->toCurrency($order->getBaseGrandTotal())
                ),
                'items' => $orderItemInfo
            );
        }
        return $orderInfo;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    private function prepareShippingTrackingForOrder($order)
    {
        $shippingCollection = $order->getShipmentsCollection();
        $result = array();
        foreach ($shippingCollection as $shipmentItem) {
            /** @var Mage_Sales_Model_Order_Shipment $shipment */
            $shipment = Mage::getModel('sales/order_shipment')->load($shipmentItem->getId());
            if (!$shipment->getId()) {
                continue;
            }
            $trackList = $shipment->getAllTracks();
            foreach ($trackList as $track) {
                $carrier = $this->getCarrierName($track->getCarrierCode(), $order->getStoreId());
                $result[] = array(
                    'carrier' => $carrier,
                    'number' => $track->getTrackNumber(),
                    'title' => $track->getTitle()
                );
            }
        }
        return $result;
    }

    /**
     * @param string $carrierCode
     * @param null|integer|Mage_Core_Model_Store $store
     * @return mixed
     */
    private function getCarrierName($carrierCode, $store = null)
    {
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title', $store)) {
            return $name;
        }
        return $carrierCode;
    }
}
