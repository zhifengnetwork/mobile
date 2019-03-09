<?php


namespace app\common\model\saas;

class MiniappTemplate extends ExtendBase
{

    public function app()
    {
        return $this->belongsTo('App', 'app_id', 'app_id');
    }
}