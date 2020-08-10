<?php

use Imi\App;
use Swoole\Event;
use Imi\Event\EventParam;
$loader = require dirname(__DIR__) . '/vendor/autoload.php';

\Swoole\Runtime::enableCoroutine();

ini_set('date.timezone', date_default_timezone_get());

App::initFramework('ImiApp');

$statusCode = 0;
go(function() use(&$statusCode){
    go(function() use(&$statusCode){
        \Imi\Event\Event::on('IMI.INIT_TOOL', function(EventParam $param){
            $data = $param->getData();
            $data['skip'] = true;
            \Imi\Tool\Tool::init();
        });
        \Imi\Event\Event::on('IMI.INITED', function(EventParam $param){
            App::initWorker();
            $param->stopPropagation();
        }, 1);
        App::run('ImiApp');
        try {
            if($phpunitPath = getenv('TEST_PHPUNIT_PATH'))
            {
                require $phpunitPath;
            }
            PHPUnit\TextUI\Command::main(false);
        } catch (\Swoole\ExitException $e) {
            $statusCode = $e->getStatus();
        }
        Event::exit();
    });
});
Event::wait();
exit($statusCode);
