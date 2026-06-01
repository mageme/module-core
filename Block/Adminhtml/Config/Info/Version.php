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

namespace MageMe\Core\Block\Adminhtml\Config\Info;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends AbstractInfo
{
    /**
     * Get element HTML with version info
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $moduleName = $this->getElementModuleName($element);
        return $this->getVersionHtml($moduleName);
    }

    /**
     * Get version HTML for module
     *
     * @param string $moduleName
     * @return string
     */
    public function getVersionHtml(string $moduleName): string
    {
        if (!$moduleName) {
            return '';
        }
        $moduleInfo = $this->_moduleList->getOne($moduleName);
        $version    = $this->escapeHtml((string)($moduleInfo['setup_version'] ?? ''));
        $info       = $this->getModuleInfo($moduleName);
        if (!empty($info) && version_compare((string)$info[self::VERSION], (string)($moduleInfo['setup_version'] ?? ''), '>')) {
            $version .= ' | ' . sprintf(
                "<a href='%s' target='_blank' rel='noopener noreferrer'>%s</a>",
                $this->escapeUrl((string)($info[self::RELEASE_NOTES] ?? '')),
                $this->escapeHtml((string)__('%1 update available', $info[self::VERSION]))
            );
        }

        return sprintf('<div class="control-value special">%s</div>', $version);
    }
}
