<?php
namespace app\api\validate;

use    think\Validate;

class VerifyIdentity extends Validate
{

    protected $rule = [
        'nation' => 'require|between:1,10',
        'name' => 'require|length:2,20',
        'cardno' => 'require|length:15,18',
        'hz_name' => 'require|length:1,30|regex:/^.*(?=.*[A-Za-z\s*]).*$/',
        'hz_cardno' => 'require|length:2,9',
        'state' => 'require|in:5',
        // 'pic_front' => 'require|checkPic:',
        // 'pic_back' => 'require|checkPic:',
        // 'pic_hand' => 'require|checkPic:',
        'img_front' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'img_back' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'img_hand' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'type' => 'require|in:setPhone,oldPhone,newPhone,googlePhone,findFundPwd,setFundPwd',
        'email' => 'requireIf:type,setEmail|requireIf:type,newEmail|email|unique:user,email,0,state',
        'phone' => ['requireIf:type,setPhone', 'requireIf:type,newPhone', 'regex' => '/(^(13\d|15[^4,\D]|17[13678]|18\d)\d{8}|170[^346,\D]\d{7})$/', 'unique:user,phone,0,state'],
        'avatar' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'nickname' => 'require|alphaNum|unique:user,nickname,0,state',
    ];

    protected $message = [

        'nation.between' => '{%nation_between}',
        // 'pic_front.checkPic' => '{%pic_front_checkpic}',
        // 'pic_back.checkPic' => '{%pic_back_checkpic}',
        // 'pic_hand.checkPic' => '{%pic_hand_checkpic}',
        'email.unique' => '{%email_unique}',
        'phone.unique' => '{%phone_unique}',
        'cardno.unique' => '{%cardno_unique}',
        'cardno.length' => '{%vi.yz_tip_3}',
        'img_front.fileExt' => '{%pic_support}',
        'img_back.fileExt' => '{%pic_support}',
        'img_hand.fileExt' => '{%pic_support}',
        'img_front.fileSize' => '{%pic_size}',
        'img_back.fileSize' => '{%pic_size}',
        'nickname.require' => '{%nickname_null}',
        'nickname.alphaNum' => '{%nickname_alphaNum}',
        'nickname.unique' => '{%nickname_unique}',

        'hz_name.require' => '{%vi.yz_tip_1}',
        'hz_name.length' => '{%vi.yz_tip_1}',
        'hz_name.regex' => '{%vi.yz_tip_1}',
        'hz_cardno.require' => '{%vi.yz_tip_2}',
        'hz_cardno.length' => '{%vi.yz_tip_2}',
    ];

    protected $scene = [
        'one' => ['nation', 'name', 'cardno'],
        'two' => ['img_hand'],
        'three' => ['state'],
        'sendphonecode' => ['type', 'phone'],
        'sendemailcode' => ['email', 'type' => 'require|in:checkEmail,setEmail,oldEmail,newEmail,googleEmail'],
        'avatar' => ['avatar'],
        'nickname' => ['nickname'],
        'checknickname' => ['nickname'],
        'one_two' => ['nation', 'hz_name', 'hz_cardno'],

    ];

}
