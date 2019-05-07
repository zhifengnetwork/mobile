<?php
/**
 * 竞拍 的 PHP 后端
 */
namespace app\api\controller;

use app\common\logic\AuctionLogic;
use app\common\model\Goods;
use think\Db;
use app\common\model\WxNews;

class Auction extends ApiBase
{

    public $user_id = 0;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct(); 
    }

    /**
     * 包含一个商品模型
     * @param $goods_id
     */
    public function setGoodsModel($goods_id)
    {
        if ($goods_id > 0) {

            $goodsModel = new Goods();
            $this->goods = $goodsModel::get($goods_id);
        }
    }

    /**
     * 通过item_id包含一个商品规格模型
     * @param $item_id
     */
    public function setSpecGoodsPriceById($item_id)
    {
        if ($item_id > 0) {
            $specGoodsPriceModel = new SpecGoodsPrice();
            $this->specGoodsPrice = $specGoodsPriceModel::get($item_id, '', 10);
        }else{
            $this->specGoodsPrice = null;
        }
    }

     /**
     * 竞拍
     */
    public function index()
    {
       
        return $this->fetch();
    }


    /**
     * 竞拍详情
     */
    public function auction_detail()
    {
		$user_id = $this->get_user_id();
        if(!$user_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);		
        
        $auction_id = I("post.id/d",0);
		$goods = C('database.prefix') . 'goods';
		$field = 'A.id,A.goods_id,A.activity_name,A.goods_name,A.start_price,A.start_time,A.end_time,A.deposit,A.increase_price,A.auction_status,A.delay_time,A.delay_num,G.original_img';
		$auction = M('Auction')->alias('A')->field($field)->join("$goods G" ,"A.goods_id=G.goods_id",'LEFT')->where(['A.is_end'=>0,'id'=>$auction_id])->order("A.preview_time desc")->find();	

        //$this->setGoodsModel($auction['goods_id']);

        if (empty($auction) || ($auction['auction_status'] == 0)) {
            $this->error('此商品不存在或者已下架');
        }
        $auction['delay_end_time'] = $auction['end_time']+($auction['delay_num']*$auction['delay_time']*60); //延时结束时间

        $isBond = $this->getUserIsBond($user_id, $auction_id);
        $bondUser = $this->getHighPrice($auction_id);
        //是否有交保证金
        if (empty($isBond)){
            $auction['isBond'] = 0;
        }else{
            $auction['isBond'] = 1;
        }

		$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['auction'=>$auction,'bondUser'=>$bondUser,'bondCount'=>count($bondUser)]]);
    }

    /*
     * 出价
     */
    public function offerPrice()
    {
        $auction_id = I("post.auction_id/d",0); // 竞拍商品id
        $price = I("post.price/f",0.00);// 竞拍价格
		
		$user_id = $this->get_user_id();
        if(!$user_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);

        $auction = \app\common\model\Auction::get($auction_id);
        $isBond = $this->getUserIsBond($user_id, $auction_id);
        if(empty($isBond)){										
            $this->ajaxReturn(['status' => -2, 'msg' => '您还未交保证金', 'data' => null]);
        }
        $high = $this->getHighPrice($auction_id);
        // 活动是否已开始
        if ( time() < $auction['start_time']){
            $this->ajaxReturn(['status' => -3, 'msg' => '本轮活动还没开始', 'data' => null]);
        }
        // 当前时间是否已结束
        if ( time() > $auction['end_time']+($auction['delay_num']*$auction['delay_time'])){
            $this->ajaxReturn(['status' => -4, 'msg' => '本轮活动已结束', 'data' => null]);
        }

		// 是否小于起拍价
        if ( $price < $auction['start_price'] ){
            $this->ajaxReturn(['status' => -9, 'msg' => '必须不小于起拍价', 'data' => null]);
        }

        if (empty($high)){
            $this->addAuctionOffer($user_id, $auction_id, $price);
        } else {
            if($user_id == $high[0]['user_id']){
                $this->ajaxReturn(['status' => -5, 'msg' => '您已经是目前最高出价者了', 'data' => null]);
            }
            if($price <= $high[0]['offer_price']){
                $this->ajaxReturn(['status' => -6, 'msg' => '您的出价不是最高价', 'data' => null]);
            }
            if ($price < ($high[0]['offer_price']+$auction['increase_price'])){
                $this->ajaxReturn(['status' => -7, 'msg' => '加价幅度'.$auction['increase_price'], 'data' => null]);
            }
            // 结束时间小于延时时间的话就添加延时次数
            if($auction['end_time']-time() <= $auction['delay_time']*60){
                $this->addDelayTime($auction_id, $auction['delay_time']);
            }

            $this->addAuctionOffer($user_id, $auction_id, $price);
        }

        return $this->ajaxReturn(['status' => 0, 'msg' => '出价成功', 'data' => null]);

    }

     /*
    * 前端每N秒获取一次竞拍结果,报名人数，出价条数，最高出价信息
    */
    public function GetAucMaxPrice(){
        $id = I('post.aid/d',0);
        $num = I('post.num/d',5);
        if(!$id){
            return $this->ajaxReturn(['status' => -2, 'msg' => '参数错误', 'data' => null]);  
        }

		$user_id = $this->get_user_id();
        if(!$user_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);

        //报名人数
        $buy_num = M('Auction')->where(['id'=>$id])->value('buy_num');
        //出价条数
        $price_num = M('Auction_price')->where(['auction_id'=>$id])->count();
        //最高出价信息前3条
        $max_price = M('Auction_price')->alias('A')->field('A.*,U.head_pic')->join('tp_users U','A.user_id=U.user_id','left')->where(['A.auction_id'=>$id])->order('A.offer_price desc')->limit(0,$num)->select();
        foreach($max_price as $k=>$v){
            $max_price[$k]['offer_time'] = date('m.d H:i:s',$v['offer_time']);
			$max_price[$k]['isnowuser'] = ($user_id == $v['user_id']) ? 1 : 0;
        }
        return $this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['buy_num'=>$buy_num,'price_num'=>$price_num,'max_price'=>$max_price]]); 
    }   

    /*
     * 活动结束统计获奖者
     */
    public function auctionEnd()
    {
        $auction_id = input("aid/d", 0);
        try {
            $auction = \app\common\model\Auction::get($auction_id); 
            $this->auction = $auction::get($auction_id);   
            $buyGoods = $this->winnersUser();

        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        } 
            return $this->ajaxReturn($buyGoods);
    }

    /**
     * 获取竞拍结果
     */
    public function auctionResult()
    {   
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        } 
        $auction_id = input("aid/d", 69);
        $victory = M('AuctionPrice')->field('id,offer_price,is_out')->where(['user_id' => $user_id, 'auction_id' => $auction_id])->order('offer_price desc')->find();
        if(!empty($victory)){
            $this->ajaxReturn(['status' => 0, 'msg' => '请求成功！', 'data' => ['']]);
        } else {
            $this->ajaxReturn(['status'=>0]);
        }

    }

    /**
     * 竞拍失败修改已读状态
     */
    public function alreadyRead()
    {
        $auction_id = input("aid/d", 0);
        $victory = M('AuctionPrice')->where(['user_id' => $this->user_id, 'auction_id' => $auction_id])->save(['is_read'=>1]);
        if(!empty($victory)){
            $this->ajaxReturn(['status' => 1]);
        } else {
            $this->ajaxReturn(['status'=>0]);
        }
    }

    /**
     * 条数具体化
     */
    public function tiaoshu()
    {
        $goods_id = I("get.id/d");
        $bondUser = $this->getHighPrice($goods_id);
        $this->assign('bondUser', $bondUser);
        return $this->fetch();
    }

    /**
     *临时方法-交定金扣余额
     
    public function addBond()
    {
        $goods_id = I("get.goods_id/d");

        $money = Db::name('Auction')->where('id',$goods_id)->value('deposit');

        $user = session('user');
        $data['deposit'] = $money;
        $data['user_id'] = $user['user_id'];
        $data['auction_id'] = $goods_id;
        $data['order_sn'] = 'Bond'.get_rand_str(10,0,1);
        $data['create_time'] = time();
        $data['status'] = 1;
        $order_id = M('auctionDeposit')->add($data);
        if ($order_id!=0) {
            $this->ajaxReturn(['status' => 1]);
        } else {
            $this->ajaxReturn(['status'=>0]);
        }
    }
*/
    /**
     * 用户是否有交保证金
     * @param type $sort_type
     * @param type $page_index
     * @param type $page_size
     */
    public function getUserIsBond($user_id, $auction_id)
    {
        $where['user_id'] = $user_id;
        $where['auction_id'] = $auction_id;
        $where['status'] = 1; // 1已支付，2退款

        $query = M('AuctionDeposit')
            ->where($where)
            ->select();

        return $query;
    }

    /**
     * 获取出价者按价格排序
     * @param type $sort_type
     * @param type $page_index
     * @param type $page_size
     */
    public function getHighPrice($auction_id,$limit = '')
    {
        $where['auction_id'] = $auction_id;
        $query = M('AuctionPrice')
			->alias('A')
			->field('A.*,U.head_pic')
			->join('tp_users U','A.user_id=U.user_id','left')	
            ->where($where)
            ->order('A.offer_price desc')
            ->limit($limit)
            ->select();

        return $query;
    }

    /**
     * 添加竞拍出价
     * @param type $sort_type
     * @param type $page_index
     * @param type $page_size
     */
    public function addAuctionOffer($uid,$auction_id,$money)
    {
		$AuctionPrice = M('AuctionPrice');
        // 启动事务
        Db::startTrans();
        try{
            $data=[
                'user_id'      => $uid,
                'offer_price'  => $money,
                'offer_time'   => time(),
                'user_name'    => M('users')->where(['user_id'=>$uid])->value('nickname'),
                'auction_id'  => $auction_id,
                'is_out'  => 1,
            ];
            $id = $AuctionPrice->lock(true)->add($data);
			$info = $AuctionPrice->field('user_id,offer_price,offer_time,is_out')->order('offer_price desc')->find($auction_id);
			if($info['user_id'] && ($info['user_id'] !== $uid)){
				Db::rollback();
				$this->ajaxReturn(['status' => -6, 'msg' => '您的出价不是最高价', 'data' => null]);
			}
			if($AuctionPrice->where(['auction_id'=>$auction_id,'pay_status'=>1])->count()){
				Db::rollback();
				$this->ajaxReturn(['status' => -8, 'msg' => '您的出价无效，商品已完成竞拍！', 'data' => null]);
			}

            $map['auction_id']  = ['=', $auction_id];
            $map['id']  = ['<>', $id];
            $AuctionPrice->where($map)->save(['is_out'=>0]);
			//M('Auction')->where(['id'=>$auction_id])->setInc('buy_num',1);
            // 提交事务
            Db::commit(); 
        } catch (TpshopException $t) {
            // 回滚事务
            Db::rollback();
            $error = $t->getErrorArr();
            $this->ajaxReturn(['status' => -99, 'msg' => $error, 'data' => null]);
        }

    }


    /**
     * 添加竞拍延时
     * @param type $sort_type
     * @param type $page_index
     * @param type $page_size
     */
    public function addDelayTime($id)
    {

        $query = M('Auction')->where('id',$id)->setInc('delay_num');

        return $query;
    }

    /*
     * 活动结束统计获奖者
     */
    public function winnersUser()
    {

        if (empty($this->auction)) {
            return $data = ['status' => 0, 'msg' => '竞拍商品不存在', 'result' => ''];
        }

        if($this->auction['is_end'] == 1){
            return $data = ['status' => 0, 'msg' => '活动已结束', 'result' => ''];
        }

        $price = $this->getHighPrice($this->auction['id'],1);
        Db::name('Auction')->where('id', $this->auction['id'])->save(['is_end' => 1, 'transaction_price' => $price[0]['offer_price']]);
        Db::name('AuctionPrice')->where(['auction_id' => $this->auction['id'], 'is_out' => 1])->save(['is_out' => 2]);

    }


}