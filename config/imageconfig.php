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

// extra module configs
/** @var array<string, mixed> $modversion */
$modversion['config'][] = [
    'name'        => 'imageConfigs',
    'title'       => '_CO_MODULEINSTALLER_IMAGE_CONFIG',
    'description' => '_CO_MODULEINSTALLER_IMAGE_CONFIG_DSC',
    'formtype'    => 'line_break',
    'valuetype'   => 'textbox',
    'default'     => 'head',
];

$modversion['config'][] = [
    'name'        => 'imageWidth',
    'title'       => '_CO_MODULEINSTALLER_IMAGE_WIDTH',
    'description' => '_CO_MODULEINSTALLER_IMAGE_WIDTH_DSC',
    'formtype'    => 'textbox',
    'valuetype'   => 'int',
    'default'     => 1200,
]; // =1024/16

$modversion['config'][] = [
    'name'        => 'imageHeight',
    'title'       => '_CO_MODULEINSTALLER_IMAGE_HEIGHT',
    'description' => '_CO_MODULEINSTALLER_IMAGE_HEIGHT_DSC',
    'formtype'    => 'textbox',
    'valuetype'   => 'int',
    'default'     => 800,
]; // =768/16

$modversion['config'][] = [
    'name'        => 'imageUploadPath',
    'title'       => '_CO_MODULEINSTALLER_IMAGE_UPLOAD_PATH',
    'description' => '_CO_MODULEINSTALLER_IMAGE_UPLOAD_PATH_DSC',
    'formtype'    => 'textbox',
    'valuetype'   => 'text',
    'default'     => 'uploads/' . $modversion['dirname'] . '/images',
];
