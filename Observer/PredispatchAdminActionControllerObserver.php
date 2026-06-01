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

namespace MageMe\Core\Observer;

use MageMe\Core\Config\FeedFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PredispatchAdminActionControllerObserver implements ObserverInterface
{
    protected FeedFactory $feedFactory;

    protected Session $backendAuthSession;

    /**
     * @param FeedFactory $feedFactory
     * @param Session $backendAuthSession
     */
    public function __construct(
        FeedFactory $feedFactory,
        Session     $backendAuthSession
    ) {
        $this->feedFactory        = $feedFactory;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * Execute observer for admin predispatch
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $feed = $this->feedFactory->create();
            $feed->checkUpdate();
        }
    }
}
