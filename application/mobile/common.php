<?php

use app\common\model\RebateLog;

function agent_level($agent_user){
  $level_name = M("user_level")->where(['level'=>$agent_user])->find();
  if($level_name){
    return $level_name['level_name'];
  }
}