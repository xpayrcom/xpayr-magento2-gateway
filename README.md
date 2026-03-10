# XPayr Gateway for Magento 2

Accept cryptocurrency payments on your Magento 2 store with XPayr.

## Requirements

- Magento 2.4.x
- PHP 8.1+

## Install

1. Copy module into your Magento root:

```bash
mkdir -p app/code/Xpayr/Gateway
cp -R app/code/Xpayr/Gateway/* <MAGENTO_ROOT>/app/code/Xpayr/Gateway/
```

2. Run Magento commands:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Configure

Navigate to `Stores → Configuration → Sales → Payment Methods → XPayr Gateway`

| Setting | Description |
|---------|-------------|
| **Enable** | Set to "Yes" to activate the payment method |
| **API Base URL** | Your XPayr API endpoint (e.g. `https://xpayr.com/api/v1`) |
| **Secret API Key** | Your XPayr secret key (`sk_test_...` or `sk_live_...`) |
| **Network** | Select a blockchain network (auto-loaded from your XPayr account) |
| **Currency** | Select a token (auto-loaded from your XPayr account) |
| **Webhook Secret** | Auto-populated when "Auto-sync webhook" is enabled |
| **Auto-sync webhook on save** | When enabled, saves will auto-register the webhook URL |

## Payment Flow

1. Customer selects "Pay with Crypto (XPayr)" at checkout
2. After placing the order, customer is redirected to XPayr payment page
3. Customer connects wallet and confirms the payment
4. On success, customer is redirected back to the Magento order success page
5. XPayr sends a webhook to update the order status automatically

## Webhook URL

Set this in your XPayr dashboard webhook configuration:

```
https://<your-magento-domain>/rest/V1/xpayr/ipn
```

The plugin verifies `X-XPayr-Signature` if webhook secret is set.

## Order Statuses

| Event | Default Status |
|-------|---------------|
| Order placed | `pending_payment` |
| Payment completed (webhook) | `processing` |
| Payment failed/expired (webhook) | `canceled` |

## Support

For issues or questions, contact support@xpayr.com.
