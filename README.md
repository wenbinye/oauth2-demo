OAuth2 服务器实现和客户端调用示例
==========

演示如何使用 [oauth-server-php][oauth-server-php] 搭建一个 oauth2 服务器，
并使用 [PHP-OAuth2][PHP-OAuth2] 进行验证并调用服务器的接口。

使用 [Composer][Composer] 可以快速安装依赖库：

    $ git clone git://github.com/wenbinye/oauth2-demo.git
    $ cd oauth2-demo
    $ curl -s http://getcomposer.org/installer | php
    $ composer.phar install

修改 index.php 中 *$yii* 位置：

```php
<?php
...
$yii='/path/to/your/yii.php';
```

配置好服务器后在浏览器中打开 *http://your-host/index.php/client/index*

为了区分 oauth 客户端和 oauth 服务器，可以通过修改本机 host 文件，添加一个虚拟域名：

    your.server.ip oauth.host
    
然后修改 *protected/config/main.php* 中 *params*:
```php
        'oauthServerHost' => 'oauth.host'
```

[Composer]: http://getcomposer.org/
[oauth-server-php]: https://github.com/bshaffer/oauth2-server-php
[PHP-OAuth2]: https://github.com/adoy/PHP-OAuth2
