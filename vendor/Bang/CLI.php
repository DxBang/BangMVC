<?php
namespace Bang;

final class CLI extends Bang {
    function __construct() {
        try {
            if (PHP_SAPI != 'cli') throw new \Error('Not running in console', 1);
            if (($config = require_once(SITE_PRIVATE.'/cli.php')) == false) {
                throw new \Error('Cannot locate CLI config file', 2);
            }
            if (is_array($config)) {
                $config = json_decode(json_encode($config), false);
            }
            if (empty($config->routes)) {
                throw new \Error('Security flaw! Missing routes', 3);
            }
            Core::set('routes', $config->routes);
            if (!empty($config->session)) {
                unset($config->session);
            }
            parent::__construct($config);
        }  catch (\Exception $e) {
            echo 'EXCEPTION: '.$e->getCode.':'.$e->getMessage().PHP_EOL;
        }  catch (\Error $e) {
            echo 'ERROR: '.$e->getCode.':'.$e->getMessage().' in '.$e->getFile().' on '.$e->getLine().PHP_EOL;
            exit;
        }
    }
}