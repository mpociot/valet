<?php

namespace Valet;

class Caddy
{
    var $cli;
    var $files;
    var $daemonPath = '/Library/LaunchDaemons/com.laravel.valetServer.plist';

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Install the system launch daemon for the Caddy server.
     *
     * @return void
     */
    function install()
    {
        $this->installCaddyFile();
        $this->installCaddyDirectory();
        $this->installCaddyDaemon();
    }

    /**
     * Install the Caddyfile to the ~/.valet directory.
     *
     * This file serves as the main server configuration for Valet.
     *
     * @return void
     */
    function installCaddyFile()
    {
        if (!isWindows()) {
            $this->files->putAsUser(
                VALET_HOME_PATH.'/Caddyfile',
                str_replace('VALET_HOME_PATH', str_replace('\\','/',VALET_HOME_PATH), $this->files->get(__DIR__.'/../stubs/Caddyfile'))
            );
        } else {
            $phpCGI = str_replace(PHP_EOL, '', $this->cli->run('where php-cgi'));
            $this->files->putAsUser(
                VALET_HOME_PATH.'/Caddyfile',
                str_replace(['VALET_HOME_PATH','PHP_CGI'], [str_replace('\\','/',VALET_HOME_PATH), $phpCGI], $this->files->get(__DIR__.'/../stubs/WindowsCaddyfile'))
            );
        }
    }

    /**
     * Install the Caddy configuration directory to the ~/.valet directory.
     *
     * This directory contains all site-specific Caddy definitions.
     *
     * @return void
     */
    function installCaddyDirectory()
    {
        if (! $this->files->isDir($caddyDirectory = VALET_HOME_PATH.'/Caddy')) {
            $this->files->mkdirAsUser($caddyDirectory);
        }

        $this->files->touchAsUser($caddyDirectory.'/.keep');
    }

    /**
     * Install the Caddy daemon on a system level daemon.
     *
     * @return void
     */
    function installCaddyDaemon()
    {
        if (!isWindows())
        {
            $contents = str_replace(
                'VALET_PATH', str_replace('\\','/',$this->files->realpath(__DIR__.'/../../')),
                $this->files->get(__DIR__.'/../stubs/daemon.plist')
            );

            $this->files->put(
                $this->daemonPath, str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents)
            );
        } else {
            $contents = str_replace(
                'VALET_PATH', str_replace('\\','/',$this->files->realpath(__DIR__.'/../../')),
                $this->files->get(__DIR__.'/../stubs/winsw.xml')
            );

            $this->files->put(
                VALET_HOME_PATH.'/Service/caddy.xml', str_replace('VALET_HOME_PATH', str_replace('\\','/',VALET_HOME_PATH), $contents)
            );

            $this->files->put(
                VALET_HOME_PATH.'/Service/caddy.exe.config', $this->files->get(__DIR__.'/../stubs/winsw.config')
            );

            $this->files->copy(__DIR__.'/../../bin/winsw.exe', VALET_HOME_PATH.'/Service/caddy.exe');

            $cwd = $this->files->realpath(__DIR__.'/../../').'/bin';
            $this->cli->runInPath('elevate.exe '.VALET_HOME_PATH.'\Service\caddy.exe install', $cwd);
        }
    }

    /**
     * Restart the launch daemon.
     *
     * @return void
     */
    function restart()
    {
        if (!isWindows()) {
            $this->cli->quietly('sudo launchctl unload '.$this->daemonPath);

            $this->cli->quietly('sudo launchctl load '.$this->daemonPath);
        } else {
            $cwd = $this->files->realpath(__DIR__.'/../../').'/bin';
            $this->cli->runInPath('elevate.exe '.VALET_HOME_PATH.'\Service\caddy.exe restart', $cwd);
        }
    }

    /**
     * Stop the launch daemon.
     *
     * @return void
     */
    function stop()
    {
        if (!isWindows()) {
            $this->cli->quietly('sudo launchctl unload '.$this->daemonPath);
        } else {
            $cwd = $this->files->realpath(__DIR__.'/../../').'/bin';
            $this->cli->runInPath('elevate.exe '.VALET_HOME_PATH.'\Service\caddy.exe stop', $cwd);
        }
    }

    /**
     * Remove the launch daemon.
     *
     * @return void
     */
    function uninstall()
    {
        $this->stop();

        if (!isWindows()) {
            $this->files->unlink($this->daemonPath);
        } else {
            $cwd = $this->files->realpath(__DIR__.'/../../').'/bin';
            $this->cli->runInPath('elevate.exe '.VALET_HOME_PATH.'\Service\caddy.exe uninstall', $cwd);
        }
    }
}
