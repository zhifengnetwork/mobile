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

        $where['status'] = ['=', 1];
        $where['end_time'] = ['<', time()];
        $where['deleted'] = ['=', 0];
        $count = Db::table('tp_team_activity')->where($where)->count();
        $Page = new Page($count, 15);
        $list = Db::table('tp_team_activity')->where($where)->order('end_time asc')
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
        # 用户ID
        $user_id = cookie('user_id');
        # 获取 GET 参数
        $data = I('get.');
        # 拼团ID
        $teamid = intval($data['team_id']) > 0 ? intval($data['team_id']) : 0;
        if(!$teamid){
            return $this->redirect('grouplist');
        } 
        # 查看拼团信息
        $info = Db::table('tp_team_activity')
            ->where("team_id", $data['team_id'])
            ->alias('t')
            ->join('goods g','g.goods_id = t.goods_id','left')
            ->field('t.team_id, t.act_name, t.goods_id, t.goods_name, t.group_price, t.cluster_type, t.end_time, t.sales_sum, g.original_img,       g.shop_price, g.market_price')
            ->find();
        // dump($info);exit;
        if($info){
            # 对拼团信息进行组装
            $goodsModel = new \app\common\model\Goods();
            $info['cluster_type'] = [0 => '', 1 => '小团', 2 => '打团', 3 => '阶梯团'][$info['cluster_type']];
            $info['comment'] = Db::table('tp_comment')->where('goods_id',$info['goods_id'])->count();
            $info['comment_fr'] = $goodsModel->getCommentStatisticsAttr('', ['goods_id', $info['goods_id']]);
            $info['end_time'] = $info['end_time'] - time();
            // dump($info);exit;
            # 正在开团的数量
            $team_found_num = Db::table('tp_team_found')
                ->where('team_id',$info['team_id'])
                ->where('found_end_time', '<', time())
                ->where('status', 1)
                ->count();

            if( $team_found_num){
                # 正在开团的拼团信息
                $team_found = Db::table('tp_team_found')
                    ->field('`found_id`,`found_time`,`found_end_time`,`user_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`')
                    ->order('found_end_time asc')
                    ->where('team_id',$info['team_id'])
                    ->where('found_end_time', '<', time())
                    ->where('status', 1)
                    ->limit(3)
                    ->select();

                $this->assign('team_found_num', $team_found_num);
                $this->assign('team_found', $team_found);
            }
            
            # 商品轮播图
            $goodsImg = Db::table('tp_goods_images')->where('goods_id',$info['goods_id'])->select();
            $this->assign('goodsImg', $goodsImg);

            # 商品收藏
            $collect = db('goods_collect')->where(array("goods_id" => $info['goods_id'], "user_id" => $user_id))->count(); 
            $this->assign('collect', $collect);
        }else{
            $this->error('商品信息不存在');
        }


        // dump($team_found);exit;
        $this->assign('info', $info);
        






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