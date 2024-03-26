<?php

declare(strict_types=1);

/** @var xPDOTransport $transport */
if (!$transport->xpdo) {
    return false;
}

foreach (
    [
        'pdo' => 'PHP Data Objects',
        'curl' => 'Client URL Library',
        'simplexml' => 'SimpleXML',
        'json' => 'JavaScript Object Notation',
    ] as $ext => $title) {
    if (!extension_loaded($ext)) {
        $msg = sprintf('
            PHP extension `%s` (https://php.net/manual/en/book.%s.php) does not loaded. 
            This PHP extension is required for a proper work of this package.
            Please, ask your sysadmin or hosting company to install and configure it before continue.',
            $title, $ext
        );
        $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, $msg);
        return false;
    }
}

return true;