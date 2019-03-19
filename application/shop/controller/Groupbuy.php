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
use app\common\model\UserAddress;
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
        $where['start_time'] = ['<=', time()];
        $where['end_time'] = ['>', time()];
        $where['deleted'] = ['=', 0];
        $count = Db::table('tp_team_activity')->where($where)->count();
        $Page = new Page($count, 15);
        $list = Db::table('tp_team_activity')->where($where)->order('end_time asc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->alias('t')
            ->Join('goods g',"g.goods_id=t.goods_id",'LEFT')
            ->field('t.team_id,t.act_name,t.goods_name,t.goods_id,t.group_price,t.start_time,t.end_time,t.group_number,t.purchase_qty,g.shop_price,g.market_price,g.original_img')
            ->select();
        
        // dump(Db::table('tp_team_activity')->getLastSql());exit;
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
            ->field('t.team_id, t.act_name, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, g.original_img, g.shop_price, g.market_price')
            ->find();
        // dump($info);exit;
        if($info){
            # 对拼团活动状态进行判断
            if($info['start_time'] > time()){
                $this->error('活动未开启');
            }
            if($info['end_time'] <= time()){
                $this->error('活动已结束');
            }
            if($info['deleted']){
                $this->error('活动已关闭');
            }


            # 对拼团信息进行组装
            $goodsModel = new \app\common\model\Goods();
            $info['cluster_type'] = [0 => '', 1 => '小团', 2 => '打团', 3 => '阶梯团'][$info['cluster_type']];
            $info['comment'] = Db::table('tp_comment')->where('goods_id',$info['goods_id'])->count();
            $info['comment_fr'] = $goodsModel->getCommentStatisticsAttr('', ['goods_id', $info['goods_id']]);
            $info['end_time'] = $info['end_time'] > time() ? $info['end_time'] - time() : 0;
            if($info['goods_item_id']){
                # 当商品为特定规格商品时，获取价格。
                $spec_price = Db::table('tp_spec_goods_price')->field('price')->find($info['goods_item_id']);
                $info['shop_price'] = $spec_price['price'];
            }
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


            # 商品规格样式
            // $field = '`item_id`,`goods_id`,`key`,`key_name`,`price`,`store_count`';
            // $spec = Db::query("select $field from `tp_spec_goods_price` where `goods_id` = '$info[goods_id]'");
            // if($spec){
            //     $spec_min_pric = $spec_max_pric = 0;
            //     foreach($spec as $key => $val){
            //         $spec[$key]['name'] = str_replace(array('选择颜色:', '码数:'), '', $val['key_name']);
            //         if($spec_min_pric == 0) $spec_min_pric = $val['price'];
            //         $spec_min_pric = $spec_min_pric > $val['price'] ? $val['price'] : $spec_min_pric;
            //         $spec_max_pric = $spec_max_pric < $val['price'] ? $val['price'] : $spec_max_pric;
            //     }
            //     $this->assign('spec', $spec);
            //     $this->assign('spec_min_pric', $spec_min_pric);
            //     $this->assign('spec_max_pric', $spec_max_pric);
            // }
            
        }else{
            $this->error('商品信息不存在');
        }


        // dump($info);exit;
        $this->assign('info', $info);
        
        return $this->fetch();
    }

    /**
     * 检查开团数
     */
    public function checkTeamCount(){
        $teamid = intval(input('post.teamid'));
        if(!$teamid) ajaxReturn(['status' => 0, 'msg' => '参数错误！']);
        $info = Db::query("select `team_id`,`max_open_num` from `tp_team_activity` where `team_id` = '$teamid'");
        if(!$info) ajaxReturn(['status' => 0, 'msg' => '商品信息不存在！']);
        $info = $info[0];
        

        ajaxReturn(['status' => 1]);
    }

    /**
     * 确认 发起订单
     */
    public function submit(){
        
        $team_id = intval(input('get.team_id'));
        $buy_num = intval(input('get.buy_num'));
        $buy_type = intval(input('get.buy_type'));
        
        

        # 数据验证
        if(!$team_id){
            $this->error('参数错误！');
        }
        if(!$buy_num){
            $this->error('请输入购买数量');
        }

        $user = session('user');
        $user_id = $user['user_id'];
        if(!$user_id){
            $this->error('登陆超时，请先登录', 'User/login');
        }


        # 获取商品信息
        $info = Db::table('tp_team_activity')
            ->where("team_id", $team_id)
            ->alias('t')
            ->join('goods g','g.goods_id = t.goods_id','left')
            ->field('t.team_id, t.act_name, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, g.original_img, g.shop_price, g.market_price')
            ->find();

        if(!$info){
            $this->error('商品信息不存在');
        }

        # 对拼团活动状态进行判断
        if($info['start_time'] > time()){
            $this->error('活动未开启');
        }
        if($info['end_time'] <= time()){
            $this->error('活动已结束');
        }
        if($info['deleted']){
            $this->error('活动已关闭');
        }
        if($info['buy_limit'] > 0 && $buy_num > $info['buy_limit']){
            $this->error('最大限购数量：'.$info['buy_limit']);
        }
        # 发起拼团，判断开团最大数
        if($buy_type == 2 && $info['max_open_num']){
            $open_team = Db::query("select count(*) from `tp_team_found` where `team_id` = '$info[team_id]' and `status` in ('1','2')");
            if($open_team && $info['max_open_num'] <= $open_team[0]['count']){
                $this->error('已达到最大开团数，发起拼团失败');
            }
        }

        # 收货地址
        $address = Db::table('tp_user_address')
            ->field('address_id,consignee,address,is_default,province,city,district,mobile')
            ->where('user_id',$user_id)
            ->order('is_default desc')
            ->select();
        if($address){
            foreach($address as $val){
                $region[$val['province']] = $val['province'];
                $region[$val['city']] = $val['city'];
                $region[$val['district']] = $val['district'];
            }
            $regionstr = implode("','",$region);
            $regionarr = Db::query("select `id`,`name` from `tp_region` where `id` in ('$regionstr')");
            if($regionarr){
                foreach($regionarr as $reval){
                    $region[$reval['id']] = $reval['name'];
                }
            }
            // dump($address);exit;
            $this->assign('address', $address);
            $this->assign('region', $region);
        }
        
        # 组装数据
        $info['price'] = $buy_type == 1 ? $info['shop_price'] : $info['group_price'];
        $info['buy_type'] = $buy_type;
        $info['buy_num'] = $buy_num; 
        $info['wprice'] = (intval($info['price'] * 100) * $buy_num) / 100;
        $info['user_money'] = $user['user_money'];
        // dump($info);exit;
        

        $this->assign('info', $info);
        return $this->fetch();
    }

    /**
     * 提交订单 
     */
    public function falceOrder(){
        if(IS_AJAX){
            $data = input('post.');
            dump($data);



        }
        exit;
    }

    /**
     * ajax 省市区三级选项
     */
    public function ajaxAreaSelect(){
        $select = trim(input("get.select"));
        $areaid = intval(input("get.areaid"));
        switch($select){
            case 'city':
                $res = Db::query("select `id`, `name` from `tp_region` where `level` = 2 and `parent_id` = '$areaid' order by id asc");
                break;
            case 'area':
                $res = Db::query("select `id`, `name` from `tp_region` where `level` = 3 and `parent_id` = '$areaid' order by id asc");
                break;
            default:
                $res = Db::query("select `id`, `name` from `tp_region` where `level` = 1 order by id asc");
                break;
        }

        if($res){
            ajaxReturn($res);
        }else{
            exit('');
        }
    }

    /**
     * 新增或修改收货地址
     */
    public function addEditAddress(){
        $data = input('post.');
        $user_id = cookie('user_id');
        # 数据验证
        if(!$user_id){
            ajaxReturn(['status'=> 0, msg => '未登陆，请先登陆']);
        }
        if(!$data['province'] || !$data['area']){
            ajaxReturn(['status'=> 0, msg => '请选择地区']);
        }
        if(!$data['consignee']){
            ajaxReturn(['status'=> 0, msg => '请填写收货人']);
        }
        if(!$data['mobile']){
            ajaxReturn(['status'=> 0, msg => '请填写联系电话']);
        }

        if($data['address_id']){

            $upsql = "update `tp_user_address` set `consignee` = '$data[consignee]', `province` = '$data[province]', `city` = '$data[city]', `district` = '$data[district]', `address` = '$data[address]', `mobile` = '$data[mobile]' where `user_id` = '$user_id' and `address_id` = '$data[address_id]'";
            $res = Db::execute($upsql);

        }else{
            $insql = "insert into `tp_user_address` (`user_id`,`consignee`,`province`,`city`,`district`,`address`,`mobile`) values ('$user_id','$data[consignee]','$data[province]','$data[city]','$data[district]','$data[address]','$data[mobile]')";
            $res = Db::execute($insql);
            if($res){
                $data['address_id'] = Db::table('tp_user_address')->getLastInsID();
            }
        }

        if($res){
            ajaxReturn(['status'=> 1, id => $data['address_id'], msg => '操作成功']);
        }else{
            ajaxReturn(['status'=> 0, msg => '操作失败，请重试']);
        }


    }



    /**
     * 商品收藏
     */
    public function collect(){
        $goods_id = intval(input('post.goods_id'));
        $user_id = cookie('user_id');
        if($goods_id && $user_id){
            $goodsInfo = Db::table('tp_goods')->where('goods_id',$goods_id)->find();
            if($goodsInfo){
                $collect = Db::table('tp_goods_collect')->where(array("goods_id" => $goods_id, "user_id" => $user_id))->count();
               if($collect){
                    ajaxReturn(['status' => 1]);
                    exit;
                } 
                $time = time();
                $insql = "insert into `tp_goods_collect` (`user_id`, `goods_id`, `add_time`) values ('$user_id', '$goods_id', '$time')";
                $res = Db::execute($insql);
                if($res){
                    ajaxReturn(['status' => 1]);
                    exit;
                }
                
            }
        }
        ajaxReturn(['status' => 0]);

    }

     /**
     * 用户 评价
     */
    public function comment()
    {
       
        return $this->fetch();
    }

  
    
}