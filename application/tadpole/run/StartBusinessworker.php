<?php

use GatewayWorker\BusinessWorker;
use Workerman\Worker;

$businessInstance = new StartBusinessWorker();
call_user_func_array([$businessInstance, 'index'], []);

class StartBusinessWorker
{

    public function __construct()
    {
        require_once dirname(__FILE__) . '/../../../vendor/autoload.php';
        include_once dirname(__FILE__) . '/../const.php';
    }

    public function index()
    {
        // bussinessWorker 进程
        $worker = new BusinessWorker();
        // worker名称
        $worker->name = business_worker_name;
        // bussinessWorker进程数量
        $worker->count = business_worker_count;
        // 服务注册地址
        $worker->registerAddress = business_register_address;
        //设置处理业务的类,此处制定Events的命名空间
        $worker->eventHandler = business_event_handler;

        // 如果不是在根目录启动，则运行runAll方法
        if (!defined('GLOBAL_START')) {
            Worker::runAll();
        }
    }

}
