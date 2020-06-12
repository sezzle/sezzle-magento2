<?php


namespace Sezzle\Payment\Model\ResourceModel;

class Quote extends \Magento\Quote\Model\ResourceModel\Quote
{
    /**
     * Serializable field: sezzle_information
     *
     * @var array
     */
    protected $_serializableFields = ['sezzle_information' => [null, []]];
}
