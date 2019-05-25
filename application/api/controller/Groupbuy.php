<?php
/**
 * 拼团
 */
// namespace app\mobile\controller;
namespace app\api\controller;

use think\Db;
use app\common\model\WxNews;
use app\common\model\TeamActivity;
use app\common\model\GoodsImages;
use app\common\model\Comment;
use app\common\model\UserAddress;
use app\common\logic\OrderLogic;
use think\Page;
use app\common\model\Goods;

class Groupbuy extends ApiBase
{

    /** 
     *  空操作：当访问拼团控制器不存在的方法时，重定向到拼团列表页
    */
    public function _empty(){
		$this->ajaxReturn(['status' => -2, 'msg' => '请求错误', 'data' => '']);
    }


    /**
     * 拼团列表
     */
    public function grouplist()
    {	
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
											
		$page = I('post.page/d',1);
		$num = I('post.num/d',2);
		$limit = (($page - 1)) * $num . ',' . $num;	 

        $where['status'] = ['=', 1];
        $where['start_time'] = ['<=', time()];
        $where['end_time'] = ['>', time()];
        $where['deleted'] = ['=', 0];
        $list = Db::table('tp_team_activity')->where($where)->order('end_time asc')
            ->limit($limit)
            ->alias('t')
            ->Join('goods g',"g.goods_id=t.goods_id",'LEFT')
            ->field('t.team_id,t.act_name,t.goods_name,t.goods_id,t.group_price,t.start_time,t.end_time,t.group_number,t.sales_sum,t.purchase_qty,g.shop_price,g.market_price,g.original_img')
            ->select();
		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功！', 'data' => ($list ? $list : null) ]);

    }

    /*
     * 拼团详情页
     *
     **/
    public function detail()
    {
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }

        $team_id = I('post.team_id/d',0);
        # 拼团ID
        if(!$team_id){
			$this->ajaxReturn(['status' => -2 , 'msg'=>'活动参数错误','data'=>null]);
        } 
        # 查看拼团信息
        $info = Db::table('tp_team_activity')
            ->where("team_id", $team_id)
            ->alias('t')
            ->join('goods g','g.goods_id = t.goods_id','left')
            ->field('t.team_id, t.act_name, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, t.goods_item_id, g.original_img, g.shop_price, g.market_price')
            ->find();
        // dump($info);exit;
        if($info){
            # 对拼团活动状态进行判断
            if($info['start_time'] > time()){
                $this->ajaxReturn(['status' => -3 , 'msg'=>'活动未开启','data'=>null]);
            }
            if($info['end_time'] <= time()){
                $this->ajaxReturn(['status' => -4 , 'msg'=>'活动已结束','data'=>null]);
            }
            if($info['deleted']){
                $this->ajaxReturn(['status' => -5 , 'msg'=>'活动已关闭','data'=>null]);
            }


            # 对拼团信息进行组装
            $goodsModel = new \app\common\model\Goods();
            $info['cluster_type'] = [0 => '', 1 => '小团', 2 => '打团', 3 => '阶梯团'][$info['cluster_type']];
			$info['commentinfo'] = M('Comment')->field('comment_id,username,content,add_time,img,deliver_rank,goods_rank,service_rank')->where(["goods_id" => $info['goods_id'], 'is_show' => 1, 'parent_id' => 0])->order('add_time desc')->find(); //最新一条评论 
			if($info['commentinfo']){
				$info['commentinfo']['img'] = $info['commentinfo']['img'] ? unserialize($info['commentinfo']['img']) : [];
			}
            $info['comment'] = Db::table('tp_comment')->where(['goods_id'=>$info['goods_id'],'is_show'=>1])->count();
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

			$team_found = [];
            if( $team_found_num){
                # 正在开团的拼团信息
                $team_found = Db::table('tp_team_found')
                    ->field('`found_id`,`found_time`,`found_end_time`,`user_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`')
                    ->order('found_end_time asc')
                    ->where('team_id',$info['team_id'])
                    ->where('found_time', '<', time())
                ->where('found_end_time', '>', time())
                    ->where('status', 1)
                    ->limit(2)
                    ->select();
            }
            
            # 商品轮播图
            $goodsImg = Db::table('tp_goods_images')->where('goods_id',$info['goods_id'])->select();

            # 商品收藏
            $collect = db('goods_collect')->where(array("goods_id" => $info['goods_id'], "user_id" => $user_id))->count(); 

			$data = [
				'info'			 => $info,
				'team_found_num' =>	$team_found_num,
				'team_found'	 =>	$team_found,
				'goodsImg'		 => $goodsImg,
				'collect'		 => $collect
			];
			$this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功！','data'=>$data]);

        }else{
            $this->ajaxReturn(['status' => -6 , 'msg'=>'商品信息不存在','data'=>null]);
        }
    }

    /**
     * 获取正在开团的前5人//结束时间升序
     */
	 public function getTeamFive(){  
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }	

        $team_id = I('post.team_id/d',0);
        # 拼团ID
        if(!$team_id){
			$this->ajaxReturn(['status' => -2 , 'msg'=>'活动参数错误','data'=>null]);
        } 

		$team_found = Db::table('tp_team_found')
			->field('`found_id`,`found_time`,`found_end_time`,`user_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`')
			->order('found_end_time asc')
			->where('team_id',$team_id)
			->where('found_time', '<', time())
            ->where('found_end_time', '>', time())
			->where('status', 1)
			->order('found_end_time asc')
			->limit(5)
			->select();

		$this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>['team_found'=>$team_found]]);
	 }

    /**
     * 提交订单 
     */
    public function falceOrder(){ 
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
		$user = M('Users')->field('user_id,user_money,head_pic,nickname,paypwd')->find($user_id);

		$buy_type = input("buy_type/d", 1); //  //1：单独购买，2：拼团
		$team_id = input("team_id/d", 0); //  拼团活动id
		$buy_num = input("buy_num/d", 0); //  购买数量
        $address_id = input("address_id/d", 0); //  收货地址id
		$user_money = input("user_money/f", 0); //  使用余额
		$invoice_type = input("invoice_type/d", 0); //  是否开发票 0不开|1开
		$invoice_identity = input("invoice_identity/s", ''); //  发票种类 个人|空|公司
        $invoice_title = input('invoice_title/s','');  // 发票抬头  
        $invoice_code = input('invoice_code/s','');       // 纳税人识别号
		$user_note = input("user_note/s", ''); // 用户留言
		$found_id = input("found_id/s", ''); // 团ID
		$pay_pwd = input("pay_pwd/s", ''); // 支付密码
		$act = input('act/d',1);	
		
		# 数据验证
		if(!$buy_type || !$team_id || !$buy_num){
			$this->ajaxReturn(['status' => -2 , 'msg'=>'订单提交失败，参数错误','data'=>null]);
		}
		
		if(($act == 1) && !$address_id){
			$this->ajaxReturn(['status' => -3 , 'msg'=>'请选择配送地址','data'=>null]);
		}

		# 获取商品信息
		$info = Db::table('tp_team_activity')
			->where("team_id", $team_id)
			->alias('t')
			->join('goods g','g.goods_id = t.goods_id','left')
			->field('t.team_id, t.act_name, t.time_limit, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, g.cat_id, g.goods_sn, g.seller_id, g.shop_price, g.market_price,g.cost_price')
			->find();
		if(!$info){
			$this->ajaxReturn(['status'=>-4, 'msg'=>'订单提交失败,商品信息不存在','data'=>null]);
		}
		# 对拼团活动状态进行判断
		if($info['start_time'] > time()){
			$this->ajaxReturn(['status'=>-5, 'msg'=>'订单提交失败,活动未开启','data'=>null]);
		}
		if($info['end_time'] <= time()){
			$this->ajaxReturn(['status'=>-6, 'msg'=>'订单提交失败,活动已结束','data'=>null]);
		}
		if($info['deleted']){
			$this->ajaxReturn(['status'=>-7, 'msg'=>'订单提交失败,活动已关闭','data'=>null]);
		}
		if($info['buy_limit'] > 0 && $buy_num > $info['buy_limit']){
			$this->ajaxReturn(['status'=>-8, 'msg'=>'最大限购数量：'.$info['buy_limit'],'data'=>null]);
		}
		# 发起拼团，判断开团最大数
		if(!$found_id && $buy_type == 2 && $info['max_open_num']){
			$open_team = Db::query("select count(*) from `tp_team_found` where `team_id` = '$info[team_id]' and `status` in ('1','2')");
			if($open_team && $info['max_open_num'] <= $open_team[0]['count']){
				$this->ajaxReturn(['status'=>-9, 'msg'=>'订单提交失败,已达到最大开团数','data'=>null]);
			}
		}

		if($pay_pwd && $user_money){
			if (encrypt($pay_pwd) !== $user['paypwd']) {
				$this->ajaxReturn(['status'=>-9, 'msg'=>'支付密码错误','data'=>null]);
            }
		}

		if($found_id){
			$buy_type = 2;
			$found_info = M('team_found')->field('found_end_time,user_id,need,status')->find($found_id);
			$info['needer'] = $found_info['need'];
			if($found_info['found_end_time'] < time())
				$this->ajaxReturn(['status'=>-9, 'msg'=>'此团已结束','data'=>null]);
			if($found_info['user_id'] == $user_id)
				$this->ajaxReturn(['status'=>-9, 'msg'=>'不能参加自己开的团','data'=>null]);
			elseif($found_info['need'] == 0)
				$this->ajaxReturn(['status'=>-9, 'msg'=>'此团已满','data'=>null]);
			elseif($found_info['status'] != 1)
				$this->ajaxReturn(['status'=>-9, 'msg'=>'此团已不能加入','data'=>null]);
		}

		if(M('team_found')->where(['user_id'=>$user_id,'team_id'=>$team_id,'status'=>['not in','3,4'],'found_end_time'=>['egt',time()]])->count())
			$this->ajaxReturn(['status'=>-9, 'msg'=>'您已有正在开的团啦','data'=>null]);

		if($info['goods_item_id']){
			$spec_goods_price = M('spec_goods_price')->where(['item_id'=>$info['goods_item_id']])->value('price');
			$spec_goods_price && ($info['shop_price'] = $spec_goods_price);
		}

		### 配送地址信息
		//$addressInfo = Db::query("select `consignee`,`province`,`city`,`district`,`address`,`mobile` from `tp_user_address` where `address_id` = '$address_id'");
		//$addressInfo = $addressInfo[0];
		if($act != 1)
			$addressInfo = Db::name('user_address')->where("user_id", $user_id)->order('is_default desc')->find();
		else
			$addressInfo = Db::name('user_address')->where(["address_id"=>$address_id])->find();

		if($act == 0){
			$Goods = new \app\common\model\Goods();
			$goods = $Goods->cache(true)->where(['goods_id' => $info['goods_id']])->find();
			$freightLogic = new \app\common\logic\FreightLogic();
			$freightLogic->setGoodsModel($goods);
			$freightLogic->setRegionId($addressInfo['city']);
			$freightLogic->setGoodsNum($buy_num);
			$freightLogic->doCalculation();
            $freight = $freightLogic->getFreight(); 
			$goods_price = (($buy_type==2) ? ($info['group_price'] * $buy_num) : ($info['shop_price'] * $buy_num));
			$pricearr = [
				'shipping_price'		=> $freight,
				'coupon_price'			=> 0,
				'sign_price'			=> 0,
				'deposit'				=> 0,
				'user_money'			=> 0,
				'integral_money'		=> 0,
				'pay_points'			=> 0,
				'order_amount'			=> $goods_price + $freight,
				'total_amount'			=> $goods_price + $freight,
				'goods_price'			=> $goods_price,
				'total_num'				=> $buy_num,
				'order_prom_amount'		=> 0
		
			];
			if($addressInfo){
				$addressInfo['province_name'] = $addressInfo['province'] ? M('region')->where(['id'=>$addressInfo['province']])->value('name') : '';
				$addressInfo['city_name'] = $addressInfo['city'] ? M('region')->where(['id'=>$addressInfo['city']])->value('name') : '';
				$addressInfo['district_name'] = $addressInfo['district'] ? M('region')->where(['id'=>$addressInfo['district']])->value('name') : '';
				$addressInfo['twon_name'] = $addressInfo['twon'] ? M('region')->where(['id'=>$addressInfo['twon']])->value('name') : '';
			} 
			$arr = M('Goods')->field('goods_id,goods_name,shop_price,original_img')->find($goods_id);
			if($info['goods_item_id']){
				$arr['item'] = M('spec_item')->where(['id'=>$info['goods_item_id']])->value('item');
				$price = M('spec_goods_price')->field('price,spec_img')->find($info['goods_item_id']);
				$arr['shop_price'] = $price['price'] ? $price['price'] : $arr['shop_price'];
				$arr['original_img'] = $price['spec_img'] ? $price['spec_img'] : $arr['original_img'];
			}	
			$arr['goods_num'] = $buy_num;
			$goodsinfo[] = $arr;
			$this->ajaxReturn(['status' => 0, 'msg' => '计算成功', 'data' => ['user_money'=>$user['user_money'],'price'=>$pricearr,'address'=>$addressInfo,'goodsinfo'=>$goodsinfo]]);
		}
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
		if($buy_type == 1){
			# 订单需支付金额
			# 独立商品 || 规格商品
			if($info['goods_item_id']){
				$spec = Db::query("select `price`,`cost_price`,`key`,`key_name` from `tp_spec_goods_price` where `item_id` = '$info[goods_item_id]' and `goods_id` = '$info[goods_id]'");
				if(!$spec){
					$this->ajaxReturn(['status'=>-10, 'msg'=>'订单提交失败,商品信息不存在','data'=>null]);
				}
				
				$final_price = $price = $spec[0]['price'];
				$cost_price = $spec[0]['cost_price'];
				$total = $price * $buy_num;
			}else{
				$total = $price * $buy_num;
			}
			# 订单类型
			$prom_type = 0;
		}else{
			$final_price = $info['group_price'];
			$price = $info['group_price'];
			$total = $price * $buy_num;
			# 订单类型
			$prom_type = 6;
		} 
		# 使用余额 || 直接支付
		if($user_money){
			$user_money = $user['user_money'];
			# 用户余额满足支付金额
			if($user_money >= $total){
				# 剩余支付金额
				$rpay = 0;
				# 用户余额
				$ruser_money = $user_money - $total;
				# 已使用余额
				$auser_money = $total;
				# 订单支付状态
				$pay_status = 1;
			}else{ /*
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
				}else{*/
					# 剩余支付金额
					$rpay = $total;
					# 用户余额
					$ruser_money = 0;
					# 已使用余额
					$auser_money = 0;
					# 订单支付状态
					$pay_status = 0;
				//}
				
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
		}

		# 发票信息拼装
		if($invoice_type == '0' || $invoice_type == '不开发票'){  
			$invoice_title = '';
			$invoice_code = '';
			$invoice_desc = '不开发票';
		}else{
			if($invoice_identity == '个人'){
				$invoice_title = '';
				$invoice_code = '';
				$invoice_desc = '纸质（个人-'.$invoice_type.'）';
			}else{
				$invoice_title = $invoice_title;
				$invoice_code = $invoice_code;
				$invoice_desc = '纸质（'.$invoice_title.'-'.$invoice_type.'）';
			}
		}
		# 下单时间 时间戳
		$add_time = time();

		# 订单数据拼装
		$order_sql = "insert into `tp_order` (`seller_id`,`order_sn`,`user_id`,`pay_status`,`consignee`,`province`,`city`,`district`,`address`,`mobile`,`invoice_title`,`taxpayer`,`invoice_desc`,`goods_price`,`user_money`,`order_amount`,`total_amount`,`add_time`,`prom_id`,`prom_type`,`order_prom_id`,`user_note`) values ('$info[seller_id]','$order_sn','$user_id','$pay_status','$addressInfo[consignee]','$addressInfo[province]','$addressInfo[city]','$addressInfo[district]','$addressInfo[address]','$addressInfo[mobile]','$invoice_title','$invoice_code','$invoice_desc','$total','$auser_money','$rpay','$total','$add_time','$team_id','$prom_type','$found_id','$user_note')";   
	    
		# 运行sql语句，插入订单
		$order_ins = Db::execute($order_sql);
		# 添加订单成功时运行
		if($order_ins){
			# 订单ID
			$order_insid = Db::table('tp_order')->getLastInsID();
			# 订单商品表sql拼装
			$ogsql = "insert into `tp_order_goods` (`order_id`,`goods_id`,`cat_id`,`seller_id`,`order_sn`,`consignee`,`mobile`,`goods_name`,`goods_sn`,`goods_num`,`final_price`,`goods_price`,`cost_price`,`item_id`,`spec_key`,`spec_key_name`,`prom_type`) values ('$order_insid','$info[goods_id]','$info[cat_id]','$info[seller_id]','$order_sn','$addressInfo[consignee]','$addressInfo[mobile]','$info[goods_name]','$info[goods_sn]','$buy_num','$final_price','$price','$cost_price','$info[goods_item_id]','$spec[key]','$spec[key_name]','$prom_type')";
			$ogins = Db::execute($ogsql);
			if(!$ogins){
				Db::execute("delete from `tp_order` where `order_id` = '$order_insid'");
				$this->ajaxReturn(['status'=>-11, 'msg'=>'订单提交失败，订单商品写入失败','data'=>null]);
			}

			# 单独购买 || 拼团
			if($prom_type){
				# 开团时间
				$found_time = $add_time;
				# 成团截止时间
				$found_end_time = $add_time + ($info['time_limit'] * 60 * 60);
				# 开团数据拼装
				if($pay_status == 1){

					# 拼团状态
					$status = 1;
				}else{
					//$found_time = $found_end_time = 0;
					$status = 0;
				}

				# 成团人数余
				$needer = $info['needer'] - 1;
				# 用户头像
				$head_pic = addslashes($user[head_pic]);
				if(!$found_id){
					# 组装sql语句
					$found_sql = "insert into `tp_team_found` (`found_time`,`found_end_time`,`user_id`,`team_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`,`price`,`goods_price`,`status`) values ('$found_time','$found_end_time','$user_id','$info[team_id]','$user[nickname]','$head_pic','$order_insid','1','$needer','$final_price','$price','$status')";
				
					$found_ins = Db::execute($found_sql);
					if($needer == 0)M('team_found')->update(['found_id'=>Db::name('team_follow')->getLastInsID(),'status'=>2]);
				}else{
					$found_ins = M('team_follow')->add([
						'follow_user_id'		=> $user_id,
						'follow_user_nickname'	=> $user['nickname'],
						'follow_user_head_pic'	=> $user['head_pic'],
						'follow_time'			=> time(),
						'order_id'				=> $order_insid,
						'found_id'				=> $found_id,
						'found_user_id'			=> $found_info['user_id'],
						'team_id'				=> $info['team_id']
					]);
					M('team_found')->where(['found_id'=>$found_id])->setInc('join');
					M('team_found')->where(['found_id'=>$found_id])->setDec('need');
					if($needer == 0){
						M('team_found')->update(['found_id'=>$found_id,'status'=>2]); 
						M('team_follow')->where(['found_id'=>$found_id])->update(['status'=>2]);
					
					}
				}
				# 组装sql语句
				
				// dump($found_ins);exit;
				if($found_ins){
					# 更新用户余额
					//session('user.user_money',$ruser_money);
					if($user_money)Db::execute("update `tp_users` set `user_money` = '$ruser_money' where `user_id` = '$user_id'");
					$this->ajaxReturn(['status'=>($user_money ? 1 : 0), 'msg'=>'订单提交成功','data'=>['type'=>2]]);
				}else{
					Db::execute("delete from `tp_order` where `order_id` = '$order_insid'");
					$this->ajaxReturn(['status'=>-13, 'msg'=>'订单提交失败，开团时不成功','data'=>null]);
				}
			}else{
				# 更新用户余额
				//session('user.user_money',$ruser_money);
				if($user_money)Db::execute("update `tp_users` set `user_money` = '$ruser_money' where `user_id` = '$user_id'");
				$this->ajaxReturn(['status'=>($user_money ? 1 : 0), 'msg'=>'订单提交成功','data'=>['type'=>1]]);
			}
		}else{
			$this->ajaxReturn(['status'=>-14, 'msg'=>'订单提交失败,创建订单失败','data'=>null]);
		}

    }

	public function a(){	
		$OrderLogic = new OrderLogic();  echo $OrderLogic->get_order_sn_auction_deposit();
	}
    
}