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

namespace MageMe\Core\Controller\Adminhtml\Ecosystem;

use MageMe\Core\Block\Adminhtml\Config\ModuleEcosystem as EcosystemBlock;
use MageMe\Core\Model\ModuleEcosystem\EcosystemDataProvider;
use MageMe\Core\Model\ModuleEcosystem\RemoteCatalog;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Throwable;

/**
 * GET /admin/core/ecosystem/render?module_name=...&section_id=... → raw HTML.
 * Refreshes the catalog and re-renders the ecosystem block; JS uses the response
 * to hydrate the DOM in place when the initial server-render saw an empty cache.
 */
class Render extends Action
{
    public const ADMIN_RESOURCE = 'MageMe_Core::info';

    /** @var RawFactory */
    private $rawFactory;
    /** @var LayoutFactory */
    private $layoutFactory;
    /** @var RemoteCatalog */
    private $catalog;
    /** @var EcosystemDataProvider */
    private $dataProvider;

    public function __construct(
        Context               $context,
        RawFactory            $rawFactory,
        LayoutFactory         $layoutFactory,
        RemoteCatalog         $catalog,
        EcosystemDataProvider $dataProvider
    ) {
        parent::__construct($context);
        $this->rawFactory    = $rawFactory;
        $this->layoutFactory = $layoutFactory;
        $this->catalog       = $catalog;
        $this->dataProvider  = $dataProvider;
    }

    public function execute()
    {
        $moduleName = (string)$this->getRequest()->getParam('module_name', '');
        $sectionId  = (string)$this->getRequest()->getParam('section_id', '');

        $result = $this->rawFactory->create();
        if ($moduleName === '' || $sectionId === '') {
            return $result->setHttpResponseCode(400)->setContents('');
        }

        try {
            $this->catalog->refresh();
            $view  = $this->dataProvider->buildFor($moduleName);
            $block = $this->layoutFactory->create()->createBlock(EcosystemBlock::class);
            $block->setData('ecosystem_view', $view);
            $block->setData('section_id', $sectionId);
            return $result
                ->setHeader('Content-Type', 'text/html; charset=UTF-8')
                ->setContents($block->toHtml());
        } catch (Throwable $e) {
            return $result->setHttpResponseCode(500)->setContents('');
        }
    }
}
