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

namespace MageMe\Core\Model\Declaration;

class ModuleDeclaration
{
    /** @var string */
    public $moduleName;
    /** @var SectionDeclaration|null */
    public $section;
    /** @var LicenseDeclaration|null */
    public $license;

    public function __construct(
        string $moduleName,
        ?SectionDeclaration $section,
        ?LicenseDeclaration $license
    ) {
        $this->moduleName = $moduleName;
        $this->section    = $section;
        $this->license    = $license;
    }
}
