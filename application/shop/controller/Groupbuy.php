<?php
/**
 * 拼团
 */
// namespace app\mobile\controller;
namespace app\shop\controller;

use think\Db;
use app\common\model\WxNews;
use app\common\model\TeamActivity;
use app\common\model\GoodsImages;
use app\common\model\Comment;
use think\Page;
use app\common\model\Goods;
class Groupbuy extends MobileBase
{

    /** 
     *  空操作：当访问拼团控制器不存在的方法时，重定向到拼团列表页
    */
    public function _empty(){
        return $this->redirect('grouplist');
    }


    /**
     * 拼团列表
     */
    public function grouplist()
    {

        $where['status'] = ['=',1];
        $where['end_time'] = ['<',time()];
        $count = Db::table('tp_team_activity')->where($where)->count();
        $Page = new Page($count, 15);
        $list = Db::table('tp_team_activity')->where($where)->order('end_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->alias('t')
            ->Join('goods g',"g.goods_id=t.goods_id",'LEFT')
            ->field('t.team_id,t.act_name,t.goods_name,t.goods_id,t.group_price,t.start_time,t.end_time,t.group_number,t.purchase_qty,g.shop_price,g.market_price,g.original_img')
            ->select();
        
        // dump($list);exit;
        $this->assign('list', $list);
        $this->assign('page', $Page);
        return $this->fetch();
    }

    /*
     * 拼团详情页
     *
     **/
    public function detail()
    {
        // $data = I('get.');
        // $teamAct = new TeamActivity();
        // $team = $teamAct->where('deleted',0)->where('team_id',$data['team_id'])
        //     ->alias('t')
        //     ->Join('goods g',"g.goods_id=t.goods_id",'LEFT')
        //     ->field('t.team_id,t.act_name,t.goods_name,t.goods_id,t.group_price,t.start_time,t.end_time,t.group_number,t.purchase_qty,g.shop_price,g.market_price,g.original_img')
        //     ->find();

        // $goodsImg = new GoodsImages();
        // $gImg = $goodsImg->where('goods_id',$data['goods_id'])
        //     ->field('image_url,img_id')
        //     ->select();

        // $common = new Comment();
        // $comList = $common->where('goods_id',$data['goods_id'])
        //             ->field('comment_id,goods_id,username,content,img,add_time,user_id,goods_rank,service_rank,rec_id')->limit(3)
        //             ->select();
        // $info = [];
        // foreach ($comList as $k => $v){
        //     $info[$k]['goods_id'] = $v['goods_id'];
        //     $info[$k]['username'] = $v['username'];
        //     $info[$k]['content'] = $v['content'];
        //     $info[$k]['add_time'] = date('Y-m-d',time());
        //     $info[$k]['goods_rank'] = $v['goods_rank'];
        //     $info[$k]['img'] = unserialize($v['img']);
        // }


        // $this->assign('team', $team);
        // $this->assign('goodsImg', $gImg);
        // $this->assign('comList', $info);
        return $this->fetch();
    }



     /**
     * 用户 评价
     */
    public function comment()
    {
       
        return $this->fetch();
    }

  
    
}