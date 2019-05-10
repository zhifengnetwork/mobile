<?php

namespace app\api\controller;

use think\Db;


class Material extends ApiBase {

	//获取素材分类
	public function GetMaterialCat(){
		$category = M('material_cat')->field('cat_id, cat_name')->where('show_in_nav=1')->order('sort_order')->select();   
		$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$category]]);
	}

    //获取素材列表
    public function GetMaterialList(){  
		$cid = I('post.cid/d',0);
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = ($page-1)*$num . ',' . $num;

        // $userInfo = session('user'); // 获取用户信息
        $where = " is_open = 1";
		if($cid)$where .= ' and cat_id=' . $cid;
        $material = M('material')->field('material_id,title,keywords,add_time,describe,thumb')->where($where)->limit($limit)->select(); // 查询已发布的列表
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$material]]);
    }

    //获取素材详情
    public function GetMaterialDetail(){
        // 获取列表对应ID
        $atID = I('post.id/d',0);
        if(!$atID){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>null]);
        }
        //修改阅读量
        M('material')->where(['material_id' => $atID])->setInc('click');

        // 根据id获取对应内容
        $atDetail = M('material')->field('material_id, cat_id, title, keywords, add_time, describe, content, click, video, thumb')->find($atID);
        if(!$atDetail){
			$this->ajaxReturn(['status' => -2 , 'msg'=>'获取的内容不存在','data'=>null]);
        }

		$atDetail['content'] = htmlspecialchars_decode($atDetail['content']); 
		$content = preg_replace('/src="(.*?)"/', 'src="'.SITE_URL.'$1"', $atDetail['content']);
		unset($atDetail['content']);

		$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['info'=>$atDetail,'content'=>$content]]);  
    }
}