<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13 0013
 * Time: 10:20
 */

namespace app\cron\controller;


abstract class Init
{
    const WRITE_LOG = true;
    const WRITE_PATH= '/cron';

    private $log_path;
    public function __construct()
    {
        #   判断是否在cli模式下面运行,如果不是则退出程序
        if (!IS_CLI) exit();
        if (self::WRITE_LOG) $this->log_path = RUNTIME_PATH . self::WRITE_PATH;
    }
    
    abstract public function run();
}