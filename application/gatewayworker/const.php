<?php

// 注册协议
define('register_protocol', '0.0.0.0:1237');

// 注册地址
define('register_address', '127.0.0.1:1237');

// 网关地址
define('gateway_address', '0.0.0.0:8284');

// 网关起始端口
define('gateway_start_port', '2900');

// 心跳检测间隔，单位：秒，0 表示不发送心跳检测
define('gateway_ping_interval', 10);

// 本机ip，分布式部署时请设置成内网ip（非127.0.0.1）
define('local_host_ip', '127.0.0.1');

// 网关名称
define('gateway_name', 'Gateway');

// worker进程名称
define('worker_name', 'WorkerClientXiaowu');

// Gateway进程数量，建议与CPU核数相同
define('gateway_count', 2);

// BusinessWorker进程数量，建议设置为CPU核数的1倍-3倍
define('business_worker_count', 6);

// Business业务处理类，可以带命名空间
define('business_event_handler', 'app\gatewayworker\controller\Events');
