<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Index extends ApiBase
{

   /**
    * 首页接口
    */
    public function index()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }





        $data = '首页数据';
        

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * 首页滚动订单
     */
    public function virtual_order()
    {
        if(IS_GET){
            $virtual_list = M('virtual_order')->where('is_show', '1')->column('id, head_ico, content');

            if($virtual_list){
                $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$virtual_list]);
                exit;
            }else{
                $this->ajaxReturn(['status' => -1, 'msg'=>'获取失败','data'=>'']);
                exit;
            }
        }
    }

    

    
}
