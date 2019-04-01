<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Goods extends ApiBase
{

   /**
    * 分类接口
    */
    public function categoryList()
    {
        
        $data = Db::name('goods_category')->order('id')->select();
        // dump($data);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }
    public function Products()
    {
        $cat_id = I('get.cat_id/d');
        // dump($cat_id);exit;

        // $data = Db::name('goods')->where('cat_id',$cat_id)->select();
        $data = Db::name('goods')->where('cat_id',$cat_id)->field('goods_id,goods_name,original_img')->select();
        //  dump($data);exit;

        foreach($data as $k => $v){
            $data[$k]['original_img'] = SITE_URL.$v['original_img'];
        }

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }


    

    
}
