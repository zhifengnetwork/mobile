<?php
namespace app\api\validate;

use	think\Validate;

class User extends Validate
{

	protected $rule = [
		'login_type' => 'require|in:1,2',
		'phone'=>'require|max:15',
		'email'=>'require|length:3,50|email',
		'user_name'=>'require|max:50',
		'verify_code' => 'require|length:6|regex:/^.*(?=.*[0-9]).*$/',
		'area_code' => 'require|length:1,8|regex:/^.*(?=.*[0-9]).*$/',
		'user_password' => 'require|length:7,20',
		'password' => 'require|length:7,20|alphaNum|regex:/^.*(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z]).*$/',
		'confirm_password' => 'require|length:7,20',
		'old_password' => 'require|length:7,20',
		'new_password' => 'require|length:7,20|alphaNum|regex:/^.*(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z]).*$/',
	];


	protected $message = [
//		'email.email' => '{%email_email}',
        'login_type.require' => '{%validate.user_tip_1}',
		'email.email' => '{%validate.user_tip_2}',
		'email.length' => '{%vi.yz_tip_6}',
        'phone.require' => '{%validate.user_tip_3}',
		'verify_code.require' => '{%validate.user_tip_4}',
		'verify_code.length' => '{%validate.user_tip_4}',
		'verify_code.regex' => '{%validate.user_tip_4}',
		'user_name.max' => '{%validate.user_tip_5}',
		'area_code.require' =>'{%user.phone_code}',
		'area_code.length' =>'{%user.phone_code}',
		'area_code.regex' =>'{%user.phone_code}',
		'phone.max' => '{%validate.user_tip_6}',
		'password.require' =>  '{%validate.user_tip_7}',
		'password.length' =>  '{%validate.user_tip_8}',
		'old_password.require' => '{%validate.user_tip_8}',
		'new_password.require' => '{%validate.user_tip_8}',
		'user_password.require' =>  '{%validate.user_tip_8}',
		'user_password.length' => '{%validate.user_tip_8}',
		'confirm_password.length' => '{%validate.user_tip_8}',
		'confirm_password.require' => '{%validate.user_tip_8}',
		'password.alphaNum'=>'{%vi.yz_tip_4}',
		'password.regex'=>'{%vi.yz_tip_4}',
		'new_password.alphaNum'=>'{%vi.yz_tip_4}',
		'new_password.length' =>  '{%validate.user_tip_8}',
		'new_password.regex'=>'{%vi.yz_tip_5}',
		'confirm_password.regex'=>'{%vi.yz_tip_5}',
		'old_password.length' =>  '{%validate.user_tip_8}',
		'confirm_password.length' =>  '{%validate.user_tip_8}',
    ];

	protected $scene = [
		'login'=>['user_name','user_password','login_type'],
		'register_email'=>['email','password','login_type','verify_code'],
		'register_phone'=>['phone','password','login_type','verify_code'],
		'find_login_password'=>['password','confirm_password','login_type','verify_code'],
		'edit_login_password'=>['old_password','new_password','confirm_password'],
		'validate_email'=>['email'],
		'name_email'=>['email','login_type'],
		'name_phone'=>['phone','login_type','area_code'],
		'reglogin_email'=>['email','password','login_type'],
		'reglogin_phone'=>['phone','password','login_type','area_code'],
	];

}
