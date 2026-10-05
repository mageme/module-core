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
