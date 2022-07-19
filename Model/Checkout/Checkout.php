<?php

namespace Sezzle\Sezzlepay\Model\Checkout;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Sezzle;

/**
 * Checkout
 */
class Checkout implements CheckoutInterface
{

    /**
     * @var array
     */
    private array $additionalInformation = [];

    /**
     * @var CheckoutValidator
     */
    private $checkoutValidator;

    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * @var V2Interface
     */
    private $v2;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;

    /**
     * @param Data $sezzleHelper
     * @param V2Interface $v2
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param QuoteResourceModel $quoteResourceModel
     * @param CheckoutValidator $checkoutValidator
     */
    public function __construct(
        Data               $sezzleHelper,
        V2Interface        $v2,
        CustomerSession    $customerSession,
        CheckoutSession    $checkoutSession,
        QuoteResourceModel $quoteResourceModel,
        CheckoutValidator  $checkoutValidator
    )
    {
        $this->sezzleHelper = $sezzleHelper;
        $this->v2 = $v2;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->checkoutValidator = $checkoutValidator;
    }


    /**
     * @inerhitDoc
     */
    public function getCheckoutURL(): ?string
    {
        try {
            $quote = $this->initQuote();
            $session = $this->createSession($quote);
            $this->setTokenizeDetailsInSession($session);

            $quote->getPayment()->setAdditionalInformation($this->additionalInformation);
            $this->quoteResourceModel->save($quote->collectTotals());
            $this->checkoutSession->replaceQuote($quote);

            return $session->getOrder()->getCheckoutURL();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function initQuote(): Quote
    {
        $quote = $this->checkoutSession->getQuote();
        $this->checkoutValidator->validate($quote);

        return $quote->reserveOrderId();
    }

    /**
     * @param CartInterface $quote
     * @return SessionInterface
     * @throws LocalizedException
     */
    private function createSession(CartInterface $quote): SessionInterface
    {
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $this->additionalInformation[Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID] = $referenceID;
        $session = $this->v2->createSession($referenceID, $quote->getStoreId());
        $order = $session->getOrder();
        if (!$order) {
            throw new LocalizedException(__('Session creation failed at Sezzle.'));
        }

        if ($order->getUuid()) {
            $this->additionalInformation = array_merge($this->additionalInformation, [
                Sezzle::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID => $order->getUuid()
            ]);
        }
        $links = [];
        foreach ($order->getLinks() as $link) {
            $rel = "sezzle_" . $link->getRel() . "_link";
            if ($link->getMethod() == 'GET' && strpos($rel, "self") !== false) {
                $rel = Sezzle::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK;
            }
            $links[$rel] = $link->getHref();
        }
        $this->additionalInformation = array_merge($this->additionalInformation, $links);

        return $session;
    }

    /**
     * @param SessionInterface $session
     * @return void
     */
    private function setTokenizeDetailsInSession(SessionInterface $session): void
    {
        if (!$tokenize = $session->getTokenize()) {
            return;
        }

        $this->customerSession->setCustomerSezzleToken($tokenize->getToken());
        $this->customerSession->setCustomerSezzleTokenExpiration($tokenize->getExpiration());
        $this->customerSession->setCustomerSezzleTokenStatus(true);

        foreach ($tokenize->getLinks() as $link) {
            if ($link->getRel() == Sezzle::ADDITIONAL_INFORMATION_KEY_GET_TOKEN_DETAILS_LINK) {
                $this->customerSession->setGetTokenDetailsLink($link->getHref());
            }
        }
    }

}
