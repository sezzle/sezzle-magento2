<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model\System\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Rejects anything that is not CSS margin shorthand before it is saved
 */
class CssMargin extends Value
{
    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $margin = preg_replace('/\s+/', ' ', trim((string)$this->getValue()));

        if ($margin !== '' && !preg_match(Config::SEZZLE_ADS_MARGIN_PATTERN, $margin)) {
            throw new LocalizedException(__(
                'Sezzle Ads Margin "%1" is not valid. Use one to four values such as 24px 0 or 16px 0 32px, '
                . 'with units px, em, rem, %, vh or vw, or 0 or auto.',
                $margin
            ));
        }

        $this->setValue($margin);

        return parent::beforeSave();
    }
}
