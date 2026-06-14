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

namespace MageMe\Core\Model\ModuleEcosystem;

use MageMe\Core\Model\License\LicenseStatus;

/**
 * Pure builder for the ecosystem-block CTA URLs (Renew / Get Pro).
 *
 * No Magento dependencies — unit-testable in isolation. Implements the
 * frozen URL contract from the design spec §6.A. Price and signature are
 * intentionally never part of the URL (price is derived server-side from
 * the serial).
 */
class CtaLinkBuilder
{
    public const DEFAULT_RENEW_URL = 'https://mageme.com/licenses/renew';

    /**
     * @param array<string, mixed> $entry catalog entry from modules.json
     * @return array{renewUrl: string|null, getProUrl: string|null, purchaseUrl: string|null, buyUrl: string|null}
     */
    public function build(array $entry, ?LicenseInfo $license): array
    {
        return [
            'renewUrl'    => $this->renewUrl($entry, $license),
            'getProUrl'   => $this->getProUrl($entry, $license),
            'purchaseUrl' => $this->purchaseUrl($entry),
            'buyUrl'      => $this->buyUrl($entry, $license),
        ];
    }

    /**
     * Raw purchase/pricing page for the suite (modules.json purchase_url),
     * UNGATED — unlike getProUrl this ignores tier and licence state. Used
     * for the subtle "no licence yet" hint next to the serial input (a pro
     * module is installed but unlicensed). Null when no purchase_url.
     *
     * @param array<string, mixed> $entry
     */
    private function purchaseUrl(array $entry): ?string
    {
        $url = isset($entry['purchase_url']) && is_string($entry['purchase_url']) ? $entry['purchase_url'] : '';
        if ($url === '') {
            return null;
        }
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . http_build_query([
            'utm_source'   => 'admin',
            'utm_medium'   => 'ecosystem',
            'utm_campaign' => 'buylicense',
        ]);
    }

    /**
     * One-click buy URL: deep-links to the store's guest buy endpoint that adds
     * the configured product to the cart. Requires buy_url + buy_sku in the
     * catalog entry AND a resolvable platform token (M2CE or M2EE) from the
     * license. Returns null when any required piece is missing.
     *
     * @param array<string, mixed> $entry
     */
    private function buyUrl(array $entry, ?LicenseInfo $license): ?string
    {
        $url = isset($entry['buy_url']) && is_string($entry['buy_url']) ? $entry['buy_url'] : '';
        $sku = isset($entry['buy_sku']) && is_string($entry['buy_sku']) ? $entry['buy_sku'] : '';
        if ($url === '' || $sku === '') {
            return null;
        }
        if ($license === null) {
            return null;
        }
        $edition = $license->magentoEdition;
        if ($edition !== 'M2CE' && $edition !== 'M2EE') {
            return null;
        }
        $base = rtrim($url, '/');
        $separator = strpos($base, '?') === false ? '?' : '&';

        return $base . $separator . http_build_query([
            'sku'          => $sku,
            'platform'     => $edition,
            'utm_source'   => 'admin',
            'utm_medium'   => 'ecosystem',
            'utm_campaign' => 'buylicense',
        ]);
    }

    /**
     * The serial is the only functional parameter — license type, platform
     * and expiry are already bound to it server-side, so no edition/domain
     * is sent. utm_* is campaign tracking only, not serial-bound data.
     *
     * @param array<string, mixed> $entry
     */
    private function renewUrl(array $entry, ?LicenseInfo $license): ?string
    {
        if ($license === null || !$license->supported) {
            return null;
        }
        if ($license->state !== LicenseStatus::STATE_EXPIRED) {
            return null;
        }
        $serial = (string)($license->serial ?? '');
        if ($serial === '') {
            return null;
        }
        $base = isset($entry['renew_url']) && is_string($entry['renew_url']) && $entry['renew_url'] !== ''
            ? $entry['renew_url']
            : self::DEFAULT_RENEW_URL;

        return $base . '?' . http_build_query([
            'serial'       => $serial,
            'utm_source'   => 'admin',
            'utm_medium'   => 'ecosystem',
            'utm_campaign' => 'renew',
        ]);
    }

    /**
     * Shown only when the Pro module for the suite is NOT installed, or is
     * disabled in app/etc/config.php (user decision, 2026-05-17).
     *
     * `LicenseInfo->supported` is exactly that signal: LicenseMetaResolver
     * sets it true only when an enabled pro module / pro license declaration
     * resolves for the suite, and it uses Magento's ModuleManager::isEnabled()
     * (which reads the config.php enabled list). So `supported === true` means
     * Pro is installed AND enabled → hide; otherwise (no paid licence
     * resolved — not installed or disabled) → show.
     *
     * @param array<string, mixed> $entry
     */
    private function getProUrl(array $entry, ?LicenseInfo $license): ?string
    {
        if ($license !== null && $license->supported) {
            return null;
        }

        $tier = isset($entry['tier']) && is_string($entry['tier']) ? $entry['tier'] : null;
        if ($tier !== 'free') {
            return null;
        }
        $url = isset($entry['purchase_url']) && is_string($entry['purchase_url']) ? $entry['purchase_url'] : '';
        if ($url === '') {
            return null;
        }
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . http_build_query([
            'utm_source'   => 'admin',
            'utm_medium'   => 'ecosystem',
            'utm_campaign' => 'getpro',
        ]);
    }
}
