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

use MageMe\Core\Api\ModuleHelperInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\ObjectManagerInterface;

class ModulesHelper
{
    public const MODULE_PREFIX = 'MageMe_';
    public const CORE = 'MageMe_Core';

    protected ModuleListInterface $moduleList;

    protected ScopeConfigInterface $scopeConfig;

    protected Config $config;

    protected ObjectManagerInterface $objectManager;

    /**
     * ModulesHelper constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config                 $config,
        ScopeConfigInterface   $scopeConfig,
        ModuleListInterface    $moduleList
    ) {
        $this->moduleList    = $moduleList;
        $this->scopeConfig   = $scopeConfig;
        $this->config        = $config;
        $this->objectManager = $objectManager;
    }

    /**
     * Get list of MageMe modules
     *
     * @return array
     */
    public function getModules(): array
    {
        $modules    = [];
        $moduleList = $this->moduleList->getNames();
        foreach ($moduleList as $name) {
            if (strpos($name, self::MODULE_PREFIX) === false) {
                continue;
            }
            if ($name == self::CORE) {
                continue;
            }
            $modules[] = $name;
        }
        return $modules;
    }

    /**
     * Get module helper by name
     *
     * @deprecated 2.1.0 The per-module helper convention
     * (`MageMe\<Module>\Helper\<Name>` discovered by class_exists + ObjectManager) is no
     * longer used by MageMe_Core. License metadata is sourced from local
     * `etc/license.xml` declarations via {@see \MageMe\Core\Model\Declaration\DeclarationRegistry}
     * and the catalog. Scheduled for removal in MageMe_Core 3.0.
     *
     * @param string $moduleName
     * @param string $helperName
     * @return ModuleHelperInterface|null
     */
    public function getModuleHelper(string $moduleName, string $helperName): ?ModuleHelperInterface
    {
        $helperClassName = $this->getHelperClassName($moduleName, $helperName);
        if (class_exists($helperClassName)) {
            $helper = $this->objectManager->create($helperClassName);
            if ($helper instanceof ModuleHelperInterface) {
                return $helper;
            }
        }
        return null;
    }

    /**
     * Build helper class name from module name
     *
     * @deprecated 2.1.0 Supporting machinery for the deprecated {@see getModuleHelper()}.
     * Scheduled for removal in MageMe_Core 3.0.
     *
     * @param string $moduleName
     * @param string $helperName
     * @return string
     */
    protected function getHelperClassName(string $moduleName, string $helperName): string
    {
        return str_replace('_', '\\', $moduleName) . '\Helper\\' . $helperName;
    }

    /**
     * Get module ID from full module name
     *
     * @deprecated 2.1.0 Unused by MageMe_Core. Scheduled for removal in MageMe_Core 3.0.
     *
     * @param string $moduleName
     * @return string
     */
    public function getModuleIdFormName(string $moduleName): string
    {
        return strtolower(substr($moduleName, strpos($moduleName, '_') + 1));
    }

    /**
     * Get module label from full module name
     *
     * @deprecated 2.1.0 Unused by MageMe_Core. Scheduled for removal in MageMe_Core 3.0.
     *
     * @param string $moduleName
     * @return string
     */
    public function getModuleLabelFormName(string $moduleName): string
    {
        return substr($moduleName, strpos($moduleName, '_') + 1);
    }
}
