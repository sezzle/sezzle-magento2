<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Quote\Model\QuoteIdMaskFactory;
use Sezzle\Sezzlepay\Api\GuestShippingInformationManagementInterface;
use Sezzle\Sezzlepay\Api\ShippingInformationManagementInterface;

/**
 * GuestShippingInformationManagement
 */
class GuestShippingInformationManagement implements GuestShippingInformationManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var ShippingInformationManagementInterface
     */
    private $shippingInformationManagement;

    /**
     * GuestShippingInformationManagement constructor.
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ShippingInformationManagementInterface $shippingInformationManagement
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ShippingInformationManagementInterface $shippingInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->shippingInformationManagement = $shippingInformationManagement;
    }

    /**
     * @inheritDoc
     */
    public function updateOrderWithAddress(
        string $cartId,
        string $countryCode,
        string $state,
        string $city,
        string $postalCode,
        string $street,
        string $street2,
        string $addressUuid,
        string $firstName,
        string $lastName,
        string $phone,
    ): string {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->shippingInformationManagement->updateOrderWithAddress(
            (int)$quoteIdMask->getQuoteId(),
            $countryCode,
            $state,
            $city,
            $postalCode,
            $street,
            $street2,
            $addressUuid,
            $firstName,
            $lastName,
            $phone
        );
    }
}
