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

    /** utm_content values — where in the panel the click came from. */
    public const PLACEMENT_HEADER = 'header_button';
    public const PLACEMENT_PLATE  = 'plate_button';
    public const PLACEMENT_ROW    = 'row_';

    /**
     * @param array<string, mixed> $entry catalog entry from modules.json
     * @param bool $panelExpandedByDefault whether the panel's server-rendered default is expanded
     * @return array{renewUrl: string|null, getProUrl: string|null, proPlateUrl: string|null, purchaseUrl: string|null, buyUrl: string|null}
     */
    public function build(array $entry, ?LicenseInfo $license, bool $panelExpandedByDefault = false): array
    {
        return [
            'renewUrl'    => $this->renewUrl($entry, $license),
            'getProUrl'   => $this->proUrl($entry, $license, $panelExpandedByDefault, self::PLACEMENT_HEADER),
            'proPlateUrl' => $this->proUrl($entry, $license, $panelExpandedByDefault, self::PLACEMENT_PLATE),
            'purchaseUrl' => $this->purchaseUrl($entry),
            'buyUrl'      => $this->buyUrl($entry, $license),
        ];
    }

    /**
     * Per-row upgrade link for an uninstalled paid add-on. Same destination as the plate CTA,
     * tagged with the row it was clicked from so the nine "Requires Pro" rows are measurable
     * against each other.
     *
     * Add-ons have no pages of their own — they are all part of Pro. What differs per row is
     * the section of the suite page it opens: the add-on's catalog `url` carries the anchor of
     * its feature block, so a row lands on the capability it sells instead of the page top.
     *
     * @param array<string, mixed> $entry core catalog entry (the suite's purchase_url lives there)
     * @param array<string, mixed> $addonEntry the add-on's own catalog entry, when available
     */
    public function proUrlForRow(
        array $entry,
        ?LicenseInfo $license,
        bool $panelExpandedByDefault,
        string $addonModuleName,
        array $addonEntry = []
    ): ?string {
        $slug = strtolower((string)preg_replace(
            '/[^a-z0-9]+/i',
            '_',
            (string)preg_replace('/^MageMe_/', '', $addonModuleName)
        ));
        $target = isset($addonEntry['url']) && is_string($addonEntry['url']) && $addonEntry['url'] !== ''
            ? $addonEntry['url']
            : null;

        return $this->proUrl(
            $entry,
            $license,
            $panelExpandedByDefault,
            self::PLACEMENT_ROW . $slug,
            $target
        );
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
            'utm_content'  => 'license_hint_pricing_page',
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
            'utm_content'  => 'license_hint_one_click_buy',
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
     * @param bool $panelExpandedByDefault tags the click with the panel's SERVER default.
     *             It is not the state at click time — localStorage can flip the panel first.
     * @param string $placement which of the panel's upgrade surfaces was clicked.
     * @param string|null $overrideUrl destination for this placement — the suite's purchase page
     *                                 is used when the placement has none of its own.
     */
    private function proUrl(
        array $entry,
        ?LicenseInfo $license,
        bool $panelExpandedByDefault,
        string $placement,
        ?string $overrideUrl = null
    ): ?string {
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
        if ($overrideUrl !== null) {
            $url = $overrideUrl;
        }
        [$url, $fragment] = $this->splitFragment($url);
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . http_build_query([
            'utm_source'   => 'admin',
            'utm_medium'   => 'ecosystem',
            'utm_campaign' => 'getpro',
            'utm_content'  => $placement,
            'utm_term'     => $panelExpandedByDefault ? 'default_expanded' : 'default_collapsed',
        ]) . $fragment;
    }

    /**
     * Query parameters belong before the fragment — appended after it they end up inside the
     * anchor and neither the anchor nor the utm tags survive.
     *
     * @return array{0: string, 1: string}
     */
    private function splitFragment(string $url): array
    {
        $hash = strpos($url, '#');

        return $hash === false
            ? [$url, '']
            : [substr($url, 0, $hash), substr($url, $hash)];
    }
}
