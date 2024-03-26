<?php

declare(strict_types=1);

/** @var xPDOTransport $transport */
if (!$transport->xpdo) {
    return false;
}

$composer = MODX_BASE_PATH . 'composer.phar';
if (!file_exists($composer)) {
    copy("https://getcomposer.org/composer.phar", $composer);
}
if (!file_exists($composer)) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not download Composer into {$composer}. Please do it manually.");
    return false;
}

$json = MODX_BASE_PATH . '/composer.json';
if (!file_exists($json)) {
    $version = [];
    if ($transport->xpdo instanceof \MODX\Revolution\modX) {
        $version = $transport->xpdo->version;
    }
    if (!empty($version['full_version'])) {
        copy("https://raw.githubusercontent.com/modxcms/revolution/v{$version['full_version']}/composer.json", $json);
    }
}
if (!file_exists($json)) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not download composer.json into {$json}. Please do it manually.");
    return false;
}

return true;