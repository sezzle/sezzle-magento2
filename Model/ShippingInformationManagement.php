<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory;
use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Sezzle\Sezzlepay\Api\ShippingInformationManagementInterface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * ShippingInformationManagement
 */
class ShippingInformationManagement implements ShippingInformationManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var ShipmentEstimationInterface
     */
    private $shipmentEstimation;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var TotalsInformationManagementInterface
     */
    private $totalsInformationManagement;

    /**
     * @var TotalsInformationInterfaceFactory
     */
    private $totalsInformationFactory;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param Json $jsonSerializer
     * @param Config $config
     * @param Curl $curl
     * @param Data $helper
     * @param RegionFactory $regionFactory
     * @param ShipmentEstimationInterface $shipmentEstimation
     * @param AddressInterfaceFactory $addressFactory
     * @param TotalsInformationManagementInterface $totalsInformationManagement
     * @param TotalsInformationInterfaceFactory $totalsInformationFactory
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        Json $jsonSerializer,
        Config $config,
        Curl $curl,
        Data $helper,
        RegionFactory $regionFactory,
        ShipmentEstimationInterface $shipmentEstimation,
        AddressInterfaceFactory $addressFactory,
        TotalsInformationManagementInterface $totalsInformationManagement,
        TotalsInformationInterfaceFactory $totalsInformationFactory
    ) {
        $this->cartRepository = $cartRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->config = $config;
        $this->curl = $curl;
        $this->helper = $helper;
        $this->regionFactory = $regionFactory;
        $this->shipmentEstimation = $shipmentEstimation;
        $this->addressFactory = $addressFactory;
        $this->totalsInformationManagement = $totalsInformationManagement;
        $this->totalsInformationFactory = $totalsInformationFactory;
    }

    /**
     * @inheritDoc
     */
    public function updateOrderWithAddress(
        int $cartId,
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
        try {
            $quote = $this->cartRepository->getActive($cartId);

            $payment = $quote->getPayment();
            $orderUuid = $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);
            if (!$orderUuid) {
                throw new LocalizedException(__('Order UUID not found in payment'));
            }
            $storeId = $quote->getStoreId();

            // Load region to get proper region ID
            $region = $this->regionFactory->create();
            $region->loadByCode($state, $countryCode);

            // Create address for estimation (like standard checkout does)
            $address = $this->addressFactory->create();
            $address->setCountryId($countryCode)
                ->setPostcode($postalCode)
                ->setRegion($region->getName() ?: $state);

            if ($region->getId()) {
                $address->setRegionId($region->getId())
                    ->setRegionCode($region->getCode());
            }

            // Use the standard Magento estimation service (this triggers proper tax calculation)
            $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($cartId, $address);

            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Shipping methods estimated',
                'methods_count' => count($shippingMethods),
                'address' => [
                    'country_id' => $countryCode,
                    'region' => $region->getName() ?: $state,
                    'region_id' => $region->getId(),
                    'postcode' => $postalCode
                ]
            ]);

            // Now set the full address on the quote
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCountryId($countryCode)
                ->setCity($city)
                ->setPostcode($postalCode)
                ->setStreet([$street, $street2])
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setTelephone($phone)
                ->setEmail($quote->getCustomerEmail())
                ->setCollectShippingRates(true);

            if ($region->getId()) {
                $shippingAddress->setRegionId($region->getId())
                    ->setRegion($region->getName())
                    ->setRegionCode($region->getCode());
            } else {
                $shippingAddress->setRegion($state);
            }

            // Get currency code
            $currencyCode = $quote->getQuoteCurrencyCode();

            // Build shipping options array from estimated methods
            $shippingOptions = [];

            foreach ($shippingMethods as $method) {
                $shippingAmount = $method->getAmount();
                $carrierCode = $method->getCarrierCode();
                $methodCode = $method->getMethodCode();

                // Set the shipping method on the quote's shipping address
                $shippingAddress->setShippingMethod("{$carrierCode}_{$methodCode}");
                $this->cartRepository->save($quote);

                // Create address information for totals calculation (mimics standard checkout)
                $addressInformation = $this->totalsInformationFactory->create();
                $addressInformation->setAddress($address);
                $addressInformation->setShippingCarrierCode($carrierCode);
                $addressInformation->setShippingMethodCode($methodCode);

                // Calculate totals using the standard service (this properly calculates tax)
                $totals = $this->totalsInformationManagement->calculate($cartId, $addressInformation);

                $taxAmount = $totals->getTaxAmount();
                $grandTotal = $totals->getGrandTotal();
                $subtotal = $totals->getSubtotal();
                $discountAmount = $totals->getDiscountAmount();

                $this->helper->logSezzleActions([
                    'method' => "{$carrierCode}_{$methodCode}",
                    'carrier_title' => $method->getCarrierTitle(),
                    'method_title' => $method->getMethodTitle(),
                    'shipping_method' => $shippingAddress->getShippingMethod(),
                    'subtotal' => $subtotal,
                    'shipping_amount' => $shippingAmount,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'grand_total' => $grandTotal,
                    'tax_segments' => $totals->getTotalSegments()
                ]);

                // Convert to cents
                $shippingAmountInCents = (int)round($shippingAmount * 100);
                $taxAmountInCents = (int)round($taxAmount * 100);
                $finalOrderAmountInCents = (int)round($grandTotal * 100);

                $shippingOptions[] = [
                    'id' => "{$carrierCode}_{$methodCode}",
                    'name' => $method->getMethodTitle(),
                    'description' => $method->getCarrierTitle(),
                    'shipping_amount_in_cents' => $shippingAmountInCents,
                    'tax_amount_in_cents' => $taxAmountInCents,
                    'final_order_amount_in_cents' => $finalOrderAmountInCents
                ];
            }

            // Unset shipping method on the quote's shipping address
            $shippingAddress->unsShippingMethod();
            $this->cartRepository->save($quote);

            $this->helper->logSezzleActions([
                'quote_id' => $cartId,
                'order_uuid' => $orderUuid,
                'log_origin' => __METHOD__,
                'shipping_options' => $shippingOptions
            ]);

            // Authenticate with Sezzle
            $authUrl = $this->config->getGatewayURL($storeId) . '/authentication';
            $authPayload = [
                'public_key' => $this->config->getPublicKey($storeId),
                'private_key' => $this->config->getPrivateKey($storeId)
            ];

            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $this->curl->post($authUrl, $this->jsonSerializer->serialize($authPayload));

            $authResponse = $this->curl->getBody();
            $authData = $this->jsonSerializer->unserialize($authResponse);

            if (!isset($authData['token'])) {
                throw new LocalizedException(__('Failed to authenticate with Sezzle'));
            }

            $token = $authData['token'];

            // Update Sezzle order
            $updateUrl = $this->config->getGatewayURL($storeId) . '/order/' . $orderUuid . '/checkout';
            $updatePayload = [
                'currency_code' => $currencyCode,
                'address_uuid' => $addressUuid,
                'shipping_options' => $shippingOptions
            ];

            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
            $this->curl->setOption(CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            $this->curl->post($updateUrl, $this->jsonSerializer->serialize($updatePayload));

            $httpCode = $this->curl->getStatus();
            $updateStatus = ($httpCode >= 200 && $httpCode < 300);

            $this->helper->logSezzleActions([
                'quote_id' => $cartId,
                'order_uuid' => $orderUuid,
                'log_origin' => __METHOD__,
                'update_status' => $updateStatus,
                'http_code' => $httpCode
            ]);

            return $this->jsonSerializer->serialize(['ok' => $updateStatus]);
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'quote_id' => $cartId,
                'order_uuid' => $orderUuid,
                'log_origin' => __METHOD__,
                'error' => $e->getMessage()
            ]);
            throw new LocalizedException(__('Unable to update order: %1', $e->getMessage()));
        }
    }
}
