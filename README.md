# MageMe Core for Magento 2

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mageme/module-core.svg?style=flat-square)](https://packagist.org/packages/mageme/module-core)
[![Packagist Downloads](https://img.shields.io/packagist/dt/mageme/module-core.svg?style=flat-square)](https://packagist.org/packages/mageme/module-core)
[![Magento](https://img.shields.io/badge/Magento-2.4.x-EE672F.svg?style=flat-square)](https://magento.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%20–%208.5-777BB4.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/license-MageMe%20EULA-blue.svg?style=flat-square)](https://mageme.com/license/)

Foundation module for all [MageMe extensions](https://mageme.com) for Magento 2. Provides the shared infrastructure used across the MageMe product family: license activation, the admin module ecosystem panel, news feed integration, and shared utility libraries.

`mageme/module-core` is normally installed automatically as a dependency when you add any other MageMe extension. You rarely need to install it on its own.

## What it provides

- **License management** — activate, deactivate, and verify MageMe extension licenses against `license.mageme.com` from each module's admin configuration section. Status updates live on page load without blocking the admin UI.
- **Module ecosystem panel** — admin block injected into every MageMe module's configuration page. Shows the product family overview, installed add-ons, available updates, license status, and quick links to Renew, Get Pro, or Buy.
- **News feed** — surfaces MageMe announcements in the Magento admin notifications inbox through Magento's standard `AdminNotification` infrastructure.
- **Shared utilities** — locale-aware date and time formatting, client IP detection through proxies, static asset content inlining, area detection helpers, and a self-sizing admin multiselect form field.
- **Bundled JS libraries** — SweetAlert2 and Tingle modal, shared by other MageMe modules' storefront UI to avoid duplication.

## Data sent with the news feed

The daily news-feed request carries the list of enabled MageMe modules with their versions, plus an irreversible installation identifier derived from the store's base URL and installation date. It tells us which extensions are actually in use and lets announcements be limited to those extensions. The store domain, customer data, and order data are never sent.

To turn it off:

```bash
bin/magento config:set mageme/feed/send_modules 0
```

## Requirements

- Magento 2.4.x (Open Source or Commerce)
- PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, or 8.5
- PHP `curl`, `intl`, and `json` extensions

## Installation

In most cases `mageme/module-core` is pulled in automatically as a dependency of another MageMe extension. To install it standalone:

```
composer require mageme/module-core
bin/magento setup:upgrade
bin/magento cache:flush
```

## MageMe extensions that depend on this module

[**MageMe WebForms 3**](https://mageme.com/magento-2-form-builder.html) — Magento 2 form builder with conditional fields, multi-step layouts, file uploads, approval workflows, and a full integration stack:

- [Salesforce](https://github.com/mageme/module-webforms-3-salesforce) — create leads with campaign tracking
- [HubSpot](https://github.com/mageme/module-webforms-3-hubspot) — sync contacts, companies, and tickets
- [Zoho CRM & Desk](https://github.com/mageme/module-webforms-3-zoho) — create leads and helpdesk tickets
- [Freshdesk](https://github.com/mageme/module-webforms-3-freshdesk) — support tickets with agent routing
- [Zendesk](https://github.com/mageme/module-webforms-3-zendesk) — tickets with custom field types
- [Klaviyo](https://github.com/mageme/module-webforms-3-klaviyo) — profiles and email lists
- [Mailchimp](https://github.com/mageme/module-webforms-3-mailchimp) — audience subscriptions
- [Zapier](https://github.com/mageme/module-webforms-3-zapier) — connect forms to 7000+ apps

[**MageMe Hide Price**](https://mageme.com/magento-2-hide-price-extension.html) — control catalog visibility. Hide prices and the Add to Cart button from specific customer groups or per-product, with optional replacements: a sign-in button, an info alert, or a Request a Price form that emails the admin and the customer.

[**MageMe EasyQuote**](https://mageme.com/magento-2-quote-extension.html) — B2B request-a-quote workflow. Customers submit quote requests from the cart, admins respond with custom per-item pricing, volume tiers, custom shipping, and discounts. Full quote lifecycle, two-way messaging thread with attachments, PDF and CSV export, GraphQL and REST APIs.

[**MageMe EU Withdrawal**](https://mageme.com/magento-2-withdrawal-button-extension.html) — EU consumer right-of-withdrawal compliance. Adds the statutory "Withdraw from Contract" flow for B2C distance and off-premises contracts under Article 11a of Directive 2011/83/EU (as amended by Directive (EU) 2023/2673), effective 19 June 2026. Customers withdraw within the 14-day cooling-off period, with full audit trail, evidence capture, and admin approval workflow.

See the full catalog at [mageme.com](https://mageme.com).

## Custom Magento development

Need a feature an extension doesn't cover, or a bespoke Magento build? MageMe takes on custom extension development and integration work.

→ **[Custom Magento development](https://mageme.com/magento-services/custom-development)**

## Support

- Documentation: [docs.mageme.com](https://docs.mageme.com)
- Bug reports and feature requests: [GitHub Issues](https://github.com/mageme/module-core/issues)

## License

Governed by the **MageMe End User License Agreement** ([mageme.com/license](https://mageme.com/license/)). Distributed free of charge as the shared foundation for MageMe extensions.

---

**MageMe** builds Magento 2 and Adobe Commerce extensions for B2B merchants — form building, quoting, catalog control, and EU compliance.