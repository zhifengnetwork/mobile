<?php
namespace app\home\controller;
use think\Page;
use think\Verify;
use app\common\logic\SmsChuanglanLogic;
use think\Image;
use think\Db;
class Index extends Base {
    

    public function test(){

        // $user = M('users')->where(['user_id'=>$user_id])->find();

        // $access_token = access_token();
        // $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$user['openid'].'&lang=zh_CN';
    
        // $resp = httpRequest($url, "GET");
        // $res = json_decode($resp, true);

        // dump($res);

        //

        // $res = M('users')->where(['is_distribut'=>0,'is_agent'=>1])->field('user_id,nickname,is_distribut,is_agent')->limit(100)->select();
        
        // foreach($res as $k => $v){
        //     M('users')->where(['user_id'=>$v['user_id']])->update(['is_distribut'=>1]);
        // }

        // dump($res);

        // ini_set('max_execution_time', '0');

        // $order = M('order')->where(['pay_status'=>1])->field('order_id,user_id')->select();

        // dump($order);

        // foreach($order as $key => $val){
        //     $res = can_super_nsign($val['order_id'],$val['user_id']);
        // }

        // exit;

        // $account = M('config')->where(['name'=>'sms_appkey'])->value('value');
        // $password = M('config')->where(['name'=>'sms_secretKey'])->value('value');;
        // $logic = new SmsChuanglanLogic($account,$password);
        // $mobile = '13516565558';
        // $msg = '【253云通讯】测试一下,这里是内容';
        // $res = $logic->sendSMS($mobile, $msg, $needstatus = 'true');

        // "{"code":"0","msgId":"19031122454723995","time":"20190311224547","errorMsg":""}"
    }

    public function index(){      

        // 如果是手机跳转到 手机模块
        if(isMobile()){
            header("Location:".U('Mobile/Index/index'));
        }
        $hot_goods = $hot_cate = $cateList = $recommend_goods = array();
        $sql = "select a.goods_name,a.goods_id,a.shop_price,a.market_price,a.cat_id,b.parent_id_path,b.name from ".C('database.prefix')."goods as a left join ";
        $sql .= C('database.prefix')."goods_category as b on a.cat_id=b.id where a.is_hot=1 and a.is_on_sale=1 order by a.sort";//二级分类下热卖商品       
        $index_hot_goods = S('index_hot_goods');
        if(empty($index_hot_goods))
        {
            $index_hot_goods = Db::query($sql);//首页热卖商品
            S('index_hot_goods',$index_hot_goods,TPSHOP_CACHE_TIME);
        }
       
        if($index_hot_goods){
              foreach($index_hot_goods as $val){
                  $cat_path = explode('_', $val['parent_id_path']);
                  $hot_goods[$cat_path[1]][] = $val;
              }
        }
        
        $sql2 = "select a.goods_name,a.goods_id,a.shop_price,a.market_price,a.cat_id,b.parent_id_path,b.name from ".C('database.prefix')."goods as a left join ";
        $sql2 .= C('database.prefix')."goods_category as b on a.cat_id=b.id where a.is_recommend=1 and a.is_on_sale=1 order by a.sort";//二级分类下热卖商品
        $index_recommend_goods = S('index_recommend_goods');
        if(empty($index_recommend_goods))
        {
        	$index_recommend_goods = Db::query($sql2);//首页推荐商品
        	S('index_recommend_goods',$index_recommend_goods,TPSHOP_CACHE_TIME);
        }
         
        if($index_recommend_goods){
        	foreach($index_recommend_goods as $va){
        		$cat_path2 = explode('_', $va['parent_id_path']);
        		$recommend_goods[$cat_path2[1]][] = $va;
        	}
        }

        $hot_category = M('goods_category')->where("is_hot=1 and level=3 and is_show=1")->cache(true,TPSHOP_CACHE_TIME)->select();//热门三级分类
        foreach ($hot_category as $v){
        	$cat_path = explode('_', $v['parent_id_path']);
        	$hot_cate[$cat_path[1]][] = $v;
        }
        foreach ($this->cateTrre as $k=>$v){
            if($v['is_hot']==1){
        		$v['hot_goods'] = empty($hot_goods[$k]) ? '' : $hot_goods[$k];
        		$v['recommend_goods'] = empty($recommend_goods[$k]) ? '' : $recommend_goods[$k];
        		$v['hot_cate'] = empty($hot_cate[$k]) ? array() : $hot_cate[$k];
        		$cateList[]=$goods_category_tree[] = $v;
        	}else{
                $goods_category_tree[] = $v;
            }
        }
        $this->assign('cateList',$cateList);
        $this->assign('goods_category_tree',$goods_category_tree);
        return $this->fetch();
    }
 
    /**
     *  公告详情页
     */
    public function notice(){
        return $this->fetch();
    }
    
    // 二维码
    public function qr_code_raw(){        
        ob_end_clean();
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.dchqzg1688.com/Home/Index/erweima/data/www.99soubao.com
         //require_once 'vendor/phpqrcode/phpqrcode.php';
         vendor('phpqrcode.phpqrcode'); 
          //import('Vendor.phpqrcode.phpqrcode');
            error_reporting(E_ERROR);            
            $url = urldecode($_GET["data"]);
            \QRcode::png($url);
			exit;        
    }
    
    // 二维码
    public function qr_code()
    {
        ob_end_clean();
        vendor('topthink.think-image.src.Image');
        vendor('phpqrcode.phpqrcode');

        error_reporting(E_ERROR);
        $url = isset($_GET['data']) ? $_GET['data'] : '';
        $url = urldecode($url);
        $head_pic = input('get.head_pic', '');
        $back_img = input('get.back_img', '');
        $valid_date = input('get.valid_date', 0);
        
        $qr_code_path = UPLOAD_PATH.'qr_code/';
        if (!file_exists($qr_code_path)) {
            mkdir($qr_code_path);
        }
        
        /* 生成二维码 */
        $qr_code_file = $qr_code_path.time().rand(1, 10000).'.png';
        \QRcode::png($url, $qr_code_file, QR_ECLEVEL_M);
        
        /* 二维码叠加水印 */
        $QR = Image::open($qr_code_file);
        $QR_width = $QR->width();
        $QR_height = $QR->height();

        /* 添加背景图 */
        if ($back_img && file_exists($back_img)) {
            $back =Image::open($back_img);
            $back->thumb($QR_width, $QR_height, \think\Image::THUMB_CENTER)
             ->water($qr_code_file, \think\Image::WATER_NORTHWEST, 60);//->save($qr_code_file);
            $QR = $back;
        }
        
        /* 添加头像 */
        if ($head_pic) {
            //如果是网络头像
            if (strpos($head_pic, 'http') === 0) {
                //下载头像
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $head_pic); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
                $file_content = curl_exec($ch);
                curl_close($ch);
                //保存头像
                if ($file_content) {
                    $head_pic_path = $qr_code_path.time().rand(1, 10000).'.png';
                    file_put_contents($head_pic_path, $file_content);
                    $head_pic = $head_pic_path;
                }
            }
            //如果是本地头像
            if (file_exists($head_pic)) {
                $logo = Image::open($head_pic);
                $logo_width = $logo->height();
                $logo_height = $logo->width();
                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = $logo_height / $scale;
                $logo_file = $qr_code_path.time().rand(1, 10000);
                $logo->thumb($logo_qr_width, $logo_qr_height)->save($logo_file, null, 100);
                $QR = $QR->thumb($QR_width, $QR_height)->water($logo_file, \think\Image::WATER_CENTER);     
                unlink($logo_file);
            }
            if ($head_pic_path) {
                unlink($head_pic_path);
            }
        }
        
        if ($valid_date && strpos($url, 'weixin.qq.com') !== false) {
            $QR = $QR->text('有效时间 '.$valid_date, "./vendor/topthink/think-captcha/assets/zhttfs/1.ttf", 7, '#00000000', Image::WATER_SOUTH);
        }
        $QR->save($qr_code_file, null, 100);
        
        $qrHandle = imagecreatefromstring(file_get_contents($qr_code_file));
        unlink($qr_code_file); //删除二维码文件
        header("Content-type: image/png");
        imagepng($qrHandle);
        imagedestroy($qrHandle);
        exit;
    }
    
    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';
        
        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);    
		exit();    
    }

    function truncate_tables (){
        $tables = DB::query("show tables");
        $table = array('tp_admin','tp_config','tp_region','tp_admin_role','tp_system_menu','tp_article_cat','tp_wx_user');
        foreach($tables as $key => $val)
        {                                    
            if(!in_array($val['Tables_in_tpshop2.0'], $table))                             
                echo "truncate table ".$val['Tables_in_tpshop2.0'].' ; ';
                echo "<br/>";         
        }                
    }

    /**
     * 猜你喜欢
     * @author lxl
     * @time 17-2-15
     */
    public function ajax_favorite(){
        $p = I('p/d',1);
        $i = I('i',5); //显示条数
        $time = time();
        $where = ['is_on_sale'=>1 , 'is_virtual' => ['exp' ,"=0 or virtual_indate > $time"]];
        $favourite_goods = Db::name('goods')->where($where)->order('goods_id DESC')->page($p,$i)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    /**
     * 所有订单统计业绩方法
     */
    public function all_order_fous(){
        set_time_limit(0);
        $order_list = Db::name("order")->alias('o')
            ->join('tp_order_goods og','og.order_id = o.order_id')
            ->join('tp_goods g ',' g.goods_id = og.goods_id')
            ->join('tp_users u ',' u.user_id = o.user_id')
            ->order('o.order_id asc')
            ->field('o.order_id, o.total_amount, o.order_sn, o.user_id, og.goods_id, og.goods_num,
            g.shop_price, g.is_distribut, g.is_agent, u.first_leader, u.is_agent as is_user_agent, u.is_distribut as is_user_distribut')
            ->select();
        if(!empty($order_list)) {
            $order_list_data  = [];
            foreach ($order_list as $item) {
                $order_list_data[$item['order_id']][] = $item;
            }
            if(!empty($order_list_data)){
                //获取分红比例
                //$rateArr  = M('user_level')->getField("level,rate");
                $user_list = M('users')->field('user_id,first_leader')->select();
                $user_list_data = [];
                foreach ($user_list as $item){
                    $user_list_data[$item['user_id']][] = $item;
                }

                foreach ($order_list_data as $key =>$value){
                    if($value[0]['total_amount'] <= 9.9){
                        continue;
                    }
                    if(!$value[0]['first_leader']){
                        continue;
                    }
                    $recUser = $this->get_all_up($user_list_data,$value[0]['user_id']);
                    $shop_price = 0;
                    $is_distribut_shop_price = 0;
                    //$is_distribut = 0;
                    //$is_agent = 0;
                    foreach ($value as $ke => $va){
                        if($va['is_agent'] == 1){
                            //$is_agent = 1;
                            $shop_price += ($va['shop_price'] * $va['goods_num']);
                        }
                        if($va['is_distribut'] == 1){
                            //$is_distribut = 1;
                            $is_distribut_shop_price += ($va['shop_price'] * $va['goods_num']);
                        }
                    }
                    // $distribut = M('distribut')->find();
                    // $first_leader = M('users')->where(['user_id'=>$value[0]['first_leader']])->find();

                    //只有代理?
                    if($value[0]['is_user_agent'] == 1 || $value[0]['is_user_distribut'] == 1){
                        // $commission = $is_distribut_shop_price * ($distribut['rate'] / 100);
                        if($is_distribut_shop_price){
                            agent_performance_person_log($first_leader['user_id'], $is_distribut_shop_price, $value[0]['order_id']);
                        }
                    }
                    foreach($recUser as $k => $user){
                        $money = $shop_price;
                        if($money){
                            agent_performance_log($user['user_id'], $money, $value[0]['order_id']);
                        }
                    }
                }
            }
        }
    }

    
    //记录日志
    public function writeLog($userId,$money,$orderSn,$orderId,$goodsId,$desc,$states)
    {
        $data = array(
            'user_id'=>$userId,
            'user_money'=>$money,
            'change_time'=>time(),
            'desc'=>$desc,
            'order_sn'=>$orderSn,
            'order_id'=>$orderId,
            'states'=>$states
        );
        $bool = M('account_log')->insert($data);
        if($bool){
            //分钱记录
            $data = array(
                'order_id'=>$orderId,
                'user_id'=>$userId,
                'status'=>1,
                'goods_id'=>$goodsId,
                'money'=>$money
            );
            M('order_divide')->add($data);
            agent_performance_log($userId, $money, $orderId);
        }
        return $bool;
    }

    /**
     * 获取用户所有上级
     */
    public function get_all_up($data,$user_id,&$list = array()){
        if($data[$user_id][0]['first_leader'] > 0){
            $list[] = $data[$user_id][0];
            $this->get_all_up($data,$data[$user_id][0]['first_leader'],$list);
        }
        return $list;
    }
}