<?php
$autoload=__DIR__.'/vendor/autoload.php';
$yii=__DIR__ . '/vendor/yiisoft/yii/framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';

defined('YII_DEBUG') or define('YII_DEBUG',true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($autoload);
require_once($yii);
Yii::createWebApplication($config)->run();
