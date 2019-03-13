<?php
namespace app\chat\controller;

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
        // 调试
        // $client    = "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}";
        // $gateway   = "gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}";
        // $client_id = "client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        // echo "{$client} {$gateway} {$client_id}";

        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return;
        }

        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 判断是否有房间号
                if(!isset($message_data['room_id'])){
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                // 把房间号昵称放到session中
                $room_id                 = $message_data['room_id'];
                $client_name             = htmlspecialchars($message_data['client_name']);
                $_SESSION['room_id']     = $room_id;
                $_SESSION['client_name'] = $client_name;

                // 获取房间内所有用户列表
                $clients_list = Gateway::getClientSessionsByGroup($room_id);
                foreach($clients_list as $tmp_client_id=>$item)
                {
                    $clients_list[$tmp_client_id] = $item['client_name'];
                }
                $clients_list[$client_id] = $client_name;

                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
                $new_message = [
                    'type'        => $message_data['type'],
                    'client_id'   => $client_id,
                    'client_name' => htmlspecialchars($client_name),
                    'time'        => date('Y-m-d H:i:s')
                ];

                // 发送信息给一个用户组里面的所有成员
                Gateway::sendToGroup($room_id, json_encode($new_message));

                // 将用户加入某个房间的数组里
                Gateway::joinGroup($client_id, $room_id);

                // 给当前用户发送用户列表
                $new_message['client_list'] = $clients_list;
                Gateway::sendToCurrentClient(json_encode($new_message));
                return;

            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION['room_id'])){
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }

                $room_id     = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];

                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                        'type'=>'say',
                        'from_client_id'=>$client_id,
                        'from_client_name' =>$client_name,
                        'to_client_id'=>$message_data['to_client_id'],
                        'content'=>"<b>对你说: </b>".nl2br(htmlspecialchars($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s'),
                    );
                    Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                    $new_message['content'] = "<b>你对".htmlspecialchars($message_data['to_client_name'])."说: </b>".nl2br(htmlspecialchars($message_data['content']));
                    return Gateway::sendToCurrentClient(json_encode($new_message));
                }

                $new_message = array(
                    'type'=>'say',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
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
        // 调试
        // $client    = "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}";
        // $gateway   = "gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}";
        // $client_id = "client_id:$client_id onClose:''\n";
        // echo "{$client} {$gateway} {$client_id}";

        // 从房间的客户端列表中删除
        if(isset($_SESSION['room_id']))
        {
            $room_id = $_SESSION['room_id'];
            $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
            Gateway::sendToGroup($room_id, json_encode($new_message));
        }

        // 向所有人发送
        // GateWay::sendToAll(sprintf('用户 %s 已退出！', $client_id));
    }

}
