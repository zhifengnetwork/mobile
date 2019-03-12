<?php


namespace app\admin\controller;

use app\admin\logic\OrderLogic;
use app\common\model\UserLabel;
use think\AjaxPage;
use think\console\command\make\Model;
use think\Page;
use think\Verify;
use think\Db;
use app\common\model\Config;
use app\admin\logic\UsersLogic;
use app\common\logic\MessageTemplateLogic;
use app\common\logic\MessageFactory;
use app\common\model\Withdrawals;
use app\common\model\Users;
use think\Loader;

class Sign extends Base
{

       /**
     * 签到列表
     * @date 2017/09/28
     */
    public function signList()
    {

       


       return $this->fetch();
    }


    /**
     * 会员签到 ajax
     * @date 2017/09/28
     */
    public function ajaxsignList()
    {

        $list = M('sign_log')->group("user_id")->select();


        

        $this->assign('list',$list);

        return $this->fetch();
    }

    /**
     * 签到规则设置
     * @date 2017/09/28
     */
    public function signRule()
    {
        if(IS_POST){

            $post = I('post.');

            // ["sign_on_off"] => string(1) "1"
            // ["sign_integral"] => string(2) "10"
            // ["sign_signcount"] => string(2) "10"
            // ["sign_award"] => string(2) "10"

            
            $model = new Config();
            $model->where(['name'=>'sign_rule'])->save(['value'=>$post['sign_rule']]);
            $model->where(['name'=>'sign_on_off'])->save(['value'=>$post['sign_on_off']]);
            $model->where(['name'=>'sign_integral'])->save(['value'=>$post['sign_integral']]);
            $model->where(['name'=>'sign_signcount'])->save(['value'=>$post['sign_signcount']]);
            $model->where(['name'=>'sign_award'])->save(['value'=>$post['sign_award']]);
            $model->where(['name'=>'sign_agent_days'])->save(['value'=>$post['sign_agent_days']]);
            $model->where(['name'=>'sign_distribut_days'])->save(['value'=>$post['sign_distribut_days']]);

            

            $this->success('保存成功');
        }

        $model = new Config();

        $config['sign_on_off'] = $model->where(['name'=>'sign_on_off'])->value('value');
        $config['sign_integral'] = $model->where(['name'=>'sign_integral'])->value('value');

        $config['sign_signcount'] = $model->where(['name'=>'sign_signcount'])->value('value');
        $config['sign_award'] = $model->where(['name'=>'sign_award'])->value('value');

        $config['sign_rule'] = $model->where(['name'=>'sign_rule'])->value('value');
        $config['sign_agent_days'] = $model->where(['name'=>'sign_agent_days'])->value('value');
        $config['sign_distribut_days'] = $model->where(['name'=>'sign_distribut_days'])->value('value');

        $this->assign('config',$config);

        return $this->fetch();
    }
   
}