<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/31
 * Time: 11:59
 */

namespace app\common\model;


use think\Model;

class Auction extends Model
{
	public function specGoodsPrice()
	{
		return $this->hasOne('specGoodsPrice','goods_id','goods_id');
	}

	public function goods()
	{
		return $this->hasOne('goods','goods_id','goods_id');
	}

	//是否发货
	public function getSendStatusAttr($value, $data)
	{
		if($data['is_send'] == 1){
			return '发货';
		}else {
            return '不发货';
        }
	}

    //是否上架
    public function getPutawayStatusAttr($value, $data)
    {
        if($data['auction_status'] == 1){
            return '上架';
        }else{
            return '下架';
        }
    }


	/**
	*是否编辑
	*@param $value
	*@param $data
	*@param $int
	*/
	public function getIsEditAttr($value, $data)
	{
		if($data['is_end'] == 1 || $data['start_time'] < time()){
			return 0;
		}
		return 1;
	}

}