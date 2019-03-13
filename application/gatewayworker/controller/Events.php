<?php
namespace app\gatewayworker\controller;

use GatewayWorker\Lib\Gateway;

/**
 * 与ThinkPHP等框架结合
 *
 * 总体原则:
 * 现有mvc框架项目与GatewayWorker独立部署互不干扰
 * 所有的业务逻辑都由网站页面post/get到mvc框架中完成
 * GatewayWorker不接受客户端发来的数据，即GatewayWorker不处理任何业务逻辑，GatewayWorker仅仅当做一个单向的推送通道
 * 仅当mvc框架需要向浏览器主动推送数据时才在mvc框架中调用Gateway的API(GatewayClient)完成推送
 *
 * 具体实现步骤
 * 1、网站页面建立与GatewayWorker的websocket连接
 * 2、GatewayWorker发现有页面发起连接时，将对应连接的client_id发给网站页面
 * 3、网站页面收到client_id后触发一个ajax请求(假设是bind.php)将client_id发到mvc后端
 * 4、mvc后端bind.php收到client_id后利用GatewayClient调用Gateway::bindUid($client_id, $uid)将client_id与当前uid(用户id或者客户端唯一标识)
 * 绑定。如果有群组、群发功能，也可以利用Gateway::joinGroup($client_id, $group_id)将client_id加入到对应分组
 * 5、页面发起的所有请求都直接post/get到mvc框架统一处理，包括发送消息
 * 6、mvc框架处理业务过程中需要向某个uid或者某个群组发送数据时，直接调用GatewayClient的接口Gateway::sendToUid Gateway::sendToGroup 等发送即可
 */
class Events
{

    // 当有客户端连接时，将client_id返回，让mvc框架判断当前uid并执行绑定
    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, json_encode([
            'type'      => 'init',
            'client_id' => $client_id
        ]));
    }

    // GatewayWorker建议不做任何业务逻辑，onMessage留空即可
    public static function onMessage($client_id, $message)
    {

    }

}
