README
===========

ModuleInstaller is a XOOPS admin module to install, uninstall, update, activate,
and deactivate modules in bulk, and to manage Module Sets for focused testing.

Version
-------
1.6.0 Stable (2026-07-23)
Previous public baseline: 1.5.1 Final (2022-12-15)

See CHANGELOG.md and docs/changelog.txt for the full 1.5.1 → 1.6.0 delta.


Requirements
------------
- XOOPS >= 2.5.12
- PHP >= 8.2
- mtools module >= 1.2.0 (installed; active not required)
- MySQL >= 5.5


Install / uninstall
-------------------
1. Install mtools >= 1.2.0 first. It must be INSTALLED (Admin -> System -> Modules),
   not merely present in modules/mtools/; it does not need to be activated.
2. Extract this module into ../modules/moduleinstaller
3. Install through Admin → System Module → Modules.

Detailed module install instructions:
https://xoops.gitbook.io/


Installer home
--------------
Dashboard counts, last Focus snapshot link, configuration check, and optional
sample-data buttons (preference: Show sample-data buttons?).


Sample module sets
------------------
On Installer home, use Load sample module sets to import shipped sets
(e.g. PM, Profile & Protector). Manage them under Module Sets.
Sample data is YAML module sets, not database tables.


Operating instructions (bulk tabs)
----------------------------------
i)   Open Admin → Installer
ii)  Choose a tab: Install, Uninstall, Update, Activate, or Deactivate
iii) Optional: Apply set (checks Yes for set members on this page)
iv)  Filter / Select All / Un-Select All; click logo or name to toggle
v)   Click Continue, review the result log

Update tab: version mismatches are pre-selected; optional “needs update only”.


Module Sets (1.6.0)
-------------------
Admin → Installer → Module Sets

- Create / edit / duplicate / delete named sets (YAML under
  XOOPS_VAR_PATH/configs/moduleinstaller/sets/)
- Snapshot currently active modules; import / export YAML
- Apply: Activate, Deactivate, Install, Uninstall, or Focus
- Focus activates set members and deactivates all other non-protected modules
  (system and moduleinstaller are never touched). An auto-snapshot is saved first.
- Modules listed in a set but missing on disk are skipped with a notice;
  apply never fatals.


UI notes
--------
Control Panel look & feel comes from XOOPS ModuleAdmin and the admin theme.
Module CSS (assets/css/admin.css) only styles installer helpers (selection
highlight, filter, Apply set / sets panels, sticky selection bar).


Upgrade from 1.5.1
------------------
1. Install mtools >= 1.2.0
2. Replace module files and run System → Modules → Update on ModuleInstaller
3. Ensure PHP 8.2+ and XOOPS 2.5.12+


Tutorial
--------
docs/TUTORIAL.md — full operator walkthrough (bulk tabs, Module Sets, Focus).
README.md and GitBook: https://xoops.gitbook.io/xoops-moduleinstaller/
