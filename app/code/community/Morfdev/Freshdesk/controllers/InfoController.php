<?php

class Morfdev_Freshdesk_InfoController extends Mage_Core_Controller_Front_Action
{

    /** @var null  */
    private $postData = null;

    protected $customerNotFound = true;

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function dataAction()
    {
        $defaultData = array(
            'order_list' => array(),
            'customer_list' => array()
        );
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            //CORS Preflight
            return $this->getResponse()->setBody(
                Zend_Json::encode($defaultData)
            );
        }
        $scope = $this->authorise();
        if (null === $scope) {
            try {
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_UNAUTHORIZED);
            } catch (Exception $e) {
                //Oops something went wrong
            }
            return $this->getResponse()->setBody('');
        }

        try {
            $customerInfo = $this->getCustomerInfo($scope);
            $recentOrderInfo = $this->getRecentOrderInfo($scope);
            $data = array_merge($customerInfo, $recentOrderInfo);

            //customer data not found, but found some orders
            $customerInfo = null;
            if ($this->customerNotFound && isset($data['order_list']) && count($data['order_list'])) {
                $customerInfo = Mage::getModel('md_freshdesk/orderRecent')->getInfoFromOrder($data['order_list'][0]['increment_id'], $scope);
            }
            if ($customerInfo) {
                $data['customer_list'] = $customerInfo;
            }
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            return $this->getResponse()->setBody(Zend_Json::encode(array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            )));
        }


        if (!$data) {
            try {
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            } catch (Exception $e) {
                //Oops something went wrong
            }
            return $this->getResponse()->setBody(Zend_Json::encode($defaultData));
        }
        return $this->getResponse()->setBody(
            Zend_Json::encode($data)
        );
    }

    /**
     * @return mixed|null
     */
    private function getPostData()
    {
        if (null !== $this->postData) {
            return $this->postData;
        }
        $this->postData = file_get_contents('php://input');
        if (false === $this->postData) {
            return $this->postData = null;
        }
        $this->postData = json_decode($this->postData, true);
        return $this->postData;
    }

    /**
     * Check authorization with Freshdesk account
     * @return bool
     */
    private function authorise()
    {
        /** @var Morfdev_Freshdesk_Model_Authorization $authModel */
        $authModel = Mage::getModel('md_freshdesk/authorization');
        return $authModel->isAuth($this->getPostData());
    }

    /**
     * @param integer|Mage_Core_Model_Website|Mage_Core_Model_Store $scope
     * @return array
     */
    private function getCustomerInfo($scope)
    {
        $result = array('customer_list' => array());
        $postData = $this->getPostData();
        if (null === $postData) {
            return $result;
        }
        /** @var Morfdev_Freshdesk_Model_Customer $customerManager */
        $customerManager = Mage::getModel('md_freshdesk/customer');

        $customerInfo = null;
        if (isset($postData['order_id'])) {
            $customerInfo = $customerManager->getInfoFromOrder($postData['order_id'], $scope);
        }
        if (!$customerInfo && isset($postData['email'])) {
            $customerInfo = $customerManager->getInfo($postData['email'], $scope);
        }

        if ($customerInfo) {
            $this->customerNotFound = false;
            $result = array('customer_list' => $customerInfo);
        }
        return $result;
    }

    /**
     * @param integer|Mage_Core_Model_Website|Mage_Core_Model_Store $scope
     * @return array
     */
    private function getRecentOrderInfo($scope)
    {
        $result = array('order_list' => array());
        $postData = $this->getPostData();
        if (null === $postData) {
            return $result;
        }

        /** @var Morfdev_Freshdesk_Model_OrderRecent $orderRecentManager */
        $orderRecentManager = Mage::getModel('md_freshdesk/orderRecent');
        $orderItemInfo = null;
        if (isset($postData['order_id'])) {
            $orderItemInfo = $orderRecentManager->getInfoFromOrder($postData['order_id'], $scope);
        }
        if (!$orderItemInfo && isset($postData['email'])) {
            $orderItemInfo = $orderRecentManager->getInfo($postData['email'], $scope);
        }
        if ($orderItemInfo) {
            $result = array('order_list' => $orderItemInfo);
        }
        return $result;
    }
}
