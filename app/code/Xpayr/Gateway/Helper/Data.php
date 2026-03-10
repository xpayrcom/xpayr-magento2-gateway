<?php
namespace Xpayr\Gateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;

class Data extends AbstractHelper
{
    private const XML_PATH = 'payment/xpayr_gateway/';
    private WriterInterface $configWriter;
    private TypeListInterface $cacheTypeList;
    private StoreManagerInterface $storeManager;
    private EncryptorInterface $encryptor;
    private Curl $curlClient;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        Curl $curlClient
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->curlClient = $curlClient;
    }

    public function getConfig(string $code, $storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isDebugEnabled($storeId = null): bool
    {
        return (bool) $this->getConfig('debug', $storeId);
    }

    public function getApiBaseUrl($storeId = null): string
    {
        return rtrim((string) $this->getConfig('api_base_url', $storeId), '/');
    }

    public function getSecretKey($storeId = null): string
    {
        $raw = trim((string) $this->getConfig('secret_key', $storeId));
        // Encrypted values start with 0:X: prefix
        if (str_starts_with($raw, '0:')) {
            return $this->encryptor->decrypt($raw);
        }
        return $raw;
    }

    public function fetchNetworkCatalog($storeId = null): array
    {
        $apiBase = $this->getApiBaseUrl($storeId);
        $secret = $this->getSecretKey($storeId);
        if ($apiBase === '' || $secret === '') {
            return [];
        }

        try {
            $this->curlClient->setHeaders([
                'Authorization' => 'Bearer ' . $secret,
                'Accept' => 'application/json'
            ]);
            $this->curlClient->setTimeout(20);
            $this->curlClient->get($apiBase . '/me/networks');

            $code = $this->curlClient->getStatus();
            $body = $this->curlClient->getBody();

            if ($code < 200 || $code >= 300) {
                return [];
            }

            $json = json_decode((string) $body, true);
            if (!is_array($json) || !isset($json['data']) || !is_array($json['data'])) {
                return [];
            }

            return $json['data'];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function syncWebhookSecret($storeId = null): bool
    {
        $enabled = (bool) $this->getConfig('webhook_auto_sync', $storeId);
        if (!$enabled) {
            return false;
        }

        $apiBase = $this->getApiBaseUrl($storeId);
        $secret = $this->getSecretKey($storeId);
        if ($apiBase === '' || $secret === '') {
            return false;
        }

        try {
            $baseUrl = rtrim($this->storeManager->getStore($storeId)->getBaseUrl(), '/');
        } catch (\Throwable $e) {
            $baseUrl = rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
        }
        $callback = $baseUrl . '/rest/V1/xpayr/ipn';
        $payload = json_encode(['url' => $callback]);

        try {
            $this->curlClient->setHeaders([
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);
            $this->curlClient->setTimeout(20);
            $this->curlClient->post($apiBase . '/webhooks', $payload);

            $code = $this->curlClient->getStatus();
            $body = $this->curlClient->getBody();

            if ($code < 200 || $code >= 300) {
                return false;
            }

            $json = json_decode((string) $body, true);
            $newSecret = is_array($json) ? (string) ($json['secret'] ?? '') : '';
            if ($newSecret === '') {
                return false;
            }

            $this->configWriter->save(
                self::XML_PATH . 'webhook_secret',
                $newSecret,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->cacheTypeList->cleanType('config');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build a full store URL for the given path
     *
     * @param string $path
     * @return string
     */
    public function getStoreUrl(string $path): string
    {
        try {
            $baseUrl = rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
        } catch (\Throwable $e) {
            $baseUrl = '';
        }
        return $baseUrl . '/' . ltrim($path, '/');
    }
}
