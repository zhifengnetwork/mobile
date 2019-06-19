<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 14:40
 */

namespace app\live\controller;


use app\mobile\controller\MobileBase;
use app\common\model\Users as UserModel;
use think\console\command\make\Controller;

class Abtract extends Controller
{
    public $level = [
        0=>'普通会员',
        1=>'分销商',
        2=>'总代',
        3=>'经理',
        4=>'总监',
        5=>'总裁'
    ];
    public $url = '';
    public $user_id = 0;
    public $user = array();

    public function successResult($data = [])
    {
        return $this->getResult(200, 'success', $data);
    }

    public function failResult($message, $status = 301)
    {
        return $this->getResult($status, $message, false);
    }

    public function getResult($status, $message, $data)
    {
        return json(
            [
                'status' => $status,
                'msg' => $message,
                'data' => $data,
            ]
        );exit;
    }
}