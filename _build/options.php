<?php

/** @var modX $modx */
/** @var array $options */
if (!in_array($options[xPDOTransport::PACKAGE_ACTION], [xPDOTransport::ACTION_INSTALL, xPDOTransport::ACTION_UPGRADE])) {
    return '';
}

include_once __DIR__ . '/wrapper.php';
if (!PackageComposerWrapper::load()) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, "I can't initialize the \"PackageComposerWrapper\" class");
    return;
}

$package = 'mmx/forms';
$pcw = new \PackageComposerWrapper();
$output = $pcw->show($package, [
    '--latest' => true,
    '--no-dev' => true,
    '--all' => true,
    '--available' => true,
    '--format' => 'json',
]);
if (empty($output['success'])) {
    return '';
}
$versions = $output['result']['versions'] ?? [];
$installed = null;
try {
    $installed = \Composer\InstalledVersions::getPrettyVersion($package);
} catch (Throwable $e) {
}

$output = '';
if ($installed) {
    $output .= str_replace(
        [
            '{header}',
            '{package}',
            '{version}',
        ],
        [
            'Текущая версия',
            $package,
            $installed,
        ],
        '<p> &nbsp;{header} <b>{package}</b>:&nbsp; <b>{version}</b> </p>'
    );
}

$output .= str_replace(
    '{header}',
    'Выберите доступную версию',
    '<fieldset style="border-radius:5px;border-style:dashed;max-height: 200px;overflow-y: scroll;"><legend> &nbsp;{header}:&nbsp; </legend>'
);
foreach ($versions as $id => $version) {
    $output .= str_replace(
        [
            '{id}',
            '{version}',
            '{checked}',
        ],
        [
            $id,
            $version,
            $installed ? ($version === $installed ? 'checked' : '') : (empty($id) ? 'checked' : ''),
        ],
        '<div style="display: flex;"><input type="radio" id="ver-{id}" name="version" value="{version}" {checked}/><label for="ver-{id}"> &nbsp;{version}</label></div>'
    );
}
$output .= '</fieldset>';

return $output;