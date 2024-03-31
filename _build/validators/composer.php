<?php

declare(strict_types=1);

/** @var xPDOTransport $transport */
if (!$transport->xpdo) {
    return false;
}

include_once dirname(__DIR__, 3) . '/wrapper.php';
if (!PackageComposerWrapper::load()) {
    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, "I can't initialize the \"PackageComposerWrapper\" class");
    return false;
}

return true;