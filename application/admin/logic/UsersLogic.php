<?php



namespace app\admin\logic;

use app\common\model\UserLabel;
use think\Loader;
use think\Model;
use think\Db;

class UsersLogic extends Model
{

    /**
     * 获取指定用户信息
     * @param $uid int 用户UID
     * @param bool $relation 是否关联查询
     *
     * @return mixed 找到返回数组
     */
    public function detail($uid, $relation = true)
    {
        $user = M('users')->where(array('user_id' => $uid))->relation($relation)->find();
        return $user;
    }

    /**
     * 改变用户信息
     * @param int $uid
     * @param array $data
     * @return array
     */
    public function updateUser($uid = 0, $data = array())
    {
        $db_res = M('users')->where(array("user_id" => $uid))->data($data)->save();
        if ($db_res) {
            return array(1, "用户信息修改成功");
        } else {
            return array(0, "用户信息修改失败");
        }
    }


    /**
     * 添加用户
     * @param $user
     * @return array
     */
    public function addUser($user)
    {
        $user_count = Db::name('users')
            ->where(function ($query) use ($user) {
                if ($user['email']) {
                    $query->where('email', $user['email']);
                }
                if ($user['mobile']) {
                    $query->whereOr('mobile', $user['mobile']);
                }
            })
            ->count();
        if ($user_count > 0) {
            return array('status' => -1, 'msg' => '账号已存在');
        }
        $user['password'] = encrypt($user['password']);
        $user['reg_time'] = time();
        $user_id = M('users')->add($user);
        if (!$user_id) {
            return array('status' => -1, 'msg' => '添加失败');
        } else {
            // 会员注册赠送积分
            $isRegIntegral = tpCache('integral.is_reg_integral');
            if ($isRegIntegral == 1) {
                $pay_points = tpCache('integral.reg_integral');
            } else {
                $pay_points = 0;
            }
            //$pay_points = tpCache('basic.reg_integral'); // 会员注册赠送积分
            if ($pay_points > 0)
                accountLog($user_id, 0, $pay_points, '会员注册赠送积分'); // 记录日志流水
            return array('status' => 1, 'msg' => '添加成功', 'user_id' => $user_id);
        }
    }
}