<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;
use think\Page;
use app\common\logic\Message;
use app\common\model\UserMessage;
use app\common\logic\wechat\WechatUtil;
use app\common\logic\ShareLogic;

class User extends ApiBase
{

   /**
    * 登录接口
    */
    public function login()
    {
     
        $mobile = I('mobile');
        $password1 = I('password');
        $password = md5('TPSHOP'.$password1);

        $data = Db::name("users")->where('mobile',$mobile)
        ->field('password,user_id')
        ->find();

        if(!$data){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'手机不存在或错误','data'=>null]);
        }
        if ($password != $data['password']) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'登录密码错误','data'=>null]);
        }
        unset($data['password']);
        //重写
        $data['token'] = $this->create_token($data['user_id']);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'登录成功','data'=>$data]);
       
    }


    public function userinfo(){
        //解密token
        $user_id = $this->get_user_id();
        if($user_id!=""){
            $data = Db::name("users")
            ->where(['user_id'=>$user_id])
            ->field('user_id,agent_user as level,nickname,user_money,head_pic,agent_user,first_leader,realname,mobile,is_distribut,is_agent,sex,birthyear,birthmonth,birthday')
            ->find();
            $data['date_birth'] = $data['birthyear'].'-'.$data['birthmonth'].'-'.$data['birthday'];
            unset($data['birthyear']);
            unset($data['birthmonth']);
            unset($data['birthday']);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
		$data['level_name'] = $data['level'] ? M('user_level')->where(['level'=>$data['level']])->value('level_name') : '普通用户';
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);

    }
    
    public function reset_pwd(){//重置密码
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $password1 = I('password');
        $password = md5('TPSHOP'.$password1);
        $data = array('password'=>$password);
        $data = Db::name('users')->data($data)->where('user_id',$user_id)->save();
        if($data){
            $this->ajaxReturn(['status' => 0 , 'msg'=>'修改成功','data'=>$data]);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'修改失败','data'=>$data]);
        }
        
    }

    /*
    注册接口
     */
    // public function reg(){
    //     if (IS_POST) {
    //         $mobile = I('useriphone');
    //         $password = I('password');
    //         $user = Db::name('user')->where('mobile',$mobile)->find();
    //         if($user){
    //             $this->ajaxReturn(['status' => -1 , 'msg'=>'手机号码已存在','data'=>'']);
    //         }else{

    //         }
    //         $this->ajaxReturn($data);
    //     }
    // }
    // 
    // 
    
    /**
     * 头像上传
     */
      public function update_head_pic(){

            $user_id = $this->get_user_id();
            if($user_id!=""){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('image');
                // 移动到框架应用根目录/uploads/ 目录下
                $info = $file->validate(['size'=>204800,'ext'=>'jpg,png,gif']);
                $info = $file->rule('md5')->move(ROOT_PATH . DS.'public/upload');//加密->保存路径
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    // echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getSaveName();
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    $data = SITE_URL.'/public/upload/'.$info->getSaveName(); //输出路径
                    // ROOT_PATH . DS.
                    
                    // 存着 地址
                    M('users')->where(['user_id'=>$user_id])->update(['head_pic'=>$data]);

                }else{
                    $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
                }

            }
            $this->ajaxReturn(['status' => 0 , 'msg'=>'上传成功','data'=>$data]);
    }

    /**
     * +---------------------------------
     * 地址管理列表
     * +---------------------------------
    */
    public function address_list(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $data =  db('user_address')->where('user_id', $user_id)->select();
        $region_list = db('region')->cache(true)->getField('id,name');
        foreach ($data as $k => $v) {
            $data[$k]['province_name']=$region_list[$v['province']];
            $data[$k]['city_name']=$region_list[$v['city']];
            $data[$k]['district_name'] = $region_list[$v['district']];
            $data[$k]['twon_name']=$region_list[$v['twon']];
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * +---------------------------------
     * 添加地址
     * +---------------------------------
    */
    public function add_address()
    {
        $user_id = $this->get_user_id();
        if (IS_POST) {
            $post_data = input('post.');
            $logic = new UsersLogic();
            $data = $logic->add_address($user_id, 0, $post_data);
      
            if ($data['status'] != 1){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'添加失败','data'=>$data]);
            } else {
                // $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->se   lect();
                $post_data['address_id'] = $data['result'];
                $this->ajaxReturn(['status' => 0 , 'msg'=>'添加成功','data'=>$post_data]);
            }
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
       
    }

    /**
     * +---------------------------------
     * 删除地址
     * +---------------------------------
    */
    public function del_address()
    {   
        $user_id = $this->get_user_id();
        $id = I('get.id/d',0);
        $address = M('user_address')->where("address_id", $id)->find();
        if(!$address){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'地址id不存在！','data'=>'']);
        }
        $row = M('user_address')->where(array('user_id' => $user_id, 'address_id' => $id))->delete();

        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = M('user_address')->where("user_id", $user_id)->find();
            $address2 && M('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if ($row)
            $this->ajaxReturn(['status' => 0 , 'msg'=>'删除地址成功','data'=>$row]);
        else
            $this->ajaxReturn(['status' => -1 , 'msg'=>'删除失败','data'=>'']);
    }
    
    /**
     * +---------------------------------
     * 地址编辑
     * +---------------------------------
    */
    public function edit_address()
    {
        $user_id = $this->get_user_id();
        $id = I('id/d');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $user_id))->find();
        if(!$address){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'地址id不存在！','data'=>'']);
        }
        if (IS_POST) {
            $post_data = input('post.');
            // $source = $post_data['source'];
            $logic = new UsersLogic();
            $data = $logic->add_address($user_id, $id, $post_data);
            if ($data['status'] != 1){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'修改地址失败','data'=>$data]);
            } else {
                $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $user_id))->find();
                //获取省份
                $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
                $c = M('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
                $d = M('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
                $data = [
                    'address' => $address,
                    'province' => $p,
                    'city' => $c,
                    'district' => $d
                ];
                $this->ajaxReturn(['status' => 0 , 'msg'=>'修改地址成功','data'=>$data]);
            }
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
    }

    /**
     * +---------------------------------
     * 修改个人信息 名称、头像、生日
     * +---------------------------------
    */
    public function update_username()
    {
        $user_id = $this->get_user_id();
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(!$user_info){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_POST) {
        	if ($_FILES['head_pic']['tmp_name']) {
        		$file = $this->request->file('head_pic');
                $image_upload_limit_size = config('image_upload_limit_size');
        		$validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
                $dir = UPLOAD_PATH.'head_pic/';
        		if (!($_exists = file_exists($dir))){
        			$isMk = mkdir($dir);
        		}
        		$parentDir = date('Ymd');
        		$info = $file->validate($validate)->move($dir, true);
        		if($info){
        			$post['head_pic'] = '/'.$dir.$parentDir.'/'.$info->getFilename();
        		}else{
                    // $this->error($file->getError());//上传错误提示错误信息
                    $this->ajaxReturn(['status' => -1 , 'msg'=>'上传错误','data'=>'']);
        		}
        	}
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
        
            if(empty(I('post.sex')) && ($user_info['sex'] != I('post.sex'))){
                $post['sex'] = I('post.sex');
            }
            
            I('post.birthyear') ? $post['birthyear'] = I('post.birthyear') : false;  // 年
            I('post.birthmonth') ? $post['birthmonth'] = I('post.birthmonth') : false;  // 月
            I('post.birthday') ? $post['birthday'] =I('post.birthday') : false;  // 日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            I('post.email') ? $post['email'] = I('post.email') : false; //邮箱
            I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //手机

            $email = I('post.email');
            $mobile = I('post.mobile');
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 6);

            if (!empty($email)) {
                $c = M('users')->where(['email' => input('post.email'), 'user_id' => ['<>', $user_id]])->count();
                $c && $this->ajaxReturn(['status' => -1 , 'msg'=>'邮箱已被使用','data'=>'']);
                
            }
            if (!empty($mobile)) {
                $c = M('users')->where(['mobile' => input('post.mobile'), 'user_id' => ['<>', $user_id]])->count();
                $c && $this->ajaxReturn(['status' => -1 , 'msg'=>'手机已被使用','data'=>'']);
                if (!$code)
                    $this->ajaxReturn(['status' => -1 , 'msg'=>'请输入验证码','data'=>'']);
                $check_code = $userLogic->check_validate_code($code, $mobile, 'phone', $this->session_id, $scene);
                if ($check_code['status'] != 1)
                    $this->ajaxReturn(['status' => -1 , 'msg'=>$check_code['msg'],'data'=>'']);
            }
            if (!$userLogic->update_info($user_id, $post))
                $this->ajaxReturn(['status' => -1 , 'msg'=>'保存失败','data'=>'']);

            setcookie('uname',urlencode($post['nickname']),null,'/');
            //$user_info = $userLogic->get_info($user_id); // 获取用户信息

            $result['user_id'] = $user_info['user_id'];
            $result['head_pic'] = $user_info['head_pic'];
            $result['nickname'] = $user_info['nickname'];
            $result['mobile'] = $user_info['mobile'];
            $result['sex'] = $user_info['sex'];
            $result['birthyear'] = $user_info['birthyear'];
            $result['birthmonth'] = $user_info['birthmonth'];
            $result['birthmonth'] = $user_info['birthmonth'];
            $result['birthday'] = $user_info['birthday'];
            $result['province'] = $user_info['province'];
            $result['city'] = $user_info['city'];
            $result['district'] = $user_info['district'];
            $this->ajaxReturn(['status' => 0 , 'msg'=>'保存成功','data'=>$result]);

        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
    }

    /**
     * +---------------------------------
     * 我的钱包
     * +---------------------------------
    */
    public function my_wallet(){
        
        $user_id = $this->get_user_id();   
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($user_id);  // 获取用户信息
        $user_info = $user_info['result'];
        // 统计用户优惠券数量
        $coupon_num = M('coupon_list')->where(array('uid'=>$user_id))->count('id');
        // $couponList = M('coupon_list')->alias('cl')
        //     // ->field('v.visit_id, v.goods_id, v.visittime, g.goods_name, g.shop_price, g.cat_id')
        //     ->join('coupon c', 'cl.cid=c.id')
        //     ->where('cl.uid', $user_id)
        //     ->order('cl.send_time desc')
        //     ->select();
        $user_info = [
            'user_money' => $user_info['user_money']?$user_info['user_money']:0, // 余额
            'pay_points' => $user_info['pay_points']?$user_info['pay_points']:0, // 积分
            'coupon_num' => $coupon_num, // 优惠券数量
            'alipay' => $user_info['alipay'], // 支付宝
            'realname' => $user_info['realname'], //真实姓名
            'bank_name' => $user_info['bank_name'], // 银行名称
            'bank_card' => $user_info['bank_card'], // 银行卡号
            'openid'    => $user_info['openid'] ? ('openid:'.$user_info['openid']) : '',
            'service_ratio'    => tpCache('cash.service_ratio'),
            'min_service_money'    => tpCache('cash.min_service_money'),
            'max_service_money'    => tpCache('cash.max_service_money'),
            'min_cash'    => tpCache('cash.min_cash'),
            'max_cash'    => tpCache('cash.max_cash'),
            'count_cash'    => tpCache('cash.count_cash'),
            'cash_times'    => tpCache('cash.cash_times'),
        ];
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$user_info]);
        
    }

    /**
     * +---------------------------------
     * 用户关注收藏列表
     * +---------------------------------
    */
    public function collect_list()
    {
        $user_id = $this->get_user_id();  
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1) * $num) . ',' . $num;
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($user_id,$limit);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$data['result'],'count'=>$data['count']]]);
    }

    /**
     * +---------------------------------
     * 用户浏览记录
     * +---------------------------------
    */
    public function visit_log()
    {
        $user_id = $this->get_user_id();

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1) * $num) . ',' . $num;

        $visit = M('goods_visit')->alias('v')
            ->field('v.visit_id, v.goods_id, v.visittime, g.goods_name, g.shop_price, g.cat_id, g.comment_count, g.sales_sum, g.original_img')
            ->join('__GOODS__ g', 'v.goods_id=g.goods_id')
            ->where('v.user_id', $user_id)
            ->order('v.visittime desc')
            ->limit($limit)
            ->select();

        /* 浏览记录按日期分组 */
        $curyear = date('YMD');
        $visit_list = [];
        foreach ($visit as $k=>$v) {
            if ($curyear == date('YMD', $v['visittime'])) {
                $date = date('Y年m月d日', $v['visittime']);
               
            } else {
                $date = date('Y年m月d日', $v['visittime']);
            }

            $visit_list[] = $v;
            $visit_list[$k]['date'] = $date;

           
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$visit_list]);
    }

    /* 浏览记录按日期分组 */
// function groupVisit($visit)
// {
//  $curyear = date('Y');
//  $visit_list = [];
//  foreach ($visit as $v) {
//   if ($curyear == date('Y', $v['visittime'])) {
//    $date = date('m月d日', $v['visittime']);
//   } else {
//    $date = date('Y年m月d日', $v['visittime']);
//   }
//   $visit_list[$date][] = $v;
//  }
//  return $visit_list;
// }

    /**
     * +---------------------------------
     * 用户删除浏览记录
     * +---------------------------------
    */
    public function del_visit_log()
    {
        $user_id = $this->get_user_id();
        $visit_ids = I('get.visit_ids', 0);
        if(!$visit_ids){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'visit_ids不可为空','data'=>(object)null]);
        }
        $row = M('goods_visit')->where('visit_id','IN', $visit_ids)->delete();
        if(!$row) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'操作失败','data'=>(object)null]);

        } else {
            $this->ajaxReturn(['status' => 0 , 'msg'=>'操作成功','data'=>(object)null]);
        }
    }

    /**
     * +---------------------------------
     * 用户清空浏览记录
     * +---------------------------------
    */
    public function clear_visit_log()
    {
        $user_id = $this->get_user_id();
        $row = M('goods_visit')->where('user_id', $user_id)->delete();
        if(!$row) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'操作失败','data'=>(object)null]);
        } else {
            $this->ajaxReturn(['status' => 0 , 'msg'=>'操作成功','data'=>(object)null]);
        }
    }

    /**
     * +---------------------------------
     * 分销会员主页
     * +---------------------------------
    */
    public function distribut_index(){
        
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>null]);
        }
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($user_id);  // 获取用户信息
        if($user_info['result']['status'] == '-1'){
            $this->ajaxReturn(['status' => -1 , 'msg'=>$user_info['result']['msg'],'data'=>(object)null]);
        }
        $user_info = [
            'user_money' => $user_info['result']['user_money'],
            'distribut_money' => $user_info['result']['distribut_money'],
            'total_property' => $user_info['result']['user_money']+$user_info['distribut_money'],
            'alipay' => $user_info['result']['alipay'], // 支付宝
            'realname' => $user_info['result']['realname'], //真实姓名
            'bank_name' => $user_info['result']['bank_name'], // 银行名称
            'bank_card' => $user_info['result']['bank_card'], // 银行卡号
            'openid'    => $user_info['result']['openid'] ? ('openid:'.$user_info['result']['openid']) : '',
            'service_ratio'    => tpCache('cash.service_ratio'),
            'min_service_money'    => tpCache('cash.min_service_money'),
            'max_service_money'    => tpCache('cash.max_service_money'),
            'min_cash'    => tpCache('cash.min_cash'),
            'max_cash'    => tpCache('cash.max_cash'),
            'count_cash'    => tpCache('cash.count_cash'),
            'cash_times'    => tpCache('cash.cash_times'),
        ];
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$user_info]);

    }

    /**
     * 我的分销
     */
    public function distribut(){
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>null]);
        }
       
        $per_logic =  new \app\common\logic\PerformanceLogic();
        $money_total = $per_logic->distribut_caculate_by_user_id($user_id);
       
        //补业绩
        if($money_total['moneys'] < 0){
            $bu_moneys = -1 * $money_total['moneys'] * 2; //补 两倍 的 差值
            //这里重新
            $add_logic = new \app\common\logic\AgentPerformanceAddLogic();
            $add_logic->add($user_id,$bu_moneys);
           
            //重新来
            $per_logic =  new \app\common\logic\PerformanceLogic();
            $money_total = $per_logic->distribut_caculate_by_user_id($user_id);
        }

        //上级用户信息
        $leader_id = M('users')->where(['user_id'=> $user_id])->value('first_leader');
        if($leader_id){
            $leader = M('users')->where(['user_id'=>$leader_id])->field('user_id, nickname')->find();
        }else{
            $leader = (object)[];
        }
      
        $underling_number = M('users')->where(['user_id'=>$user_id])->value('underling_number');
        $underling_number == NULL ? $underling_number = '0' : $underling_number;
      

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['user_id'=>$user_id,'money_total'=>$money_total,'leader'=>$leader,'underling_number'=>$underling_number,'statistical_time'=>date('Y-m-d H:i:s')]]);
    }
    
    
    /**
     * +---------------------------------
     * 我的会员
     * +---------------------------------
    */
    public function team_list(){
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }
        // $user_info = $userLogic->get_info($user_id);  // 获取用户信息
        //下级信息

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = ($page-1)*$num . ',' . $num;
		$next_user_id = I('post.next_user_id/d',0);
		$user_id = $next_user_id ? $next_user_id : $user_id;

		$Users = M('users');
        $list = $Users->field('user_id,agent_user as level,nickname,mobile')->order('user_id DESC')->where(['first_leader'=>$user_id])->limit($limit)->select();
		$UserLevel = M('User_level');
		$per_logic =  new \app\common\logic\PerformanceLogic();
		$add_logic = new \app\common\logic\AgentPerformanceAddLogic();
		foreach($list as $k=>$v){
			$openid = $Users->where(['user_id'=>$v['user_id']])->value('openid');
			$list[$k]['levle_name'] = $v['level']? $UserLevel->where(['levle'=>$v['level']])->value('levle_name') : '';
			
			$money_total = $per_logic->distribut_caculate($v['user_id'],$openid);
			//补业绩
			if($money_total['moneys'] < 0){
				$bu_moneys = -1 * $money_total['moneys'] * 2; //补 两倍 的 差值
				//这里重新
				$add_logic->add($user_id,$bu_moneys);
			   
				//重新来
				$money_total = $per_logic->distribut_caculate();
			}
			$list[$k]['money_total'] = $money_total['money_total'];

		}
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$list]);
    }

    //团队订单列表
    public function order_list(){  
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>null]);
        }

		$next_user_id = I('post.next_user_id/d',0);
		$user_id = $next_user_id ? $next_user_id : $user_id;
		$type = I('post.type/s','');

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = ($page-1)*$num . ',' . $num;
		if($type == 'all'){ //取所有团队成员的订单
			$UsersLogic = new UsersLogic();
			$bot_arr = [];
			$bot_arr = $UsersLogic->getUserLevBotAll($user_id,$bot_arr);
			$bot_arr[] = $user_id;
			$order = M('order')->alias('O')->join('tp_users U','O.user_id=U.user_id','left')->field('O.order_id,O.order_sn, O.consignee, O.add_time,O.total_amount,U.nickname')->where(['O.user_id'=>['in',$bot_arr],'O.pay_status'=>1])->limit($limit)->order('O.add_time DESC')->select();	
			$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$order,'user'=>$user]]);
		}
        $order = M('order')->field('order_id,order_sn, consignee, add_time,total_amount')->where(['user_id'=>$user_id,'pay_status'=>1])->limit($limit)->order('add_time DESC')->select();
        
        $user = M('users')->field('user_id,nickname,mobile')->where(['user_id'=>$user_id])->find();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$order,'user'=>$user]]);
    }

    /**
     * +---------------------------------
     * 我的主页
     * +---------------------------------
    */
    public function myIndex()
    {	
        $user_id = $this->get_user_id();	

		if(!$user_id)
			$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>(object)null]);


        //当前登录用户信息
        $logic = new UsersLogic();
        $user_info = $logic->get_info($user_id); 
        // $order_info['uncomment_count'] = $user_info['result']['uncomment_count'];
        // 用户名称头像
        $user = Db::name("users")
        ->where('user_id',$user_id)
        ->field('nickname,head_pic,pay_points,user_money')
        ->find();

        // 统计优惠券
        $coupon_num = Db::name("coupon_list")->where('uid',$user_id)->count();
        // 个人信息
        $order_info['nickname'] = $user['nickname'];
        $order_info['head_pic'] = $user['head_pic'];
        $order_info['user_money'] = $user['user_money'];

        // 收藏 足迹 店铺关注 
        $goods_collect_num = M('goods_collect')->where('user_id', $user_id)->count();
        $goods_visit_num = M('goods_visit')->where('user_id', $user_id)->count();
        $seller_goods_num = M('seller_collect')->where('user_id', $user_id)->count();

        $order_info['goods_collect_num'] = $goods_collect_num;
        $order_info['goods_visit_num'] = $goods_visit_num;
        $order_info['seller_goods_num'] = $seller_goods_num;

        // 我的订单状态
        $order_info['waitPay'] = $user_info['result']['waitPay'];
        $order_info['waitSend'] = $user_info['result']['waitSend'];
        $order_info['waitReceive'] = $user_info['result']['waitReceive'];
		$order_info['uncomment_count'] = M('Order')->where(['user_id'=>$user_id,'order_status'=>2])->count();

        // 积分优惠券
        $order_info['pay_points'] =$user['pay_points'];
        $order_info['coupon_num'] = $coupon_num;

        // 公益基金
        //todo
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$order_info]);
    }

    /**
     * +---------------------------------
     * 店铺关注列表
     * +---------------------------------
     */
    public function getSellerCollect(){ 
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1) * $num) . ',' . $num;

        // 获取所有店铺
        $seller_arr = Db::name('seller_collect')
        ->alias('a')
        ->join('tp_seller b', 'a.seller_id=b.seller_id','left')
        ->join('tp_seller_store c', 'b.seller_id=c.seller_id', 'left')
        ->where('user_id',$user_id)
        ->field('a.collect_id,a.seller_id,c.store_name as seller_name,c.avatar')
		->limit($limit)
        ->select();

		$count = Db::name('seller_collect')
        ->alias('a')
        ->join('tp_seller b', 'a.seller_id=b.seller_id','left')
        ->join('tp_seller_store c', 'b.seller_id=c.seller_id', 'left')
        ->where('user_id',$user_id)
        ->field('a.collect_id,a.seller_id,b.seller_name,c.avatar')
        ->count();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$seller_arr,'count'=>$count]]);

    }

	//添加/删除店铺关注
	public function add_seller_collect(){ 
        $user_id = $this->get_user_id();	
		$seller_id = I('post.seller_id/d',0);
		$type = I('post.type/d',1);
		$collect_id = I('post.collect_id/d',1);
        if (!$user_id || !$seller_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>null]);
        }

		if($type == 1){
			$res = M('seller_collect')->where(['user_id'=>$user_id,'seller_id'=>$seller_id])->find();
			if($res)$this->ajaxReturn(['status' => 0 , 'msg'=>'您已经关注过此店铺啦','data'=>null]);
			M('seller_collect')->add(['user_id'=>$user_id,'seller_id'=>$seller_id,'add_time'=>time()]);
		}else{
			if(!$collect_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'关注ID必须','data'=>null]);
			 $res = M('seller_collect')->where(['user_id'=>$user_id,'collect_id'=>$collect_id])->find();
			 if(!$res)$this->ajaxReturn(['status' => -1 , 'msg'=>'您没有关注过此店铺','data'=>null]);
			M('seller_collect')->delete($collect_id);
		}
		$this->ajaxReturn(['status' => 0 , 'msg'=>'操作成功','data'=>null]);
	}

    /**
     *  用户消息通知
     * @author yhj
     * @time 2018/07/10
     */
    public function message_notice()
    {	
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$type = I('post.type/d',1); //1：消息，2：公告
        $message_logic = new Message($user_id);
        $message_logic->checkPublicMessage();
        $where = array(
            'user_id' => $user_id,
            'deleted' => 0,
        );
		$where['category'] = (($type == 1) ? ['neq',4] : 4);
		$limit = (($page - 1) * $num) . ',' . $num;
        $list = M('user_message')->where($where)->LIMIT($limit)->order('rec_id desc')->select();

		$MessageActivity = M('message_activity');
		$MessageLogistics = M('message_logistics');
		$MessageNotice = M('message_notice');
		$MessagePrivate = M('message_private');
		foreach($list as $k=>$v){
			if($v['category'] == 0)
				$info = $MessageNotice->field('message_title,message_content,send_time')->where(['message_id'=>$v['message_id']])->find();
			if($v['category'] == 1)
				$info = $MessageActivity->field('message_title,message_content,send_time')->where(['message_id'=>$v['message_id']])->find();
			if($v['category'] == 2)
				$info = $MessageLogistics->field('message_title,message_content,send_time')->where(['message_id'=>$v['message_id']])->find();
			if($v['category'] == 4)
				$info = $MessageNotice->field('message_title,message_content,send_time')->where(['message_id'=>$v['message_id']])->find();
			$list[$k]['message_title'] = $info['message_title'];
			$list[$k]['send_time'] = $info['send_time'];
			$list[$k]['message_content'] = $info['message_content'];
		}

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$list]]);
    }

    /**
     * 通知消息详情
     */
    public function message_notice_info(){
        $user_id = $this->get_user_id();
		$rec_id = I('post.rec_id/d',0);
        if (!$user_id || !$rec_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }	

		$UserMessage = M('user_message');
		$minfo = $UserMessage->find($rec_id);
		$UserMessage->update(['rec_id'=>$rec_id,'is_see'=>1]);
		if($minfo['category'] == 0)
			$info = M('Message_notice')->field('message_title,message_content,send_time')->where(['message_id'=>$minfo['message_id']])->find();
		if($minfo['category'] == 1)
			$info = M('Message_activity')->field('message_title,message_content,send_time')->where(['message_id'=>$minfo['message_id']])->find();
		if($minfo['category'] == 2)
			$info = M('Message_logistics')->field('message_title,message_content,send_time')->where(['message_id'=>$minfo['message_id']])->find();
		if($minfo['category'] == 4)
			$info = M('Message_notice')->field('message_title,message_content,send_time')->where(['message_id'=>$minfo['message_id']])->find();
        
		$minfo['message_title'] = $info['message_title'];
		$minfo['send_time'] = $info['send_time'];
		$minfo['message_content'] = htmlspecialchars_decode($info['message_content']);

		$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['info'=>$minfo]]);
    }

	//余额明细
	public function account_list()
    { 
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page-1)*$num) . ',' . $num;

    	$type = I('type','all');  //全部all，赚取plus，消费minus
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->account($user_id, $type, $limit);		
		$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$result['account_log']]]);
    }

	//充值记录
    public function recharge_list(){ 
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page-1)*$num) . ',' . $num;

    	$usersLogic = new UsersLogic;
        $result= $usersLogic->get_recharge_log($user_id,0,'recharge',$limit);  //充值记录
    	$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$result['result']]]);
    }

    //提现记录
    public function withdrawals_list()
    {	
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page-1)*$num) . ',' . $num;

        $withdrawals_where['user_id'] = $user_id;
        $list = M('withdrawals')->field('id,money,create_time,check_time,pay_time,refuse_time,bank_name,bank_card,realname,remark,taxfee,status,pay_code,error_code')->where($withdrawals_where)->order("id desc")->limit($limit)->select();

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$list]]);
    }

    /**
     *  注册
     */
    public function reg()
    {
        $reg_sms_enable = tpCache('sms.regis_sms_enable');
        $reg_smtp_enable = tpCache('sms.regis_smtp_enable');

        if (IS_POST) {
            $logic = new UsersLogic();
            //验证码检验
            //$this->verifyHandle('user_reg');
            $nickname = I('post.nickname', '');
            $username = I('post.username', '');
            if(!$username){
                $username = I('post.useriphone');
            }
            $password = I('post.password', '');
            $password2 = I('post.password2', '');  
            $is_bind_account = tpCache('basic.is_bind_account');
            //是否开启注册验证码机制
            $code = I('post.code', '');
            $scene = I('post.scene', 1);
            //$session_id = session_id();

            //是否开启注册验证码机制
            if(check_mobile($username)){
                if($reg_sms_enable){
                    //手机功能没关闭
                    $check_code = $logic->check_validate_code($code, $username, 'phone', $session_id, $scene);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn($check_code);
                    }
                }
            }
            //是否开启注册邮箱验证码机制
            if(check_email($username)){
                if($reg_smtp_enable){
                    //邮件功能未关闭
                    $check_code = $logic->check_validate_code($code, $username);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn($check_code);
                    }
                }
            }
            
            $invite = I('invite');
            if(!empty($invite)){
                $invite = get_user_info($invite,2);//根据手机号查找邀请人
                if(empty($invite)){
                    $this->ajaxReturn(['status'=>-1,'msg'=>'推荐人不存在','result'=>'']);
                }
            }else{
                $invite = array();
            }
            if($is_bind_account && session("third_oauth")){ //绑定第三方账号
                $thirdUser = session("third_oauth");
                $head_pic = $thirdUser['head_pic'];
                $data = $logic->reg($username, $password, $password2, 0, $invite ,$nickname , $head_pic);
                //用户注册成功后, 绑定第三方账号
                $userLogic = new UsersLogic();
                $data = $userLogic->oauth_bind_new($data['result']);
            }else{
                $data = $logic->reg($username, $password, $password2,0,$invite,$nickname);
            }
             
            
            if ($data['status'] != 1) $this->ajaxReturn($data);
            
            //获取公众号openid,并保持到session的user中
            //$oauth_users = M('OauthUsers')->where(['user_id'=>$data['result']['user_id'] , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();
            //$oauth_users && $data['result']['open_id'] = $oauth_users['open_id'];
            
            //setcookie('user_id', $data['result']['user_id'], null, '/');
            //('is_distribut', $data['result']['is_distribut'], null, '/');
			if($data['status'] == 1)
				$this->ajaxReturn(['status' => 0 , 'msg'=>'注册成功','data'=>['user_id'=>$data['user_id'],'token'=>$data['token'],'head_pic'=>$data['head_pic']]]);
			else
				$this->ajaxReturn(['status' => -1 , 'msg'=>$data['msg'], 'data'=>null]);
        }
        
    }

	//微信登录
	public function weixin_login(){
		//上面获取到code后这里跳转回来
		$code =I('post.code/s','');
		$data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid

		$first_leader = $this->user_unionid($data['unionid']);
		!$first_leader && $first_leader = $this->user_openid($data['openid']);
		$this->write_log('first_leader:'.$first_leader);
		$this->write_log("openid:".$data['openid']);
		$data2 = $this->GetUserInfo($data['access_token'],$data['openid']);//获取微信用户信息
		$data['nickname'] = $data2['nickname'] ? replaceSpecialStr($data2['nickname']) : '微信用户';
		//$data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
		$data['sex'] = $data2['sex'];
		$data['first_leader'] = $first_leader;
		$data['head_pic'] = $data2['headimgurl']; 
		$data['subscribe'] = $data2['subscribe'];      
		$data['oauth_child'] = 'mp';
		$data['oauth'] = 'weixin';
		if(isset($data2['unionid'])){
			$data['unionid'] = $data2['unionid'];
		}
		$userinfo = M('users')->field('user_id,token')->where(['openid'=>$data['openid']])->find();
		if(!$userinfo){
			$logic = new UsersLogic(); 
			$data = $logic->thirdLogin($data);
			$data['status'] = ($data['status'] == 1) ? 0 : $data['status'];
			if(isset($data['result'])){
				$data['data']['user_id'] = $data['result']['user_id'];
				$data['data']['token'] = $data['result']['token'];
				unset($data['result']);
			}
		}else
			$data = ['status' => 0 , 'msg'=>'请求成功', 'data'=>$userinfo];

		$data['data']['token'] = $this->create_token($data['data']['user_id']);
		$this->ajaxReturn($data);
	}

	//找回密码短信对比
	public function FindPwdCheckSms(){
		$mobile = I('post.mobile/s','');
        $code = I('post.code/s','');
        $scene = I('post.scene/d',2);

		if(empty($mobile) || empty($code))$this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误', 'data'=>null]);

		$info = M('sms_log')->where(['mobile'=>$mobile,'scene'=>$scene])->order('add_time desc')->find();
		if(!$info)$this->ajaxReturn(['status' => -1 , 'msg'=>'请先获取验证码', 'data'=>null]);
		if(($info['add_time']+180) < time())$this->ajaxReturn(['status' => -1 , 'msg'=>'验证码已失效', 'data'=>null]);
		if($code != $info['code'])
			$this->ajaxReturn(['status' => -1 , 'msg'=>'验证码错误', 'data'=>null]);
		else
			$this->ajaxReturn(['status' => 0 , 'msg'=>'验证码正确', 'data'=>null]);
	}

	//找回密码
	public function FindPwd(){
		$mobile = I('post.mobile/s','');
		$password = I('post.password/s','');
        $password2 = I('post.password2/s','');
        $scene = I('post.scene/d',2);


		if(!$mobile || !$password || !$password2)$this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误', 'data'=>null]);
		if(strlen($password) < 6)$this->ajaxReturn(['status' => -1 , 'msg'=>'密码至少6位', 'data'=>null]);
		if($password != $password2)$this->ajaxReturn(['status' => -1 , 'msg'=>'两次密码不一致', 'data'=>null]);

		if(strlen($password) < 6){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'密码至少6位', 'data'=>null]);
        }

		if($password != $password2){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'两次密码不一致', 'data'=>null]);
        }

        $user_id = M('Users')->where(['mobile'=>$mobile])->value('user_id');

        if($scene == 2){
            $res = M('Users')->update(['user_id'=>$user_id,'password'=>encrypt($password)]);
        }elseif($scene == 6){
            $res = M('Users')->update(['user_id'=>$user_id,'paypwd'=>encrypt($password)]);
        }else{
            $this->ajaxReturn(['status' => -3 , 'msg'=>'scene参数错误', 'data'=>null]);
        }

		if(false !== $res){
			$this->ajaxReturn(['status' => 0 , 'msg'=>'操作成功', 'data'=>null]);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'操作失败', 'data'=>null]);
        }

    }

	//修改密码
	public function UpdatePwd(){
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$passold = I('post.passold/s','');
		$password = I('post.password/s','');
        $password2 = I('post.password2/s','');

		if(!$passold || !$password || !$password2)$this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误', 'data'=>null]);
		if(strlen($password) < 6)$this->ajaxReturn(['status' => -1 , 'msg'=>'密码至少6位', 'data'=>null]);
		if($password != $password2)$this->ajaxReturn(['status' => -1 , 'msg'=>'两次密码不一致', 'data'=>null]);

		if(strlen($password) < 6){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'密码至少6位', 'data'=>null]);
        }

		if($password != $password2){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'两次密码不一致', 'data'=>null]);
        }

        $userinfo = M('Users')->where(['user_id'=>$user_id,'password'=>encrypt($passold)])->count();
        if(!$userinfo){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'原密码错误', 'data'=>null]);
        }

        $res = M('Users')->update(['user_id'=>$user_id,'password'=>encrypt($password)]);

		if(false !== $res){
			$this->ajaxReturn(['status' => 0 , 'msg'=>'修改密码成功', 'data'=>null]);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'修改密码失败', 'data'=>null]);
        }

    }    
    
    //业绩明细
    public function performance_log(){
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

    	$DistributLogic = new \app\common\logic\DistributLogic;
        $result= $DistributLogic->get_recharge_log($user_id,'','agent_performance_log');  //业务记录
        $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>['list'=>$result['result']]]); 
    }
	
	//明细记录
    public function commision(){ 
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = ($page - 1) * $num . ',' . $num;
    	$DistributLogic = new \app\common\logic\DistributLogic;
        $result= $DistributLogic->get_commision_log($user_id,0,$limit);  //佣金明细
        $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>['list'=>$result['result']]]); 
    }

    //下级分销订单
    public function distribut_order()
    {	
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = ($page - 1) * $num . ',' . $num;
        $data = array(
            'user_id' => $user_id,
            'states' => 102,
        );

        $divide_order = M('order_divide')->where($data)->group('order_id')
                   ->limit($limit)->column('order_id');

        $orders = M('order')->where('order_id', ['in', $divide_order])->order('order_id DESC')
                ->field('user_id, order_id, pay_time')->select();
        $user_ids = array_column($orders, 'user_id');
        $lower = M('users')->where('user_id', ['in', $user_ids])->column('user_id, nickname');

        //添加下级昵称
		$OrderGoods = M('Order_goods');
        foreach($orders as $key => $value){
            $orders[$key]['nickname'] = $lower[$value['user_id']];
			$orders[$key]['goods'] = $OrderGoods->field('goods_name,goods_num,final_price')->where(['order_id'=>$value['order_id']])->select();
        }
    
        $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>['list'=>$orders]]); 
    }
    
    //绑定支付宝
    public function BindZfb(){ 
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

        $zfb_account = I('post.zfb_account/s','');
        $realname = I('post.realname/s','');
        if (!$zfb_account || !$realname) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'请输入支付宝账号或真实姓名','data'=>(object)null]);
        }

        $res = M('Users')->update(['user_id'=>$user_id,'realname'=>$realname,'alipay'=>$zfb_account]);
        if (false !== $zfb_account)   
           $this->ajaxReturn(['status' => 0 , 'msg'=>'绑定成功','data'=>null]);
        else
            $this->ajaxReturn(['status' => -1 , 'msg'=>'绑定失败','data'=>null]);
    }

    /**
     * 申请提现
     */
    public function withdrawals()
    {   
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }
        //paypwd money
        $cash_open=tpCache('cash.cash_open');
        if($cash_open!=1){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'提现功能已关闭,请联系商家','data'=>(object)null]);
        }

        $data = I('post.'); 
        $data['user_id'] = $user_id;
        $data['create_time'] = time();
        $cash = tpCache('cash');

        $userinfo = M('Users')->field('user_money,paypwd')->find($user_id);
        $user_money = $userinfo['user_money'];
        if(encrypt($data['paypwd']) != $userinfo['paypwd']){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'支付密码错误','data'=>(object)null]);
        }
        if ($data['money'] > $user_money) {
            $this->ajaxReturn(['status' => -4 , 'msg'=>'本次提现余额不足','data'=>(object)null]);
        } 
        if ($data['money'] <= 0) {
            $this->ajaxReturn(['status' => -5 , 'msg'=>'提现额度必须大于0','data'=>(object)null]);
        }

        // 统计所有0，1的金额
        //$status = ['in','0,1'];   
        // $status
        $total_money = Db::name('withdrawals')->where(array('user_id' => $user_id, 'status' => 0))->sum('money');
        if ($total_money + $data['money'] > $user_money) {
            $this->ajaxReturn(['status' => -6 , 'msg'=>'您有提现申请待处理，本次提现余额不足','data'=>(object)null]);
        }
        if ($cash['cash_open'] == 1) {
            $taxfee =  round($data['money'] * $cash['service_ratio'] / 100, 2);
            // 限手续费
            if ($cash['max_service_money'] > 0 && $taxfee > $cash['max_service_money']) {
                $taxfee = $cash['max_service_money'];
            }
            if ($cash['min_service_money'] > 0 && $taxfee < $cash['min_service_money']) {
                $taxfee = $cash['min_service_money'];
            }
            if ($taxfee >= $data['money']) {
                $this->ajaxReturn(['status' => -7 , 'msg'=>'提现额度必须大于手续费！','data'=>(object)null]);
            }
            $data['taxfee'] = $taxfee;

            // 每次限最多提现额度
            if ($cash['min_cash'] > 0 && $data['money'] < $cash['min_cash']) {
                $this->ajaxReturn(['status' => -8 , 'msg'=>'每次最少提现额度','data'=>(object)null]);
            }
            if ($cash['max_cash'] > 0 && $data['money'] > $cash['max_cash']) {
                $this->ajaxReturn(['status' => -9 , 'msg'=>'每次最多提现额度','data'=>(object)null]);
            }

            $status = ['in','0,1,2,3'];
            $create_time = ['gt',strtotime(date("Y-m-d"))];
            // 今天限总额度
            if ($cash['count_cash'] > 0) {
                $total_money2 = Db::name('withdrawals')->where(array('user_id' => $user_id, 'status' => $status, 'create_time' => $create_time))->sum('money');
                if (($total_money2 + $data['money'] > $cash['count_cash'])) {
                    $total_money = $cash['count_cash'] - $total_money2;
                    if ($total_money <= 0) {
                        $this->ajaxReturn(['status' => -10 , 'msg'=>"您今天累计提现额为{$total_money2},金额已超过可提现金额.",'data'=>(object)null]);
                    } else {
                        $this->ajaxReturn(['status' => -10 , 'msg'=>"您今天累计提现额为{$total_money2}，最多可提现{$total_money}账户余额.",'data'=>(object)null]);
                    }
                }
            }
            // 今天限申请次数
            if ($cash['cash_times'] > 0) {
                $total_times = Db::name('withdrawals')->where(array('user_id' => $user_id, 'status' => $status, 'create_time' => $create_time))->count();
                if ($total_times >= $cash['cash_times']) {
                    $this->ajaxReturn(['status' => -10 , 'msg'=>"今天申请提现的次数已用完",'data'=>(object)null]);
                }
            }
        }else{
            $data['taxfee'] = 0;
        }

        if (M('withdrawals')->add($data)) {
            
            accountLog($user_id, -$data['money'] , 0, '提现扣款',  0, 0, '');

            // 发送公众号消息给用户
            $user = Db::name('OauthUsers')->where(['user_id'=>$user_id ])->find();
            if ($user) {
                $wx_content = "您的提现申请已提交，正在处理...";
                $wechat = new \app\common\logic\wechat\WechatUtil();
                $wechat->sendMsg($user['openid'], 'text', $wx_content);
            }
            
            $this->ajaxReturn(['status'=>0,'msg'=>"已提交申请",'data'=>null]);
        } else {
            $this->ajaxReturn(['status'=>-11,'msg'=>'提交失败,联系客服!','data'=>null]);
        }
    }   

    public function GetSharePic(){
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

        if(!$user_id){
            $this->ajaxReturn(['status'=>-2,'msg'=>"您没有分享权限",'data'=>null]);
        }
        $userinfo = M('users')->where(['user_id'=>$user_id])->find();
        if(!$userinfo){
            $this->ajaxReturn(['status'=>-2,'msg'=>"您没有分享权限",'data'=>null]);
        }
        if($userinfo['is_distribut'] == 0 && $userinfo['is_agent'] == 0){
            $this->ajaxReturn(['status'=>-2,'msg'=>"您没有分享权限",'data'=>null]);
        }


        define('IMGROOT_PATH', str_replace("\\","/",realpath(dirname(dirname(__FILE__)).'/../../'))); //图片根目录（绝对路径）
       
        //加上 refresh == 1 , 强制重新获取海报
        if(I('refresh') == '1'){
            //删掉文件
            @unlink(IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg');//删除头像
            @unlink(IMGROOT_PATH."/public/share/picture_ok44/'.$user_id.'.jpg");//删除 44
            @unlink(IMGROOT_PATH."/public/share/picture_888/".$user_id.".jpg");

            //强制获取头像
            $openid = M('Users')->where(['user_id'=>$user_id])->value('openid');
            $access_token = access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
            $resp = httpRequest($url, "GET");
            $res = json_decode($resp, true);
           
            $head_pic = $res['headimgurl'];
            if($head_pic){
                //得到头像
                M('users')->where(['openid'=>$openid])->update(['head_pic'=>$head_pic]);
            }
        }
        

        //没头像 默认头像
        $head_pic_url = M('users')->where(['user_id'=>$user_id])->value('head_pic');
        if(!$head_pic_url || $head_pic_url == ''){
            $head_pic_url = '/public/images/default.jpg';
        }

        $logic = new ShareLogic();
        $ticket = $logic->get_ticket($user_id);

        if( strlen($ticket) < 3){
            $this->error("ticket不能为空");
            exit;
        }
        $url= "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;

        $url222 = IMGROOT_PATH.'/public/share/code/'.$user_id.'.jpg';
        if( @fopen( $url222, 'r' ) )
        {
            //已经有二维码了
        	$url_code = IMGROOT_PATH.'/public/share/code/'.$user_id.'.jpg';
        }else{
            //还没有二维码
            $re = $logic->getImage($url,IMGROOT_PATH.'/public/share/code', $user_id.'.jpg');
            $url_code = $re['save_path'];
        }
        
        //判断图片大小
        $logo_url = \think\Image::open($url_code);
        $logo_url_logo_width = $logo_url->height();
        $logo_url_logo_height = $logo_url->width();

        if($logo_url_logo_height > 420 || $logo_url_logo_width > 420){
            //压缩图片
            $url_code = IMGROOT_PATH.'/public/share/code/'.$user_id.'.jpg';
            $logo_url->thumb(410, 410)->save($url_code , null, 100);
        }

        $head_url = IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg';
        if( @fopen( $head_url, 'r' ) )
        {
            //已经有二维码了
        	$url_head_pp = IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg';
        }else{
            //还没有二维码
            $re = $logic->getImage($head_pic_url,IMGROOT_PATH.'/public/share/head', $user_id.'.jpg');
            $url_head_pp = $re['save_path'];
        }
        
        //判断图片大小
        $logo = \think\Image::open($url_head_pp);
        $logo_width = $logo->height();
        $logo_height = $logo->width();
 
        //头像变成200
        if($logo_height > 260 || $logo_width > 260){
            //压缩图片
             $url_head_file = IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg';
             $logo->thumb(240, 240)->save($url_head_file , null, 100);
        }
        
        //得到二维码的绝对路径

        $pic = IMGROOT_PATH."/public/share/picture_ok44/'.$user_id.'.jpg";
        if( @fopen( $pic, 'r' ) )
        {
        	$pic = "/share/picture_ok44/".$user_id.".jpg";
        }
        else
        {
        	$image = \think\Image::open(IMGROOT_PATH.'/public/share/bg1.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_code,\think\Image::DCHQZG)->save(IMGROOT_PATH.'/public/share/picture_ok44/'.$user_id.'.jpg');
        	
        	$pic = "/public/share/picture_ok44/".$user_id.".jpg";
        }
    
        //再次叠加

        $pic111 = IMGROOT_PATH."/public/share/picture_888/".$user_id.".jpg";
        if( @fopen( $pic111, 'r' ) )
        {
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }
        else
        {
        	$image = \think\Image::open(IMGROOT_PATH.'/public/share/picture_ok44/'.$user_id.'.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_head_pp,\think\Image::TOUXIANG)->save(IMGROOT_PATH.'/public/share/picture_888/'.$user_id.'.jpg');
          
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }

        $picture = $picture.'?v='.time();
        $this->ajaxReturn(['status' => 0, 'msg' => "请求成功", 'data'=>['pic'=>$picture]]);
    }

	//绑定手机号
	public function BindTel(){
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>(object)null]);
        }

		$mobile = I('post.mobile/s','');
		$mobile_code = I('post.mobile_code/s','');
		$scene = I('post.scene', 6);

		if(!$mobile || !$mobile_code)$this->ajaxReturn(['status' => -1 , 'msg'=>'请输入手机号码或验证码','data'=>(object)null]);

		$c = Db::name('users')->where(['mobile' => $mobile, 'user_id' => ['<>', $user_id]])->count();
		$c && $this->ajaxReturn(['status' => -2, 'msg' => "手机已被使用",'data'=>null]);
		if (!$mobile_code)
			$this->ajaxReturn(['status' => -3, 'msg' => "请输入验证码",'data'=>null]);

		$userLogic = new UsersLogic();
		$check_code = $userLogic->check_validate_code($mobile_code, $mobile, 'phone', 0, $scene);
		if($check_code['status'] !=1){
			$this->ajaxReturn(['status' => -4, 'msg' => $check_code['msg']]);
		}

		$res = Db::name('users')->where(['user_id' => $user_id])->update(['mobile'=>$mobile,'mobile_validated'=>1]);

		if($res!==false){
			$this->ajaxReturn(['status' => 0, 'msg' => '绑定成功','data'=>null]);
		}else
			$this->ajaxReturn(['status' => 0, 'msg' => '绑定失败','data'=>null]);
			
	}

//----------------------------------------------------------------------------------------------------------

    private function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
    	//1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
    	//2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);       
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);         
        curl_close($ch);
        return $data;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = C('customize.WX_APP_LOGIN_APPID');
        $urlObj["secret"] = C('customize.WX_APP_LOGIN_SECRET');
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
		$urlObj["connect_redirect"] = 1;
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    private function write_log($content)
    {
        $content = "[".date('Y-m-d H:i:s')."]".$content."\r\n";
        $dir = rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']),'/').'/logs';
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        $path = $dir.'/'.date('Ymd').'.txt';
        file_put_contents($path,$content,FILE_APPEND);
    }
    private function user_openid($openid){
        $user = M('users')->where(['openid'=>$openid])->find();
        if($user){
            return $user['first_leader'];
        }else{
            $user['first_leader']=0;
            return $user['first_leader'];
        }
    }

    private function user_unionid($unionid){
        $user = M('users')->where(['unionid'=>$unionid])->find();
        if($user){
            return $user['first_leader'];
        }else{
            $user['first_leader']=0;
            return $user['first_leader'];
        }
    }

        /**
     *
     * 通过access_token openid 从工作平台获取UserInfo      
     * @return openid
     */
    private function GetUserInfo($access_token,$openid)
    {         
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);            
        curl_close($ch);
        //获取用户是否关注了微信公众号， 再来判断是否提示用户 关注
        //if(!isset($data['unionid'])){
            $wechat = new WechatUtil($this->weixin_config);
            $fan = $wechat->getFanInfo($openid);//获取基础支持的access_token
            if ($fan !== false) {
                $data['subscribe'] = $fan['subscribe'];
            }
        //}
        return $data;
    }

    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址     
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';        
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;                    
    }  


}
