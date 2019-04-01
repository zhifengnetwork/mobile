<?php

use app\common\model\RebateLog;

function agent_level($agent_user){
  $level_name = M("user_level")->where(['level'=>$agent_user])->find();
  //如果找不到，就是分销商
  if(!$level_name){
    return '分销商';
  }
  if($level_name){
    return $level_name['level_name'];
  }
}