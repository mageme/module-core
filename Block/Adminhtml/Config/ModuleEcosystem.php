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

namespace MageMe\Core\Block\Adminhtml\Config;

use MageMe\Core\Model\ModuleEcosystem\EcosystemDataProvider;
use MageMe\Core\Model\ModuleEcosystem\EcosystemRow;
use MageMe\Core\Model\ModuleEcosystem\EcosystemView;
use MageMe\Core\Model\ModuleEcosystem\RemoteCatalog;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class ModuleEcosystem extends Template implements RendererInterface
{
    public const FRAMEWORK_MODULE = 'MageMe_Core';

    protected $_template = 'MageMe_Core::system/config/module-ecosystem.phtml';

    private EcosystemDataProvider $ecosystemDataProvider;
    private RemoteCatalog $catalog;

    public function __construct(
        Context $context,
        EcosystemDataProvider $ecosystemDataProvider,
        RemoteCatalog $catalog,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->ecosystemDataProvider = $ecosystemDataProvider;
        $this->catalog = $catalog;
    }

    public function getFrameworkRow(): EcosystemRow
    {
        return $this->ecosystemDataProvider->buildRowFor(self::FRAMEWORK_MODULE);
    }

    /**
     * Magento\Config\Block\System\Config\Form calls render($element) on group frontend_models.
     * We satisfy that contract here by reading the injected module_name from group data and
     * rendering our own template via Template::toHtml().
     */
    public function render(AbstractElement $element): string
    {
        $groupData = $element->getData('group');
        if (!is_array($groupData)) {
            $groupData = [];
        }
        $moduleName = (string) ($groupData['module_name'] ?? $element->getData('module_name'));
        if ($moduleName === '') {
            return '';
        }
        $sectionId = (string) ($groupData['path'] ?? $element->getData('path'));
        $this->setData('ecosystem_view', $this->ecosystemDataProvider->buildFor($moduleName));
        $this->setData('section_id', $sectionId);
        return $this->toHtml();
    }

    public function getEcosystemView(): EcosystemView
    {
        return $this->getData('ecosystem_view');
    }

    public function getSectionId(): string
    {
        return (string) $this->getData('section_id');
    }

    public function getLogoUrl(): string
    {
        return $this->getViewFileUrl('MageMe_Core::images/mageme-logo.svg');
    }

    public function getCatalogRefreshUrl(): string
    {
        return $this->getUrl('core/catalog/refresh');
    }

    public function getEcosystemRenderUrl(): string
    {
        return $this->getUrl('core/ecosystem/render', [
            'module_name' => $this->getEcosystemView()->core->moduleName,
            'section_id'  => $this->getSectionId(),
        ]);
    }

    public function isCatalogEmpty(): bool
    {
        return empty($this->catalog->getAll());
    }

    public function canViewLicense(): bool
    {
        return $this->_authorization->isAllowed('MageMe_Core::license_view');
    }

    public function formatLicenseDate(?string $iso): string
    {
        if (!$iso) {
            return '';
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $iso);
        if (!$dt) {
            return $iso;
        }
        return $dt->format('M j, Y');
    }

    /**
     * @return array{letter:string,gradient:string}
     */
    public function getMonogramTheme(string $coreModuleName): array
    {
        $palette = [
            ['accent' => '#2563eb', 'deep' => '#1d4ed8'], // 0 brand blue
            ['accent' => '#14b8a6', 'deep' => '#0d9488'], // 1 teal
            ['accent' => '#f59e0b', 'deep' => '#d97706'], // 2 amber
            ['accent' => '#3b82f6', 'deep' => '#1d4ed8'], // 3 blue
            ['accent' => '#ec4899', 'deep' => '#be185d'], // 4 pink
            ['accent' => '#10b981', 'deep' => '#047857'], // 5 emerald
            ['accent' => '#0ea5e9', 'deep' => '#0369a1'], // 6 sky
        ];
        // Distinct colour per flagship suite (crc32 alone collides several
        // popular suites onto one index); unknown modules fall back to a
        // deterministic hash across the palette.
        $map = [
            'MageMe_WebForms'          => 0, // brand blue
            'MageMe_HidePrice'         => 2, // amber
            'MageMe_HidePricePro'      => 2, // amber
            'MageMe_EUWithdrawal'      => 6, // sky
            'MageMe_EasyQuote'         => 5, // emerald
            'MageMe_ProductProtection' => 3, // blue
        ];
        $idx = $map[$coreModuleName] ?? (abs(crc32($coreModuleName)) % count($palette));
        $theme = $palette[$idx];

        return [
            'gradient' => "linear-gradient(135deg, {$theme['accent']} 0%, {$theme['deep']} 100%)",
        ];
    }
}
