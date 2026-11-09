<div align="center">
  <a href="https://xpayr.com">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://xpayr.com/public/images/xpayr-logo-white.png" />
      <source media="(prefers-color-scheme: light)" srcset="https://xpayr.com/public/images/xpayr-logo-black.png" />
      <img src="https://xpayr.com/public/images/xpayr-logo-white.png" alt="XPayr Official Logo" width="220" />
    </picture>
  </a>
  <p>
    <a href="https://xpayr.com"><img src="https://xpayr.com/partner-badge.php?type=powered_by&theme=dark" alt="Powered by XPayr" height="28" /></a>
    <a href="https://mcp.xpayr.com"><img src="https://img.shields.io/badge/MCP_Portal-mcp.xpayr.com-029bfd?style=for-the-badge" alt="XPayr MCP Portal" height="28" /></a>
    <a href="https://docs.xpayr.com"><img src="https://img.shields.io/badge/Docs-docs.xpayr.com-10b981?style=for-the-badge" alt="XPayr Docs" height="28" /></a>
    <a href="https://www.npmjs.com/package/@xpayr/mcp-server"><img src="https://img.shields.io/npm/v/@xpayr/mcp-server?style=for-the-badge&color=cb3837&label=MCP%20NPM" alt="NPM @xpayr/mcp-server" height="28" /></a>
  </p>
  <p>
    <a href="https://xpayr.com"><b>Official Website</b></a> •
    <a href="https://mcp.xpayr.com"><b>AI &amp; MCP Portal (21 Tools)</b></a> •
    <a href="https://docs.xpayr.com"><b>Developer Docs</b></a> •
    <a href="https://xpayr.com/developers"><b>Developer Hub</b></a> •
    <a href="https://xpayr.com/tools/crypto-payment-fee-calculator"><b>0.5% Fee Calculator</b></a>
  </p>
</div>

---

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
