<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13 0013
 * Time: 10:20
 */

namespace app\cron\controller;


use think\Db;
use app\common\util\Exception;

class Team extends Init
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 执行方法
     */
    public function run()
    {

        dump(15 * 0.2);
        dump(12 * 0.25);

        dump(11 - (11 * 0.2));
        dump(8.8 + (8.8 * 0.25));

        file_put_contents('cron.txt', date('Y-m-d H:i:s', time()) . PHP_EOL, FILE_APPEND);
        echo date('Y-m-d H:i:s', time()) . PHP_EOL;
    }


    protected function sql()
    {
        try {
            Db::startTrans();





            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return $e->getData();
        }
    }

}