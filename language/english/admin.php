<?php declare(strict_types=1);

// Text for Admin footer
define('_AM_ADMIN_FOOTER', "<div class='center smallsmall italic pad5'>Module Installer is maintained by the <a class='tooltip' rel='external' href='https://xoops.org/' title='Visit XOOPS Community'>XOOPS Community</a></div>");
define('_AM_MODULEINSTALLER_SELECT_ALL', 'Select All');
define('_AM_MODULEINSTALLER_SELECT_NONE', 'Un-Select All');
define('_AM_MODULEINSTALLER_MODULES_UNINSTALL', 'Uninstall');
define('_AM_MODULEINSTALLER_APPLY_SET', 'Apply set');
define('_AM_MODULEINSTALLER_APPLY_SET_PLACEHOLDER', '— Select a set —');
define('_AM_MODULEINSTALLER_APPLY_SET_EMPTY', 'No sets yet — create one under Module Sets');
define('_AM_MODULEINSTALLER_APPLY_SET_HINT', 'Checks Yes for set members listed on this page');

// Bulk pages (CP chrome — Phase 3)
define('_AM_MODULEINSTALLER_SUBMIT', 'Continue');
define('_AM_MODULEINSTALLER_BACK_HOME', 'Installer home');
define('_AM_MODULEINSTALLER_NO_MODULES', 'No modules found for this action.');
define('_AM_MODULEINSTALLER_NO_SELECTION_REPORT', 'No modules selected.');
define('_AM_MODULEINSTALLER_ERR_TOKEN', 'Invalid security token. Please reload the page and try again.');
define('_AM_MODULEINSTALLER_DONE_INSTALL', 'The following modules have been installed.');
define('_AM_MODULEINSTALLER_DONE_UNINSTALL', 'The following modules have been uninstalled.');
define('_AM_MODULEINSTALLER_DONE_ACTIVATE', 'The following modules have been activated.');
define('_AM_MODULEINSTALLER_DONE_DEACTIVATE', 'The following modules have been deactivated.');
define('_AM_MODULEINSTALLER_DONE_UPDATE', 'The following modules have been updated.');

// Module sets
define('_AM_MODULEINSTALLER_SET_INTRO', 'Module sets are named lists of modules for testing. Use Focus to keep only a set active (others deactivate, except system and ModuleInstaller). Missing modules are skipped with a notice.');
define('_AM_MODULEINSTALLER_SET_EMPTY', 'No module sets yet. Create one, or snapshot currently active modules.');
define('_AM_MODULEINSTALLER_SET_NEW', 'New set');
define('_AM_MODULEINSTALLER_SET_EDIT', 'Edit set');
define('_AM_MODULEINSTALLER_SET_NAME', 'Name');
define('_AM_MODULEINSTALLER_SET_DESC', 'Description');
define('_AM_MODULEINSTALLER_SET_MODULES', 'Modules');
define('_AM_MODULEINSTALLER_SET_MODULE', 'Module');
define('_AM_MODULEINSTALLER_SET_MISSING', 'Missing');
define('_AM_MODULEINSTALLER_SET_ACTIONS', 'Actions');
define('_AM_MODULEINSTALLER_SET_ACTION', 'Action');
define('_AM_MODULEINSTALLER_SET_APPLY', 'Apply');
define('_AM_MODULEINSTALLER_SET_DUPLICATE', 'Duplicate');
define('_AM_MODULEINSTALLER_SET_BACK_LIST', 'Back to sets');
define('_AM_MODULEINSTALLER_SET_FROM_ACTIVE', 'Snapshot active modules');
define('_AM_MODULEINSTALLER_SET_FROM_ACTIVE_DEFAULT', 'Active modules');
define('_AM_MODULEINSTALLER_SET_FROM_ACTIVE_DESC', 'Created from modules that were active at snapshot time.');
define('_AM_MODULEINSTALLER_SET_SAVED', 'Set "%s" saved.');
define('_AM_MODULEINSTALLER_SET_DELETED', 'Set deleted.');
define('_AM_MODULEINSTALLER_SET_DUPLICATED', 'Duplicated as "%s".');
define('_AM_MODULEINSTALLER_SET_ERR_NAME', 'Set name is required.');
define('_AM_MODULEINSTALLER_SET_ERR_NOTFOUND', 'Module set not found.');
define('_AM_MODULEINSTALLER_SET_ERR_ACTION', 'Invalid action.');
define('_AM_MODULEINSTALLER_SET_ERR_CONFIRM', 'Please confirm this action before continuing.');
define('_AM_MODULEINSTALLER_SET_DELETE_CONFIRM', 'Delete set "%s"? This only removes the saved list, not the modules themselves.');
define('_AM_MODULEINSTALLER_SET_MISSING_NOTICE', 'These modules are in the set but no longer on disk:');
define('_AM_MODULEINSTALLER_SET_MISSING_HINT', 'They will be ignored during apply. Uncheck them and save to remove them from the set.');
define('_AM_MODULEINSTALLER_SET_MISSING_COUNT', '%d set member(s) are missing on disk and will be skipped.');
define('_AM_MODULEINSTALLER_SET_STATUS', 'Status');
define('_AM_MODULEINSTALLER_SET_IN_SET', 'In set');
define('_AM_MODULEINSTALLER_SET_NOTICE', 'Notice');
define('_AM_MODULEINSTALLER_SET_REASON', 'Reason');
define('_AM_MODULEINSTALLER_SET_ST_ACTIVE', 'Active');
define('_AM_MODULEINSTALLER_SET_ST_INACTIVE', 'Installed (inactive)');
define('_AM_MODULEINSTALLER_SET_ST_NOT_INSTALLED', 'Not installed');
define('_AM_MODULEINSTALLER_SET_ST_MISSING', 'Missing on disk');
define('_AM_MODULEINSTALLER_SET_ST_PROTECTED', 'Protected');
define('_AM_MODULEINSTALLER_SET_APPLY_TITLE', 'Apply set: %s');
define('_AM_MODULEINSTALLER_SET_APPLY_INTRO', 'Preview the plan, then run. Focus deactivates modules outside the set (data is kept). Uninstall is destructive.');
define('_AM_MODULEINSTALLER_SET_ACT_FOCUS', 'Focus (activate set, deactivate others)');
define('_AM_MODULEINSTALLER_SET_ACT_ACTIVATE', 'Activate set members');
define('_AM_MODULEINSTALLER_SET_ACT_DEACTIVATE', 'Deactivate set members');
define('_AM_MODULEINSTALLER_SET_ACT_INSTALL', 'Install set members');
define('_AM_MODULEINSTALLER_SET_ACT_UNINSTALL', 'Uninstall set members');
define('_AM_MODULEINSTALLER_SET_PREVIEW', 'Preview plan');
define('_AM_MODULEINSTALLER_SET_PREVIEW_EMPTY', 'Nothing to do for this action with the current site state.');
define('_AM_MODULEINSTALLER_SET_PREVIEW_COUNT', '%d step(s) will run.');
define('_AM_MODULEINSTALLER_SET_MEMBER_STATUS', 'Set member status');
define('_AM_MODULEINSTALLER_SET_CONFIRM_DESTRUCTIVE', 'I understand this will change module active state (or uninstall). A snapshot is saved before Focus.');
define('_AM_MODULEINSTALLER_SET_CONFIRM_FOCUS', 'I understand Focus will activate set members and deactivate other non-protected modules (data is kept). A snapshot of currently active modules is saved first.');
define('_AM_MODULEINSTALLER_SET_CONFIRM_UNINSTALL', 'I understand Uninstall is destructive and may remove module data. Protected modules are never uninstalled.');
define('_AM_MODULEINSTALLER_SET_RUN', 'Run');
define('_AM_MODULEINSTALLER_SET_APPLY_DONE', 'Finished action "%2$s" on set "%1$s"');
define('_AM_MODULEINSTALLER_SET_NOTICES', 'Notices');
define('_AM_MODULEINSTALLER_SET_SNAPSHOT_SAVED', 'Auto-snapshot saved as id "%s".');
define('_AM_MODULEINSTALLER_SET_OPEN_SNAPSHOT', 'Open snapshot set');
define('_AM_MODULEINSTALLER_SET_RESTORE_SNAPSHOT', 'Restore previous actives (Focus this snapshot)');
define('_AM_MODULEINSTALLER_SET_PLAN_SUMMARY', 'Plan summary: activate %1$d · deactivate %2$d · install %3$d · uninstall %4$d · other %5$d');
define('_AM_MODULEINSTALLER_SET_TIP_FOCUS', 'Focus: only set members stay active; other non-protected modules are deactivated (not uninstalled). A snapshot is saved first.');
define('_AM_MODULEINSTALLER_SET_TIP_UNINSTALL', 'Uninstall removes modules from the site. This can delete module tables/data. Prefer Deactivate when you only need them off.');
define('_AM_MODULEINSTALLER_SET_TIP_APPLY', 'Preview the plan for the selected action, then confirm and Run.');
define('_AM_MODULEINSTALLER_SET_EXPORT', 'Export YAML');
define('_AM_MODULEINSTALLER_SET_IMPORT', 'Import set (YAML)');
define('_AM_MODULEINSTALLER_SET_IMPORT_BTN', 'Import');
define('_AM_MODULEINSTALLER_SET_IMPORTED', 'Imported set "%s".');
define('_AM_MODULEINSTALLER_SET_ERR_IMPORT', 'Import failed: invalid or empty YAML.');
define('_AM_MODULEINSTALLER_SET_FILTER', 'Filter modules');
define('_AM_MODULEINSTALLER_SET_FILTER_PLACEHOLDER', 'Type name or folder…');
define('_AM_MODULEINSTALLER_SET_FILTER_NONE', 'No modules match this filter.');
define('_AM_MODULEINSTALLER_SET_ERR_EMPTY_RUN', 'Nothing to run for this plan.');
define('_AM_MODULEINSTALLER_SELECTED_COUNT', '%d selected');
define('_AM_MODULEINSTALLER_ERR_NONE_SELECTED', 'Select at least one module (Yes) before continuing.');
define('_AM_MODULEINSTALLER_FILTER', 'Filter');
define('_AM_MODULEINSTALLER_FILTER_PLACEHOLDER', 'Filter by name or folder…');
define('_AM_MODULEINSTALLER_FILTER_HINT', 'Press / to focus filter · A selects all visible Yes');
define('_AM_MODULEINSTALLER_SHOW_UPDATES_ONLY', 'Show only modules that need update');
define('_AM_MODULEINSTALLER_PRESELECTED_UPDATES', 'Pre-selected modules whose version on disk differs from the database.');
define('_AM_MODULEINSTALLER_DASH_TITLE', 'Installer overview');
define('_AM_MODULEINSTALLER_DASH_SETS', 'Module sets');
define('_AM_MODULEINSTALLER_DASH_SETS_COUNT', '%d saved set(s)');
define('_AM_MODULEINSTALLER_DASH_UPDATES', 'Updates available');
define('_AM_MODULEINSTALLER_DASH_UPDATES_COUNT', '%d installed module(s) differ from disk');
define('_AM_MODULEINSTALLER_DASH_ACTIVE', 'Active modules');
define('_AM_MODULEINSTALLER_DASH_INSTALLED', 'Installed modules');
define('_AM_MODULEINSTALLER_DASH_ONDISK', 'On disk (folders)');
define('_AM_MODULEINSTALLER_DASH_LAST_SNAPSHOT', 'Last Focus snapshot');
define('_AM_MODULEINSTALLER_DASH_NO_SNAPSHOT', 'None yet — run Focus on a set to create one.');
define('_AM_MODULEINSTALLER_DASH_GO_INSTALL', 'Install modules');
define('_AM_MODULEINSTALLER_DASH_GO_UPDATE', 'Update modules');
define('_AM_MODULEINSTALLER_DASH_GO_SETS', 'Module sets');
define('_AM_MODULEINSTALLER_BADGE_ACTIVE', 'Active');
define('_AM_MODULEINSTALLER_BADGE_INACTIVE', 'Installed (inactive)');
define('_AM_MODULEINSTALLER_BADGE_NOT_INSTALLED', 'Not installed');
define('_AM_MODULEINSTALLER_BADGE_MISSING', 'Missing on disk');
define('_AM_MODULEINSTALLER_BADGE_PROTECTED', 'Protected');

// Bulk result report — one line per module in the report produced by
// ModuleActionService. These were English literals inside the service until now,
// which made the report untranslatable regardless of the language pack installed.
define('_AM_MODULEINSTALLER_RES_EMPTY_DIRNAME', 'Empty dirname');
define('_AM_MODULEINSTALLER_RES_PROTECTED', 'Protected module "%1$s": action "%2$s" is not allowed.');
define('_AM_MODULEINSTALLER_RES_UNKNOWN_ACTION', 'Unknown action: %s');
define('_AM_MODULEINSTALLER_RES_EXCEPTION', 'Exception: %s');
define('_AM_MODULEINSTALLER_RES_NOT_ON_DISK', 'Not found on disk');
define('_AM_MODULEINSTALLER_RES_ALREADY_INSTALLED', 'Already installed');
define('_AM_MODULEINSTALLER_RES_NOT_INSTALLED', 'Not installed');
define('_AM_MODULEINSTALLER_RES_NOT_INSTALLED_ACTIVATE', 'Not installed (cannot activate)');
define('_AM_MODULEINSTALLER_RES_NOT_INSTALLED_DEACTIVATE', 'Not installed (cannot deactivate)');
define('_AM_MODULEINSTALLER_RES_ALREADY_ACTIVE', 'Already active');
define('_AM_MODULEINSTALLER_RES_ALREADY_INACTIVE', 'Already inactive');
define('_AM_MODULEINSTALLER_RES_INVALID_MID', 'Invalid module id');
define('_AM_MODULEINSTALLER_RES_UNVERIFIED', 'Result could not be verified (module state unreadable): %s');
define('_AM_MODULEINSTALLER_RES_FAIL_INSTALL', 'Install did not complete');
define('_AM_MODULEINSTALLER_RES_FAIL_UNINSTALL', 'Uninstall did not complete');
define('_AM_MODULEINSTALLER_RES_FAIL_ACTIVATE', 'Activation did not take effect');
define('_AM_MODULEINSTALLER_RES_FAIL_DEACTIVATE', 'Deactivation did not take effect (start-page or protected module?)');
define('_AM_MODULEINSTALLER_RES_FAIL_UPDATE', 'Update did not complete');

// Set-resolver notices — same report, same reason they need constants.
define('_AM_MODULEINSTALLER_RES_ORPHANED', 'Installed in database but folder missing on disk');
define('_AM_MODULEINSTALLER_RES_REMOVED', 'Not found on disk (removed module)');
define('_AM_MODULEINSTALLER_RES_ON_DISK_NOT_INSTALLED', 'Present on disk but not installed');

// Module list chrome + module-set notices (round 4: the last English literals
// outside the language files).
define('_AM_MODULEINSTALLER_TOGGLE_SELECTION', 'Toggle selection');
define('_AM_MODULEINSTALLER_FOLDER_LABEL', 'folder: /%s');
define('_AM_MODULEINSTALLER_SET_SNAPSHOT_NOTICE', 'Saved snapshot: %1$s (%2$s)');
define('_AM_MODULEINSTALLER_SET_SNAPSHOT_NAME', 'Snapshot before Focus: %s');
define('_AM_MODULEINSTALLER_SET_REASON_MEMBER', 'Member of set');
define('_AM_MODULEINSTALLER_SET_REASON_FOCUS_ON', 'Focus: activate set member');
define('_AM_MODULEINSTALLER_SET_REASON_FOCUS_OFF', 'Focus: deactivate non-member');

// Set-apply recovery snapshot (round 5: the last hardcoded strings on this path).
define('_AM_MODULEINSTALLER_SET_SNAPSHOT_ABORTED', 'Aborted: could not save recovery snapshot before Focus (%s)');
define('_AM_MODULEINSTALLER_SET_SNAPSHOT_DESC', 'Auto-created before applying Focus on set "%s"');
