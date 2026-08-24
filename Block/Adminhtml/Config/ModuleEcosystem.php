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

    public function canHydrate(): bool
    {
        return $this->_authorization->isAllowed('MageMe_Core::info');
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
}
