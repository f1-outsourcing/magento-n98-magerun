<?php

namespace N98\Magento\Command\Order;

use Mage_Sales_Model_Order;
use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractOrderCommand
 *
 * @package N98\Magento\Command\Order
 */
abstract class AbstractOrderCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_r_Model_Customer
     */
    protected function getOrderModel()
    {
        return $this->_getModel('sales/order', 'Mage_Sales_Model_Order');
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getOrderCollection()
    {
        return $this->_getResourceModel(
            'sales/order_collection',
            'Mage_Sales_Model_Resource_Order_Collection'
        );
    }

}
