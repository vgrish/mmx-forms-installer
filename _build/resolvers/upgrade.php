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

return true;
