<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

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
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        Gateway::sendToAll("$client_id login\r\n");
    }

    /**
     * 有消息时
     * @param int $client_id
     * @param mixed $message
     */
    public static function onMessage($client_id, $message)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";

        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
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
                if(!isset($message_data['room_id']))
                {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                // 把房间号昵称放到session中
                $room_id = $message_data['room_id'];
                $client_name = htmlspecialchars($message_data['client_name']);
                $_SESSION['room_id'] = $room_id;
                $_SESSION['client_name'] = $client_name;

                // 获取房间内所有用户列表
                $clients_list = Gateway::getClientSessionsByGroup($room_id);
                foreach($clients_list as $tmp_client_id=>$item)
                {
                    $clients_list[$tmp_client_id] = $item['client_name'];
                }
                $clients_list[$client_id] = $client_name;

                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
                //新增用户等级字段  add by zgp
                $user_level = isset($message_data['user_level'])&&!empty($message_data['user_level']) ? $message_data['user_level'] : 0;
                $new_message = array('type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'),'user_level'=>$user_level);
                print_r($new_message);
                Gateway::sendToGroup($room_id, json_encode($new_message));
                Gateway::joinGroup($client_id, $room_id);
                //更新观看人数
                if(!empty($room_id)){
                    $arr = include dirname(dirname(dirname(dirname(__DIR__)))).'/application/database.php';
                    $conn = @mysqli_connect($arr['hostname'],$arr['username'],$arr['password']);
                    if (!$conn){die("连接数据库失败：" . mysql_error());}
                    mysqli_select_db($conn,$arr['database']);
                    //字符转换，读库
                    $query_sql = "update tp_user_video set look_amount =look_amount + 1  where room_id=".$room_id;
                    mysqli_query($conn,$query_sql);
                }

                // 给当前用户发送用户列表
                $new_message['client_list'] = $clients_list;
                Gateway::sendToCurrentClient(json_encode($new_message));
                return;

            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];

                //新增用户等级字段  add by zgp
                $user_level = isset($message_data['user_level'])&&!empty($message_data['user_level']) ? $message_data['user_level'] : 0;
                $new_message = array(
                    'type'=>'say',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'user_level'=>$user_level,
                    'time'=>date('Y-m-d H:i:s'),
                );
                print_r($new_message);

                return Gateway::sendToGroup($room_id ,json_encode($new_message));

            //礼物
            case 'gift':
                print_r($message_data);
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                $gift_id = isset($message_data['gift_id'])&&!empty($message_data['gift_id']) ? $message_data['gift_id'] : 0;
                $new_message = array(
                    'type'=>'gift',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'gift_id'=>$gift_id,
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                break;
            //红包
            case 'redpacket':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];

                $new_message = array(
                    'type'=>'redpacket',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                break;
            //主播发红包
            case 'red_anchor':
                // 非法请求
                // dump($_SESSION['room_id']);die;
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];

                $new_message = array(
                    'type'=>'red_anchor',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'm_id'=>$message_data['m_id'],
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                break;
            //主播用户抢红包
            case 'red_receive':
                // 非法请求
                // dump($_SESSION['room_id']);die;
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];

                $new_message = array(
                    'type'=>'red_receive',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'moeny'=>$message_data['money'],
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                break;
            //用户抢红包
            case 'red_receive_user':
                // 非法请求
                // dump($_SESSION['room_id']);die;
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];

                $new_message = array(
                    'type'=>'red_receive_user',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'moeny'=>$message_data['money'],
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                print_r($new_message);
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                break;
            //购物链接
            case 'goods':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                $goods_url = isset($message_data['goods_url'])&&!empty($message_data['goods_url']) ? $message_data['goods_url'] : 0;
                $new_message = array(
                    'type'=>'goods',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'goods_url'=>$goods_url,
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
                break;
        }
    }



    /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       // 向所有人发送 
       // GateWay::sendToAll("$client_id logout\r\n");
   }
}
