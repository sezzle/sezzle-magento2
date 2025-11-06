<?php

declare(strict_types=1);

/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Api;

use Exception;

interface SettlementReportsRepositoryInterface
{

    /**
     * @param array|null $settlementReports
     * @return mixed|void
     * @throws Exception
     */
    public function saveMultiple(array $settlementReports = null);

}
