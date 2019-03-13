<?php
namespace app\browsergame\controller;

use think\Controller;

/**
 * BrowserQuest WorkerMan版本 与 tp5 的整合使用
 *
 * BrowserQuest是Mozilla发布的一款2D图形的MMO（大型多人在线）游戏，
 * 玩家可以聊天、打怪、升级、寻宝、获得成就。这里基于WorkerMan框架重写了BrowserQuest服务端nodejs部分，
 * 浏览器与后端同样是基于websocket协议通讯。
 */

class Index extends Controller {

    public function index() {
    	return view('');
    }
}
