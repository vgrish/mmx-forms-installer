<?php

declare(strict_types=1);

set_time_limit(0);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

$config = [
    'name' => 'mmxForms',
    'version' => '1.0.1',
    'release' => 'pl',
    'install' => false,
    'download' => false,
];

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') and (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

@ob_start();

define('PKG_NAME', $config['name']);
define('PKG_NAME_LOWER', strtolower($config['name']));
define('PKG_VERSION', $config['version']);
define('PKG_RELEASE', $config['release']);

$root = dirname(dirname(__FILE__)) . '/';
$core = $root . 'core/components/' . PKG_NAME_LOWER . '/';
$assets = $root . 'assets/components/' . PKG_NAME_LOWER . '/';

require_once MODX_CORE_PATH . 'vendor/autoload.php';

use xPDO\xPDO;
use xPDO\Om\xPDOGenerator;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modNamespace;

/* instantiate xpdo instance */
$xpdo = new xPDO(
    'mysql:host=localhost;dbname=modx;charset=utf8', 'root', '',
    [xPDO::OPT_TABLE_PREFIX => 'modx_', xPDO::OPT_CACHE_PATH => MODX_CORE_PATH . 'cache/'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING]
);
$cacheManager = $xpdo->getCacheManager();
define('LOG', xPDO::LOG_LEVEL_INFO);
$xpdo->setLogLevel(LOG);

$buildLogs = [];
$xpdo->setLogTarget(['target' => 'ARRAY', 'options' => ['var' => &$buildLogs]]);
$xpdo->log(LOG, print_r($config, true));

$signature = implode('-', [PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE]);
$filename = $signature . '.transport.zip';
$directory = MODX_CORE_PATH . 'packages/';

$xpdo->log(LOG, 'Package directory: "' . $directory . '"');
$xpdo->log(LOG, 'Package filename: "' . $filename . '"');

/* remove the package if it exists */
if (file_exists($directory . $filename)) {
    unlink($directory . $filename);
}
if (file_exists($directory . $signature) and is_dir($directory . $signature)) {
    $cacheManager = $xpdo->getCacheManager();
    if ($cacheManager) {
        $cacheManager->deleteTree($directory . $signature, true, false, []);
    }
}

$package = new xPDOTransport($xpdo, $signature, $directory);

// Add composer wrapper
$cacheManager->copyFile(__DIR__ . '/wrapper.php', $directory . $signature . '/wrapper.php');

// Add validators
$validators = [];
foreach (scandir(__DIR__ . '/validators/') as $file) {
    if (in_array($file[0], ['_', '.'])) {
        continue;
    }
    $validators[] = ['type' => 'php', 'source' => __DIR__ . '/validators/' . $file];
    $xpdo->log(LOG, 'Added validator "' . preg_replace('#\.php$#', '', $file) . '"');
}

// Add resolvers
$resolvers = [];
foreach (scandir(__DIR__ . '/resolvers/') as $file) {
    if (in_array($file[0], ['_', '.'])) {
        continue;
    }
    $resolvers[] = ['type' => 'php', 'source' => __DIR__ . '/resolvers/' . $file];
    $xpdo->log(LOG, 'Added resolver "' . preg_replace('#\.php$#', '', $file) . '"');
}

$package->put(
    new modNamespace($xpdo),
    [
        xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
        'namespace' => PKG_NAME_LOWER,
        'package' => 'modx',
        'validate' => $validators,
        'resolve' => $resolvers,
    ],
);

$package->setAttribute('changelog', file_get_contents(__DIR__ . '/../changelog.md'));
$package->setAttribute('license', file_get_contents(__DIR__ . '/../license.md'));
$package->setAttribute('readme', file_get_contents(__DIR__ . '/../readme.md'));
$package->setAttribute(
    'requires',
    [
        'php' => '>=8.1',
        'modx' => '>=3.0',
    ]
);

if ($package->pack()) {
    $xpdo->log(LOG, "Package built");
}


if (!empty($config['install'])) {
    /* Create an instance of the modX class */
    $modx = new \MODX\Revolution\modX();
    if (is_object($modx) and ($modx instanceof \MODX\Revolution\modX)) {
        $modx->initialize('mgr');

        $modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
        $modx->setLogTarget();
        $modx->runProcessor('Workspace/Packages/ScanLocal');

        if ($r = $modx->runProcessor('Workspace/Packages/Install', ['signature' => $signature])) {
            $response = $r->getResponse();
        }

        $xpdo->log(LOG, $response['message']);
        $modx->getCacheManager()->refresh(['system_settings' => []]);
        $modx->reloadConfig();
    }
}

@ob_clean();

$buildLog = '';
foreach ($buildLogs as $info) {
    $buildLog .= xPDOGenerator::varExport($info) . "\n";
}
if (XPDO_CLI_MODE) {
    echo $buildLog;
} else {
    echo "<pre>" . $buildLog . "</pre>";
}

