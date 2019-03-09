<?php


namespace app\common\model\saas;

class ExtendService extends ServiceBase
{
    public function terminal()
    {
        return $this->belongsTo('Terminal', 'terminal_id', 'extend_id');
    }

    public function module()
    {
        return $this->belongsTo('Module', 'module_id', 'extend_id');
    }

    public function miniappTemplate()
    {
        return $this->belongsTo('MiniappTemplate', 'template_id', 'extend_id');
    }

    public function entrust()
    {
        return $this->belongsTo('Entrust', 'entrust_id', 'extend_id');
    }

    public function moduleRights()
    {
        return $this->hasMany('ModuleRight', 'module_id', 'extend_id');
    }
}