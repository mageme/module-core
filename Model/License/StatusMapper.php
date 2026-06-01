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

namespace MageMe\Core\Model\License;

class StatusMapper
{
    public function fromStoredConfig(array $stored): LicenseStatus
    {
        $isActive = !empty($stored['active']) && (string)$stored['active'] !== '0';
        $isDev = !empty($stored['development']) && (string)$stored['development'] !== '0';
        $validUntil = $stored['valid_until'] ?? null;
        if ($validUntil === '') {
            $validUntil = null;
        }
        return new LicenseStatus(
            state: $this->resolveState($isActive, $isDev, $validUntil),
            isActive: $isActive,
            isDev: $isDev,
            validUntil: $validUntil
        );
    }

    public function fromApiResponse(array $response): LicenseStatus
    {
        $success = !empty($response['success']);
        $isActive = $success && !empty($response['active']) && (string)$response['active'] !== '0';
        $isDev = $isActive && !empty($response['dev']) && (string)$response['dev'] !== '0';
        $validUntil = $response['support_expiry_date'] ?? null;
        if ($validUntil === '') {
            $validUntil = null;
        }
        return new LicenseStatus(
            state: $this->resolveState($isActive, $isDev, $validUntil),
            isActive: $isActive,
            isDev: $isDev,
            validUntil: $validUntil
        );
    }

    private function resolveState(bool $isActive, bool $isDev, ?string $validUntil): string
    {
        if (!$isActive) {
            return LicenseStatus::STATE_INACTIVE;
        }
        if ($isDev) {
            return LicenseStatus::STATE_DEV;
        }
        if ($validUntil !== null && preg_match('/^\d{4}-\d{2}-\d{2}/', $validUntil) && $validUntil < date('Y-m-d')) {
            return LicenseStatus::STATE_EXPIRED;
        }
        return LicenseStatus::STATE_ACTIVE;
    }
}
