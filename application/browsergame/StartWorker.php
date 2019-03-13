<?php

use Workerman\Worker;
use \Server\Utils;
use \Server\Player;
use \Server\WorldServer;

$workerInstance = new StartWorker();

// call_user_func_array 类的方法或静态方法的回调  call_user_func_array([类名, 类方法], 参数)
call_user_func_array([$workerInstance, 'index'], []);

class StartWorker
{

    public function __construct()
    {
        // __FILE__ 文件的完整路径和文件名。如果用在被包含文件中，则返回被包含的文件名
        require_once dirname(__FILE__) . '/../../vendor/autoload.php';
        include_once dirname(__FILE__) . '/const.php';
    }

    public function index()
    {
        // sprintf 把百分号（%）符号替换成一个作为参数进行传递的变量：
        $workerAddress        = sprintf('Websocket://%s', worker_protocol); // 注册协议
        $worker               = new Worker($workerAddress);
        $worker->name         = worker_name;

        $worker->onWorkerStart = function($worker)
        {
            $worker->server = new \Server\Server();
            $worker->config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
            $worker->worlds = array();

            foreach(range(0, $worker->config['nb_worlds']-1) as $i)
            {
                $world = new WorldServer('world'. ($i+1), $worker->config['nb_players_per_world'], $worker);
                $world->run($worker->config['map_filepath']);
                $worker->worlds[] = $world;
            }
        };

        $worker->onConnect = function($connection) use ($worker)
        {
            $connection->server = $worker->server;
            if(isset($server->connectionCallback))
            {
                call_user_func($worker->server->connectionCallback);
            }
            $world = Utils::detect($worker->worlds, function($world)use($worker)
            {
                return $world->playerCount < $worker->config['nb_players_per_world'];
            });
            $world->updatePopulation(null);
            if($world && isset($world->connectCallback))
            {
                call_user_func($world->connectCallback, new Player($connection, $world));
            }
        };

        // 如果不是在根目录启动，则运行runAll方法
        if (!defined('GLOBAL_START')) {
            Worker::runAll();
        }
    }

}
