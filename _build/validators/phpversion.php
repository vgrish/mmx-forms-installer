<?php

declare(strict_types=1);

/** @var xPDOTransport $transport */
if (!$transport->xpdo) {
    return false;
}

if (!version_compare(PHP_VERSION, '8.1', '>=')) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Invalid php version. Minimal supported version – 8.1');
    return false;
}

return true;