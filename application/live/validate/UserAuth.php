<?php
namespace app\api\validate;

use    think\Validate;

class UserAuth extends Validate
{

    protected $rule = [
        'nation' => 'require|between:1,10',
        'name' => 'require|length:2,20',
        'cardno' => 'require|length:7,18|alphaNum|unique:verify_identity,cardno,0,verify_state',
        'state' => 'require|in:5',
        'sex'=>'require|in:-1,0,1',
        'img_front' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'img_back' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'img_hand' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'type' => 'require|in:setPhone,oldPhone,newPhone,googlePhone,findFundPwd,setFundPwd',
        'email' => 'requireIf:type,setEmail|requireIf:type,newEmail|email|unique:user,email,0,state',
        'phone' => ['requireIf:type,setPhone', 'requireIf:type,newPhone', 'regex' => '/(^(13\d|15[^4,\D]|17[13678]|18\d)\d{8}|170[^346,\D]\d{7})$/', 'unique:user,phone,0,state'],
        'avatar' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'nickname' => 'require|alphaNum|unique:user,nickname,0,state',
        'verify_code' => 'require|length:6|regex:/^.*(?=.*[0-9]).*$/',
        'google_code' => 'require|length:6|regex:/^.*(?=.*[0-9]).*$/',
        'fund_password' => 'require|checkFundPassword:',
        'confirm_password' => 'require|checkFundPassword:',
        'old_password' => 'require|length:6|regex:/^.*(?=.*[0-9]).*$/',
        'new_password' => 'require|checkFundPassword:',
    ];

    protected $message = [
        'nation.between' => '{%nation_between}',
        'email.unique' => '{%email_unique}',
        'phone.unique' => '{%phone_unique}',
        'cardno.unique' => '{%cardno_unique}',
        'nickname.require' => '{%nickname_null}',
        'nickname.alphaNum' => '{%nickname_alphaNum}',
        'nickname.unique' => '{%nickname_unique}',
        'fund_password.require' => '{%validate.userauth_tip_1}',
        'new_password.require' => '{%validate.userauth_tip_2}',
        'confirm_password.require' => '{%validate.userauth_tip_3}',
        'fund_password.checkFundPassword' => '{%vi.yz_tip_7}',
        'new_password.checkFundPassword' => '{%vi.yz_tip_7}',
        'confirm_password.checkFundPassword' => '{%vi.yz_tip_7}',
        'password.checkFundPassword' => '{%vi.yz_tip_7}',
        'sex.require' => '{%validate.userauth_tip_4}',
        'verify_code.require' => '{%validate.user_tip_4}',
        'verify_code.length' => '{%validate.user_tip_4}',
        'verify_code.regex' => '{%validate.user_tip_4}',
        'google_code.require' => '{%validate.user_tip_4}',
        'google_code.length' => '{%validate.user_tip_4}',
        'google_code.regex' => '{%validate.user_tip_4}',
        'old_password.require' => '{%user.fund_password_tip_2}',
        'old_password.length' => '{%user.fund_password_tip_2}',
        'old_password.regex' => '{%user.fund_password_tip_2}',
    ];

    protected $scene = [
        'setgoogle' => ['verify_code', 'google_code'],
        'set_sex' => ['sex'],
        'forget_fund' => ['verify_code', 'fund_password', 'confirm_password'],
        'edit_fund_password' => ['new_password', 'confirm_password', 'old_password'],
        'set_fund_password' => ['password', 'confirm_password'],
        'login_google'=>['google_code'],
    ];

    /**
     * 判断资金密码格式是否正确
     * @param $value
     * @param $rule
     * @return bool
     * @author Zgp Create At 2018年8月29日
     */
    protected function checkFundPassword($value, $rule)
    {
        if ($value == '') {
            return false;
        }
        if (!preg_match('/^[0-9]{6}$/i', $value)) {
            return false;
        }
        //判断是否为相同的数字
        if (preg_match('/^([0-9])\1{5}$/i', $value)) {
            return false;
        }
        //判断字符串是否连续数字  $value = "123456"; or $value = "876543"  规律 1+6 == 2+5 == 3+4
        $arr = str_split($value);
        $temp = [];
        for ($i = 0; $i < count($arr); $i++) {
            $temp[$i] = $arr[0] + $i;
        }
        $str_1 = implode(",", $arr);
        $str_2 = implode(",", $temp);
        if ($str_1 == $str_2) {//如果相等。说明是从小到大
            return false;
        }
        $temp = [];
        for ($i = 0; $i < count($arr); $i++) {
            $temp[$i] = $arr[0] - $i;
        }
        $str_3 = implode(",", $temp);
        if ($str_1 == $str_3) {//如果相等。说明是从大到小
            return false;
        }
        return true;
    }

}
