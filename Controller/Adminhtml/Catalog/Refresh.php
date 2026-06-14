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

namespace MageMe\Core\Controller\Adminhtml\Catalog;

use MageMe\Core\Model\ModuleEcosystem\RemoteCatalog;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/** GET /admin/core/catalog/refresh → {ok: bool}. Triggered fire-and-forget by admin JS. */
class Refresh extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageMe_Core::info';

    /** @var JsonFactory */
    private $jsonFactory;
    /** @var RemoteCatalog */
    private $catalog;

    public function __construct(Context $context, JsonFactory $jsonFactory, RemoteCatalog $catalog)
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->catalog     = $catalog;
    }

    public function execute()
    {
        $ok = $this->catalog->refresh();
        return $this->jsonFactory->create()->setData(['ok' => $ok]);
    }
}
