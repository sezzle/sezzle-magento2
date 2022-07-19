<?php

namespace Sezzle\Sezzlepay\Gateway\Config;

class Constants
{
    // Configs
    const KEY_ACTIVE = 'active';
    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';
    const KEY_PAYMENT_MODE = 'payment_mode';
    const KEY_MERCHANT_ID = "merchant_id";
    const KEY_PAYMENT_ACTION = "payment_action";
    const KEY_GATEWAY_REGION = "gateway_region";
    const KEY_MIN_CHECKOUT_AMOUNT = "min_checkout_amount";
    const KEY_TOKENIZE = 'tokenize';

    const KEY_WIDGET_PDP = "widget_pdp";
    const KEY_WIDGET_CART = "widget_cart";
    const KEY_WIDGET_TICKET_CREATED_AT = 'widget_ticket_created_at';
    const KEY_WIDGET_INSTALLMENT = 'widget_installment';
    const KEY_WIDGET_INSTALLMENT_PRICE = 'widget_installment_price_path';

    const KEY_INCONTEXT_ACTIVE = 'active_in_context';
    const KEY_INCONTEXT_MODE = 'in_context_mode';

    const KEY_LOG_TRACKER = 'log_tracker';
    const KEY_CRON_LOGS = 'send_logs_via_cron';

    const KEY_SETTLEMENT_REPORTS = 'settlement_reports';
    const KEY_SETTLEMENT_REPORTS_RANGE = 'settlement_reports_range';

    const PAYMENT_MODE_SANDBOX = "sandbox";
    const PAYMENT_MODE_LIVE = "live";

    const GATEWAY_URL = "https://%sgateway.sezzle.com/";
    // Configs


    // Gateway
    const KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';
    const KEY_EXTENDED_ORDER_UUID = 'sezzle_extended_order_uuid';

    const KEY_AUTH_AMOUNT = 'sezzle_auth_amount';
    const KEY_CAPTURE_AMOUNT = 'sezzle_capture_amount';
    const KEY_REFUND_AMOUNT = 'sezzle_refund_amount';
    const KEY_RELEASE_AMOUNT = 'sezzle_release_amount';

    const KEY_GET_ORDER_LINK = 'sezzle_get_order_link';

    const TOKEN_CACHE_PREFIX = 'SEZZLE_AUTH_TOKEN';

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_PATCH = "PATCH";

    const ROUTE_PARAMS = ['order_uuid', 'customer_uuid'];

    const GROUP_CAPTURE_AMOUNT = 'capture_amount';
    const GROUP_ORDER_AMOUNT = 'order_amount';

    const INTENT = 'intent';
    const REFERENCE_ID = 'reference_id';

    const AMOUNT_IN_CENTS = 'amount_in_cents';
    const CURRENCY = 'currency';

    const KEY_ROUTE_PARAMS = 'route_params';

    const ORDER_UUID = 'order_uuid';
    const CUSTOMER_UUID = "customer_uuid";

    const __STORE_ID = '__storeId';

    const KEY_CUSTOMER_UUID = "sezzle_customer_uuid";
    const KEY_REFERENCE_ID = 'sezzle_reference_id';

    const AMOUNT = 'amount';

    const KEY_AUTH_EXPIRY = 'sezzle_auth_expiry';

}
