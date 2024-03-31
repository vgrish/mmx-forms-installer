<?php

declare(strict_types=1);

/** @var xPDOTransport $transport */
/** @var array $options */
if (!$transport->xpdo) {
    return false;
}
if (!in_array($options[xPDOTransport::PACKAGE_ACTION], [xPDOTransport::ACTION_INSTALL, xPDOTransport::ACTION_UPGRADE])) {
    return true;
}

include_once dirname(__DIR__, 3) . '/wrapper.php';
if (!PackageComposerWrapper::load()) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, "I can't initialize the \"PackageComposerWrapper\" class");
    return false;
}

$package = $packageWithVersion = 'mmx/forms';
if ($version = $options['version'] ?? '') {
    $packageWithVersion = "{$package}:{$version}";
}

$pcw = new \PackageComposerWrapper();
$output = $pcw->require([$packageWithVersion]);
$transport->xpdo->log(empty($output['success']) ? xPDO::LOG_LEVEL_ERROR : xPDO::LOG_LEVEL_INFO, print_r($output['result'], true));
if (empty($output['success'])) {
    return false;
}

$output = $pcw->exec('mmx-database', 'install');
$transport->xpdo->log(empty($output['success']) ? xPDO::LOG_LEVEL_ERROR : xPDO::LOG_LEVEL_INFO, print_r($output['result'], true));

$output = $pcw->exec('mmx-forms', 'install');
$transport->xpdo->log(empty($output['success']) ? xPDO::LOG_LEVEL_ERROR : xPDO::LOG_LEVEL_INFO, print_r($output['result'], true));

$installed = null;
try {
    $installed = \Composer\InstalledVersions::getPrettyVersion($package);
} catch (Throwable $e) {
}

if ($installed) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_INFO, "The \"{$package}:{$installed}\" package has been successfully installed");
    return true;
}

$transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, "The \"{$package}\" package was not installed");
return false;