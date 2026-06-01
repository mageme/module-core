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

class DeclarationReader
{
    /** @var string */
    private $xsdPath;

    public function __construct()
    {
        $resolved = realpath(__DIR__ . '/../../etc/license.xsd');
        $this->xsdPath = $resolved !== false ? $resolved : __DIR__ . '/../../etc/license.xsd';
    }

    /**
     * @throws \InvalidArgumentException on parse error or schema violation
     */
    public function readString(string $xml, string $sourceLabel): ModuleDeclaration
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$dom->loadXML($xml)) {
            $err = $this->collectLibXmlErrors();
            throw new \InvalidArgumentException(
                sprintf('XML parse error in %s: %s', $sourceLabel, $err)
            );
        }

        if (!$dom->schemaValidate($this->xsdPath)) {
            $err = $this->collectLibXmlErrors();
            throw new \InvalidArgumentException(
                sprintf('%s failed schema validation: %s', $sourceLabel, $err)
            );
        }

        $moduleEl = $dom->documentElement->getElementsByTagName('module')->item(0);
        if (!$moduleEl instanceof \DOMElement) {
            throw new \InvalidArgumentException(
                sprintf('%s: <module> element missing', $sourceLabel)
            );
        }

        $moduleName = (string) $moduleEl->getAttribute('name');

        $section = null;
        $sectionEl = $moduleEl->getElementsByTagName('section')->item(0);
        if ($sectionEl instanceof \DOMElement) {
            $section = new SectionDeclaration((string) $sectionEl->getAttribute('name'));
        }

        $license = null;
        $licenseEl = $moduleEl->getElementsByTagName('license')->item(0);
        if ($licenseEl instanceof \DOMElement) {
            $codes = [];
            foreach ($licenseEl->getElementsByTagName('code') as $codeEl) {
                /** @var \DOMElement $codeEl */
                $codes[(string) $codeEl->getAttribute('platform')] = (string) $codeEl->textContent;
            }
            $license = new LicenseDeclaration(
                (string) $licenseEl->getAttribute('section'),
                (string) $licenseEl->getAttribute('tier'),
                $codes
            );
        }

        return new ModuleDeclaration($moduleName, $section, $license);
    }

    private function collectLibXmlErrors(): string
    {
        $messages = [];
        foreach (libxml_get_errors() as $err) {
            $messages[] = trim($err->message);
        }
        libxml_clear_errors();
        return $messages === [] ? '(no detail)' : implode('; ', $messages);
    }
}
