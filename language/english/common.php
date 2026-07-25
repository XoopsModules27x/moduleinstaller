<?php declare(strict_types=1);

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.
*/

/**
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author    Michael Beck (mamba)
 */

\define('_CO_MODULEINSTALLER_GDLIBSTATUS', 'GD library support: ');
\define('_CO_MODULEINSTALLER_GDLIBVERSION', 'GD Library version: ');
\define('_CO_MODULEINSTALLER_GDOFF', "<span style='font-weight: bold;'>Disabled</span> (No thumbnails available)");
\define('_CO_MODULEINSTALLER_GDON', "<span style='font-weight: bold;'>Enabled</span> (Thumbsnails available)");
\define('_CO_MODULEINSTALLER_IMAGEINFO', 'Server status');
\define('_CO_MODULEINSTALLER_MAXPOSTSIZE', 'Max post size permitted (post_max_size directive in php.ini): ');
\define('_CO_MODULEINSTALLER_MAXUPLOADSIZE', 'Max upload size permitted (upload_max_filesize directive in php.ini): ');
\define('_CO_MODULEINSTALLER_MEMORYLIMIT', 'Memory limit (memory_limit directive in php.ini): ');
\define('_CO_MODULEINSTALLER_METAVERSION', "<span style='font-weight: bold;'>Downloads meta version:</span> ");
\define('_CO_MODULEINSTALLER_OFF', "<span style='font-weight: bold;'>OFF</span>");
\define('_CO_MODULEINSTALLER_ON', "<span style='font-weight: bold;'>ON</span>");
\define('_CO_MODULEINSTALLER_SERVERPATH', 'Server path to XOOPS root: ');
\define('_CO_MODULEINSTALLER_SERVERUPLOADSTATUS', 'Server uploads status: ');
\define('_CO_MODULEINSTALLER_SPHPINI', "<span style='font-weight: bold;'>Information taken from PHP ini file:</span>");
\define('_CO_MODULEINSTALLER_UPLOADPATHDSC', 'Note. Upload path *MUST* contain the full server path of your upload folder.');

\define('_CO_MODULEINSTALLER_PRINT', "<span style='font-weight: bold;'>Print</span>");
\define('_CO_MODULEINSTALLER_PDF', "<span style='font-weight: bold;'>Create PDF</span>");

\define('_CO_MODULEINSTALLER_UPGRADEFAILED0', "Update failed - couldn't rename field '%s'");
\define('_CO_MODULEINSTALLER_UPGRADEFAILED1', "Update failed - couldn't add new fields");
\define('_CO_MODULEINSTALLER_UPGRADEFAILED2', "Update failed - couldn't rename table '%s'");
\define('_CO_MODULEINSTALLER_ERROR_COLUMN', 'Could not create column in database : %s');
\define('_CO_MODULEINSTALLER_ERROR_BAD_XOOPS', 'This module requires XOOPS %s+ (%s installed)');
\define('_CO_MODULEINSTALLER_ERROR_BAD_PHP', 'This module requires PHP version %s+ (%s installed)');
\define('_CO_MODULEINSTALLER_ERROR_TAG_REMOVAL', 'Could not remove tags from Tag Module');

\define('_CO_MODULEINSTALLER_FOLDERS_DELETED_OK', 'Upload Folders have been deleted');

// Error Msgs
\define('_CO_MODULEINSTALLER_ERROR_BAD_DEL_PATH', 'Could not delete %s directory');
\define('_CO_MODULEINSTALLER_ERROR_BAD_REMOVE', 'Could not delete %s');
\define('_CO_MODULEINSTALLER_ERROR_NO_PLUGIN', 'Could not load plugin');

//Help
\define('_CO_MODULEINSTALLER_DIRNAME', basename(dirname(__DIR__, 2)));
\define('_CO_MODULEINSTALLER_HELP_HEADER', __DIR__ . '/help/helpheader.tpl');
\define('_CO_MODULEINSTALLER_BACK_2_ADMIN', 'Back to Administration of ');
\define('_CO_MODULEINSTALLER_OVERVIEW', 'Overview');

//\define('_CO_MODULEINSTALLER_HELP_DIR', __DIR__);

//help multipage
\define('_CO_MODULEINSTALLER_DISCLAIMER', 'Disclaimer');
\define('_CO_MODULEINSTALLER_LICENSE', 'License');
\define('_CO_MODULEINSTALLER_SUPPORT', 'Support');

// Sample data = sample module sets (YAML under testdata/), not DB tables
\define('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA', 'Load sample module sets');
\define('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_CONFIRM', 'Import sample module sets? Existing sets with the same id (e.g. PM / Profile / Protector) will be overwritten.');
\define('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_SUCCESS', 'Sample module sets imported successfully.');
\define('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_COUNT', 'Imported %d sample module set(s).');
\define('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_FAILURE', 'Sample module set import failed (no valid YAML under testdata/).');
\define('_CO_MODULEINSTALLER_SAVE_SAMPLEDATA', 'Export module sets to YAML');
\define('_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_CONFIRM', 'Export all current module sets to a new timestamped folder under testdata/?');
\define('_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_SUCCESS', 'Module sets exported to YAML successfully.');
\define('_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_COUNT', 'Exported %1$d set(s) to %2$s.');
\define('_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA', 'Remove sample module sets');
\define('_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_OK', 'Sample module sets removed.');
\define('_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_COUNT', 'Removed %d sample module set(s).');
\define('_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_CONFIRM', 'Remove the shipped sample module sets (e.g. PM, Profile & Protector)? Other custom sets are kept.');
\define('_CO_MODULEINSTALLER_EXPORT_SCHEMA', 'Export DB Schema to YAML');
\define('_CO_MODULEINSTALLER_EXPORT_SCHEMA_SUCCESS', 'Export DB Schema to YAML was a success');
\define('_CO_MODULEINSTALLER_EXPORT_SCHEMA_ERROR', 'ERROR: Export of module sets failed');
\define('_CO_MODULEINSTALLER_SHOW_SAMPLE_BUTTON', 'Show sample-data buttons?');
\define('_CO_MODULEINSTALLER_SHOW_SAMPLE_BUTTON_DESC', 'If yes, Load / Save / Clear sample module set buttons appear on the Installer home page.');
\define('_CO_MODULEINSTALLER_HIDE_SAMPLEDATA_BUTTONS', 'Hide sample-data buttons');
\define('_CO_MODULEINSTALLER_SHOW_SAMPLEDATA_BUTTONS', 'Show sample-data buttons');

\define('_CO_MODULEINSTALLER_CONFIRM', 'Confirm');

//letter choice
\define('_CO_MODULEINSTALLER_BROWSETOTOPIC', "<span style='font-weight: bold;'>Browse items alphabetically</span>");
\define('_CO_MODULEINSTALLER_OTHER', 'Other');
\define('_CO_MODULEINSTALLER_ALL', 'All');

// block defines
\define('_CO_MODULEINSTALLER_ACCESSRIGHTS', 'Access Rights');
\define('_CO_MODULEINSTALLER_ACTION', 'Action');
\define('_CO_MODULEINSTALLER_ACTIVERIGHTS', 'Active Rights');
\define('_CO_MODULEINSTALLER_BADMIN', 'Block Administration');
\define('_CO_MODULEINSTALLER_BLKDESC', 'Description');
\define('_CO_MODULEINSTALLER_CBCENTER', 'Center Middle');
\define('_CO_MODULEINSTALLER_CBLEFT', 'Center Left');
\define('_CO_MODULEINSTALLER_CBRIGHT', 'Center Right');
\define('_CO_MODULEINSTALLER_SBLEFT', 'Left');
\define('_CO_MODULEINSTALLER_SBRIGHT', 'Right');
\define('_CO_MODULEINSTALLER_SIDE', 'Alignment');
\define('_CO_MODULEINSTALLER_TITLE', 'Title');
\define('_CO_MODULEINSTALLER_VISIBLE', 'Visible');
\define('_CO_MODULEINSTALLER_VISIBLEIN', 'Visible In');
\define('_CO_MODULEINSTALLER_WEIGHT', 'Weight');

\define('_CO_MODULEINSTALLER_PERMISSIONS', 'Permissions');
\define('_CO_MODULEINSTALLER_BLOCKS', 'Blocks Admin');
\define('_CO_MODULEINSTALLER_BLOCKS_DESC', 'Blocks/Group Admin');

\define('_CO_MODULEINSTALLER_BLOCKS_MANAGMENT', 'Manage');
\define('_CO_MODULEINSTALLER_BLOCKS_ADDBLOCK', 'Add a new block');
\define('_CO_MODULEINSTALLER_BLOCKS_EDITBLOCK', 'Edit a block');
\define('_CO_MODULEINSTALLER_BLOCKS_CLONEBLOCK', 'Clone a block');

//myblocksadmin
\define('_CO_MODULEINSTALLER_AGDS', 'Admin Groups');
\define('_CO_MODULEINSTALLER_BCACHETIME', 'Cache Time');
\define('_CO_MODULEINSTALLER_BLOCKS_ADMIN', 'Blocks Admin');
\define('_CO_MODULEINSTALLER_UPDATE_SUCCESS', 'Update successful');

//Template Admin
\define('_CO_MODULEINSTALLER_TPLSETS', 'Template Management');
\define('_CO_MODULEINSTALLER_GENERATE', 'Generate');
\define('_CO_MODULEINSTALLER_FILENAME', 'File Name');

//Menu
\define('_CO_MODULEINSTALLER_ADMENU_MIGRATE', 'Migrate');
\define('_CO_MODULEINSTALLER_FOLDER_YES', 'Folder "%s" exist');
\define('_CO_MODULEINSTALLER_FOLDER_NO', 'Folder "%s" does not exist. Create the specified folder with CHMOD 777.');
\define('_CO_MODULEINSTALLER_SHOW_DEV_TOOLS', 'Show Development Tools Button?');
\define('_CO_MODULEINSTALLER_SHOW_DEV_TOOLS_DESC', 'If yes, the "Migrate" Tab and other Development tools will be visible to the Admin.');
\define('_CO_MODULEINSTALLER_ADMENU_FEEDBACK', 'Feedback');
\define('_CO_MODULEINSTALLER_MIGRATE_OK', 'Database migrated to current schema.');
\define('_CO_MODULEINSTALLER_MIGRATE_WARNING', 'Warning! This is intended for developers only. Confirm write schema file from current database.');
\define('_CO_MODULEINSTALLER_MIGRATE_SCHEMA_OK', 'Current schema file written');

//Latest Version Check
\define('_CO_MODULEINSTALLER_NEW_VERSION', 'New Version: ');

//DirectoryChecker
\define('_CO_MODULEINSTALLER_AVAILABLE', "<span style='color: green;'>Available</span>");
\define('_CO_MODULEINSTALLER_NOTAVAILABLE', "<span style='color: red;'>Not available</span>");
\define('_CO_MODULEINSTALLER_NOTWRITABLE', "<span style='color: red;'>Should have permission ( %d ), but it has ( %d )</span>");
\define('_CO_MODULEINSTALLER_CREATETHEDIR', 'Create it');
\define('_CO_MODULEINSTALLER_SETMPERM', 'Set the permission');
\define('_CO_MODULEINSTALLER_DIRCREATED', 'The directory has been created');
\define('_CO_MODULEINSTALLER_DIRNOTCREATED', 'The directory cannot be created');
\define('_CO_MODULEINSTALLER_PERMSET', 'The permission has been set');
\define('_CO_MODULEINSTALLER_PERMNOTSET', 'The permission cannot be set');

//FileChecker
//\define('_CO_MODULEINSTALLER_AVAILABLE', "<span style='color: green;'>Available</span>");
//\define('_CO_MODULEINSTALLER_NOTAVAILABLE', "<span style='color: red;'>Not available</span>");
//\define('_CO_MODULEINSTALLER_NOTWRITABLE', "<span style='color: red;'>Should have permission ( %d ), but it has ( %d )</span>");
//\define('_CO_MODULEINSTALLER_COPYTHEFILE', 'Copy it');
//\define('_CO_MODULEINSTALLER_CREATETHEFILE', 'Create it');
//\define('_CO_MODULEINSTALLER_SETMPERM', 'Set the permission');

\define('_CO_MODULEINSTALLER_FILECOPIED', 'The file has been copied');
\define('_CO_MODULEINSTALLER_FILENOTCOPIED', 'The file cannot be copied');

//\define('_CO_MODULEINSTALLER_PERMSET', 'The permission has been set');
//\define('_CO_MODULEINSTALLER_PERMNOTSET', 'The permission cannot be set');

//image config
\define('_CO_MODULEINSTALLER_CONFIG_EXT_IMAGE', 'EXTERNAL Image configuration');

\define('_CO_MODULEINSTALLER_CONFIG_STYLING_START', '<span style="color: #FF0000; font-size: Small;  font-weight: bold;">:: ');
\define('_CO_MODULEINSTALLER_CONFIG_STYLING_END', ' ::</span> ');
\define('_CO_MODULEINSTALLER_CONFIG_STYLING_DESC_START', '<span style="color: #FF0000; font-size: Small;">');
\define('_CO_MODULEINSTALLER_CONFIG_STYLING_DESC_END', '</span> ');

\define('_CO_MODULEINSTALLER_PREFERENCE_BREAK_CONFIG_IMAGE', \_CO_MODULEINSTALLER_CONFIG_STYLING_START . \_CO_MODULEINSTALLER_CONFIG_EXT_IMAGE . \_CO_MODULEINSTALLER_CONFIG_STYLING_END);
\define('_CO_MODULEINSTALLER_IMAGE_WIDTH', 'Image Display Width');
\define('_CO_MODULEINSTALLER_IMAGE_WIDTH_DSC', 'Display width for image');
\define('_CO_MODULEINSTALLER_IMAGE_HEIGHT', 'Image Display Height');
\define('_CO_MODULEINSTALLER_IMAGE_HEIGHT_DSC', 'Display height for image');
\define('_CO_MODULEINSTALLER_IMAGE_CONFIG', '<span style="color: #FF0000; font-size: Small;  font-weight: bold;">--- EXTERNAL Image configuration ---</span> ');
\define('_CO_MODULEINSTALLER_IMAGE_CONFIG_DSC', '');
\define('_CO_MODULEINSTALLER_IMAGE_UPLOAD_PATH', 'Image Upload path');
\define('_CO_MODULEINSTALLER_IMAGE_UPLOAD_PATH_DSC', 'Path for uploading images');

\define('_CO_MODULEINSTALLER_IMAGE_FILE_SIZE', 'Image File Size (in Bytes)');
\define('_CO_MODULEINSTALLER_IMAGE_FILE_SIZE_DSC','The maximum file size of the image file (in Bytes)');

//Module Stats
\define('_CO_MODULEINSTALLER_STATS_SUMMARY', 'Module Statistics');
\define('_CO_MODULEINSTALLER_TOTAL_CATEGORIES', 'Categories:');
\define('_CO_MODULEINSTALLER_TOTAL_ITEMS', 'Items');
\define('_CO_MODULEINSTALLER_TOTAL_OFFLINE', 'Offline');
\define('_CO_MODULEINSTALLER_TOTAL_PUBLISHED', 'Published');
\define('_CO_MODULEINSTALLER_TOTAL_REJECTED', 'Rejected');
\define('_CO_MODULEINSTALLER_TOTAL_SUBMITTED', 'Submitted');

\define('_CO_MODULEINSTALLER_ERROR403', 'You are not allowed to view this page!');

//Preferences
\define('_CO_MODULEINSTALLER_TRUNCATE_LENGTH', 'Number of Characters to truncate to the long text field');
\define('_CO_MODULEINSTALLER_TRUNCATE_LENGTH_DESC', 'Set the maximum number of characters to truncate the long text fields');

\define('_CO_MODULEINSTALLER_DELETE_BLOCK_CONFIRM', 'Are you sure to delete this Block?');

//Cloning
\define('_CO_MODULEINSTALLER_CLONE', 'Clone');
\define('_CO_MODULEINSTALLER_CLONE_DSC', 'Cloning a module has never been this easy! Just type in the name you want for it and hit submit button!');
\define('_CO_MODULEINSTALLER_CLONE_TITLE', 'Clone %s');
\define('_CO_MODULEINSTALLER_CLONE_NAME', 'Choose a name for the new module');
\define('_CO_MODULEINSTALLER_CLONE_NAME_DSC', 'Do not use special characters! <br>Do not choose an existing module dirname or database table name!');
\define('_CO_MODULEINSTALLER_CLONE_INVALIDNAME', 'ERROR: Invalid module name, please try another one!');
\define('_CO_MODULEINSTALLER_CLONE_EXISTS', 'ERROR: Module name already taken, please try another one!');
\define('_CO_MODULEINSTALLER_CLONE_CONGRAT', 'Congratulations! %s was sucessfully created!<br>You may want to make changes in language files.');
\define('_CO_MODULEINSTALLER_CLONE_IMAGEFAIL', 'Attention, we failed creating the new module logo. Please consider modifying assets/images/logo_module.png manually!');
\define('_CO_MODULEINSTALLER_CLONE_FAIL', "Sorry, we failed in creating the new clone. Maybe you need to temporally set write permissions (CHMOD 777) to 'modules' folder and try again.");

//JSON-LD generation of www.schema.org
\define('_CO_MODULEINSTALLER_GENERATE_JSONLD', 'Generate Schema Markup through JSON LD');
\define('_CO_MODULEINSTALLER_GENERATE_JSONLD_DESC', 'Mark up your module with structured data to help search engines better understand the content of your web page');

//Repository not found
\define('_CO_MODULEINSTALLER_REPO_NOT_FOUND', 'Repository Not Found: ');
//Release not found
\define('_CO_MODULEINSTALLER_NO_REL_FOUND', 'Released Version Not Found: ');
//rename upload folder on uninstall
\define('_CO_MODULEINSTALLER_ERROR_FOLDER_RENAME_FAILED', 'Could not rename upload folder, please rename manually');

//TCPDF
\define('_CO_MODULEINSTALLER_ERROR_NO_PDF', 'TCPDF for XOOPS is not installed in /class/libraries/vendor/tecnickcom/tcpdf/ <br> Please read the /docs/readme.txt or click on the Help tab to learn how to get it!');

