<?php
/**
 * 竞拍 的 PHP 后端
 */
// namespace app\mobile\controller;
namespace app\shop\controller;
use app\common\logic\AuctionLogic;
use think\Db;
use app\common\model\WxNews;

class Auction extends MobileBase
{

    public $user_id = 3;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息

        }
    }

     /**
     * 竞拍
     */
    public function index()
    {
       
        return $this->fetch();
    }

//    public function index()
//    {
//        $commodity = M('Auction')->order('preview_time asc')->select();
//        $this->assign('commodity', $commodity);
//        return $this->fetch();
//    }


    /**
     * 竞拍成功 付款页面
     */
    public function pay()
    {
       
        return $this->fetch();
    }

    /**
     * 竞拍详情
     */
    public function auction_detail()
    {

        $goods_id = I("get.id/d");
        $goodsModel = new \app\common\model\Auction();
        $goods = $goodsModel::get($goods_id);
        //dump($goods);exit;
        if (empty($goods) || ($goods['auction_status'] == 0)) {
            $this->error('此商品不存在或者已下架');
        }
        $auctionLogic = new AuctionLogic();
        $isBond = $auctionLogic->getUserIsBond($this->user_id, $goods_id);
        $bondUser = $auctionLogic->getHighPrice($goods_id,10);
        //是否有交保证金
        if (empty($isBond)){
            $goods['isBond'] = 0;
        }else{
            $goods['isBond'] = 1;
        }

        $this->assign('bondUser', $bondUser);
        $this->assign('goods', $goods);
        return $this->fetch();
    }

    /*
     * 出价
     */
    public function offerPrice()
    {
        $auction_id = 64;//input("goods_id/d"); // 竞拍商品id
        $price = 20;//input("goods_price/d");// 竞拍价格
//        if ($this->user_id == 0){
//            $this->error('请先登录', U('Mobile/User/login'));
//        }
        $auction = \app\common\model\Auction::get($auction_id);
        $auctionLogic = new AuctionLogic();
        $isBond = $auctionLogic->getUserIsBond($this->user_id, $auction_id);
        if(empty($isBond)){
            $this->error('未交保证金', U('Mobile/Payment/payBond',array('goods_id'=>$auction_id)));
        }
        $high = $auctionLogic->getHighPrice($auction_id);
        // 当前时间是否已结束
        if ( time() > $auction['end_time']+($auction['delay_num']*$auction['delay_time'])){
            $this->error('本轮活动已结束！', U('Mobile/Activity/auction_list'));
        }

        if (empty($high)){
            $auctionLogic->addAuctionOffer($this->user_id, $auction_id, $price);
        } else {
            if($this->user_id == $high[0]['user_id']){
                $this->error('您已经是目前最高出价者了');
            }
            if($price <= $high[0]['offer_price']){
                $this->error('您的出价低于别人');
            }
            if ($price < ($high[0]['offer_price']+$auction['increase_price'])){
                $this->error('加价幅度'.$auction['increase_price']);
            }
            $auctionLogic->addAuctionOffer($this->user_id, $auction_id, $price);
        }

        return $this->fetch('auction_detail','','',array('id'=>$auction_id)); //分跳转 和不 跳转

    }


	/**
	 * 条数具体化
	 */
	public function tiaoshu()
    {

        return $this->fetch();
    }

}