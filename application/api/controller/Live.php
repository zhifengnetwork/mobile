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

class Live extends ApiBase
{


    /**
     * 申请直播
     */
    public function apply()
    {
        //解密token
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        if($user_id!="") {
//            //选择会员
//            $levelname = M('user_level')->field('level_name')->select();
//
//            foreach ($levelname as $k=>$v){
//                $levelname[$k]=$v['level_name'];
//            }
//
//            foreach ($levelname as $k=>$v){
//                $levelname['name'][$k] = $v;
//            }
//
//            $data['level_name'] = $levelname;


        if (IS_POST) {

            $mobile = I('post.mobile', '');
            $name = I('post.username', '');
            $level = I('post.level_id', '');

            $data['mobile'] = $mobile;
            $data['name'] = $name;
            $data['user_id'] = $user_id;
            $data['level_id'] = $level;
            $data['create_time'] = time();

            // 身份证正面
            $pic_front = request()->file('pic_front');
            $save_url = UPLOAD_PATH.'idcard/' . date('Y', time()) . '/' . date('m-d', time());
            if($pic_front) {
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $image_upload_limit_size = config('image_upload_limit_size');
                    $info = $pic_front->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                    if ($info) {
                        // 成功上传后 获取上传信息
                        // 输出 jpg
                        $comment_img = '/' . $save_url . '/' . $info->getFilename();
                    } else {
                        // 上传失败获取错误信息
                        $this->ajaxReturn(['status' =>-1,'msg' =>$pic_front->getError()]);
                    }
                $data['pic_front'] =$comment_img;
            }

            $pic_back = request()->file('pic_back');
            $save_url = UPLOAD_PATH.'idcard/' . date('Y', time()) . '/' . date('m-d', time());
            if($pic_back) {
                // 移动到框架应用根目录/public/uploads/ 目录下
                $image_upload_limit_size = config('image_upload_limit_size');
                $info = $pic_back->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $comment_img1 = '/' . $save_url . '/' . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->ajaxReturn(['status' =>-1,'msg' =>$pic_back->getError()]);
                }
                $data['pic_back'] =$comment_img1;
            }


        }
//        存入表
            $rel = M('user_verify_identity_info')->field('user_id')->where(['user_id'=>$user_id])->find();
            if($rel){
                $res = M('user_verify_identity_info')->where(['user_id'=>$user_id])->update($data);
            }else{
                $res = M('user_verify_identity_info')->insert($data);
            }
        }
        if ($res){
            $data['pic_back'] =SITE_URL.$comment_img1;
            $data['pic_front'] =SITE_URL.$comment_img;
            $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'提交失败','data'=>$pic_front->getError()]);
        }
    }


    /**
     * 设置开始直播商品
     */
    public function livegoods()
    {
        $user_id = $this->user->user_id;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $goodsList  =  Db::name('goods')->field('goods_id,goods_name,original_img,store_count,market_price,shop_price,cost_price')->limit(0, 100)->select();

        $goodsListData = [];

        foreach ($goodsList as $k => $v) {
            $goodsListData[$k] = $v;
            $goodsListData[$k]['original_img'] = SITE_URL.$v['original_img'];
        }

        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        if (empty($identity)) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'身份验证错误','data'=>'']);
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$goodsListData]);
    }

    /**
     * 开始直播
     */
    public function beginlive()
    {
//        解密token
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $good_ids = rtrim(input('good_ids', ''), ',');

        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        if (empty($identity)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'身份验证错误','data'=>'']);
        }
        if (!empty($good_ids)) {
            $good_ids  = explode(',', $good_ids);
            $goods_arr = json_encode($good_ids, JSON_NUMERIC_CHECK);
        }
        if (!($fengmian = request()->file('image'))) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'请设置封面','data'=>'']);
        }
        //设置封面
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


                $data = [
                    'good_ids' => !empty($good_ids) ? $goods_arr : '',
                    'user_id'  => $user_id,
                    'room_id'  => $user_id . time(),
                    'pic_fengmian' => SITE_URL.'/public/upload/'.$info->getSaveName(),
                    'location' => '',
                    'start_time' => time(),
                    'status' => 1
                ];
                $result = Db::name('user_video')->insert($data);
                if($result){
                    $this->ajaxReturn(['status' => 0 , 'msg'=>'开始直播成功','data'=>$data]);
                }else{
                    $this->ajaxReturn(['status' => -1 , 'msg'=>'开始直播失败','data'=>'']);
                }
            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
            }

        }

    }


    //商品弹窗
    public function goods_upwindows()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_POST) {
            $room_id = I('post.room_id');
            $rel = M('user_video')->field('good_ids')->where(['room_id'=>$room_id])->find();
            $rel['good_ids'] = rtrim($rel['good_ids'],']');

            $rel['good_ids'] = ltrim($rel['good_ids'],'[');
            $goods_id = explode(',',$rel['good_ids']);

            foreach ($goods_id as $k=>$v){
                $good = M('goods')->field('goods_id,original_img,goods_name,shop_price')->where(['goods_id'=>$v,'is_show'=>1])->find();
                $goodimg = M('goods_images')->field('image_url')->where(['goods_id'=>$v])->find();
                $good['original_img'] = SITE_URL. $goodimg['image_url'];
                $goods[$k]=$good;

            }
        }
        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $goods]);
    }
    //红包弹窗
    public function red_upwindows()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_POST) {

            $data['uid'] = $user_id;
            $data['room_id'] = I('post.room_id');
            $data['money'] = I('total_money');
            $data['num'] = I('red_number');
            $data['create_time'] = time();
            $rel = M('red_master')->insert($data);

            if ($rel){
                $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
            }

        }
    }

    //结束直播
    public function liveover()
    {
        $user_id = $this->get_user_id();
//        $user_id = 57603;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_GET) {
            $room_id = I('get.room_id');

            $data = M('user_video')->field('pic_fengmian,user_id,start_time,end_time,money,look_amount,top_amount')->where(['room_id'=>$room_id])->find();
            if ($data){
                $data['pic_fengmian'] = SITE_URL.$data['pic_fengmian'];

                //计算天数
                $timediff = $data['end_time']-$data['start_time'];
                $days = intval($timediff/86400);
                //计算小时数
                $remain = $timediff%86400;
                $hours = intval($remain/3600);
                //计算分钟数
                $remain = $remain%3600;
                $mins = intval($remain/60);
                //计算秒数
                $secs = $remain%60;
                $res = array("hour" => $hours,"min" => $mins,"sec" => $secs);
                if($res['hour'] < 10){
                    $res['hour'] = '0'.$res['hour'];
                }
                if($res['min'] < 10){
                    $res['min'] = '0'.$res['min'];
                }
                if($res['sec'] < 10){
                    $res['sec'] = '0'.$res['sec'];
                }
                $resdata = $res['hour'].':'.$res['min'].':'.$res['sec'];
                $data['live_time'] = $resdata;
                unset($data['end_time']);
                unset($data['start_time']);
                $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
            }else{
                $this->ajaxReturn(['status' => -1 , 'msg'=>'房间不存在','data'=>'']);
            }

        }
    }
}
