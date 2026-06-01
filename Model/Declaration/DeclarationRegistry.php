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

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir as ModuleDir;
use Magento\Framework\Module\ModuleListInterface;
use Psr\Log\LoggerInterface;

class DeclarationRegistry
{
    private const FILE_NAME     = 'license.xml';
    private const MODULE_PREFIX = 'MageMe_';

    /** @var ModuleListInterface */
    private $moduleList;
    /** @var ModuleDir */
    private $moduleDir;
    /** @var File */
    private $fs;
    /** @var DeclarationReader */
    private $reader;
    /** @var LoggerInterface */
    private $logger;

    /** @var array<string, ModuleDeclaration>|null */
    private $byModule;
    /** @var array<string, string>|null section => owning module */
    private $sectionOwners;
    /** @var array<string, LicenseDeclaration[]>|null section => paid license declarations */
    private $paidBySection;

    public function __construct(
        ModuleListInterface $moduleList,
        ModuleDir $moduleDir,
        File $fs,
        DeclarationReader $reader,
        LoggerInterface $logger
    ) {
        $this->moduleList = $moduleList;
        $this->moduleDir  = $moduleDir;
        $this->fs         = $fs;
        $this->reader     = $reader;
        $this->logger     = $logger;
    }

    public function getForModule(string $moduleName): ?ModuleDeclaration
    {
        $this->ensureScanned();
        return $this->byModule[$moduleName] ?? null;
    }

    public function getSectionOwner(string $sectionName): ?string
    {
        $this->ensureScanned();
        return $this->sectionOwners[$sectionName] ?? null;
    }

    /**
     * @return LicenseDeclaration[]
     */
    public function getPaidLicenseDeclarationsForSection(string $sectionName): array
    {
        $this->ensureScanned();
        return $this->paidBySection[$sectionName] ?? [];
    }

    private function ensureScanned(): void
    {
        if ($this->byModule !== null) {
            return;
        }
        $this->byModule      = [];
        $this->sectionOwners = [];
        $this->paidBySection = [];

        foreach ($this->moduleList->getNames() as $moduleName) {
            if (strpos($moduleName, self::MODULE_PREFIX) !== 0) {
                continue;
            }
            $decl = $this->loadOne($moduleName);
            if ($decl === null) {
                continue;
            }
            $this->byModule[$moduleName] = $decl;

            if ($decl->section !== null) {
                $this->sectionOwners[$decl->section->name] = $moduleName;
            }
            if ($decl->license !== null && $decl->license->tier === 'pro') {
                $section = $decl->license->section;
                if (!isset($this->paidBySection[$section])) {
                    $this->paidBySection[$section] = [];
                }
                $this->paidBySection[$section][] = $decl->license;
            }
        }
    }

    private function loadOne(string $moduleName): ?ModuleDeclaration
    {
        try {
            $etcDir = $this->moduleDir->getDir($moduleName, 'etc');
        } catch (\Throwable $e) {
            return null;
        }
        if (!is_string($etcDir) || $etcDir === '') {
            return null;
        }

        $path = rtrim($etcDir, '/') . '/' . self::FILE_NAME;
        try {
            if (!$this->fs->isExists($path)) {
                return null;
            }
            $xml = $this->fs->fileGetContents($path);
            return $this->reader->readString($xml, $path);
        } catch (\Throwable $e) {
            $this->logger->error(
                'MageMe DeclarationRegistry: failed to read license.xml',
                ['module' => $moduleName, 'path' => $path, 'error' => $e->getMessage()]
            );
            return null;
        }
    }
}
