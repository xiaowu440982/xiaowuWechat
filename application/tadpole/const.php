<?php

/**
 * StartRegister
 */

// 注册协议
define('register_protocol', '0.0.0.0:1238');

/**
 * GwGateway
 */

// gateway网关地址
define('gateway_address', '0.0.0.0:8282');

// gateway网关名称
define('gateway_name', 'TodpoleGatewayXiaowu');

// Gateway进程数量，建议与CPU核数相同
define('gateway_count', 4);

// gateway本机ip，分布式部署时请设置成内网ip（非127.0.0.1）
define('gateway_local_host_ip', '127.0.0.1');

// gateway网关起始端口
define('gateway_start_port', '2000');

// gateway注册地址
define('gateway_register_address', '127.0.0.1:1238');

// gateway心跳检测间隔，单位：秒，0 表示不发送心跳检测
define('gateway_ping_interval', 10);

/**
 * GwBusinessWorker
 */

// business_worker进程名称
define('business_worker_name', 'TodpoleBusinessWorkerXiaowu');

// BusinessWorker进程数量，建议设置为CPU核数的1倍-3倍
define('business_worker_count', 4);

// business注册地址
define('business_register_address', gateway_register_address);

// Business业务处理类，可以带命名空间
define('business_event_handler', 'app\tadpole\controller\Events');
