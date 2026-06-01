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

use Exception;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Links extends AbstractInfo
{
    public const LINKS_CLASS = 'class';
    public const LINKS_STYLE = 'style';
    public const LINKS_TITLE = 'title';
    public const LINKS_URL = 'url';

    /**
     * Get element HTML with links
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $moduleName = $this->getElementModuleName($element);
        if (!$moduleName) {
            return '';
        }
        $info = $this->getModuleInfo($moduleName);
        if (empty($info)) {
            return '';
        }

        $list = [];
        if (isset($info[self::LINKS]) &&
            is_array($info[self::LINKS])
        ) {
            foreach ($info[self::LINKS] as $link) {
                try {
                    $list[] = sprintf(
                        "<a href='%s' class='%s' style='%s' target='_blank' rel='noopener noreferrer'>%s</a>",
                        $this->escapeUrl((string)($link[self::LINKS_URL] ?? '')),
                        $this->escapeHtmlAttr((string)($link[self::LINKS_CLASS] ?? '')),
                        $this->escapeHtmlAttr((string)($link[self::LINKS_STYLE] ?? '')),
                        $this->escapeHtml((string)($link[self::LINKS_TITLE] ?? ''))
                    );
                } catch (Exception $exception) {
                    continue;
                }
            }
        }

        return empty($list) ? '' : '<div class="control-value special">' . implode('<br>', $list) . '</div>';
    }
}
