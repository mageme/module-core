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

use MageMe\Core\Model\License\LicenseStatus;
use MageMe\Core\Model\License\StatusCache;
use MageMe\Core\Model\License\StatusMapper;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Stateless license helper. All operations parametrised by $licenseSection.
 *
 * Replaces per-module <Module>\Helper\LicenseHelper subclasses. License-trigger
 * is now resolved through info.mageme.com/modules.json catalog (see RemoteCatalog +
 * LicenseMetaResolver), not via PHP class presence.
 */
class GenericLicenseHelper
{
    public const URL = 'https://license.mageme.com';

    public const ACTION_ACTIVATE   = 'activate';
    public const ACTION_DEACTIVATE = 'deactivate';
    public const ACTION_VERIFY     = 'verify';

    public const PATH_LICENSE        = '/license';
    public const PATH_ACTIVE         = self::PATH_LICENSE . '/active';
    public const PATH_SERIAL         = self::PATH_LICENSE . '/serial';
    public const PATH_ACCESS_TOKEN   = self::PATH_LICENSE . '/access_token';
    public const PATH_VERIFIED_TIME  = self::PATH_LICENSE . '/verified_time';
    public const PATH_DEVELOPMENT    = self::PATH_LICENSE . '/development';
    public const PATH_VERIFY_ATTEMPT = self::PATH_LICENSE . '/verify_attempt';
    public const PATH_VALID_UNTIL    = self::PATH_LICENSE . '/valid_until';

    /** @var Curl */
    protected $curl;
    /** @var Config */
    protected $config;
    /** @var ScopeConfigInterface */
    protected $scopeConfig;
    /** @var ReinitableConfigInterface */
    protected $reinitableConfig;
    /** @var ProductMetadataInterface */
    protected $productMetadata;
    /** @var DateTime */
    protected $dateTime;
    /** @var ConfigCollectionFactory */
    protected $configCollectionFactory;
    /** @var StatusMapper */
    protected $statusMapper;
    /** @var StatusCache */
    protected $statusCache;

    public function __construct(
        Curl                      $curl,
        Config                    $config,
        ScopeConfigInterface      $scopeConfig,
        ReinitableConfigInterface $reinitableConfig,
        ProductMetadataInterface  $productMetadata,
        DateTime                  $dateTime,
        ConfigCollectionFactory   $configCollectionFactory,
        StatusMapper              $statusMapper,
        StatusCache               $statusCache
    ) {
        $this->curl                    = $curl;
        $this->config                  = $config;
        $this->scopeConfig             = $scopeConfig;
        $this->reinitableConfig        = $reinitableConfig;
        $this->productMetadata         = $productMetadata;
        $this->dateTime                = $dateTime;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->statusMapper            = $statusMapper;
        $this->statusCache             = $statusCache;
    }

    public function activateLicense(string $licenseSection, string $ownerModuleName, string $serial): array
    {
        $params = $this->getRequestParams(self::ACTION_ACTIVATE, $licenseSection, $ownerModuleName, $serial);
        $result = $this->sendRequest($params);

        $this->saveLicenseConfig($licenseSection, self::PATH_SERIAL, $serial);

        if ($result['success']) {
            $result['messages'][] = __('License successfully activated.');
            $result['is_active']  = true;
            $this->saveLicenseConfig($licenseSection, self::PATH_ACTIVE, 1);
            $this->saveLicenseConfig($licenseSection, self::PATH_ACCESS_TOKEN, (string)($result['access_token'] ?? ''));
            $this->saveLicenseConfig($licenseSection, self::PATH_VERIFIED_TIME, $this->dateTime->gmtTimestamp());
            $this->saveLicenseConfig($licenseSection, self::PATH_VERIFY_ATTEMPT, 0);
            if (!empty($result['dev'])) {
                $this->saveLicenseConfig($licenseSection, self::PATH_DEVELOPMENT, 1);
                $result['warnings'][] = __('Development license detected. Please do not use for production.');
            }
            $this->saveLicenseConfig($licenseSection, self::PATH_VALID_UNTIL, (string)(isset($result['support_expiry_date']) ? $result['support_expiry_date'] : ''));
        } else {
            $this->saveLicenseConfig($licenseSection, self::PATH_ACTIVE, 0);
        }

        $this->reinitableConfig->reinit();
        $this->statusCache->invalidate($ownerModuleName);
        unset($result['access_token']);
        $result['status'] = $this->getStatus($licenseSection)->toArray();
        return $result;
    }

    public function deactivateLicense(string $licenseSection, string $ownerModuleName): array
    {
        $params = $this->getRequestParams(self::ACTION_DEACTIVATE, $licenseSection, $ownerModuleName);
        $result = $this->sendRequest($params);

        $this->saveLicenseConfig($licenseSection, self::PATH_ACTIVE, 0);
        $this->saveLicenseConfig($licenseSection, self::PATH_DEVELOPMENT, 0);
        $this->saveLicenseConfig($licenseSection, self::PATH_VALID_UNTIL, '');
        $this->saveLicenseConfig($licenseSection, self::PATH_ACCESS_TOKEN, '');

        if ($result['success']) {
            $result['messages'][] = __('License successfully deactivated!');
            $this->saveLicenseConfig($licenseSection, self::PATH_ACCESS_TOKEN, (string)($result['access_token'] ?? ''));
        } else {
            $result['is_active'] = false;
        }
        $this->reinitableConfig->reinit();
        $this->statusCache->invalidate($ownerModuleName);

        unset($result['access_token']);
        $result['status'] = $this->getStatus($licenseSection)->toArray();
        return $result;
    }

    public function verifyLicense(string $licenseSection, string $ownerModuleName)
    {
        $lastVerifiedTime = (int)$this->getLicenseConfig($licenseSection, self::PATH_VERIFIED_TIME);
        $currentTime      = $this->dateTime->gmtTimestamp();

        if (($currentTime - $lastVerifiedTime) > 86400 * 14
            && $this->isActive($licenseSection)) {
            $this->fetchLiveStatus($licenseSection, $ownerModuleName);
        }
    }

    public function isActive(string $licenseSection): bool
    {
        return $this->getLicenseConfig($licenseSection, self::PATH_ACTIVE) && $this->getSerial($licenseSection);
    }

    /** @return string|null */
    public function getSerial(string $licenseSection)
    {
        $value = $this->getLicenseConfig($licenseSection, self::PATH_SERIAL);
        return $value !== '' ? $value : null;
    }

    public function getStatus(string $licenseSection): LicenseStatus
    {
        return $this->statusMapper->fromStoredConfig([
            'active'      => $this->getLicenseConfig($licenseSection, self::PATH_ACTIVE),
            'development' => $this->getLicenseConfig($licenseSection, self::PATH_DEVELOPMENT),
            'valid_until' => $this->getLicenseConfig($licenseSection, self::PATH_VALID_UNTIL),
        ]);
    }

    public function fetchLiveStatus(string $licenseSection, string $ownerModuleName): LicenseStatus
    {
        $params = $this->getRequestParams(self::ACTION_VERIFY, $licenseSection, $ownerModuleName);
        $result = $this->sendRequest($params);
        $status = $this->statusMapper->fromApiResponse($result);

        $this->saveLicenseConfig($licenseSection, self::PATH_ACTIVE, $status->isActive ? 1 : 0);
        $this->saveLicenseConfig($licenseSection, self::PATH_DEVELOPMENT, $status->isDev ? 1 : 0);
        $this->saveLicenseConfig($licenseSection, self::PATH_VALID_UNTIL, (string)$status->validUntil);
        $this->saveLicenseConfig($licenseSection, self::PATH_VERIFIED_TIME, $this->dateTime->gmtTimestamp());
        $this->reinitableConfig->reinit();

        $this->statusCache->set($ownerModuleName, $status);

        return $status;
    }

    /**
     * @param string $action
     * @param string $licenseSection
     * @param string $ownerModuleName
     * @param string|null $serial
     * @return array
     */
    private function getRequestParams(string $action, string $licenseSection, string $ownerModuleName, $serial = null): array
    {
        if (!$serial) {
            $serial = $this->getSerial($licenseSection);
        }

        return [
            'action'         => $action,
            'serial'         => $serial,
            'platform'       => $this->getPlatformCode(),
            'magento_edition'=> $this->productMetadata->getEdition(),
            'magento_version'=> $this->productMetadata->getVersion(),
            'module_name'    => $ownerModuleName,
            'access_token'   => (string)$this->getLicenseConfig($licenseSection, self::PATH_ACCESS_TOKEN),
            'activated_url'  => (string)$this->scopeConfig->getValue('web/unsecure/base_url'),
        ];
    }

    private function getPlatformCode(): string
    {
        return 'M2' . ($this->productMetadata->getEdition() === 'Community' ? 'CE' : 'EE');
    }

    /**
     * @param string $licenseSection
     * @param string $pathId
     * @return mixed
     */
    private function getLicenseConfig(string $licenseSection, string $pathId)
    {
        $collection = $this->configCollectionFactory->create();
        $collection->addFieldToFilter('path', ['eq' => $licenseSection . $pathId]);
        return $collection->count() ? $collection->getFirstItem()->getData()['value'] : '';
    }

    /**
     * @param string $licenseSection
     * @param string $pathId
     * @param mixed $value
     */
    private function saveLicenseConfig(string $licenseSection, string $pathId, $value = null)
    {
        $this->config->saveConfig($licenseSection . $pathId, $value, 'default');
    }

    private function sendRequest(array $params): array
    {
        $result = [
            'success'   => false,
            'is_active' => false,
            'messages'  => [],
            'warnings'  => [],
            'errors'    => [],
        ];

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16'
        );
        $this->curl->post(static::URL, $params);
        $response = json_decode($this->curl->getBody(), true);

        if (!$response) {
            $result['errors'][] = __('Unexpected license server response.');
        } else {
            $result = array_merge($result, $response);
        }

        return $result;
    }
}
