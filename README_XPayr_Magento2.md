# XPayr Crypto Payment Gateway for Magento 2
## Installation Guide

1. Download the extension ZIP file.
2. Extract the contents into `app/code/Xpayr/Gateway` in your Magento 2 root directory.
3. Run the following commands via terminal from your Magento 2 root:
   ```bash
   php bin/magento module:enable Xpayr_Gateway
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:clean
   ```

## Configuration Guide

1. Log into your Magento 2 Admin panel.
2. Navigate to **Stores > Configuration > Sales > Payment Methods**.
3. Locate **XPayr Crypto Payment Gateway**.
4. Set **Enabled** to Yes.
5. Enter your **Secret Key** and **API Base URL** obtained from the XPayr Merchant Dashboard at https://xpayr.com.
6. Select the **Network** (e.g., BSC, Ethereum, Polygon, Avalanche, Arbitrum, Base, Solana) and **Currency** (e.g., USDT, USDC) you wish to accept. Available networks are fetched dynamically from the XPayr API based on your merchant account.
7. Click **Save Config**.

## Automated Webhook Integration
XPayr uses automated webhooks to sync payment statuses (Completed, Failed). No manual IPN configuration is required if the Webhook Auto Sync setting is enabled.

## Support
For issues or questions, visit https://xpayr.com or contact support via the XPayr Merchant Dashboard.
