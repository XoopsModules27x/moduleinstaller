<?php declare(strict_types=1);

/**
 * ModuleInstaller bootstrap.
 *
 * Consumer modules register their own namespace here, then load the public
 * mtools bootstrap instead of reaching into mtools/preloads internals.
 *
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License (GPL)
 */

require_once __DIR__ . '/preloads/autoloader.php';

if (\defined('XOOPS_ROOT_PATH')) {
    $mtoolsBootstrap = \XOOPS_ROOT_PATH . '/modules/mtools/bootstrap.php';
    if (\is_file($mtoolsBootstrap)) {
        require_once $mtoolsBootstrap;
    }
}

require_once __DIR__ . '/include/mtools_dependency.php';
