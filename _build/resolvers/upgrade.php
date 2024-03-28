<?php

declare(strict_types=1);

/** @var xPDOTransport $transport */
/** @var array $options */
if (!$transport->xpdo) {
    return false;
}

if ($options[xPDOTransport::PACKAGE_ACTION] != xPDOTransport::ACTION_UPGRADE) {
    return true;
}

if ($wrapper = glob(__DIR__ . '/*.wrapper.resolver')) {
    include_once reset($wrapper);
}
if (!class_exists('PackageComposerWrapper')) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, "I can't get the wrapper class 'PackageComposerWrapper'");
    return false;
}

$pcw = new \PackageComposerWrapper();

$output = $pcw->update(['mmx/forms']);
$transport->xpdo->log(empty($output['success']) ? xPDO::LOG_LEVEL_ERROR : xPDO::LOG_LEVEL_INFO, print_r($output['result'], true));
if (empty($output['success'])) {
    return false;
}

$output = $pcw->exec('mmx-forms', 'install');
$transport->xpdo->log(empty($output['success']) ? xPDO::LOG_LEVEL_ERROR : xPDO::LOG_LEVEL_INFO, print_r($output['result'], true));
if (empty($output['success'])) {
    return false;
}

return true;
