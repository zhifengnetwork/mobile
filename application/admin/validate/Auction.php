<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class Auction extends Validate
{
    // 验证规则
    protected $rule = [
        ['id','checkId'],
        ['activity_name', 'require|unique:auction'],
//        ['activity_round','require'],
        ['delay_time','require'],
        ['rount_time','require'],
//        ['preview_time','require'],
        ['end_time', 'require'],
        ['payment_time','require'],
        ['deposit','require'],
        ['reserve_price','require'],
        ['start_price','require'],
        ['increase_price','require'],
        ['goods_id', 'require'],
        ['start_time','require'],
        ['end_time','require|checkTime'],
    ];
    //错误信息
    protected $message  = [
        'activity_name.require'   => '竞拍标题必填',
        'activity_name.unique'    => '已存在相同竞拍标题',
        'goods_id.require'        => '请选择参与竞拍的商品',
        'activity_round.require'  => '请填写活动轮次',
        'delay_time.require'      => '请填写延时周期',
        'rount_time.require'      => '请填写每轮时间',
        'start_time.require'      => '请选择开始时间',
        'end_time.require'        => '请选择结束时间',
        'preview_time.require'    => '请选择预展时间',
        'end_time.checkTime'      => '结束时间不能早于开始时间',
        'payment_time.require'    => '请填写货款支付时间',
        'deposit.require'         => '请填写保证金',
        'reserve_price.require'   => '请填写保留价',
        'start_price.require'     => '请填写起拍价',
        'increase_price.require'  => '请填写加价幅度',
    ];

    /**
     * 检查结束时间
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected function checkTime($value, $rule ,$data)
    {
        return ($value < $data['start_time']) ? false : true;
    }

    /**
     * 该活动是否可以编辑
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected function checkId($value, $rule ,$data)
    {
        $isHaveOrder = Db::name('order_goods')->where(['prom_type'=>1,'prom_id'=>$value])->find();
        if($isHaveOrder){
            return '该活动已有用户下单购买不能编辑';
        }else{
            return true;
        }
    }
}