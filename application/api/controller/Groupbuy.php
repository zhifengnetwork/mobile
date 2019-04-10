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
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>new class{}]);
        }
        
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);

        $where['status'] = ['=', 1];
        $where['start_time'] = ['<=', time()];
        $where['end_time'] = ['>', time()];
        $where['deleted'] = ['=', 0];

        $limit = (($page - 1)) * $num . ',' . $num;
        $list = Db::table('tp_team_activity')->where($where)->order('end_time asc')
            ->limit($limit)
            ->alias('t')
            ->Join('goods g',"g.goods_id=t.goods_id",'LEFT')
            ->field('t.team_id,t.act_name,t.goods_name,t.goods_id,t.group_price,t.start_time,t.end_time,t.group_number,t.purchase_qty,g.shop_price,g.market_price,g.original_img')
            ->select();  
        // dump(Db::table('tp_team_activity')->getLastSql());exit;
        $this->ajaxReturn(['status' => 0, 'msg' => '请求成功！', 'data' => new class{} ]);
    }

    /*
     * 拼团详情页
     *
     **/
    public function detail()
    {
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>new class{}]);
        }

        $team_id = I('post.team_id/d',0);
        # 拼团ID
        if(!$team_id){
			$this->ajaxReturn(['status' => -2 , 'msg'=>'活动参数错误','data'=>new class{}]);
        } 
        # 查看拼团信息
        $info = Db::table('tp_team_activity')
            ->where("team_id", $team_id)
            ->alias('t')
            ->join('goods g','g.goods_id = t.goods_id','left')
            ->field('t.team_id, t.act_name, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, g.original_img, g.shop_price, g.market_price')
            ->find();
        // dump($info);exit;
        if($info){
            # 对拼团活动状态进行判断
            if($info['start_time'] > time()){
                $this->ajaxReturn(['status' => -3 , 'msg'=>'活动未开启','data'=>new class{}]);
            }
            if($info['end_time'] <= time()){
                $this->ajaxReturn(['status' => -4 , 'msg'=>'活动已结束','data'=>new class{}]);
            }
            if($info['deleted']){
                $this->ajaxReturn(['status' => -5 , 'msg'=>'活动已关闭','data'=>new class{}]);
            }


            # 对拼团信息进行组装
            $goodsModel = new \app\common\model\Goods();
            $info['cluster_type'] = [0 => '', 1 => '小团', 2 => '打团', 3 => '阶梯团'][$info['cluster_type']];
			$info['commentinfo'] = M('Comment')->field('comment_id,username,content,add_time,img,deliver_rank,goods_rank,service_rank')->where(["goods_id" => $info['goods_id'], 'is_show' => 1, 'parent_id' => 0])->order('add_time desc')->find(); //最新一条评论 
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

			$team_found = '';
            if( $team_found_num){
                # 正在开团的拼团信息
                $team_found = Db::table('tp_team_found')
                    ->field('`found_id`,`found_time`,`found_end_time`,`user_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`')
                    ->order('found_end_time asc')
                    ->where('team_id',$info['team_id'])
                    ->where('found_end_time', '<', time())
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
            $this->ajaxReturn(['status' => -6 , 'msg'=>'商品信息不存在','data'=>new class{}]);
        }
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
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>new class{}]);
        }
		$user = M('Users')->field('user_id,user_money,head_pic,nickname')->find($user_id);

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
		
		# 数据验证
		if(!$buy_type || !$team_id || !$buy_num){
			ajaxReturn(['status'=>0, 'msg'=>'订单提交失败，参数错误']);
			$this->ajaxReturn(['status' => -2 , 'msg'=>'订单提交失败，参数错误','data'=>new class{}]);
		}

		if(!$address_id){
			$this->ajaxReturn(['status' => -3 , 'msg'=>'请选择配送地址','data'=>new class{}]);
		}

		# 获取商品信息
		$info = Db::table('tp_team_activity')
			->where("team_id", $team_id)
			->alias('t')
			->join('goods g','g.goods_id = t.goods_id','left')
			->field('t.team_id, t.act_name, t.time_limit, t.goods_id, t.goods_item_id, t.needer, t.goods_name, t.deleted, t.group_price, t.cluster_type, t.start_time, t.end_time, t.buy_limit, t.sales_sum, t.max_open_num, g.cat_id, g.goods_sn, g.seller_id, g.shop_price, g.market_price,g.cost_price')
			->find();
		if(!$info){
			$this->ajaxReturn(['status'=>-4, 'msg'=>'订单提交失败,商品信息不存在','data'=>new class{}]);
		}
		# 对拼团活动状态进行判断
		if($info['start_time'] > time()){
			$this->ajaxReturn(['status'=>-5, 'msg'=>'订单提交失败,活动未开启','data'=>new class{}]);
		}
		if($info['end_time'] <= time()){
			$this->ajaxReturn(['status'=>-6, 'msg'=>'订单提交失败,活动已结束','data'=>new class{}]);
		}
		if($info['deleted']){
			$this->ajaxReturn(['status'=>-7, 'msg'=>'订单提交失败,活动已关闭','data'=>new class{}]);
		}
		if($info['buy_limit'] > 0 && $buy_num > $info['buy_limit']){
			$this->ajaxReturn(['status'=>-8, 'msg'=>'最大限购数量：'.$info['buy_limit'],'data'=>new class{}]);
		}
		# 发起拼团，判断开团最大数
		if($buy_type == 2 && $info['max_open_num']){
			$open_team = Db::query("select count(*) from `tp_team_found` where `team_id` = '$info[team_id]' and `status` in ('1','2')");
			if($open_team && $info['max_open_num'] <= $open_team[0]['count']){
				$this->ajaxReturn(['status'=>-9, 'msg'=>'订单提交失败,已达到最大开团数','data'=>new class{}]);
			}
		}

		### 配送地址信息
		$addressInfo = Db::query("select `consignee`,`province`,`city`,`district`,`address`,`mobile` from `tp_user_address` where `address_id` = '$address_id'");
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
		if($buy_type == 1){
			# 订单需支付金额
			# 独立商品 || 规格商品
			if($info['goods_item_id']){
				$spec = Db::query("select `price`,`cost_price`,`key`,`key_name` from `tp_spec_goods_price` where `item_id` = '$info[goods_item_id]' and `goods_id` = '$info[goods_id]'");
				if(!$spec){
					$this->ajaxReturn(['status'=>-10, 'msg'=>'订单提交失败,商品信息不存在','data'=>new class{}]);
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
		$order_sql = "insert into `tp_order` (`seller_id`,`order_sn`,`user_id`,`pay_status`,`consignee`,`province`,`city`,`district`,`address`,`mobile`,`invoice_title`,`taxpayer`,`invoice_desc`,`goods_price`,`user_money`,`order_amount`,`total_amount`,`add_time`,`prom_type`,`user_note`) values ('$info[seller_id]','$order_sn','$user_id','$pay_status','$addressInfo[consignee]','$addressInfo[province]','$addressInfo[city]','$addressInfo[district]','$addressInfo[address]','$addressInfo[mobile]','$invoice_title','$invoice_code','$invoice_desc','$total','$auser_money','$rpay','$total','$add_time','$prom_type','$user_note')";   
	    
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
				$this->ajaxReturn(['status'=>-11, 'msg'=>'订单提交失败，订单商品写入失败','data'=>new class{}]);
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
				# 组装sql语句
				$found_sql = "insert into `tp_team_found` (`found_time`,`found_end_time`,`user_id`,`team_id`,`nickname`,`head_pic`,`order_id`,`join`,`need`,`price`,`goods_price`,`status`) values ('$found_time','$found_end_time','$user_id','$info[team_id]','$user[nickname]','$head_pic','$order_insid','1','$needer','$final_price','$price','$status')";
				
				$found_ins = Db::execute($found_sql);
				// dump($found_ins);exit;
				if($found_ins){
					# 更新用户余额
					session('user.user_money',$ruser_money);
					Db::execute("update `tp_users` set `user_money` = '$ruser_money' where `user_id` = '$user_id'");
					$this->ajaxReturn(['status'=>0, 'msg'=>'订单提交成功','data'=>['type'=>2]]);
				}else{
					Db::execute("delete from `tp_order` where `order_id` = '$order_insid'");
					$this->ajaxReturn(['status'=>-13, 'msg'=>'订单提交失败，开团时不成功','data'=>new class{}]);
				}
			}else{
				# 更新用户余额
				session('user.user_money',$ruser_money);
				Db::execute("update `tp_users` set `user_money` = '$ruser_money' where `user_id` = '$user_id'");
				$this->ajaxReturn(['status'=>0, 'msg'=>'订单提交成功','data'=>['type'=>1]]);
			}
		}else{
			$this->ajaxReturn(['status'=>-14, 'msg'=>'订单提交失败,创建订单失败','data'=>new class{}]);
		}

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