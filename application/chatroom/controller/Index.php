<?php
namespace app\chatroom\controller;

use think\Controller;
use GatewayClient\Gateway;
use think\facade\Cache;

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

	// 用户
	private $arr = [
		[
			'id' => 11,
			'name' => 'xiaoyi'
		],
		[
			'id' => 22,
			'name' => 'xiaoer'
		],
		[
			'id' => 33,
			'name' => 'xiaosan'
		],
		[
			'id' => 44,
			'name' => 'xiaosi'
		],
		[
			'id' => 55,
			'name' => 'xiaowu'
		],
		[
			'id' => 66,
			'name' => 'xiaoliu'
		],
	];

	// 群
	private $group = [
		[
			'id' => 1111,
			'name' => '泡泡群',
			'member' => [11, 22, 33],
		],
		[
			'id' => 2222,
			'name' => '聊天群',
			'member' => [44, 55, 66],
		],
		[
			'id' => 3333,
			'name' => '泡妞群',
			'member' => [11, 22, 44, 55],
		],
		[
			'id' => 4444,
			'name' => '吹牛群',
			'member' => [33, 66],
		],
	];

	/**
	 * http://socket.xiaowu.com/chatroom/index/index
	 */
    public function index() {
    	$user = request()->param('user') ?? 0;
    	$friendIndex = request()->param('friend') ?? 1;
    	if($user === $friendIndex){
    		exit('参数错误');
    	}

    	$info = $this->arr[$user];
    	session('user', $info);

    	$group = [];
    	foreach ($this->group as $v) {
    		if(in_array($info['id'], $v['member'])){
    			$group[] = $v;
    		}
    	}

		$userLst  = json_encode($this->arr);
		$groupLst = json_encode($group);

    	$view = [
			'userLst'     => $userLst,
			'groupLst'    => $groupLst,
			'userIndex'   => $user,
			'friendIndex' => $friendIndex,
    	];

        return view('', $view);
    }

    /**
     * mvc后端uid绑定
     * http://socket.xiaowu.com/chatroom/index/bind
     */
    public function bind()
    {
		$client_id = request()->param('client_id');
		$uid       = session('user.id');
		$group_id  = 2222; // 群组id

		if(!Cache::get($uid)){
			Cache::set($uid, true, 3600);
    	}

		// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
		Gateway::$registerAddress = '127.0.0.1:1238';
		// client_id与uid绑定
		Gateway::bindUid($client_id, $uid);
		// 加入某个群组（可调用多次加入多个群组）
		// Gateway::joinGroup($client_id, $group_id);

		// 将用户的client_id绑定在对应的群id上
		foreach ($this->group as $v) {
    		if(in_array($uid, $v['member'])){
    			Gateway::joinGroup($client_id, $v['id']);
    		}
    	}
    }

    /**
     * 发送消息
     * http://socket.xiaowu.com/chatroom/index/send_message
     *
     * 火狐浏览器刷新会触发websocket的onClocs事件，谷歌浏览器刷新不会触发
     */
    public function send_message()
    {
		$recipient        = request()->param('recipient');

		$sender = session('user.id');

    	// 自己是否上线
    	if(!Cache::get($sender)){
    		return json([
    			'status' => 0,
    			'msg' => '发送消息失败，您暂未上线',
    		]);
    	}

    	// 判断接受信息的用户是否上线
    	if(!Cache::get($recipient)){
    		return json([
    			'status' => 0,
    			'msg' => '发送消息失败，用户暂未上线',
    		]);
    	}

    	$arr = [
			'type'      => 'receive',
			'msgType'	=> 'private', // 消息类型 私发
			'sender'    => $sender,  // 发送者
			'recipient' => $recipient,  // 接收者
			'time'      => date('Y-m-d H:i:s', time()),
			'msg'       => request()->param('msg'),
		];
		$message = json_encode($arr);

		// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
		Gateway::$registerAddress = '127.0.0.1:1238';
		// 向任意uid的网站页面发送数据
		Gateway::sendToUid($recipient, $message);
		// 向任意群组的网站页面发送数据
		// $group = 2222;
		// $arr['msg'] = '【群发信息】' . $arr['msg'];
		// $groupMsg = json_encode($arr);
		// Gateway::sendToGroup($group, $groupMsg);

		return json([
			'status' => 1,
		]);
    }

    /**
     * 群发送消息
     * http://socket.xiaowu.com/chatroom/index/group_send_message
     *
     * 火狐浏览器刷新会触发websocket的onClocs事件，谷歌浏览器刷新不会触发
     */
    public function group_send_message()
    {
		$recipient = request()->param('recipient');
		$sender    = session('user.id');

    	// 自己是否上线
    	if(!Cache::get($sender)){
    		return json([
				'status' => 0,
				'msg'    => '发送消息失败，您暂未上线',
    		]);
    	}

    	$arr = [
			'type'      => 'receive',
			'msgType'	=> 'group', // 消息类型 群发
			'sender'    => $sender,  // 发送者
			'recipient' => $recipient,  // 接收群
			'time'      => date('Y-m-d H:i:s', time()),
			'msg'       => request()->param('msg'),
		];

		foreach ($this->arr as $v) {
			if($sender == $v['id']){
				$arr['senderName'] = $v['name']; // 发送者名称
			}
		}

		$message = json_encode($arr);

		// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
		Gateway::$registerAddress = '127.0.0.1:1238';
		// 向任意群组的网站页面发送数据
		Gateway::sendToGroup($recipient, $message);

		return json([
			'status' => 1,
		]);
    }

    /**
     * socket与服务器断开连接
     * http://socket.xiaowu.com/chatroom/index/close
     */
    public function close()
    {
    	Cache::rm(session('user.id'));
    }
}
