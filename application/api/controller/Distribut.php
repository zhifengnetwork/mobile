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
    public function aaaaaaaa()
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


    public function aaa(){
        ini_set('max_execution_time', '0');

        $user_id = I('user_id');
        $res = $this->login_service_volume($user_id);

        dump($res);
    }



    function login_service_volume($memberid){
        //echo $uniacid."\\\\<p>";
        $count = 0;
    
        $memberids = M('users')->field('user_id')->where(['first_leader'=>$memberid])->select();

        //echo "<pre>";
        //print_r($memberids);
        //echo "</pre>";
        $total = count($memberids);
        //echo $total."---".$memberid."<p>";
        if(empty($memberids)){
            return $total;
        }else{
            $mun = 0;
            foreach ($memberids as $key => $value){
                $mun += $this->login_service_volume($value['user_id']);
                //echo "===".$i.";;;".$value['id']."<p>";
                /*$id = pdo_fetchcolumn('select count(id) from ' . tablename('sz_yi_member') . ' where agentid=:agentid  and uniacid=:uniacid ', array(':uniacid' => $uniacid, ':agentid' => $value['id']));
                $total += intval($id);*/
                //$mun += $i;
            }
            $total += $count;
        }
        return $total + $mun;
    }




    /**
     * 通过 user_id  查  所有
     */
    public function get_team_num(){
        ini_set('max_execution_time', '0');

        $user_id = I('user_id');
        $all = M('users')->field('user_id,first_leader')->select();

        $values = [];
        foreach ($all as $item) {
            $values[$item['first_leader']][] = $item;
        }
        //foreach ($all as $k => $v) {
            $coumun = $this->membercount($user_id, $values);
            
            //M('users')->where(['user_id'=>$v['user_id']])->update(['underling_number'=>$coumun]);
            //$coumun += $coumun;
       // }
        
       M('users')->where(['user_id'=>$user_id])->update(['underling_number'=>$coumun]);
        
       echo $coumun;
       
    }


    public function membercount($id, $data)
    {
        $count = 0;
        $num = count($data[$id]);
        if (empty($data[$id])) {
            return $num;
        } else {
            $mun = 0;
            foreach ($data[$id] as $key => $value) {
                if (empty($data[$value['user_id']])) {
                    continue;
                } else {
                    $mun += intval($this->membercount($value['user_id'], $data));
                }
            }
            $num += $count;
        }
        return $num + $mun;
    }

}
