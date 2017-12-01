<?php

namespace Sezzle\Sezzlepay\Model\Cron;

class MerchantData
{
    protected $orderFactory;
    protected $jsonHelper;
    protected $date;
    protected $timezone;
    protected $logger;
	protected $scopeConfig;
	protected $urlBuilder;
    protected $sezzleApi;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sezzle\Sezzlepay\Model\Api $sezzleApi
    ) {
        $this->orderFactory = $orderFactory;

        $this->jsonHelper = $jsonHelper;
        $this->date = $date;
        $this->timezone = $timezone;

        $this->logger = $logger;
		$this->scopeConfig = $scopeConfig;
		$this->urlBuilder = $urlBuilder;
		$this->sezzleApi = $sezzleApi;
    }

    public function execute()
    {
        $this->logger->info("Executing cron");
    }
}