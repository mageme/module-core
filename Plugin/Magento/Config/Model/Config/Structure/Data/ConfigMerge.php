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

namespace MageMe\Core\Plugin\Magento\Config\Model\Config\Structure\Data;

use Magento\Config\Model\Config\Structure\Data as StructureData;

class ConfigMerge
{
    public const MODULE_NAME = 'module_name';

    /**
     * Merge license configuration for MageMe modules and inject ecosystem headers.
     *
     * @param StructureData $object
     * @param array $config
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeMerge(StructureData $object, array $config): array
    {
        if (!isset($config['config']['system'])) {
            return [$config];
        }

        /** @var array $sections */
        $sections = $config['config']['system']['sections'];
        foreach ($sections as $sectionId => $section) {
            if (!isset($section['tab']) || $section['tab'] !== 'mageme') {
                continue;
            }
            $config['config']['system']['sections'][$sectionId]
                = $this->injectEcosystemHeader($section, $sectionId);
        }

        return [$config];
    }

    /**
     * Inject the module_ecosystem header group into a non-license MageMe section.
     *
     * @param array $section
     * @param string $sectionId
     * @return array
     */
    private function injectEcosystemHeader(array $section, string $sectionId): array
    {
        $resource = $section['resource'] ?? '';
        if (!is_string($resource) || $resource === '') {
            return $section;
        }
        $moduleName = explode('::', $resource, 2)[0];
        if (strpos($moduleName, 'MageMe_') !== 0) {
            return $section;
        }

        $existingChildren = is_array($section['children'] ?? null) ? $section['children'] : [];
        $section['children'] = ['module_ecosystem' => [
            '_elementType'   => 'group',
            'id'             => 'module_ecosystem',
            'label'          => 'Module Information',
            'path'           => $sectionId,
            'sortOrder'      => '0',
            'showInDefault'  => '1',
            'showInWebsite'  => '1',
            'showInStore'    => '1',
            'frontend_model' => \MageMe\Core\Block\Adminhtml\Config\ModuleEcosystem::class,
            'module_name'    => $moduleName,
            'children'       => [],
        ]] + $existingChildren;

        return $section;
    }
}
