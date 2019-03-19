<?php

namespace app\mobile\controller;

use think\Db;
use think\Page;


class Material extends MobileBase {

    public $user_id = 0;
    public $user = array();
    
    /**
     * 默认获取分享区数据列表
     * @author C
     * @time 2019-3
     */
    public function index(){  
        // 获取素材分类渲染到页面
        $catWhere = " show_in_nav=1";
        $category = M('material_cat')->field('cat_id, cat_name')->where($catWhere)->order('sort_order')->select();   
        $this->assign('category', $category);
        // $userInfo = session('user'); // 获取用户信息
        $where = " is_open = 1 and cat_id=9";
        $count = M('material')->where($where)->count(); // 查询满足需求的总记录数
        $pagesize = C('PAGESIZE'); // 每页显示数
        $page = new Page($count, $pagesize); // 分页类
        $material = M('material')->field('material_id,title,keywords,add_time,describe,thumb')->where($where)->limit($page->firstRow.','.$page->listRows)->select(); // 查询已发布的列表
        // 循环向数组加入用户信息
        // if($material){
        //     foreach ($material as $k => $v) {
        //         foreach ($category as $ks => $vs) {
        //             if($v['cat_id']=$vs['cat_id']){
        //                 $material[$k]['cat_name'] = $vs['cat_name'];
        //             }
        //         }
        //         $material[$k]['nickname'] = $userInfo['nickname'];
        //         $material[$k]['head_pic'] = $userInfo['head_pic'];
        //     }
        // }
        $this->assign('material', $material);
        return $this->fetch();
    }

    /**
    * 点击列表显示内容
    */
    public function getDetail(){
        // 获取列表对应ID
        $atID = intval(input('atID'));
        if(!$atID){
            return json(['code'=>'-1', 'msg'=>'请传入ID']);
        }
        // 根据id获取对应内容
        $atDetail = M('material')->field('material_id, cat_id, title, keywords, add_time, describe, content, click, thumb')->where('material_id', $atID)->find();
        if(!$atDetail){
            return json(['code'=>'-1', 'msg'=>'获取的内容不存在']);
        }

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';  

        // 获取当前url
        $url = $http_type.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].'&first_leader='.session('user.user_id');

        

        $atDetail['content'] = htmlspecialchars_decode($atDetail['content']);
        $this->assign('url', $url);
        $this->assign('atDetail', $atDetail);
        return $this->fetch('detail');
    }
}