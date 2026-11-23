## 2.2.1

+ New: MageMe.loader.addCss returns a promise that settles once the stylesheet has loaded or failed, or after 3 seconds
+ New: MageMe.loader.cssArrivedLate signals a stylesheet that loaded after addCss stopped waiting
- Fix: the dialog shows the Question icon
- Fix: no focus outline around the dialog itself when it opens (Luma, Breeze)
- Fix: sticky elements on the page stay in place while a dialog is open
* Change: license updated to MageMe EULA v6

## 2.2.0

+ New: a shared announcement line for MageMe admin grids shows the newest unread MageMe message under the page title, with a dismiss for roles allowed to mark notifications read
+ New: license checks send the installed module version; when the license does not cover that version, one admin inbox message and a warning in the module settings panel offer to renew the license
+ New: a refused license activation explains why (serial not found, for another product, disabled, active on another installation, for another Magento edition) and links to My Products when the fix is made there
+ New: the license verdict tells modules when the installed version needs a license upgrade to the new product generation, and whether that upgrade is free or comes with a renewal
+ New: modules can ask whether the installation is a development one, judged by the storefront addresses of all store views
+ New: a license manager can accept the license agreement of a free upgrade to a new product generation right in the Magento admin; the new license verdict applies at once
+ New: the module panel, the activation notice and the license section read the MageMe module catalog for the product generation that is installed (WebForms 3 and WebForms 4 share a module); an add-on is offered for the generation of the module it extends, also when one of another major is installed (Mailchimp 3.0.5 under WebForms 4 offers 4.0.0)
+ New: when the installed version comes with a free upgrade, the license warning in the module settings shows the license agreement of its generation and an Accept and Upgrade button to admins allowed to accept license upgrades
+ New: Trusted Proxies setting (Stores > Configuration > MageMe Extensions > MageMe > Network, ACL "Network Settings"): when filled, the visitor IP is taken from X-Forwarded-For only for requests that come through the listed proxies (IPv4/IPv6 addresses and CIDR ranges)
- Fix: an activation that gets no answer from the license server leaves the current license, its serial and its version verdict as they were
- Fix: activating a production license after a development one no longer leaves the license marked as development
- Fix: the loader script is printed through the CSP secure renderer, so forms mount under a strict Content Security Policy
- Fix: the house dialog shows a text message as text; a caller that passed markup in text now sees it literally — pass it as html
- Fix: links in a dialog message are underlined
- Fix: a license check that the license server refuses for a reason other than the license itself (a passing fault, a rate limit) no longer switches the license off, whether cron or Check Again runs it; it is retried later, and Check Again answers `reason: no_verdict` (or `not_run` within the rate limit) so the page can say the check did not go through
- Fix: the license warning and the inbox message about a new product generation say how it is obtained: accepting the license agreement for a free upgrade, a renewal for a paid one (without the support date, which explained nothing), a neutral note when the license server does not say
- Fix: one admin inbox message per module about an uncovered version: Core keeps its id, a newer uncovered version replaces it, and it is marked read once the version is covered again (a renewal or an accepted upgrade) or the license is deactivated; it links to what resolves it (the renewal of the serial, the license agreement, or My Products) and is not shown as a grid announcement
- Fix: the license cron loads the module catalog when the cache lost it (at once when the last attempt got it, an hour after one that failed; 2-second timeout), so license messages and the settings panel get the module names and the license block back without anyone opening a MageMe page
- Fix: opening a MageMe settings page no longer holds the admin session while the module catalog is fetched; the catalog is asked for only when it is 6 hours old or older or the cache lost it (at once when the last attempt got it), not again within an hour of an attempt that failed, and an unchanged one is not rewritten
- Fix: the module panel shows the installed release from composer.json (4.0.0-beta.1, not the schema version 4.0.0), so a beta sees the release as an update
- Fix: module names in the "please activate your license" system message are escaped
- Fix: the "Open my licenses" button in the "Support expired" window did nothing; the window now uses Core's own dialog
- Fix: deactivating a license changes nothing unless the license server confirms it or says it holds no such installation (a license code such as not_found or in_use): without an answer, or on another refusal, the license stays active with its token, expiry, coverage and inbox message, and the admin sees why; on the deprecated LicenseHelper deactivation still switches the license off here, and without an answer from the server it keeps the token so the license can be deactivated again
- Fix: a license expiry date that is not a Y-m-d date is no longer stored (also for modules on the deprecated LicenseHelper)
- Fix: reading license.xml no longer changes the libxml error mode of the rest of the request
- Fix: DateHelper::formatDateStrf() formats Laminas patterns correctly (dd.MM.yyyy gives 23.10.2026)
- Fix: missing translations in en_US.csv; ACL title "View & Manage License Serials"
* Change: license status is checked by cron at most once a day (hourly while the installed version is not covered) instead of every time a module's settings page opens; admin pages no longer call the license server
* Change: the automatic license check does not switch a license off, and neither does Check Again, whatever the license server answers (also for modules on the deprecated LicenseHelper): only Deactivate does; a granted check refreshes the support date and the version coverage, and Check Again answers `refusal` with why the server did not grant the license
* Change: license checks of different installations are spread over the hour instead of all starting at the same minute
* Change: the visitor IP stored by MageMe modules comes from Magento's remote address with CF-Connecting-IP and X-Forwarded-For; the Client-IP header is no longer read and the result is always a valid IP
* Change: license requests without a resolvable module answer {success: false, errors: [...]} like the other license endpoints
* Change: the license controllers and the module panel data provider no longer take ModulesHelper, and Core no longer writes the license status cache nobody read; the deprecated LicenseHelper keeps its constructor for older modules
* Change: composer.json and module.xml declare Magento_Ui and Magento_Cron
* Change: conflict with mageme/module-webforms-3 below 3.5.5 (those versions reference a class removed in Core 2.0.0); WebForms 3 installed in app/code is not covered by this rule, update it to 3.5.5 or later before Core 2.2.0
* Change: SweetAlert2 and tingle.js carry their MIT license notices and are deprecated, kept for older modules, to be removed in Core 3.0
* Change: README lists everything a license request sends and the actual check schedule

## 2.1.4

- Fix: on older Magento 2.4 versions (such as 2.4.2) admin pages failed with a curl_setopt() error when the MageMe news feed was checked

## 2.1.3

- Fix: static content deploy no longer fails on Magento versions whose LESS compiler does not understand container queries — the module panel stylesheet now ships as plain CSS

## 2.1.2

- Fix: closing a dialog with Esc or a click outside it now cleans up the page the same way as the close button, so it can be opened again
- Fix: Esc closes the dialog on Luma product pages, where the image gallery used to swallow the key
* Change: the admin news feed now reports a random installation identifier, the application mode and whether the B2B suite is enabled, and reads module versions from module.xml when composer.json has none — see README, "Data sent with the news feed"

## 2.1.1

- Fix: "Requires Pro" is no longer shown on add-ons of a paid extension, or to a store that already has Pro — those add-ons read as not installed

## 2.1.0

+ New: the module panel counts the Pro add-ons your product line offers and opens straight to them
+ New: the admin news feed reports which MageMe extensions are installed, so announcements can be limited to the extensions in use — turn it off with `bin/magento config:set mageme/feed/send_modules 0`
+ New: an add-on folded into a newer major version is flagged in the module panel if you still have it installed, so it can be disabled and removed instead of sitting unused
+ New: an add-on you don't have yet links straight to the part of the product page that explains it
- Fix: help (?) icons in the ecosystem panel no longer distort on narrow screen widths
- Fix: ecosystem panel no longer shows an endless loading indicator when the module catalog can't be reached
- Fix: on a narrow admin window the module list drops to one column instead of cutting module names short
- Fix: license activation no longer hangs when the license server is unreachable — it now gives up and reports the problem
- Fix: an active license is no longer switched off when the license server cannot be reached or answers with an error — a check that does not go through leaves the stored license as it was
- Fix: the renewal link is no longer shown to admin users whose role is not allowed to see license serials — the serial travelled in that link
- Fix: keyboard focus and screen readers no longer reach the controls inside a collapsed panel
- Fix: the panel's remaining untranslated text can now be translated, and phrases from a dropped design were removed from the dictionary
* Change: the module panel now opens showing all modules, so add-ons you don't have yet are visible too
* Change: the module list was restyled — colour is kept for what needs attention: an available update or an add-on you don't own
* Change: the module panel in extension settings opens expanded when the suite has Pro features that are not installed, so they are visible without opening the panel — collapse it once and it stays collapsed
* Change: links from the module panel to mageme.com identify which link was used, so it is possible to tell an upgrade click apart from a licence-purchase click
* Change: the news feed is always requested over HTTPS and no longer sends the admin URL as the referer
* Change: unsuccessful feed responses are discarded instead of being parsed as news
* Change: add-ons a newer major version folded into its core are no longer offered to stores running that version
* Change: the upgrade button in the panel header steps aside while the Pro plate is on screen, so one upgrade action is offered at a time
* Change: license actions accept only the license section that belongs to the module named in the request
* Change: the module catalog is only accepted when the server answers successfully and the response really is a catalog, so a provider error page can no longer replace it

## 2.0.1

- Fix: stopped repeated license warnings from filling the system log
- Fix: license-activation admin notice now shows readable text instead of raw markup
- Fix: category selection fields in add-ons load correctly again
- Fix: admin license panel now respects the store's translations
- Fix: restored PHP 7.4 compatibility
* Other: loading indicator while the extensions panel loads
* Other: Marketplace coding-standard compliance, added tests, and internal cleanup

## 2.0.0

+ New: redesigned and reworked license activation and information UI
+ New: ecosystem block embedded in every MageMe config section — license activation plus version info for the module and its add-ons
+ New: Renew and Get Pro shortcuts in the module ecosystem panel
* Other: removed legacy Information & Licenses admin page
