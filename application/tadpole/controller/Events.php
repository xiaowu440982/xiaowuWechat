<?php
namespace app\tadpole\controller;

use GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        $_SESSION['id'] = time();
        echo $client_id . "\n";
        Gateway::sendToCurrentClient('{"type":"welcome","id":' . $_SESSION['id'] . '}');

        // // 向当前client_id发送数据
        // Gateway::sendToClient($client_id, sprintf('Hello %s', $client_id));
        // // 向所有人发送
        // Gateway::sendToAll(sprintf('用户 %s 已登录！', $client_id));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        // 获取客户端请求
        $message_data = json_decode($message, true);
        if (!$message_data) {
            return;
        }

        switch ($message_data['type']) {
            case 'login':
                break;
            // 更新用户
            case 'update':
                // 转播给所有用户
                Gateway::sendToAll(json_encode(
                    array(
                        'type'       => 'update',
                        'id'         => $_SESSION['id'],
                        'angle'      => $message_data["angle"] + 0,
                        'momentum'   => $message_data["momentum"] + 0,
                        'x'          => $message_data["x"] + 0,
                        'y'          => $message_data["y"] + 0,
                        'life'       => 1,
                        'name'       => isset($message_data['name']) ? $message_data['name'] : 'Guest.' . $_SESSION['id'],
                        'authorized' => false,
                    )
                ));
                return;
            // 聊天
            case 'message':
                // 向大家说
                $new_message = array(
                    'type'    => 'message',
                    'id'      => $_SESSION['id'],
                    'message' => $message_data['message'],
                );
                return Gateway::sendToAll(json_encode($new_message));
        }

        // 向所有人发送
        // Gateway::sendToAll(sprintf('用户 %s 说：%s', $client_id, $message));
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        if (isset($_SESSION['id'])) {
            // 广播 xxx 退出了
            GateWay::sendToAll(json_encode(array('type' => 'closed', 'id' => $_SESSION['id'])));
        }

        // 向所有人发送
        // GateWay::sendToAll(sprintf('用户 %s 已退出！', $client_id));
    }

    /**
     * websocket回调
     * @param integer $client_id 用户id
     */
    public static function onWebSocketConnect($client_id, $message)
    {

    }

}
