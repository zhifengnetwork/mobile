<?php
/**
 * 签到API
 */
namespace app\api\controller;
use app\common\model\Users;

use think\Db;
use think\Controller;


class Distribut extends Controller
{

    /**
     * 获取团队总人数
     */
    public function get_team_num()
    {
        ini_set('max_execution_time', '0');

        $user_id = I('user_id');
        $all = M('users')->field('user_id,first_leader')->select();
        $res = count($this->get_downline($all,$user_id,0));
        M('users')->where(['user_id'=>$user_id])->update(['underling_number'=>$res]);
        echo $res;
    }


    //获取用户的所有下级ID
    function get_downline($data,$mid,$level=0){
        $arr=array();
        foreach ($data as $key => $v) {
            if($v['first_leader']==$mid){  //pid为0的是顶级分类
                $v['level'] = $level+1;
                $arr[]=$v;
                $arr = array_merge($arr,$this->get_downline($data,$v['user_id'],$level+1));
            }
        }
        return $arr;
    }

}
