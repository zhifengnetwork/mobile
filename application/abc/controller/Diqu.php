<?php
namespace app\abc\controller;

use think\Db;
use think\Controller;
use app\common\logic\RegionalAgencyLogic;

class Diqu extends Controller
{
    /**
     * 升级地区代理
     */
    public function index(){

        $logic = new RegionalAgencyLogic();
        $res = $logic->upgrade();

        // array(2) {
        //    ["status"] => int(1)
        //    ["msg"] => string(45) "升级到地级市代理级别，地区为空"
        // }

    }

}