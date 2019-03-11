<?php

namespace app\admin\controller;

use think\Page;
use think\Db;


class Material extends Base {
	public function materialList(){//素材列表
	if($_POST){
		$id = input('cat_id/s');
		$search = input('search/s');
		if($id==1){
			$where = "title like '%$search%'";
		}elseif($id==2){
			$where = "cat_name like '%$search%'";
		}
	}
	$count = M('material')->join('tp_material_cat','tp_material.cat_id=tp_material_cat.cat_id')->where($where)->count();
	$page = new Page($count,10);
	$list = M('material')->join('tp_material_cat','tp_material.cat_id=tp_material_cat.cat_id')->where($where)->order('tp_material.material_id','desc')->limit($Page->firstRow.','.$Page->listRows)->select();
	$this->assign('page',$page);
	$this->assign('list',$list);
	return $this->fetch();
	}
	
	
	public function materialClass(){//素材分类列表
	$count = Db::name('material_cat')->count();
	$page = new Page($count,10);
	$class = Db::name('material_cat')->limit("$Page->firstRow,$Page->listRows")->select();
	$this->assign('page',$page);
	$this->assign('cat_list',$class);
	return $this->fetch();
	}
	
	public function mOperate($material_id=""){//素材编辑列表
	$class = M('material_cat')->select();
	$this->assign('class',$class);
	if($material_id>0){
		$gain = M('material')->where('material_id',$material_id)->select();
		$this->assign('info',$gain[0]);
	}
	
	if($_POST){
		$material_id = I('material_id');
		
		$title = I('title');
		$thumb = I('thumb');
		$cat_id = I('cat_id');
		$video_type = I('video_type');
		$video = I('video');
		$is_open = I('is_open');
		$describe = I('describe');
		$content = I('content');
		$data = array(
			'title'=>$title,
			'thumb'=>$thumb,
			'cat_id'=>$cat_id,
			'video_type'=>$video_type,
			'video'=>$video,
			'is_open'=>$is_open,
			'describe'=>$describe,
			'content'=>$content
		);
		if($title==""){
			$this->ajaxReturn(['status' => 0, 'msg' => '请填写标题！']);
		}elseif($cat_id==0){
			$this->ajaxReturn(['status' => 0, 'msg' => '请选择分类,没有请先添加！']);
		}elseif($content==""){
			$this->ajaxReturn(['status' => 0, 'msg' => '请填写内容！']);
		}elseif($video!=""&&$video_type==0){
			$this->ajaxReturn(['status' => 0, 'msg' => '请选择视频类别！']);
		}elseif($material_id>0){
			$res = M('material')->data($data)->where('material_id',$material_id)->save();
			$msg = "您没有修改信息！";
		}else{
			$data['add_time'] = time();
			$res = M('material')->insert($data);
			$msg = "参数错误！";
		}
		if($res>0){
			$this->ajaxReturn(['status' => 1, 'msg' => '操作成功！']);
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => $msg]);
		}
	}
	
	return $this->fetch();
	}
	
	public function mClassadd($cat_id=""){//素材分类编辑表
	if($cat_id>0){
		$red = M('material_cat')->where('cat_id',$cat_id)->select();
		$this->assign('cat_info',$red[0]);
	}
	
	if($_POST){
		$cat_id = I('cat_id');
		
		$cat_name = I('cat_name');
		$show_in_nav = I('show_in_nav');
		$sort_order = I('sort_order');
		$cat_desc = I('cat_desc');
		if($sort_order==""){
			$sort_order = 50;
		}
		$data = array(
			'cat_name'=>$cat_name,
			'show_in_nav'=>$show_in_nav,
			'sort_order'=>$sort_order,
			'cat_desc'=>$cat_desc
		);
		if($cat_name==""){
			$this->ajaxReturn(['status' => 0, 'msg' => '请填写分类名称！']);
		}
		if($cat_id>0){
			$res = M('material_cat')->data($data)->where('cat_id',$cat_id)->save();
			$msg = "您没有修改信息！";
		}else{
			$res = M('material_cat')->insert($data);
			$msg = "参数错误!";
		}
		if($res>0){
			$this->ajaxReturn(['status' => 1, 'msg' => '操作成功！']);
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => $msg]);
		}
	}
	
	return $this->fetch();
	}
	
	public function del(){//删除操作
	$cat_id = I('cat_id');
	if($cat_id>0){
		$judge = Db::query("SELECT count(*) from tp_material,tp_material_cat where tp_material.cat_id=tp_material_cat.cat_id and tp_material.cat_id=$cat_id");
		if($judge!=0){
			$this->ajaxReturn(['status' => 0, 'msg' => '该分类下有素材，不允许删除，请先删除该分类下的素材！']);
		}else{
			$del = M('material_cat')->where('cat_id',$cat_id)->delete();
			$this->ajaxReturn(['status' => 1]);
		}
		
	}else{
		$this->ajaxReturn(['status' => 0, 'msg' => '参数错误！']);
	}
	}
	
	public function listdel(){
		$material_id = I('material_id');
		if($material_id>0){
			$del = M('material')->where('material_id',$material_id)->delete();
			$this->ajaxReturn(['status' => 1]);
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => $material_id]);
		}
	}
}