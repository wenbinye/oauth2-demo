<?php

$config = require(dirname(__FILE__).'/main.php');
unset($config['components']['log']);

return CMap::mergeArray($config, array(
    'components'=>array(
        'fixture'=>array(
            'class'=>'system.test.CDbFixtureManager',
        ),
        'log' => array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
                    'logFile' => 'test.log',
					'levels'=>'error, warning, trace',
				),
			),
		)
        /* uncomment the following to provide test database connection
           'db'=>array(
           'connectionString'=>'DSN for test database',
           ),
        */
    ),
));
