<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13 0013
 * Time: 10:20
 */

namespace app\cron\controller;

use think\Controller;
use think\Db;
use app\common\util\Exception;
use app\common\logic\UsersLogic;

class Team extends Controller{
    /**
     * 执行方法
     */
    public function run()
    {
        $this->CheckTeamFound();
		$this->change_group_buy_is_end();  
		$this->change_flash_sale_is_end();  

        //竞拍未成功的人返回保证金
        $Auction = M('Auction');
        $AuctionDeposit = M('Auction_deposit');
        $AuctionPprice = M('Auction_price');
        $Users = M('Users');
        $AccountLog = M('AccountLog');
        $SpecGoodsPrice = M('spec_goods_price');
        //$alist = $Auction->field('id,deposit,payment_time,end_time')->where(['end_time'=>['gt',(time()-360)],'is_end'=>1])->select();
        $alist = $Auction->field('id,goods_id,item_id,deposit,payment_time,end_time')->where(['end_time'=>['between',[(time()-360),time()]]])->select();
        $Auction->where(['end_time'=>['lt',time()],'is_end'=>['neq',1]])->update(['is_end'=>1]);
        foreach($alist as $v2){
            if($v2['item_id'])$SpecGoodsPrice->where(['item_id'=>$v2['item_id'],'goods_id'=>$v2['goods_id']])->update(['prom_id'=>0,'prom_type'=>0]);
            $aplist = $AuctionDeposit->field('user_id')->where(['auction_id'=>$v2['id']])->select();

            //成交用户
            $uid = $AuctionPprice->field('user_id')->where(['is_out'=>2,'auction_id'=>$v2['id']])->value('user_id');
            foreach($aplist as $v3){  
                if($v3['user_id'] == $uid)continue;
                $order_sn = $AuctionDeposit->where(['user_id'=>$v3['user_id'],'auction_id'=>$v2['id'],'is_back'=>0])->value('order_sn');
                if(!$order_sn)continue;
                $AuctionDeposit->where(['user_id'=>$v3['user_id'],'auction_id'=>$v2['id']])->update(['is_back'=>1]);  
                $Users->where(['user_id'=>$v3['user_id']])->setInc('user_money',$v2['deposit']);  
                $AccountLog->add(['user_id'=>$v3['user_id'],'user_money'=>$v2['deposit'],'change_time'=>time(),'desc'=>'竞拍失败保证金返回','states'=>104]);  
            }
        }
               
    }

    public function change_group_buy_is_end(){
        //取结束时间大于等于当前时间且未结束的团购
        $GroupBuy = M('group_buy');
        $list = $GroupBuy->field('id,buy_num')->where(['end_time'=>['elt',time()],'is_end'=>0])->select();
        
        $list1 = $GroupBuy->field('id,goods_id,item_id')->where(['end_time'=>['elt',time()],'is_end'=>0])->select();
        $GroupBuy->where(['end_time'=>['elt',time()],'is_end'=>0])->update(['is_end'=>1]);

        $Goods = M('Goods');
		$SpecGoodsPrice = M('Spec_goods_price');
        foreach($list1 as $v){
            $goods_info = $Goods->field('prom_type,prom_id')->find($v['goods_id']);
            if(($goods_info['prom_type'] == 2) && ($goods_info['prom_id'] == $v['id'])){
                $Goods->where(['goods_id'=>$v['goods_id']])->update(['prom_type'=>0,'prom_id'=>0]);
			} //0默认1秒杀2团购3优惠促销4预售5虚拟(5其实没用)6拼团7搭配购8竞拍
			$promitem = $SpecGoodsPrice->field('prom_id,prom_type')->where(['goods_id'=>$v['goods_id'],'item_id'=>$v['item_id']])->find();
			if(($promitem['prom_type'] == 2) && ($promitem['prom_id'] == $v['id']))
				$SpecGoodsPrice->where(['goods_id'=>$v['goods_id'],'item_id'=>$v['item_id']])->update(['prom_type'=>0,'prom_id'=>0]);
        }

    }

    public function change_flash_sale_is_end(){
        //取结束时间十分钟内大于等于当前时间的秒杀
        $flashSale = M('flash_sale');
        $list = $flashSale->field('id,goods_id,item_id,is_end')->where(['end_time'=>['between',[time()-600,time()]]])->select();
        $Goods = M('Goods');
		$SpecGoodsPrice = M('Spec_goods_price');
        foreach($list as $v){
            if(!$v['is_end'])$flashSale->where(['id'=>$v['id']])->update(['is_end'=>1]);
            $goods_info = $Goods->field('prom_type,prom_id')->find($v['goods_id']);
            if(($goods_info['prom_type'] == 1) && ($goods_info['prom_id'] == $v['id']))
                $Goods->where(['goods_id'=>$v['goods_id']])->update(['prom_type'=>0,'prom_id'=>0]);
			$promitem = $SpecGoodsPrice->field('prom_id,prom_type')->where(['goods_id'=>$v['goods_id'],'item_id'=>$v['item_id']])->find();
			if(($promitem['prom_type'] == 1) && ($promitem['prom_id'] == $v['id']))
				$SpecGoodsPrice->where(['goods_id'=>$v['goods_id'],'item_id'=>$v['item_id']])->update(['prom_type'=>0,'prom_id'=>0]);
        }
    }

    public function CheckLive(){
       $info = Db::name('config')->where(['name' => 'video_time'])->find();  
       $time = $info['value'] * 60;

       $list = Db::name('user_video')->where(['status' => 1])->select();

       if(count($list) > 0){
            foreach( $list as $v){
                if(($time + $v['start_time']) < time()){
                    Db::name('user_video')->where(['id' => $v['id']])->update(['status' => 2,'end_time' => time()]);
                }
          }
       }

       

    }

    public function CheckTeamFound(){
        //对过期的拼团订单进行取消,在服务器上由定时器任务执行
        $Tf = M('team_found');

        //获取5分钟内结束的拼团
        $time = ((time()-360) . ' and ' . time());
        $list = $Tf->field('f.found_id')->alias('f')->field('f.found_id,f.need,f.order_id,f.status,t.group_number')->join('tp_team_activity t','f.team_id=t.team_id','left')->where('(f.found_end_time between ' . $time . " and f.status <> 2) or (f.status=4 and f.need=0)")->select(); 
        //echo $Tf->getLastSql(); exit;
        $Tf->where('found_end_time between (' . $time . ") and need>0 and status <> 2")->update(['status'=>3]);

        $Tfw = M('team_follow');
        $Order = M('Order');
        $Users = M('users');
        $AccountLog = M('account_log');
        foreach($list as $v){ 
            if(($v['status'] != 4) || ($v['need'] > 0)){ 
                $this->setAccount($Tfw,$Order,$v['found_id'],$AccountLog,$Users);          
            }else{
                $order_ids = $Tfw->where(['found_id'=>$v['found_id']])->column('order_id');    
                if(!is_array($order_ids))$order_ids = [];
                $order_ids[] = $v['order_id'];    
                $ordernum = $Order->where(['order_id'=>['in',$order_ids],'pay_status'=>1])->count();
                if($ordernum != $v['group_number']){
                    $this->setAccount($Tfw,$Order,$v['found_id'],$AccountLog,$Users);
                }else{ //拼团成功
                    $Tf->where(['found_id'=>$v['found_id']])->update(['status'=>2]);
                    //echo $Tf->getLastSql() ; echo '<br />';  
                    $Tfw->where(['found_id'=>$v['found_id']])->update(['status'=>2]);  
                    //echo $Tfw->getLastSql() ; echo '<br />';     
                }
            }
        }        
    }


    private function setAccount($Tfw,$Order,$found_id,$AccountLog,$Users){
        $Tfw->where(['found_id'=>$found_id])->update(['status'=>3]); 
        //echo $Tfw->getLastSql() ; echo '<br />';     
        $oflist = $Order->field('order_id,order_sn,pay_status,user_id,integral_money,total_amount')->where(['order_prom_id'=>$found_id])->select();
        foreach($oflist as $v1){
            if($AccountLog->where(['user_id'=>$v1['user_id'],'order_sn'=>$v1['order_sn'],'order_id'=>$v1['order_id'],'states'=>103])->count())continue;
            if($v1['total_amount']){
                $AccountLog->add(['user_id'=>$v1['user_id'],'user_money'=>$v1['total_amount'],'pay_points'=>$v1['integral_money'],'change_time'=>time(),'desc'=>'拼团失败返回','order_sn'=>$v1['order_sn'],'order_id'=>$v1['order_id'],'states'=>103]);
            }
            if($v1['integral_money'])
                $Users->where(['user_id'=>$v1['user_id']])->setInc('pay_points',$v1['integral_money']);      
        }
        $Order->where(['order_prom_id'=>$found_id])->update(['order_status'=>3,'admin_note'=>'拼团失败']);	
    }





}