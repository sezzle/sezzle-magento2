<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */


namespace Sezzle\Sezzlepay\Model\System\Config\Source\Payment;

use Magento\Store\Model\ScopeInterface;
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

    /**
     * GatewayRegion constructor.
     * @param SezzleConfigInterface $sezzleConfig
     * @param V2Interface $v2
     */
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
     * @param string $scope
     * @return string
     */
    public function getValue($scope = ScopeInterface::SCOPE_STORE)
    {
        foreach ($this->supportedRegions as $region) {
            $auth = $this->v2->authenticate($region, $scope);
            if ($auth->getToken()) {
                return $region;
            }
        }
        return '';
    }
}
