<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */


namespace Sezzle\Sezzlepay\Model\System\Config\Source\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Sezzle Payment Action Dropdown source
 */
class GatewayRegion
{
    private $supportedRegions = ['US/CA', 'EU'];
    /**
     * @var V2Interface
     */
    private $v2;
    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    public function __construct(
        SezzleConfigInterface $sezzleConfig,
        V2Interface $v2
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->v2 = $v2;
    }

    /**
     * Get Gateway Region
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getValue()
    {
        foreach ($this->supportedRegions as $region) {
            $gatewayUrl = $this->sezzleConfig->getGatewayUrl('v2', $region);
            $auth = $this->v2->authenticate("$gatewayUrl/authentication");
            if ($auth->getToken()) {
                return $region;
            }
        }
        return '';
    }
}
