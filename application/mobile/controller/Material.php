<?php
/**
 * 智丰网络
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: C   2019-03-09
 */ 
namespace app\mobile\controller;
// use app\common\logic\GoodsLogic;
// use app\common\model\FlashSale;
// use app\common\model\GroupBuy;
// use app\common\model\PreSell;
use think\Db;
use think\Page;
// use app\common\logic\ActivityLogic;

class Material extends MobileBase {

    public $user_id = 0;
    public $user = array();

    // public function _initialize()
    // {
    //     parent::_initialize();
    //     if (session('?user')) {
    //         $user = session('user');
    //         $user = M('users')->where("user_id", $user['user_id'])->find();
    //         session('user', $user);  //覆盖session 中的 user
    //         $this->user = $user;
    //         $this->user_id = $user['user_id'];
    //         $this->assign('user', $user); //存储用户信息
    //         $this->assign('user_id', $this->user_id);
    //     } else {
    //         header("location:" . U('User/login'));
    //         exit;
    //     }
    // }
    
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
        // 获取当前url
        $url = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].'&first_leader='.session('user.user_id');
        $atDetail['content'] = htmlspecialchars_decode($atDetail['content']);
        $this->assign('url', $url);
        $this->assign('atDetail', $atDetail);
        return $this->fetch('Material/detail');
    }
}