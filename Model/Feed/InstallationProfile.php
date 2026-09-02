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
use Magento\Framework\App\State;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\FlagManager;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\PackageInfo;

/**
 * Query parameters describing this installation, sent with the MageMe news feed request.
 */
class InstallationProfile
{
    public const SCHEMA_VERSION = '2';

    public const XML_PATH_SEND_MODULES = 'mageme/feed/send_modules';

    public const FLAG_INSTALLATION_ID = 'mageme_feed_installation_id';

    private const MODULE_PREFIXES = ['MageMe_', 'Hyva_MageMe'];

    private const BASE_URL_PATH = 'web/unsecure/base_url';

    private const B2B_MODULE = 'Magento_Company';

    private const ID_PATTERN = '/^[0-9a-f]{32}$/';

    /** @var ModuleListInterface */
    private ModuleListInterface $moduleList;

    /** @var PackageInfo */
    private PackageInfo $packageInfo;

    /** @var DeploymentConfig */
    private DeploymentConfig $deploymentConfig;

    /** @var ScopeConfigInterface */
    private ScopeConfigInterface $scopeConfig;

    /** @var FlagManager */
    private FlagManager $flagManager;

    /** @var State */
    private State $appState;

    /**
     * @param ModuleListInterface $moduleList
     * @param PackageInfo $packageInfo
     * @param DeploymentConfig $deploymentConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param FlagManager $flagManager
     * @param State $appState
     */
    public function __construct(
        ModuleListInterface  $moduleList,
        PackageInfo          $packageInfo,
        DeploymentConfig     $deploymentConfig,
        ScopeConfigInterface $scopeConfig,
        FlagManager          $flagManager,
        State                $appState
    ) {
        $this->moduleList       = $moduleList;
        $this->packageInfo      = $packageInfo;
        $this->deploymentConfig = $deploymentConfig;
        $this->scopeConfig      = $scopeConfig;
        $this->flagManager      = $flagManager;
        $this->appState         = $appState;
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

        $fingerprint = $this->getEnvironmentFingerprint();
        if ($fingerprint !== null) {
            $params['iid'] = $fingerprint;
        }

        $installationId = $this->getInstallationId();
        if ($installationId !== null) {
            $params['id'] = $installationId;
        }

        $params['mode'] = $this->appState->getMode();

        if ($this->moduleList->has(self::B2B_MODULE)) {
            $params['b2b'] = '1';
        }

        return $params;
    }

    /**
     * Random installation identifier, generated once and kept in the flag table.
     *
     * The value sent is always the one read back from storage, so two requests that
     * race on the first generation still report the same identifier.
     *
     * @return string|null
     */
    private function getInstallationId(): ?string
    {
        try {
            $stored = $this->readInstallationId();
            if ($stored !== null) {
                return $stored;
            }
            try {
                $this->flagManager->saveFlag(self::FLAG_INSTALLATION_ID, bin2hex(random_bytes(16)));
            } catch (\Throwable $e) {
                // a concurrent request may have won the write; the re-read below decides
            }

            return $this->readInstallationId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    private function readInstallationId(): ?string
    {
        try {
            $value = $this->flagManager->getFlagData(self::FLAG_INSTALLATION_ID);
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($value) && preg_match(self::ID_PATTERN, $value) ? $value : null;
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
            $version   = $this->getModuleVersion($name);
            $modules[] = $version === '' ? $name : $name . ':' . $version;
        }
        sort($modules);

        return implode(',', $modules);
    }

    /**
     * Module version from composer metadata, falling back to module.xml
     *
     * @param string $name
     * @return string
     */
    private function getModuleVersion(string $name): string
    {
        $version = (string)$this->packageInfo->getVersion($name);
        if ($version !== '') {
            return $version;
        }

        return (string)($this->moduleList->getOne($name)['setup_version'] ?? '');
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
     * Irreversible environment fingerprint, empty when the install date is unavailable
     *
     * @return string|null
     */
    private function getEnvironmentFingerprint(): ?string
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
