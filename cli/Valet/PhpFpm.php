<?php

namespace Valet;

use Exception;
use DomainException;
use Symfony\Component\Process\Process;

class PhpFpm
{
    public $packageManager, $cli, $files;

    public $taps = [
        'homebrew/dupes', 'homebrew/versions', 'homebrew/homebrew-php'
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  PackageManager  $packageManager
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(PackageManager $packageManager, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->packageManager = $packageManager;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    public function install()
    {
        if (isWindows()) {
            if (! $this->packageManager->installed('php')) {
                $this->packageManager->ensureInstalled('php', $this->taps);
            }
        } else {
            if (! $this->packageManager->installed('php70') &&
                ! $this->packageManager->installed('php56') &&
                ! $this->packageManager->installed('php55')) {
                $this->packageManager->ensureInstalled('php70', $this->taps);
            }

            $this->files->ensureDirExists('/usr/local/var/log', user());

            $this->updateConfiguration();

            $this->restart();
        }
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public function updateConfiguration()
    {
        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public function restart()
    {
        $this->stop();

        $this->packageManager->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public function stop()
    {
        $this->packageManager->stopService('php55', 'php56', 'php70');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath()
    {
        if ($this->packageManager->linkedPhp() === 'php70') {
            return '/usr/local/etc/php/7.0/php-fpm.d/www.conf';
        } elseif ($this->packageManager->linkedPhp() === 'php56') {
            return '/usr/local/etc/php/5.6/php-fpm.conf';
        } elseif ($this->packageManager->linkedPhp() === 'php55') {
            return '/usr/local/etc/php/5.5/php-fpm.conf';
        } else {
            throw new DomainException('Unable to find php-fpm config.');
        }
    }
}
