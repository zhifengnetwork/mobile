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
use app\common\logic\OrderLogic;
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
                ->where('found_time', '<', time())
                ->where('found_end_time', '>', time())
                ->where('status', 1)
                ->count();

            if( $team_found_num){
                # 正在开团的拼团信息
                $team_found = Db::table('tp_team_found')
                    ->field('`found_id`,`found_time`,`found_end_time`,`user_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`')
                    ->order('found_end_time asc')
                    ->where('team_id',$info['team_id'])
                    ->where('found_time', '<', time())
					->where('found_end_time', '>', time())
                    ->where('status', 1)
                    ->limit(6)
                    ->select();

				foreach($team_found as $k=>$v){
					$team_found[$k]['found_end_time'] = (($v['found_end_time'] > time()) ? ($v['found_end_time'] - time()) : 0);
				}

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
        $found_id = intval(input('get.found_id'));
        

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
        if(!$found_id && $buy_type == 2 && $info['max_open_num']){
            $open_team = Db::query("select count(*) from `tp_team_found` where `team_id` = '$info[team_id]' and `status` in ('1','2')");
            if($open_team && $info['max_open_num'] <= $open_team[0]['count']){
                $this->error('已达到最大开团数，发起拼团失败');
            }
        }
		if($found_id){
			$found_info = M('team_found')->field('found_end_time,user_id,need,status')->find($found_id);
			if($found_info['found_end_time'] < time())
				$this->error('此团已结束');
			elseif($found_info['user_id'] == $user_id)
				$this->error('不能参加自己开的团');
			elseif($found_info['need'] == 0)
				$this->error('此团已满');
			elseif($found_info['status'] != 0)
				$this->error('此团已不能加入');
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
		$info['found_id'] = $found_id; 
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

            $input = input('post.');
            $data = $input['data'];
            
            # 数据验证
            if(!$data['buy_type'] || !$data['team_id'] || !$data['buy_num']){
                ajaxReturn(['status'=>0, 'msg'=>'订单提交失败，参数错误']);
            }
            $user = session('user');
            # 用户ID
            $user_id = $user['user_id'];
            if(!$user_id){
                ajaxReturn(['status'=>0, 'msg'=>'登陆超时，请先登录']);
            }
            if(!$data['address_id']){
                ajaxReturn(['status'=>0, 'msg'=>'请选择配送地址']);
            }

            # 获取商品信息
            $info = Db::table('tp_team_activity')
                ->where("team_id", $data['team_id'])
                ->alias('t')
                ->join('goods g','g.goods_id = t.goods_id','left')
                ->field('t.team_id, t.act_name, t.time_limit, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, g.cat_id, g.goods_sn, g.seller_id, g.shop_price, g.market_price,g.cost_price')
                ->find();
            if(!$info){
                ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,商品信息不存在']);
            }
            # 对拼团活动状态进行判断
            if($info['start_time'] > time()){
                ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,活动未开启']);
            }
            if($info['end_time'] <= time()){
                ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,活动已结束']);
            }
            if($info['deleted']){
                ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,活动已关闭']);
            }
            if($info['buy_limit'] > 0 && $data['buy_num'] > $info['buy_limit']){
                ajaxReturn(['status'=>0, 'msg'=>'最大限购数量：'.$info['buy_limit']]);
            }
            # 发起拼团，判断开团最大数
            if($data['found_id'] && $data['buy_type'] == 2 && $info['max_open_num']){
                $open_team = Db::query("select count(*) from `tp_team_found` where `team_id` = '$info[team_id]' and `status` in ('1','2')");
                if($open_team && $info['max_open_num'] <= $open_team[0]['count']){
                    ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,已达到最大开团数']);
                }
            }

		if($data['found_id']){
			$found_info = M('team_found')->field('found_end_time,user_id,need,status')->find($data['found_id']);
			if($found_info['found_end_time'] < time())
				$this->error('此团已结束');
			if($found_info['user_id'] == $user_id)
				$this->error('不能参加自己开的团');
			elseif($found_info['need'] == 0)
				$this->error('此团已满');
			elseif($found_info['status'] != 0)
				$this->error('此团已不能加入');
		}

            ### 配送地址信息
            $addressInfo = Db::query("select `consignee`,`province`,`city`,`district`,`address`,`mobile` from `tp_user_address` where `address_id` = '$data[address_id]'");
            $addressInfo = $addressInfo[0];

            ### 数据拼装
            # 生成订单编号 （唯一）
            $orderlogic = new OrderLogic();
            $order_sn = $orderlogic->get_order_sn();

            # 商品实际购买价
            $final_price = $info['shop_price'];
            # 本店价
            $price = $info['shop_price'];
            # 成本价
            $cost_price = $info['cost_price'];
            ## 订单类型
            # 单独购买 || 拼单购买
            if($data['buy_type'] == 1){
                # 订单需支付金额
                # 独立商品 || 规格商品
                if($info['goods_item_id']){
                    $spec = Db::query("select `price`,`cost_price`,`key`,`key_name` from `tp_spec_goods_price` where `item_id` = '$info[goods_item_id]' and `goods_id` = '$info[goods_id]'");
                    if(!$spec){
                        ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,商品信息不存在']);
                    }
                    
                    $final_price = $price = $spec[0]['price'];
                    $cost_price = $spec[0]['cost_price'];
                    $total = $price * $data['buy_num'];
                }else{
                    $total = $price * $data['buy_num'];
                }
                # 订单类型
                $prom_type = 0;
            }else{
                $final_price = $info['group_price'];
                $price = $info['group_price'];
                $total = $price * $data['buy_num'];
                # 订单类型
                $prom_type = 6;
            }
            # 使用余额 || 直接支付
            if($data['user_money']){
                $user_money = $user['user_money'];
                # 用户余额满足支付金额
                if($user_money >= $total){
                    # 剩余支付金额
                    $rpay = 0;
                    # 用户余额
                    $ruser_money = $user_money - $total;
                    # 已使用余额
                    $auser_money = $total;
     
                    if ($user['is_lock'] == 1) {
                        ajaxReturn(['status'=>0, 'msg'=>'账号异常已被锁定，不能使用余额支付！']);
                    }
                    if (empty($user['paypwd'])) {
                        ajaxReturn(['status'=>0, 'msg'=>'请先设置支付密码']);
                    }
                    if (empty($data['pay_pwd'])) {
                        ajaxReturn(['status'=>0, 'msg'=>'请输入支付密码']);
                    }
                    if ($data['pay_pwd'] !== $user['paypwd'] && encrypt($data['pay_pwd']) !== $user['paypwd']) {
                        ajaxReturn(['status'=>0, 'msg'=>'支付密码错误']);
                    }
               
                    # 订单支付状态
                    $pay_status = 1;
                    $pay_name = '余额支付';
                }else{
                    # 用户余额大于 0
                    if($user_money > 0){
                        # 剩余支付金额
                        $rpay = $total - $user_money;
                        # 用户余额
                        $ruser_money = 0;
                        # 已使用余额
                        $auser_money = $total - $rpay;
                        # 订单支付状态
                        $pay_status = 2;
                    }else{
                        # 剩余支付金额
                        $rpay = $total;
                        # 用户余额
                        $ruser_money = 0;
                        # 已使用余额
                        $auser_money = 0;
                        # 订单支付状态
                        $pay_status = 0;
                    }
                    
                }
            }else{
                # 剩余支付金额
                $rpay = $total;
                # 用户余额
                $ruser_money = $user['user_money'];
                # 已使用余额
                $auser_money = 0;
                # 订单支付状态
                $pay_status = 0;
                $pay_name = '在线支付';
            }

            # 发票信息拼装
            if($data['invoice_type'] == '0' || $data['invoice_type'] == '不开发票'){
                $invoice_title = '';
                $invoice_code = '';
                $invoice_desc = '不开发票';
            }else{
                if($data['invoice_identity'] == '个人'){
                    $invoice_title = '';
                    $invoice_code = '';
                    $invoice_desc = '纸质（个人-'.$data['invoice_type'].'）';
                }else{
                    $invoice_title = $data['invoice_title'];
                    $invoice_code = $data['invoice_code'];
                    $invoice_desc = '纸质（'.$data['invoice_title'].'-'.$data['invoice_type'].'）';
                }
            }
            # 下单时间 时间戳
            $add_time = time();

            # 订单数据拼装
            $order_sql = "insert into `tp_order` (`seller_id`,`order_sn`,`user_id`,`pay_status`,`consignee`,`province`,`city`,`district`,`address`,`mobile`,`pay_name`,`invoice_title`,`taxpayer`,`invoice_desc`,`goods_price`,`user_money`,`order_amount`,`total_amount`,`add_time`,`prom_type`,`user_note`) values ('$info[seller_id]','$order_sn','$user_id','$pay_status','$addressInfo[consignee]','$addressInfo[province]','$addressInfo[city]','$addressInfo[district]','$addressInfo[address]','$addressInfo[mobile]','$pay_name','$invoice_title','$invoice_code','$invoice_desc','$total','$auser_money','$rpay','$total','$add_time','$prom_type','$data[user_note]')";
           
            # 运行sql语句，插入订单
            $order_ins = Db::execute($order_sql);
            # 添加订单成功时运行
            if($order_ins){
                # 订单ID
                $order_insid = Db::table('tp_order')->getLastInsID();
                # 订单商品表sql拼装
                $ogsql = "insert into `tp_order_goods` (`order_id`,`goods_id`,`cat_id`,`seller_id`,`order_sn`,`consignee`,`mobile`,`goods_name`,`goods_sn`,`goods_num`,`final_price`,`goods_price`,`cost_price`,`item_id`,`spec_key`,`spec_key_name`,`prom_id`,`prom_type`,`order_prom_id`) values ('$order_insid','$info[goods_id]','$info[cat_id]','$info[seller_id]','$order_sn','$addressInfo[consignee]','$addressInfo[mobile]','$info[goods_name]','$info[goods_sn]','$data[buy_num]','$final_price','$price','$cost_price','$info[goods_item_id]','$spec[key]','$spec[key_name]','$data[team_id]','$prom_type','$data[found_id]')";
                $ogins = Db::execute($ogsql);
                if(!$ogins){
                    Db::execute("delete from `tp_order` where `order_id` = '$order_insid'");
                    ajaxReturn(['status'=>0, 'msg'=>'订单提交失败，订单商品写入失败']);
                }

                # 单独购买 || 拼团
                if($prom_type){
                    # 开团数据拼装
                    if($pay_status == 1){
                        # 开团时间
                        $found_time = $add_time;
                        # 成团截止时间
                        $found_end_time = $add_time + ($info['time_limit'] * 60 * 60);
                        # 拼团状态
                        $status = 1;
                    }else{
                        $found_time = $found_end_time = 0;
                        $status = 0;
                    }

                    # 成团人数余
                    $needer = $info['needer'] - 1;
                    # 用户头像
                    $head_pic = addslashes($user[head_pic]);
					if(!$data['found_id']){
						# 组装sql语句
						$found_sql = "insert into `tp_team_found` (`found_time`,`found_end_time`,`user_id`,`team_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`,`price`,`goods_price`,`status`) values ('$found_time','$found_end_time','$user_id','$info[team_id]','$user[nickname]','$head_pic','$order_insid','1','$needer','$final_price','$price','$status')";
						
						$found_ins = Db::execute($found_sql);
						// dump($found_ins);exit;
						
					}else{
						$found_ins = M('team_follow')->add([
							'follow_user_id'		=> $user_id,
							'follow_user_nickname'	=> $user['nickname'],
							'follow_user_head_pic'	=> $user['head_pic'],
							'follow_time'			=> time(),
							'order_id'				=> $order_insid,
							'found_id'				=> $data['found_id'],
							'found_user_id'			=> $found_info['user_id'],
							'team_id'				=> $info['team_id']
						]);
					}
					if($found_ins){
						# 更新用户余额
						session('user.user_money',$ruser_money);
						Db::execute("update `tp_users` set `user_money` = '$ruser_money' where `user_id` = '$user_id'");
						ajaxReturn(['status'=>1, 'msg'=>'订单提交成功', 'type' => 2,'order_sn'=>$order_sn]);
					}else{
						Db::execute("delete from `tp_order` where `order_id` = '$order_insid'");
						ajaxReturn(['status'=>0, 'msg'=>'订单提交失败，开团时不成功']);
					}
                }else{
                    # 更新用户余额
                    session('user.user_money',$ruser_money);
                    Db::execute("update `tp_users` set `user_money` = '$ruser_money' where `user_id` = '$user_id'");
                    ajaxReturn(['status'=>1, 'msg'=>'订单提交成功', 'type' => 1,'order_sn'=>$order_sn]);
                }
            }else{
                ajaxReturn(['status'=>0, 'msg'=>'订单提交失败,创建订单失败']);
            }
        }
        exit('ERROR ?');
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