<?php
namespace OAuth2\Tests;

class StorageTest extends \OAuth2_StorageTest
{
    function provideStorage()
    {
        $instance = Bootstrap::getInstance();
        return array(
            array($instance->getYiidb())
        );
    }
}

class Bootstrap
{
    protected static $instance;
    private $yiidb;
    
    public static function getInstance()
    {
        if ( !static::$instance ) {
            static::$instance = new self();
        }
        return static::$instance;
    }
    
    protected function getSqliteDir()
    {
        return \Yii::getPathOfAlias('application.runtime'). '/oauth-test.sqlite';
    }

    protected function createSqliteDb(\PDO $pdo)
    {
        $pdo->exec('CREATE TABLE oauth_clients (client_id TEXT, client_secret TEXT, redirect_uri TEXT)');
        $pdo->exec('CREATE TABLE oauth_access_tokens (access_token TEXT, client_id TEXT, user_id TEXT, expires TIMESTAMP, scope TEXT)');
        $pdo->exec('CREATE TABLE oauth_authorization_codes (authorization_code TEXT, client_id TEXT, user_id TEXT, redirect_uri TEXT, expires TIMESTAMP, scope TEXT)');
        $pdo->exec('CREATE TABLE oauth_users (username TEXT, password TEXT, first_name TEXT, last_name TEXT)');
        $pdo->exec('CREATE TABLE oauth_refresh_tokens (refresh_token TEXT, client_id TEXT, user_id TEXT, expires TIMESTAMP, scope TEXT)');

        // test data
        $pdo->exec('INSERT INTO oauth_clients (client_id, client_secret) VALUES ("oauth_test_client", "testpass")');
        $pdo->exec('INSERT INTO oauth_access_tokens (access_token, client_id) VALUES ("testtoken", "Some Client")');
        $pdo->exec('INSERT INTO oauth_authorization_codes (authorization_code, client_id) VALUES ("testcode", "Some Client")');
        $pdo->exec('INSERT INTO oauth_users (username, password) VALUES ("testuser", "password")');
    }

    protected function removeSqliteDb()
    {
        if (file_exists($this->getSqliteDir())) {
            unlink($this->getSqliteDir());
        }
    }

    public function getYiidb()
    {
        if ( !$this->yiidb ) {
            $this->removeSqliteDb();
            $db = \Yii::createComponent(array(
                'class' => 'CDbConnection',
                'autoConnect' => true,
                'connectionString' => 'sqlite:' . $this->getSqliteDir()
            ));
            $db->init();
            $this->createSqliteDb($db->getPdoInstance());
            $this->yiidb = new \OAuth2\Storage\YiiDb($db, array(), false);
        }
        return $this->yiidb;
    }
}