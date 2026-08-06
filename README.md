![alt XOOPS CMS](https://xoops.org/images/logo.png)

# ModuleInstaller for [XOOPS CMS 2.7.1+](https://xoops.org)

[![XOOPS CMS Module](https://img.shields.io/badge/XOOPS%20CMS-Module-blue.svg)](https://xoops.org)
[![Software License](https://img.shields.io/badge/license-GPL-brightgreen.svg?style=flat)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mambax7/moduleinstaller.svg?style=flat)](https://scrutinizer-ci.com/g/mambax7/moduleinstaller/?branch=master)
[![Latest Pre-Release](https://img.shields.io/github/tag/XoopsModules25x/moduleinstaller.svg?style=flat)](https://github.com/XoopsModules25x/moduleinstaller/tags/)
[![Latest Version](https://img.shields.io/github/release/XoopsModules25x/moduleinstaller.svg?style=flat)](https://github.com/XoopsModules25x/moduleinstaller/releases/)

**ModuleInstaller** is a XOOPS admin module for managing other modules in bulk:
install, uninstall, update, activate, and deactivate — plus **Module Sets** for
focused testing (activate a named subset and deactivate everything else without
uninstalling).

| | |
|---|---|
| **Current version** | 1.7.0 Alpha 1 (2026-07-30) |
| **Previous baseline** | 1.5.1 Final (2022-12-15) |
| **License** | GNU GPL 2.0 |
| **Author** | Michael Beck (Mamba) |

Full history: [CHANGELOG.md](CHANGELOG.md) · [docs/changelog.txt](docs/changelog.txt) · [docs/lang_diff.txt](docs/lang_diff.txt)  
How-to: [docs/TUTORIAL.md](docs/TUTORIAL.md)

---

## Requirements

| Requirement | Minimum                                                    |
|-------------|------------------------------------------------------------|
| XOOPS | **2.7.0+**                                                 |
| PHP | **8.2+**                                                   |
| [mtools](https://github.com/XoopsModules25x/mtools) | **1.2.0+** (must be **installed**; does not need to be active) |
| MySQL | 5.5+                                                       |

> ⚠️ **mtools must be _installed_, not just present.** Extracting the mtools files into `modules/mtools/` is **not** enough — ModuleInstaller verifies mtools' *installed* version (≥ 1.2.0) via **Admin → System → Modules** and refuses to install if mtools is present on disk but not installed. mtools does **not** need to be activated (installed-but-inactive is fine).

> **1.5.1** required only PHP 7.4+ and XOOPS 2.5.10+ and did not depend on mtools.

---

## Features

### Bulk operations (admin tabs)

1. **Install** — install selected uninstalled modules  
2. **Update** — run module update scripts for selected installed modules (version mismatches pre-selected; optional “needs update only” filter)  
3. **Uninstall** — remove selected modules (`system` and `moduleinstaller` are protected)  
4. **Activate** / **Deactivate** — toggle active state without uninstalling  

**Workflow**

1. Open a bulk tab under **Admin → Installer**.  
2. Optionally **Apply set** to check Yes for members of a saved set on that page.  
3. Filter the list, use **Select All** / **Un-Select All**, or click logo/name to toggle.  
4. Light-green rows are selected; the title and sticky bar show **N selected**.  
5. Click **Continue**, review the result log.

Bulk **Update** uses the System modulesadmin helpers shipped with XOOPS core.

### Dashboard (Installer home)

- Counts for sets, updates available, active / installed / on-disk modules  
- Link to restore the last **Focus** snapshot  
- Optional **sample data** buttons (mtools `TestdataButtons`) controlled by preference **Show sample-data buttons?**

### Sample data (module sets)

On **Installer home**, load shipped sample **module sets** (YAML — not DB tables), including:

| Set | Modules |
|-----|---------|
| **PM, Profile & Protector** | `pm`, `profile`, `protector` |

Then open **Module Sets** to Install or Focus that set. Export / clear sample sets are available from the same buttons.

### Module Sets

**Admin → Installer → Module Sets**

Named lists of modules stored as YAML under:

```text
XOOPS_VAR_PATH/configs/moduleinstaller/sets/
```

| Action | Effect |
|--------|--------|
| Create / Edit / Duplicate / Delete | Manage set definitions (not the modules themselves) |
| Snapshot active | New set from modules currently active |
| Import / Export YAML | Share sets between sites |
| Activate / Deactivate / Install / Uninstall | Apply only to set members |
| **Focus** | Activate set members; deactivate all other non-protected modules |

- **Focus** saves an auto-snapshot of active modules first so you can restore later.  
- Members listed in a set but **missing on disk** are skipped with a notice; apply never fatals.  
- `system` and `moduleinstaller` are never uninstalled or force-deactivated by Focus.

### UI notes (1.6.0)

- Control Panel **look & feel** comes from XOOPS ModuleAdmin and the active admin theme.  
- Module CSS (`assets/css/admin.css`) only adds installer helpers (selection highlight, filter, sticky bar, set panels).  
- Keyboard: **`/`** focuses filter; **`A`** selects all visible Yes (when not typing in a field).

### Architecture notes

- `AdminBulkPage` — CP bulk UI (`serve()`), assets, Yes/No table, reports  
- `ModuleActionService` / `ModuleActionResult` — multi-module actions with per-item results  
- `ModuleCatalog` — installed / available / active inventory and candidates  
- `Set\*` — set model, YAML repository, resolver, applier  
- `bootstrap.php` + mtools `ConsumerRuntime` dependency check  
- Admin input via **Xmf\Request**; CSRF on bulk POST and set mutations  

---

## Installation

1. Install **mtools** ≥ 1.2.0 (leave inactive if you prefer).  
2. Extract this module into `modules/moduleinstaller/`.  
3. In XOOPS Admin → System → Modules, install **Installer**.  
4. Open **Admin → Installer** and use the tabs as needed.  

Standard XOOPS module install rules apply.  
Ops manual: [XOOPS Operations Manual](https://xoops.gitbook.io/).

### Upgrade from 1.5.1

1. Install mtools ≥ 1.2.0 if missing.  
2. Upload the new module files over the old tree.  
3. Run **Update** on ModuleInstaller in System → Modules.  
4. Confirm PHP ≥ 8.2 and XOOPS ≥ 2.7.0.  
5. (Optional) Load sample module sets from Installer home.  

---

## Development

DevOps baseline (same pattern as other XOOPS modules — `module-devops`):

```bash
cd modules/moduleinstaller
composer install
composer qa          # CS-Fixer check → PHPStan → Rector dry-run → PHPUnit
composer cs:fix     # auto-fix style
composer analyse     # PHPStan (phpstan.neon.dist)
composer test        # vendor/bin/phpunit
```

Without local `vendor/`, unit tests still boot via `preloads/autoloader.php` + site `xoops_lib` + sibling `mtools` when present.

- Unit tests: `tests/Unit/` · helpers: `tests/helpers/`  
- Static analysis: `phpstan.neon.dist`, `phpstan-bootstrap.php`, `stubs/xoops.stub`  
- Style: `.php-cs-fixer.dist.php` · Rector: `rector.php`  
- CI: `.github/workflows/` (CI, release ZIP via `git archive`, optional SonarCloud)  
- Roadmap: [docs/TODO.md](docs/TODO.md)  
- Contributing: [CONTRIBUTING.md](CONTRIBUTING.md) / [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md)  

---

## Documentation & community

- In-repo tutorial: [docs/TUTORIAL.md](docs/TUTORIAL.md)  
- Tutorial (GitBook): [xoops-moduleinstaller](https://xoops.gitbook.io/xoops-moduleinstaller/)  
- Tutorial source: [XoopsDocs/moduleinstaller-tutorial](https://github.com/XoopsDocs/moduleinstaller-tutorial)  
- Translations: [Transifex](https://www.transifex.com/xoops)  
- Project site: [https://xoops.org](https://xoops.org)  
- Next-gen XOOPS: [https://github.com/XOOPS](https://github.com/XOOPS)  

---

## License

GNU General Public License v2.0 — see [docs/licence.txt](docs/licence.txt).
