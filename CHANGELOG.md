# Changelog

All notable changes to **ModuleInstaller** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project roughly follows [Semantic Versioning](https://semver.org/).

Baseline comparison for **1.6.0**: previous public tree `1.5.1-Final`
(`L:\GitHub\00mambax7\moduleinstaller`, release date 2022-12-15).

---

## [1.6.0] — 2026-07-23

### Added

- **Module Sets** — named collections of modules for focused testing
  - Admin UI: `admin/sets.php` (create, edit, duplicate, delete)
  - Snapshot currently active modules into a set
  - Apply actions: activate, deactivate, install, uninstall, **Focus**
  - **Focus**: activate set members; deactivate all other non-protected modules
    (`system` and `moduleinstaller` are never touched)
  - Auto-snapshot of active modules before Focus; restore from dashboard / result
  - Dry-run plan / preview and confirmation for Focus / Uninstall
  - Missing-on-disk set members reported as notices and skipped
  - YAML storage under `XOOPS_VAR_PATH/configs/moduleinstaller/sets/`
  - Import / export set YAML
  - Classes: `ModuleSet`, `ModuleSetRepository`, `ModuleSetResolver`,
    `ModuleSetApplier` (`class/Set/`)
- **ModuleActionService** — shared bulk install / uninstall / activate /
  deactivate / update that continues after per-module failures
- **ModuleActionResult**, **ModuleCatalog**, **AdminBulkPage** helpers
- **mtools 1.2.0** consumer integration
  - `bootstrap.php` + `include/mtools_dependency.php` (`ConsumerRuntime`)
  - `onInstall` / `onUpdate` / `onUninstall` hooks in `xoops_version.php`
  - `min_modules` requires mtools ≥ 1.2.0 (installed; active not required)
- Installer **home dashboard** (counts, updates, last Focus snapshot)
- **Sample data**: mtools sample buttons load/export/clear sample **module sets**
  (not DB tables); shipped set **PM, Profile & Protector**
  (`testdata/english/sets/pm-profile-protector.yml`)
- Preference **Show sample-data buttons?** (`displaySampleButton`)
- Bulk UX: filter, sticky **N selected**, empty-selection guard, status badges,
  Apply plan summary, tooltips, keyboard `/` and `A`, logo/name click toggle
- Update tab: pre-select version mismatches; “show only modules that need update”
- Apply set dropdown on bulk pages
- Scoped admin CSS (`assets/css/admin.css`); fixed `_CO_MODULEINSTALLER_*` language constants
- PHPUnit suite (`phpunit.xml.dist`, `tests/Unit/…`, `tests/helpers/RequiresXoops`)
- **module-devops baseline**: `composer.json` QA scripts, `phpstan.neon.dist` + stubs,
  PHP-CS-Fixer, Rector, `.github` CI/release/Dependabot, `.editorconfig`, SonarCloud opt-in
- End-user **docs/TUTORIAL.md**; **docs/TODO.md**; **docs/MTOOLS-INVESTIGATION.md**

### Changed

- Platform requirements: **PHP ≥ 8.2**, **XOOPS ≥ 2.7.0** (was PHP 7.4 / XOOPS 2.5.10)
- Version string: `1.6.0-Stable` (was `1.5.1-Final`)
- Bulk admin pages use **CP chrome only** via `AdminBulkPage::serve()` (thin tab wrappers)
- Report HTML lives in presentation (`AdminBulkPage::renderReport`), not the domain service
- CSRF token on bulk POST; cache-bust via `AdminBulkPage::ASSET_VERSION`
- Input handling via **Xmf\Request** instead of raw `$_REQUEST`
- CP look & feel from ModuleAdmin + admin theme; module CSS limited to `.installer-*`
- Language `common.php` uses fixed `_CO_MODULEINSTALLER_*` defines (no dynamic dirname)
- Module Sets list/edit polished (blue panels, logos, striping, filter tools)

### Fixed

- Null-safety on activate/deactivate when a dirname is missing
- Dual HTML / nested `install_tpl` shell on bulk admin tabs
- First-row Yes/No radio layout; crowded logos; selection highlight vs theme even/odd
- Title “(N selected)” only on bulk list pages (not Home)

### Removed

- **`extras/`** folder (legacy core `modulesadmin` patches) — bulk Update uses core helpers
- **Site-install wizard baggage**: `InstallWizard`, `common.inc.php`, `install_tpl.php`,
  `include/page.php`, `include/config.php`, `language/english/install.php`,
  `assets/js/prototype.js`, incomplete `InstallWizardTest`
- **Local `class/Common/*` copies** (Breadcrumb, Configurator, FilesManagement, ServerStats,
  SysUtility, VersionChecks) and their unit tests — use **mtools** `Common\*` instead;
  `Utility` extends `Mtools\Common\SysUtility`

### Security

- CSRF token checks on module-set mutations and bulk forms
- Set storage directory written with deny-all `.htaccess` when created

### Migration notes (from 1.5.1)

1. Install **mtools ≥ 1.2.0** (does not need to be active).
2. Replace module files and run **Update** on ModuleInstaller in System → Modules.
3. Confirm PHP ≥ 8.2 and XOOPS ≥ 2.7.0.
4. Open **Admin → Installer → Module Sets** (and optional sample sets on Home).

---

## [1.5.1] — 2022-12-15

### Fixed

- PHP 8.2 cosmetics and fixes

### [1.5.1-beta.1] — 2022-02-20

- `index.html` in `/preloads`

---

## [1.05] — 2021-08-08

- `(float)` cast for `$module->getInfo('version')` in updates
- Final release

---

## [1.05 Beta 1] — 2021-02-14

- Type hints / PHP 8.0 compatibility pass

---

## [1.04 Final] — 2019-12-12

- XOOPS 2.5.10 Final compatibility

---

## [1.04 RC 1] — 2019-07-23

- XOOPS 2.5.10 RC 1 compatibility

---

## [1.04 Beta 1] — 2019-01-13

- XOOPS 2.5.10 Beta 1 compatibility

---

## [1.03 Final] — 2018-07-12

- XOOPS 2.5.9 Final

---

## [1.03 RC 1] — 2018-03-17

- XOOPS 2.5.9 RC

---

## [1.02 Final] — 2017-08-03

- XOOPS 2.5.9 Beta 1

---

## [1.02 RC 1] — 2017-07-12

- XOOPS 2.5.9 Alpha

---

## [1.01 Final] — 2016-12-26

- PHP 7, HTML 5, XOOPS 2.5.8

---

## [1.0 Final] — 2013-08-07

- Moved to XoopsModules25x GitHub repository

---

## [1.0 Beta] — 2013-06-16

- Initial release as a standalone module (extracted from core installer concepts)
