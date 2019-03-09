<?php

namespace app\common\model;

use think\Db;
use think\Model;
use app\common\logic\FlashSaleLogic;
use app\common\logic\GroupBuyLogic;
use app\common\logic\PromGoodsLogic;

class Shipping extends Model
{
    public function setTemplateHtml($value,$data)
    {
        return htmlspecialchars_decode($value);
    }
}
