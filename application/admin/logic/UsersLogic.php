<?php

/**
 * DC环球直供网络
 * ============================================================================
 * 版权所有 2015-2027 广州滴蕊生物科技有限公司，并保留所有权利。
 * 网站地址: http://www.dchqzg1688.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-09
 */

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

    /**
     * 获得指定分销商下的上级的数组     
     * @access  public
     * @param   int     $cat_id     分销商的ID
     * @param   int     $selected   当前选中分销商的ID
     * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param   int     $level      限定返回的级数。为0时返回所有级数
     * @return  mix
     */
    public function relation($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
    {
        global $goods_category, $goods_category2;            
        $sql = "SELECT user_id,nickname,mobile,is_distribut,is_agent,first_leader,agent_user FROM  __PREFIX__users where is_agent=1 and first_leader=0 and user_id>0 and agent_user<=6 and agent_user>1 ORDER BY first_leader , agent_user ASC";
        $goods_category = DB::query($sql);
        $goods_category = convert_arr_key($goods_category, 'user_id');
        
        foreach ($goods_category AS $key => $value)
        {
            // if(($value['is_distribut'] == 1 || $value['is_agent'] == 1) && $value['first_leader'] == 0){
                                           
            // }
            $this->get_cat_tree($value['user_id'], 0);    
        }
        return $goods_category2;
    }

    /**
     * 获取指定id下的 所有分销商      
     * @global type $goods_category 所有分销商
     * @param type $id 当前显示的 菜单id
     * @param type $level 等级
     * @return 返回数组 Description
     */
    public function get_cat_tree($id, $level)
    {
        global $goods_category, $goods_category2;          
        $goods_category2[$id] = $goods_category[$id];
        $level = $level + 1;
        $goods_category2[$id]['level'] = $level;
        $k = $goods_category[$id]['level']; 

        foreach ($goods_category AS $key => $value){
             if(($value['is_distribut'] == 1 || $value['is_agent'] == 1) && $value['first_leader'] == $id)
             {
                $this->get_cat_tree($value['user_id'], $level);  
                $goods_category2[$id]['have_son'] = 1; // 还有下级
                $k++;
             }
        }            
    }
}