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

namespace MageMe\Core\Controller\Adminhtml\License;

use MageMe\Core\Helper\GenericLicenseHelper;
use MageMe\Core\Helper\ModulesHelper;
use MageMe\Core\Model\ModuleEcosystem\LicenseMetaResolver;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

abstract class AbstractAction extends Action
{
    /**
     * License view/manage operations are gated by a finer-grained ACL than the parent
     * "MageMe_Core::info" — demo / read-only roles can browse the admin without exposing
     * actual license serials.
     */
    public const ADMIN_RESOURCE = 'MageMe_Core::license_view';

    protected JsonFactory $jsonFactory;

    protected ModulesHelper $modulesHelper;

    protected GenericLicenseHelper $genericLicenseHelper;

    protected LicenseMetaResolver $licenseMetaResolver;

    public function __construct(
        Context              $context,
        JsonFactory          $jsonFactory,
        ModulesHelper        $modulesHelper,
        GenericLicenseHelper $genericLicenseHelper,
        LicenseMetaResolver  $licenseMetaResolver
    ) {
        parent::__construct($context);
        $this->jsonFactory          = $jsonFactory;
        $this->modulesHelper        = $modulesHelper;
        $this->genericLicenseHelper = $genericLicenseHelper;
        $this->licenseMetaResolver  = $licenseMetaResolver;
    }

    /**
     * Resolve license_section + owner_module_name from request.
     *
     * Three cases:
     *  - license_section + module_name (new JS, both attributes present)
     *  - module_name only (old JS, cached browser): resolve license_section via catalog
     *  - license_section only (theoretical): caller must also provide module_name; otherwise fail
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function resolveLicenseTarget(): array
    {
        $section    = (string)$this->getRequest()->getParam('license_section', '');
        $moduleName = (string)$this->getRequest()->getParam('module_name', '');

        if ($section !== '' && $moduleName !== '') {
            return [$section, $moduleName];
        }

        if ($moduleName !== '') {
            $meta = $this->licenseMetaResolver->resolveFor($moduleName);
            if ($meta !== null) {
                return [$meta->licenseSection, $meta->ownerModuleName];
            }
        }

        return [null, null];
    }
}
