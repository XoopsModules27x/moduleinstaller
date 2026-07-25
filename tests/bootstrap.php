<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap — XOOPS module DevOps baseline + ModuleInstaller extras.
 *
 * Two run modes, decided automatically:
 *   1. Integration: a *configured* XOOPS is reachable (XOOPS_ROOT_PATH points at a
 *      tree that actually has a mainfile.php). We boot it so handlers, Criteria and
 *      core classes are live. XOOPS_OVERLAY_INTEGRATION is then true.
 *   2. Unit-only: no bootable XOOPS. Composer autoload (or preloads fallback) +
 *      mtools public bootstrap when the sibling checkout exists.
 *
 * xoops-overlay:profile=core27
 */

$moduleRoot = \dirname(__DIR__);

// Prefer Composer autoload (DevOps / CI). Fall back to module preloads + site vendor.
$composerAutoload = $moduleRoot . '/vendor/autoload.php';
if (\is_file($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once $moduleRoot . '/preloads/autoloader.php';
    $xoopsVendor = \dirname($moduleRoot, 2) . '/xoops_lib/vendor/autoload.php';
    if (\is_file($xoopsVendor)) {
        require_once $xoopsVendor;
    }
}

require_once __DIR__ . '/helpers/RequiresXoops.php';

// ModuleInstaller consumes mtools (Utility extends Mtools\Common\SysUtility).
// In unit-only CI the sibling module PSR-4 is not registered unless we load it.
$mtoolsBootstrap = \dirname($moduleRoot) . '/mtools/bootstrap.php';
if (\is_file($mtoolsBootstrap)) {
    require_once $mtoolsBootstrap;
}

// Defaults used by ModuleSetRepository when tests do not inject a storage path.
if (! \defined('XOOPS_ROOT_PATH')) {
    // Prefer the site document root when this module lives under modules/
    $siteRoot = \dirname($moduleRoot, 2);
    \define('XOOPS_ROOT_PATH', \is_dir($siteRoot) ? $siteRoot : $moduleRoot);
}
if (! \defined('XOOPS_VAR_PATH')) {
    \define('XOOPS_VAR_PATH', \sys_get_temp_dir() . '/xoops_var_moduleinstaller_tests');
}
if (! \defined('XOOPS_URL')) {
    \define('XOOPS_URL', 'https://localhost');
}

$xoopsRoot = \getenv('XOOPS_ROOT_PATH') ?: '';
$integration = false;

if ($xoopsRoot !== '' && \is_dir($xoopsRoot)) {
    $mainfile = $xoopsRoot . '/mainfile.php';

    if (\is_file($mainfile)) {
        if (! \defined('XOOPS_ROOT_PATH') || \XOOPS_ROOT_PATH !== $xoopsRoot) {
            // Already defined above for unit defaults; only redefine if env points elsewhere
            // and we can boot — skip redefinition if already set.
        }
        $trust = \getenv('XOOPS_TRUST_PATH') ?: $xoopsRoot;
        if (! \defined('XOOPS_TRUST_PATH')) {
            \define('XOOPS_TRUST_PATH', $trust);
        }

        try {
            // @phpstan-ignore-next-line — runtime-only file outside analysis scope.
            require_once $mainfile;
            $integration = true;
        } catch (\Throwable $e) {
            \fwrite(\STDERR, "XOOPS present but not bootable ({$e->getMessage()}); running unit-only.\n");
            $integration = false;
        }
    } else {
        \fwrite(\STDERR, "XOOPS_ROOT_PATH set but no mainfile.php (unconfigured clone); running unit-only.\n");
    }
}

/**
 * Single source of truth for "are integration tests possible in this run?".
 * Read by the RequiresXoops trait.
 */
if (! \defined('XOOPS_OVERLAY_INTEGRATION')) {
    \define('XOOPS_OVERLAY_INTEGRATION', $integration);
}
