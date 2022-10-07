<?php

class Morfdev_Freshdesk_Model_Source_RendererType
{
    /**
     * @param $productType
     * @return Mage_Bundle_Block_Sales_Order_Items_Renderer|Mage_Downloadable_Block_Sales_Order_Item_Renderer_Downloadable|Mage_Sales_Block_Order_Item_Renderer_Default
     */
    static public function getProductRendererByType($productType)
    {
        switch ($productType) {
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                /** @var Mage_Bundle_Block_Sales_Order_Items_Renderer $renderer */
                $renderer = Mage::app()->getLayout()->createBlock(Mage_Bundle_Block_Sales_Order_Items_Renderer::class);
                $renderer->setTemplate('md_freshdesk/renderer/bundle.phtml');
                break;
            case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE:
                /** @var Mage_Downloadable_Block_Sales_Order_Item_Renderer_Downloadable $renderer */
                $renderer = Mage::app()->getLayout()->createBlock(Mage_Downloadable_Block_Sales_Order_Item_Renderer_Downloadable::class);
                $renderer->setTemplate('md_freshdesk/renderer/downloadable.phtml');
                break;
            default:
                /** @var Mage_Sales_Block_Order_Item_Renderer_Default $renderer */
                $renderer = Mage::app()->getLayout()->createBlock(Mage_Sales_Block_Order_Item_Renderer_Default::class);
                $renderer->setTemplate('md_freshdesk/renderer/default.phtml');
        }
        return $renderer;
    }
}