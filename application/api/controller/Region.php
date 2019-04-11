<?php
/**
 * 地区API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Region extends ApiBase
{

    /**
     * 获取地区
     *  
     * 如果传ID，则  获取 下级
     * 如果没传ID，则获取 省
     * 
     */
    public function get_region()
    {
        $id = input('id');
        if($id){
            $data = Db::name('region')->where(['parent_id'=>$id])->field('id,name')->select();
        }else{
            $data = Db::name('region')->field('id,name')->where('level', 1)->select();
        }
        $this->ajaxReturn(['status' => 0, 'msg' => '获取成功','data' => $data]);
    }

   
    
}
