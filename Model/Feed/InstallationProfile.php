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

namespace MageMe\Core\Model\Feed;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\PackageInfo;

/**
 * Query parameters describing this installation, sent with the MageMe news feed request.
 */
class InstallationProfile
{
    public const SCHEMA_VERSION = '1';

    public const XML_PATH_SEND_MODULES = 'mageme/feed/send_modules';

    private const MODULE_PREFIXES = ['MageMe_', 'Hyva_MageMe'];

    private const BASE_URL_PATH = 'web/unsecure/base_url';

    /** @var ModuleListInterface */
    private ModuleListInterface $moduleList;

    /** @var PackageInfo */
    private PackageInfo $packageInfo;

    /** @var DeploymentConfig */
    private DeploymentConfig $deploymentConfig;

    /** @var ScopeConfigInterface */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ModuleListInterface $moduleList
     * @param PackageInfo $packageInfo
     * @param DeploymentConfig $deploymentConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ModuleListInterface  $moduleList,
        PackageInfo          $packageInfo,
        DeploymentConfig     $deploymentConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleList       = $moduleList;
        $this->packageInfo      = $packageInfo;
        $this->deploymentConfig = $deploymentConfig;
        $this->scopeConfig      = $scopeConfig;
    }

    /**
     * Build the feed request parameters
     *
     * @return array<string, string>
     */
    public function getParams(): array
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_SEND_MODULES)) {
            return [];
        }

        $params = [
            'v' => self::SCHEMA_VERSION,
            'm' => $this->getModules(),
        ];

        $installationId = $this->getInstallationId();
        if ($installationId !== null) {
            $params['iid'] = $installationId;
        }

        return $params;
    }

    /**
     * Enabled MageMe modules with their versions, sorted for a stable value
     *
     * @return string
     */
    private function getModules(): string
    {
        $modules = [];
        foreach ($this->moduleList->getNames() as $name) {
            if (!$this->isMageMeModule($name)) {
                continue;
            }
            $version   = (string)$this->packageInfo->getVersion($name);
            $modules[] = $version === '' ? $name : $name . ':' . $version;
        }
        sort($modules);

        return implode(',', $modules);
    }

    /**
     * Check whether the module belongs to MageMe
     *
     * @param string $name
     * @return bool
     */
    private function isMageMeModule(string $name): bool
    {
        foreach (self::MODULE_PREFIXES as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Irreversible installation identifier, empty when the install date is unavailable
     *
     * @return string|null
     */
    private function getInstallationId(): ?string
    {
        $installDate = (string)$this->deploymentConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_INSTALL_DATE
        );
        if ($installDate === '') {
            return null;
        }

        $baseUrl = (string)$this->scopeConfig->getValue(self::BASE_URL_PATH);

        return substr(hash('sha256', $baseUrl . $installDate), 0, 16);
    }
}
