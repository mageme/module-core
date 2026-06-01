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

final class LicenseDeclaration
{
    /** @var string */
    public $section;
    /** @var string */
    public $tier;
    /** @var array<string, string> */
    public $codes;

    /**
     * @param array<string, string> $codes platform => code
     */
    public function __construct(string $section, string $tier, array $codes)
    {
        $this->section = $section;
        $this->tier    = $tier;
        $this->codes   = $codes;
    }
}
