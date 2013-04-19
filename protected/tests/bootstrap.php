<?php
$basedir=__DIR__ . '/../../';
require($basedir.'/vendor/autoload.php');

foreach ( spl_autoload_functions() as $autoloader ) {
    if ( is_array($autoloader) && $autoloader[0] instanceof \Composer\Autoload\ClassLoader ) {
        $loader = $autoloader[0];
        $loader->add('OAuth2', $basedir.'/vendor/bshaffer/oauth2-server-php/test');
        $loader->add('OAuth2', $basedir.'/vendor/bshaffer/oauth2-server-php/test/lib');
    }
}

$yiit=$basedir.'vendor/yiisoft/yii/framework/yiit.php';
$config=dirname(__FILE__).'/../config/test.php';

require_once($yiit);
require_once(dirname(__FILE__).'/WebTestCase.php');

Yii::createWebApplication($config);

register_shutdown_function(function() {
        Yii::app()->end();
    });
