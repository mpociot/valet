<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS != 'Darwin' && !defined('PHP_WINDOWS_VERSION_BUILD') && ! $inTestingEnvironment) {
    echo 'Valet only supports the Mac operating system.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    echo "Valet requires PHP 5.5.9 or later.";

    exit(1);
}

if (!defined('PHP_WINDOWS_VERSION_BUILD') && ! $inTestingEnvironment) {
    if (exec('which brew') != '/usr/local/bin/brew') {
        echo 'Valet requires Brew to be installed on your Mac.';

        exit(1);
    }
} elseif (!$inTestingEnvironment) {
    if (!stristr(exec('where scoop'), 'scoop.cmd')) {
        echo 'Valet requires Scoop to be installed on your PC.';

        exit(1);
    }
}