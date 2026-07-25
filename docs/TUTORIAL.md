# ModuleInstaller Tutorial (1.6.0)

## What this module does

**ModuleInstaller** is an admin tool for managing other XOOPS modules in bulk:

- Install, uninstall, update, activate, and deactivate many modules at once  
- **Module Sets** — named lists of modules for focused testing (**Focus** keeps only that subset active without uninstalling the rest)  
- Optional **sample module sets** (e.g. PM + Profile + Protector) loadable from Installer home  

It does **not** replace System → Modules for single-module installs; it speeds up multi-module work and test isolation.

---

## Requirements

| Component | Minimum |
|-----------|---------|
| XOOPS | 2.5.12+ |
| PHP | 8.2+ |
| [mtools](https://github.com/XoopsModules25x/mtools) | 1.2.0+ (**installed**; does not need to be active) |

---

## Install and upgrade

> **mtools must be _installed_, not merely present.** Having the mtools files under `modules/mtools/` is **not** enough — ModuleInstaller verifies mtools' *installed* version (≥ 1.2.0) and refuses to install otherwise. mtools does **not** need to be activated.

1. Install **mtools** ≥ 1.2.0 via **Admin → System → Modules** if it is not already installed.  
2. Place this module under `modules/moduleinstaller/`.  
3. In **Admin → System → Modules**, install or **update** “Installer”.  
4. Open **Admin → Installer**.

---

## Installer home (dashboard)

- Cards for module sets, updates available, active / installed / on-disk counts  
- Link to restore the last **Focus** snapshot (if any)  
- Configuration check (PHP / MySQL / XOOPS / ModuleAdmin)  
- Optional **sample data** buttons (preference **Show sample-data buttons?**)

### Sample module sets

1. On **Installer home**, use **Load sample module sets** (confirm).  
2. Open **Module Sets** — you should see e.g. **PM, Profile & Protector**.  
3. **Apply** → Install and/or Focus as needed.  

Export / clear sample sets use the same sample-data controls. Sample data here is **YAML module sets**, not database table dumps.

---

## Bulk tabs (Install / Update / Uninstall / Activate / Deactivate)

Path: **Admin → Installer** → choose a tab.

1. Review the module list (even/odd striping; logos and description).  
2. Select **Yes** / **No** for each module.  
   - A **light green** row means **Yes** (selected).  
   - Click the **module logo** or **name/description** to toggle Yes/No.  
3. Use **Select All** / **Un-Select All** (top buttons and sticky bar at the bottom).  
4. Optional: **Apply set** (blue panel) — checks Yes for modules that belong to a saved set and appear on this page.  
5. Use the **Filter** box (type name or folder). Keyboard: **`/`** focuses filter; **`A`** selects all visible Yes (when not typing in a field).  
6. The page title shows **(N selected)**; the sticky bar shows the same count.  
7. Click **Continue**, read the result log.

### Update tab extras

- Modules whose disk version differs from the database are **pre-selected**.  
- Use **Show only modules that need update** to hide the rest.

### Protected modules

- **system** and **moduleinstaller** cannot be bulk-uninstalled.  
- Focus never force-deactivates them either.

### UI / theme

Look & feel of the Control Panel (nav, footer, default buttons) comes from **XOOPS ModuleAdmin** and your **admin theme**. ModuleInstaller only adds selection highlight, filter bar, Apply set panel, and related helpers.

---

## Module Sets

Path: **Admin → Installer → Module Sets**

Sets are stored as YAML under:

```text
XOOPS_VAR_PATH/configs/moduleinstaller/sets/
```

(not under the web document root; directory is created with deny-all rules when possible).

### List view

- **New set** / **Snapshot active modules**  
- Intro + storage path + **Import set (YAML)** in a soft blue panel  
- Table of sets with edit / apply / export / duplicate / delete actions  

### Create or edit a set

1. **New set** (or **Edit** an existing one).  
2. Enter **Name** and optional **Description** (blue details panel).  
3. Under **Modules**, use Select All / Un-Select All and the filter, then check members.  
   - Checked rows use the same **light green** highlight as bulk Yes.  
   - Click the **logo** or **module name** to toggle membership.  
4. **Submit** (or Cancel).

**Snapshot active modules** creates a set from everything currently active — useful as a “full site” restore point.

**Duplicate** copies a set under a new name. **Delete** removes only the saved list, not the modules on disk.

**Missing on disk**: a set can still list a dirname whose folder was removed. Those rows show a **Missing** status. Uncheck and save to drop them from the set. Apply always **skips** missing members (no fatal).

### Apply a set

1. Open **Apply** on a set.  
2. Choose an **Action** (changing the dropdown reloads the plan):

   | Action | Effect |
   |--------|--------|
   | **Focus** | Activate set members; deactivate all other non-protected modules (data kept). Auto-snapshot first. |
   | Activate | Activate installed members of the set |
   | Deactivate | Deactivate active members of the set |
   | Install | Install members that exist on disk but are not installed |
   | Uninstall | Uninstall members (destructive; data removal depends on each module) |

3. Review **Preview plan** (what will run) and **Set member status**.  
4. For **Focus** or **Uninstall**, tick the confirmation checkbox.  
5. Click **Run**.

After Focus, a success notice may include a link to open the **auto-snapshot** set so you can restore previous actives. The dashboard also links the last snapshot when available.

---

## Typical testing workflow

1. **Snapshot active modules** → e.g. “full site before test”.  
2. Create a set “billing test” with only the modules under test (or load a sample set).  
3. **Focus** that set → exercise the site with a minimal active set.  
4. When done, **Focus** (or activate members of) the snapshot set to restore.

---

## Troubleshooting

| Symptom | What to try |
|---------|-------------|
| Cannot install ModuleInstaller | Install mtools ≥ 1.2.0 first |
| Bulk Update errors | Confirm System modulesadmin is present; check PHP error log |
| “Missing on disk” in a set | Module folder gone; uncheck and save |
| Focus plan empty | Site already matches the set (nothing to change) |
| Module not listed on Install | Already installed — use Update / Activate instead |
| Sample buttons missing | Preference **Show sample-data buttons?**; update module if preference is new |
| Styles look wrong after upgrade | Hard-refresh browser (Ctrl+F5); CP chrome is theme-owned |

---

## See also

- [README.md](../README.md) — overview, architecture, development  
- [CHANGELOG.md](../CHANGELOG.md) / [changelog.txt](changelog.txt) — release history  
- Tutorial (GitBook): https://xoops.gitbook.io/xoops-moduleinstaller/  
