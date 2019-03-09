<?php

namespace app\common\model;
use think\Model;
class News extends Model {
    //自定义初始化
    static $OPEN_TYPE = 0;
    static $OPEN_STATUS = 1;

    protected static function init()
    {
        //TODO:自定义的初始化
    }

}
