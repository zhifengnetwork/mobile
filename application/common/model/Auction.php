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

	//状态描述
	public function getStatusDescAttr($value, $data)
	{
		if($data['id_end'] == 1){
			return '已结束';
		}else{
			if($data['buy_num'] >= $data['goods_num']){
				return '已告罄';
			}else if($data['start_time'] > time()){
				return '未开始';
			}else if($data['start_time'] <time() && $data['ene_time'] > tiem()){
				return '进行中';
			}else{
				return '已结束';
			}
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