<?php
namespace app\gatewayworker\controller;

use think\Controller;
use GatewayClient\Gateway;

//加载GatewayClient。关于GatewayClient参见本页面底部介绍
require_once dirname(__FILE__) . '/../../../vendor/workerman/gatewayclient/Gateway.php';

/**
 * gatewaywokerman与ThinkPHP5等框架结合  GatewayClient 使用tp5发送消息 单向通讯
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

class Index extends Controller {

    public function index() {
    	return view('');
    }

    /**
     * mvc后端uid绑定
     * http://socket.xiaowu.com/gatewayworker/index/bind
     */
    public function bind()
    {
		// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
		Gateway::$registerAddress = '127.0.0.1:1237';

		// 假设用户已经登录，用户uid和群组id在session中
		// $uid      = $_SESSION['uid'];
		// $group_id = $_SESSION['group'];

		$client_id = request()->param('client_id');
		$uid = 1234;
		$group_id = 2222;

		// client_id与uid绑定
		Gateway::bindUid($client_id, $uid);
		// 加入某个群组（可调用多次加入多个群组）
		Gateway::joinGroup($client_id, $group_id);
    }

     /**
     * mvc后端发消息
     * http://socket.xiaowu.com/gatewayworker/index/send_message
     */
    public function send_message()
    {
		// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
		Gateway::$registerAddress = '127.0.0.1:1237';

		$uid = 1234;
		$message = '{"message":"xiaowu"}';
		$group = [];

		// 向任意uid的网站页面发送数据
		Gateway::sendToUid($uid, $message);
		// 向任意群组的网站页面发送数据
		// Gateway::sendToGroup($group, $message);
    }
}
