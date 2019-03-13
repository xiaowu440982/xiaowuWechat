<?php

use GatewayWorker\Register;
use Workerman\Worker;

$registerInstance = new StartRegister();

// call_user_func_array 类的方法或静态方法的回调  call_user_func_array([类名, 类方法], 参数)
call_user_func_array([$registerInstance, 'index'], []);

class StartRegister
{

    public function __construct()
    {
        // __FILE__ 文件的完整路径和文件名。如果用在被包含文件中，则返回被包含的文件名
        require_once dirname(__FILE__) . '/../../../vendor/autoload.php';
        include_once dirname(__FILE__) . '/../const.php';
    }

    public function index()
    {
        // register 必须是text协议  sprintf 把百分号（%）符号替换成一个作为参数进行传递的变量：
        $registerAddress = sprintf('text://%s', register_protocol); // 注册协议
        $register        = new Register($registerAddress);

        // 如果不是在根目录启动，则运行runAll方法
        if (!defined('GLOBAL_START')) {
            Worker::runAll();
        }
    }

}
