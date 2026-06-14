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
 * Copyright (c) MageMe (https://mageme.com)
 **/

declare(strict_types=1);

namespace MageMe\Core\Controller\Adminhtml\License;

use MageMe\Core\Helper\GenericLicenseHelper;
use MageMe\Core\Helper\ModulesHelper;
use MageMe\Core\Model\License\LicenseStatus;
use MageMe\Core\Model\License\StatusCache;
use MageMe\Core\Model\ModuleEcosystem\LicenseMetaResolver;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class Status extends AbstractAction implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageMe_Core::license_view';

    /** @var StatusCache */
    private $statusCache;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Context              $context,
        JsonFactory          $jsonFactory,
        ModulesHelper        $modulesHelper,
        GenericLicenseHelper $genericLicenseHelper,
        LicenseMetaResolver  $licenseMetaResolver,
        StatusCache          $statusCache,
        LoggerInterface      $logger
    ) {
        parent::__construct($context, $jsonFactory, $modulesHelper, $genericLicenseHelper, $licenseMetaResolver);
        $this->statusCache = $statusCache;
        $this->logger      = $logger;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();

        list($licenseSection, $ownerModuleName) = $this->resolveLicenseTarget();
        if ($licenseSection === null || $ownerModuleName === null) {
            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'license_section or module_name required']);
        }

        if (!$this->genericLicenseHelper->isActive($licenseSection)) {
            return $resultJson->setData($this->genericLicenseHelper->getStatus($licenseSection)->toArray());
        }

        try {
            $status = $this->genericLicenseHelper->fetchLiveStatus($licenseSection, $ownerModuleName);
            return $resultJson->setData($status->toArray());
        } catch (Throwable $e) {
            $this->logger->warning(
                'MageMe license status fetch failed: ' . $e->getMessage(),
                ['license_section' => $licenseSection, 'module' => $ownerModuleName]
            );
            $stored = $this->genericLicenseHelper->getStatus($licenseSection);
            $stale  = new LicenseStatus(
                $stored->state,
                $stored->isActive,
                $stored->isDev,
                $stored->validUntil,
                true
            );
            return $resultJson->setData($stale->toArray());
        }
    }
}
