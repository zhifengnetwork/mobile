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
            ->where('user_id',$user_id)
            ->field('user_id,nickname,user_money,head_pic,agent_user,first_leader,realname,mobile,is_distribut,is_agent,sex,birthyear,birthmonth,birthday')
            ->find();
            $data['date_birth'] = $data['birthyear'].'-'.$data['birthmonth'].'-'.$data['birthday'];
            unset($data['birthyear']);
            unset($data['birthmonth']);
            unset($data['birthday']);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
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
        $id = I('get.id/d');
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
        if (!$row)
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
        
            if($user_info['sex'] != I('post.sex')){
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
            $user_info = $userLogic->get_info($user_id); // 获取用户信息

            $result['user_id'] = $user_info['result']['user_id'];
            $result['head_pic'] = $user_info['result']['head_pic'];
            $result['nickname'] = $user_info['result']['nickname'];
            $result['mobile'] = $user_info['result']['mobile'];
            $result['sex'] = $user_info['result']['sex'];
            $result['birthyear'] = $user_info['result']['birthyear'];
            $result['birthmonth'] = $user_info['result']['birthmonth'];
            $result['birthmonth'] = $user_info['result']['birthmonth'];
            $result['birthday'] = $user_info['result']['birthday'];
            $result['province'] = $user_info['result']['province'];
            $result['city'] = $user_info['result']['city'];
            $result['district'] = $user_info['result']['district'];
            $this->ajaxReturn(['status' => 0 , 'msg'=>'保存成功','data'=>$result]);

        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
    }

    /**
     * +---------------------------------
     * 修改个人信息 名称、头像、生日
     * +---------------------------------
    */
    public function my_wallet(){
        
        $user_id = $this->get_user_id();
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($user_id);  // 获取用户信息
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
            'bank_name' => $user_info['bank_name'], // 银行名称
            'bank_card' => $user_info['bank_card'], // 银行卡号
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
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($user_id);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data['result']]);
    }

    /**
     * +---------------------------------
     * 用户浏览记录
     * +---------------------------------
    */
    public function visit_log()
    {
        $user_id = $this->get_user_id();
        $count = M('goods_visit')->where('user_id', $user_id)->count();
        $Page = new Page($count, 20);
        $visit = M('goods_visit')->alias('v')
            ->field('v.visit_id, v.goods_id, v.visittime, g.goods_name, g.shop_price, g.cat_id, g.comment_count, g.sales_sum, g.original_img')
            ->join('__GOODS__ g', 'v.goods_id=g.goods_id')
            ->where('v.user_id', $user_id)
            ->order('v.visittime desc')
            ->limit($Page->firstRow, $Page->listRows)
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
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($user_id);  // 获取用户信息
        if($user_info['result']['status'] == '-1'){
            $this->ajaxReturn(['status' => -1 , 'msg'=>$user_info['result']['msg'],'data'=>(object)null]);
        }
        $user_info = [
            'user_money' => $user_info['result']['user_money'],
            'distribut_money' => $user_info['result']['distribut_money'],
            'total_property' => $user_info['result']['user_money']+$user_info['distribut_money']
        ];
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$user_info]);

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
        $users = M('users')->field('user_id,nickname,mobile')->order('user_id DESC')->where(['first_leader'=>$user_id])->select();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$users]);
    }

    /**
     * +---------------------------------
     * 我的主页
     * +---------------------------------
    */
    public function myIndex()
    {
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }

        //当前登录用户信息
        $logic = new UsersLogic();
        $user_info = $logic->get_info($user_id); 
        // $order_info['uncomment_count'] = $user_info['result']['uncomment_count'];
        // 用户名称头像
        $user = Db::name("users")
        ->where('user_id',$user_id)
        ->field('nickname,head_pic,pay_points')
        ->find();

        // 统计优惠券
        $coupon_num = Db::name("coupon_list")->where('uid',$user_id)->count();
        // 个人信息
        $order_info['nickname'] = $user['nickname'];
        $order_info['head_pic'] = $user['head_pic'];
      

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
        // 获取所有店铺
        $seller_arr = Db::name('seller_collect')
        ->alias('a')
        ->join('tp_seller b', 'a.seller_id=b.seller_id','left')
        ->join('tp_seller_store c', 'b.seller_id=c.seller_id', 'left')
        ->where('user_id',$user_id)
        ->field('a.collect_id,a.seller_id,b.seller_name,c.avatar')
        ->select();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$seller_arr]]);

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
				$info = $MessageNotice->field('message_title,send_time')->where(['message_id'=>$v['message_id']])->find();
			if($v['category'] == 1)
				$info = $MessageActivity->field('message_title,send_time')->where(['message_id'=>$v['message_id']])->find();
			if($v['category'] == 2)
				$info = $MessageLogistics->field('message_title,send_time')->where(['message_id'=>$v['message_id']])->find();
			if($v['category'] == 4)
				$info = $MessageNotice->field('message_title,send_time')->where(['message_id'=>$v['message_id']])->find();
			$list[$k]['message_title'] = $info['message_title'];
			$list[$k]['send_time'] = $info['send_time'];
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
            $code = I('post.mobile_code', '');
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


}
