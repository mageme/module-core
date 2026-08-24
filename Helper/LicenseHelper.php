<?php
/**
 * MageMe
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageMe.com license that is
 * available through the world-wide-web at this URL:
 * https://mageme.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to a newer
 * version in the future.
 *
 * Copyright (c) MageMe (https://mageme.com)
 **/

declare(strict_types=1);

namespace MageMe\Core\Helper;

use Exception;
use MageMe\Core\Api\LicenseHelperInterface;
use MageMe\Core\Model\License\LicenseStatus;
use MageMe\Core\Model\License\StatusCache;
use MageMe\Core\Model\License\StatusMapper;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * @deprecated 2.0.0 Use {@see \MageMe\Core\Helper\GenericLicenseHelper} together with
 * {@see \MageMe\Core\Model\ModuleEcosystem\LicenseMetaResolver}. The license trigger is
 * now driven by local `etc/license.xml` declarations (Decision #19, May 2026) with a
 * catalog fallback to info.mageme.com/modules.json, not by per-module {@see LicenseHelper}
 * subclasses. This class is retained only so that legacy sibling subclasses
 * (e.g. older MageMe\WebForms\Helper\LicenseHelper) keep loading on installs that still
 * ship them; no MageMe_Core code path calls into it. Scheduled for removal in
 * MageMe_Core 3.0.
 */
class LicenseHelper implements LicenseHelperInterface
{
    /**
     * License server URL
     */
    public const URL = 'https://license.mageme.com';

    /**
     * Activate action identifier
     */
    public const ACTION_ACTIVATE = 'activate';

    /**
     * Deactivate action identifier
     */
    public const ACTION_DEACTIVATE = 'deactivate';

    /**
     * Verify action identifier
     */
    public const ACTION_VERIFY = 'verify';

    /**
     * License config path
     */
    public const PATH_LICENSE = '/license';

    /**
     * Active status config path
     */
    public const PATH_ACTIVE = self::PATH_LICENSE . '/active';

    /**
     * Serial config path
     */
    public const PATH_SERIAL = self::PATH_LICENSE . '/serial';

    /**
     * Access token config path
     */
    public const PATH_ACCESS_TOKEN = self::PATH_LICENSE . '/access_token';

    /**
     * Verified time config path
     */
    public const PATH_VERIFIED_TIME = self::PATH_LICENSE . '/verified_time';

    /**
     * Development mode config path
     */
    public const PATH_DEVELOPMENT = self::PATH_LICENSE . '/development';

    /**
     * Verify attempt config path
     */
    public const PATH_VERIFY_ATTEMPT = self::PATH_LICENSE . '/verify_attempt';

    public const PATH_VALID_UNTIL = self::PATH_LICENSE . '/valid_until';

    protected ModuleListInterface $moduleList;

    protected Curl $curl;

    protected ScopeConfigInterface $scopeConfig;

    protected ReinitableConfigInterface $reinitableConfig;

    protected Config $config;

    protected ProductMetadataInterface $productMetadata;

    protected DateTime $dateTime;

    private ConfigCollectionFactory $configCollectionFactory;

    protected StatusMapper $statusMapper;

    protected StatusCache $statusCache;

    /**
     * True once sendRequest() has an answer from the license server itself. A timeout, a gateway
     * error or a body that is not a license answer leaves it false.
     *
     * @var bool
     */
    private bool $lastRequestAnswered = false;

    /**
     * LicenseHelper constructor.
     *
     * @param Curl $curl
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param ModuleListInterface $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param DateTime $dateTime
     * @param ConfigCollectionFactory $configCollectionFactory
     * @param StatusMapper $statusMapper
     * @param StatusCache $statusCache
     */
    public function __construct(
        Curl                      $curl,
        Config                    $config,
        ScopeConfigInterface      $scopeConfig,
        ReinitableConfigInterface $reinitableConfig,
        ModuleListInterface       $moduleList,
        ProductMetadataInterface  $productMetadata,
        DateTime                  $dateTime,
        ConfigCollectionFactory   $configCollectionFactory,
        StatusMapper              $statusMapper,
        StatusCache               $statusCache
    ) {
        $this->moduleList              = $moduleList;
        $this->curl                    = $curl;
        $this->scopeConfig             = $scopeConfig;
        $this->config                  = $config;
        $this->reinitableConfig        = $reinitableConfig;
        $this->productMetadata         = $productMetadata;
        $this->dateTime                = $dateTime;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->statusMapper            = $statusMapper;
        $this->statusCache             = $statusCache;
    }

    /**
     * @inheritdoc
     */
    public function getModuleTitle()
    {
        return __('Core');
    }

    /**
     * Activate license with serial
     *
     * @param string $serial
     * @return array
     */
    public function activateLicense($serial): array
    {
        $params = $this->getRequestParams(self::ACTION_ACTIVATE, $serial);
        $result = $this->sendRequest($params);

        $this->saveLicenseConfig(self::PATH_SERIAL, $serial);

        if ($result['success']) {
            $result['messages'][] = __('License successfully activated.');
            $result['is_active']  = true;
            $this->saveLicenseConfig(self::PATH_ACTIVE, 1);
            $this->saveLicenseConfig(self::PATH_ACCESS_TOKEN, $result['access_token']);
            $this->saveLicenseConfig(self::PATH_VERIFIED_TIME, $this->dateTime->gmtTimestamp());
            $this->saveLicenseConfig(self::PATH_VERIFY_ATTEMPT, 0);
            if ($result['dev']) {
                $this->saveLicenseConfig(self::PATH_DEVELOPMENT, 1);
                $result['warnings'][] = __('Development license detected. Please do not use for production.');
            }
            $this->saveLicenseConfig(self::PATH_VALID_UNTIL, (string)($result['support_expiry_date'] ?? ''));
        } else {
            $this->saveLicenseConfig(self::PATH_ACTIVE, 0);
        }

        $this->reinitableConfig->reinit();
        $this->statusCache->invalidate($this->getModuleName());
        unset($result['access_token']);
        $result['status'] = $this->getStatus()->toArray();
        return $result;
    }

    /**
     * Build request parameters
     *
     * @param string $action
     * @param string|null $serial
     * @return array
     */
    private function getRequestParams(string $action, $serial = null): array
    {
        if (!$serial) {
            $serial = $this->getSerial();
        }

        $moduleInfo = $this->moduleList->getOne($this->getModuleName());

        return [
            'action' => $action,
            'serial' => $serial,
            'platform' => $this->getPlatformCode(),
            'magento_edition' => $this->productMetadata->getEdition(),
            'magento_version' => $this->productMetadata->getVersion(),
            'module_name' => $this->getModuleName(),
            'module_version' => (string)$moduleInfo['setup_version'],
            'access_token' => (string)$this->getLicenseConfig(self::PATH_ACCESS_TOKEN),
            'activated_url' => (string)$this->scopeConfig->getValue('web/unsecure/base_url')
        ];
    }

    /**
     * Get license serial number
     *
     * @return null|string
     */
    public function getSerial(): ?string
    {
        return $this->getLicenseConfig(self::PATH_SERIAL);
    }

    /**
     * Get license configuration value
     *
     * @param string $pathId
     * @return mixed
     */
    protected function getLicenseConfig($pathId)
    {
        $collection = $this->configCollectionFactory->create();
        $collection->addFieldToFilter('path', ['eq' => $this->getConfigSection() . $pathId]);
        return $collection->count() ? $collection->getFirstItem()->getData()['value'] : '';
    }

    /**
     * @inheritdoc
     */
    public function getConfigSection(): string
    {
        return 'core';
    }

    /**
     * @inheritdoc
     */
    public function getModuleName(): string
    {
        return 'MageMe_Core';
    }

    /**
     * Get platform code
     *
     * @return string
     */
    protected function getPlatformCode(): string
    {
        $version = 'M2';
        $this->productMetadata->getEdition() == 'Community' ?
            $edition = 'CE' :
            $edition = 'EE';
        return $version . $edition;
    }

    /**
     * Send request to license server
     *
     * @param array $params
     * @return array
     */
    /**
     * A JSON array decodes to a PHP list; the license server always answers with an object.
     * (array_is_list() is PHP 8.1 and this module still supports 7.4.)
     *
     * @param array<mixed> $value
     */
    private function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    private function sendRequest($params): array
    {
        $this->lastRequestAnswered = false;

        $result = [
            'success' => false,
            'is_active' => false,
            'messages' => [],
            'warnings' => [],
            'errors' => [],
        ];

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        // The body carries the serial and the access token, so a redirect must not be followed:
        // whatever host the response names would receive them.
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, false);
        $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, GenericLicenseHelper::CONNECT_TIMEOUT_S);
        $this->curl->setOption(CURLOPT_TIMEOUT, GenericLicenseHelper::REQUEST_TIMEOUT_S);
        $this->curl->setOption(CURLOPT_USERAGENT, GenericLicenseHelper::USER_AGENT);

        try {
            $this->curl->post(static::URL, $params);
        } catch (Exception $e) {
            $result['errors'][] = __('Could not reach the license server. Please try again.');
            return $result;
        }

        $status = (int)$this->curl->getStatus();
        if ($status < 200 || $status > 299) {
            $result['errors'][] = __('Unexpected license server response.');
            return $result;
        }

        $response = json_decode($this->curl->getBody(), true);

        if (!is_array($response) || $response === [] || $this->isList($response)) {
            $result['errors'][] = __('Unexpected license server response.');

            return $result;
        }

        $this->lastRequestAnswered = true;

        $result = array_merge($result, $response);
        // The response owns these keys once merged, and callers append to them — a scalar in any of
        // them would turn "License activated." into a fatal.
        foreach (['messages', 'warnings', 'errors'] as $key) {
            if (!is_array($result[$key])) {
                $result[$key] = [];
            }
        }
        $result['success'] = !empty($result['success']);

        return $result;
    }

    /**
     * Build license URL with parameters
     *
     * @param array $params
     * @return string
     */
    protected function getLicenseUrl(array $params): string
    {
        return static::URL . '?' . http_build_query($params);
    }

    /**
     * Save license configuration value
     *
     * @param string $pathId
     * @param string|null $value
     * @return $this
     */
    protected function saveLicenseConfig($pathId, $value = null): LicenseHelper
    {
        return $this->saveConfig($this->getConfigSection() . $pathId, $value);
    }

    /**
     * Save configuration value
     *
     * @param string|array $pathId
     * @param string|null $value
     * @return $this
     */
    protected function saveConfig($pathId, $value = null): LicenseHelper
    {
        if (is_array($pathId)) {
            foreach ($pathId as $path => $pathValue) {
                $this->saveConfig($path, $pathValue);
            }

            return $this;
        }

        $this->config->saveConfig(
            $pathId,
            $value,
            'default'
        );

        return $this;
    }

    /**
     * Deactivate license
     *
     * @return array
     */
    public function deactivateLicense(): array
    {
        $params = $this->getRequestParams(self::ACTION_DEACTIVATE);
        $result = $this->sendRequest($params);

        $this->saveLicenseConfig(self::PATH_ACTIVE, 0);
        $this->saveLicenseConfig(self::PATH_DEVELOPMENT, 0);
        $this->saveLicenseConfig(self::PATH_VALID_UNTIL, '');
        $this->saveLicenseConfig(self::PATH_ACCESS_TOKEN, '');

        if ($result['success']) {
            $result['messages'][] = __('License successfully deactivated!');
            $this->saveLicenseConfig(self::PATH_ACTIVE, 0);
            $this->saveLicenseConfig(self::PATH_DEVELOPMENT, 0);
            $this->saveLicenseConfig(self::PATH_VALID_UNTIL, '');
            $this->saveLicenseConfig(self::PATH_ACCESS_TOKEN, $result['access_token']);
        } else {
            $result['is_active'] = false;
        }
        $this->reinitableConfig->reinit();
        $this->statusCache->invalidate($this->getModuleName());

        unset($result['access_token']);
        $result['status'] = $this->getStatus()->toArray();
        return $result;
    }

    /**
     * Verify license status
     *
     * @return void
     */
    public function verifyLicense()
    {
        $lastVerifiedTime = (int)$this->getLicenseConfig(self::PATH_VERIFIED_TIME);
        $currentTime      = $this->dateTime->gmtTimestamp();

        if (($currentTime - $lastVerifiedTime) > 86400 * 14
            && $this->isActive()) {
            $this->fetchLiveStatus();
        }
    }

    /**
     * Check if license is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getLicenseConfig(self::PATH_ACTIVE) && $this->getSerial();
    }

    public function getStatus(): LicenseStatus
    {
        return $this->statusMapper->fromStoredConfig([
            'active' => $this->getLicenseConfig(self::PATH_ACTIVE),
            'development' => $this->getLicenseConfig(self::PATH_DEVELOPMENT),
            'valid_until' => $this->getLicenseConfig(self::PATH_VALID_UNTIL),
        ]);
    }

    public function fetchLiveStatus(): LicenseStatus
    {
        $params = $this->getRequestParams(self::ACTION_VERIFY);
        $result = $this->sendRequest($params);

        // An unanswered request carries no verdict on the license. Storing it as one would switch
        // a working license off, and isActive() then keeps this method from ever asking again — the
        // merchant would have to re-enter the serial by hand. The stored state stands, marked stale
        // so the caller can say the check did not go through.
        if (!$this->lastRequestAnswered) {
            $stored = $this->getStatus();

            return new LicenseStatus($stored->state, $stored->isActive, $stored->isDev, $stored->validUntil, true);
        }

        $status = $this->statusMapper->fromApiResponse($result);

        $this->saveLicenseConfig(self::PATH_ACTIVE, $status->isActive ? 1 : 0);
        $this->saveLicenseConfig(self::PATH_DEVELOPMENT, $status->isDev ? 1 : 0);
        $this->saveLicenseConfig(self::PATH_VALID_UNTIL, (string)$status->validUntil);
        $this->saveLicenseConfig(self::PATH_VERIFIED_TIME, $this->dateTime->gmtTimestamp());
        $this->reinitableConfig->reinit();

        $this->statusCache->set($this->getModuleName(), $status);

        return $status;
    }
}
