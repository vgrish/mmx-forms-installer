<?php

class PackageComposerWrapper
{
    private string $workingPath;
    private string $vendorPath;
    private string $jsonPath;
    private string $phpPath;

    public static function load(): bool
    {
        $load = self::loadComposer() and self::loadComposerJson();
        if ($load) {
            require_once 'phar://' . MODX_BASE_PATH . 'composer.phar/vendor/autoload.php';
        }
        return $load;
    }

    protected static function loadComposer(): bool
    {
        $composer = MODX_BASE_PATH . 'composer.phar';
        if (!file_exists($composer)) {
            return copy("https://getcomposer.org/composer.phar", $composer);
        }
        return true;
    }

    protected static function loadComposerJson(): bool
    {
        $json = MODX_BASE_PATH . '/composer.json';
        if (!file_exists($json)) {
            $version = @ include_once MODX_CORE_PATH . 'docs/version.inc.php';
            if ($version and isset($version['full_version'])) {
                return copy("https://raw.githubusercontent.com/modxcms/revolution/v{$version['full_version']}/composer.json", $json);
            }
            return false;
        }

        return true;
    }

    public function __construct()
    {
        $this->workingPath = realpath(MODX_CORE_PATH);
        $this->vendorPath = $this->workingPath . '/vendor';

        $jsonPath = MODX_BASE_PATH . 'composer.json';
        if (file_exists($jsonPath)) {
            $this->jsonPath = $jsonPath;
        } else {
            $this->jsonPath = '';
        }

        $phpPath = \PHP_BINDIR . '/php' . round((float)PHP_VERSION, 1);
        if (!$phpPath = strtok(exec('command -v --' . ' ' . escapeshellarg($phpPath)), \PHP_EOL)) {
            $phpPath = \PHP_BINDIR . '/php';
        }
        $this->phpPath = $phpPath;

        putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
        putenv('COMPOSER_HTACCESS_PROTECT=0');
        putenv('COMPOSER_BIN_COMPAT=full');
        putenv('COMPOSER_BIN_DIR=' . $this->workingPath . '/vendor/bin');
        putenv("COMPOSER=" . $this->jsonPath);

        register_shutdown_function([$this, 'shutdown']);
    }

    public function application(): \Composer\Console\Application
    {
        $application = new Composer\Console\Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return $application;
    }

    public function getComposer(): ?Composer\Composer
    {
        return $this->application()?->getComposer();
    }

    public function show(string $package, $arg = []): array
    {
        $stream = fopen('php://temp', 'w+');
        $code = $this->application()->run(
            new \Symfony\Component\Console\Input\ArrayInput(
                array_merge(
                    [
                        'command' => 'show',
                        'package' => $package,
                        '--no-interaction' => true,
                        '--no-scripts' => true,
                        '--no-plugins' => true,
                        '--working-dir' => MODX_BASE_PATH,
                    ],
                    $arg
                )
            ),
            new \Symfony\Component\Console\Output\StreamOutput($stream)
        );
        $result = stream_get_contents($stream, -1, 0);
        fclose($stream);

        return $this->response($code, $result);
    }

    public function require(array $packages): array
    {
        $stream = fopen('php://temp', 'w+');
        $code = $this->application()->run(
            new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'require',
                'packages' => $packages,
                '--no-interaction' => true,
                '--no-scripts' => true,
                '--no-plugins' => true,
                '--working-dir' => MODX_BASE_PATH,
            ]),
            new \Symfony\Component\Console\Output\StreamOutput($stream)
        );
        $result = stream_get_contents($stream, -1, 0);
        fclose($stream);

        return $this->response($code, $result);
    }

    public function update(array $packages): array
    {
        $stream = fopen('php://temp', 'w+');
        $code = $this->application()->run(
            new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'update',
                'packages' => $packages,
                '--no-interaction' => true,
                '--no-scripts' => true,
                '--no-plugins' => true,
                '--working-dir' => MODX_BASE_PATH,
            ]),
            new \Symfony\Component\Console\Output\StreamOutput($stream)
        );
        $result = stream_get_contents($stream, -1, 0);
        fclose($stream);

        return $this->response($code, $result);
    }

    public function remove(array $packages): array
    {
        $stream = fopen('php://temp', 'w+');
        $code = $this->application()->run(
            new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'remove',
                'packages' => $packages,
                '--no-interaction' => true,
                '--no-scripts' => true,
                '--no-plugins' => true,
                '--working-dir' => MODX_BASE_PATH,
            ]),
            new \Symfony\Component\Console\Output\StreamOutput($stream)
        );
        $result = stream_get_contents($stream, -1, 0);
        fclose($stream);

        return $this->response($code, $result);
    }

    public function exec(string $bin, string $action): array
    {
        $stream = fopen('php://temp', 'w+');
        $code = $this->application()->run(
            new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'exec',
                'binary' => "{$this->phpPath} {$this->vendorPath}/bin/{$bin} {$action}",
                '--no-scripts' => true,
                '--no-plugins' => true,
                '--no-interaction' => true,
                '--working-dir' => MODX_BASE_PATH,
            ]),
            new \Symfony\Component\Console\Output\StreamOutput($stream)
        );
        $result = stream_get_contents($stream, -1, 0);
        fclose($stream);

        return $this->response($code, $result);
    }

    public function response(string $code, string $result): array
    {
        if (in_array($result[0], ['{', '['])) {
            $result = json_decode($result, true);
        }

        if (!is_array($result) and !in_array(\PHP_SAPI, ['cli', 'cli-server', 'phpdbg'], true)) {
            $result = str_replace(
                [
                    '<info>', '</info>',
                    '<question>', '</question>',
                    '<warning>', '</warning>',
                    '<error>', '</error>',
                ],
                [
                    '<span style="color: green">', '</span>',
                    '<span style="color: cyan">', '</span>',
                    '<span style="color: crimson">', '</span>',
                    '<span style="color: red">', '</span>',
                ],
                nl2br($result)
            );
        }

        return [
            'success' => empty($code),
            'code' => $code,
            'result' => $result,
        ];
    }

    public function shutdown()
    {
        // remove composer cache
        $filesystem = new Symfony\Component\Filesystem\Filesystem();
        $filesystem->remove(MODX_BASE_PATH . 'cache/');
    }

}