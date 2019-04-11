<?php
namespace app\api\controller;
use app\common\logic\GoodsLogic;
use app\common\model\FlashSale;
use app\common\model\GroupBuy;
use app\common\model\PreSell;
use think\Db;
use think\Page;
use app\common\logic\ActivityLogic;

class Activity extends ApiBase {
    

    /**
     * +---------------------------------
     * 优惠券列表中心
     * +---------------------------------
    */
    public function coupon_list()
    {
        $user_id = $this->get_user_id();
        $atype = I('atype', 1);
        $p = I('p', '');
        $activityLogic = new ActivityLogic();
        $result = $activityLogic->getCouponList($atype, $user_id, $p);
        // $this->assign('coupon_list', $result);
        // if (request()->isAjax()) {
        //     return $this->fetch('ajax_coupon_list');
        // }
        $result['coupon_list'] = $result;
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$result['coupon_list']]);
    }

}