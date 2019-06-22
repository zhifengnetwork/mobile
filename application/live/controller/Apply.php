<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 11:31
 */

namespace app\live\controller;

use think\Db;

class Apply extends Base
{
    private $uploadDir = 'public' . DS . 'static' . DS . 'uploads' . DS . 'apply';

    /**
     * 直播审核页面
     * @return mixed
     */
    public function index()
    {
        $userId = $this->user->user_id;
        $verifyData = Db::name('user_verify_identity_info')->where(['user_id' => $userId])->find();
        if ($verifyData) {
            if ($verifyData['verify_state'] == 1) {
                $this->redirect('/Live/Index/videoList');
            } else if ($verifyData['verify_state'] == 2) {
                $log = Db::name('user_verify_identity_log')->where('verify_id', $verifyData['id'])->order('id')->find();
                $log && $verifyData['reason'] = $log['reason_cn'];
            }
        }

        // state:认证状态,默认-1，显示编辑页面
        $this->assign(['data' => $verifyData, 'state' => $verifyData ? $verifyData['verify_state'] : -1, 'user' => $this->user]);
        return $this->fetch();
    }

    /**
     * 提交审核页面
     * @return mixed
     */
    public function upload()
    {
        $userId = $this->user->user_id;
        $verifyData = Db::name('user_verify_identity_info')->where(['user_id' => $userId])->find();
        if ($verifyData && $verifyData['verify_state'] == 0 || $verifyData['verify_state'] == 1) {
            return $this->failResult('信息审核中或已通过', 301);
        }

        $name = I('post.name/s', '', 'trim');
        $mobile = I('post.mobile/s', '', 'trim');
        $validate = $this->validate(I('post.'), 'Apply.upload');
        if (true !== $validate) {
            return $this->failResult($validate, 301);
        }

        if (!($file = request()->file('front'))) {
            return $this->failResult('请上传身份证正面照', 301);
        }
        //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
        if (!($info = $file->move(ROOT_PATH . $this->uploadDir))) {
            // 上传失败获取错误信息
            return $this->failResult('身份证正面照上传失败', 301);
        }
        $picFront = DS . $this->uploadDir . DS . $info->getSaveName();

        if (!($file = request()->file('back'))) {
            return $this->failResult('请上传身份证反面照', 301);
        }
        if (!($info = $file->move(ROOT_PATH . $this->uploadDir))) {
            return $this->failResult('身份证反面照上传失败', 301);
        }
        $picBack = DS . $this->uploadDir . DS . $info->getSaveName();

        //提交申请直播
        $logData = array(
            'user_id' => $userId,
            'name' => $name,
            'mobile' => $mobile,
            'pic_front' => $picFront,
            'pic_back' => $picBack,
            'verify_state' => 0,
            'create_time' => time(),
        );
        if ($verifyData) {
            $result = Db::name('user_verify_identity_info')->where(['user_id' => $userId])->update($logData);
        } else {
            $result = Db::name('user_verify_identity_info')->insert($logData);
        }
        if (!$result) {
            return $this->failResult('提交失败', 301);
        }

        return $this->successResult('提交成功');

    }

}