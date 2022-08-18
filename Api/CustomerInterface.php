<?php

namespace Sezzle\Sezzlepay\Api;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface CustomerInterface
{

    /**
     * Creates order by customer UUID
     *
     * @param int $cartId
     * @return void
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function createOrder(int $cartId): void;

}
