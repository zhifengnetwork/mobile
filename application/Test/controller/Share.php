<?php
/**
 * 测试
 */
namespace app\test\controller;


use think\Db;
use think\Controller;

use app\common\logic\ShareLogic;


class Share extends Controller
{
    /**
     * 新的分享
     */
    public function fenxiang()
    {
        $user_id = 56873;
        $head_pic_url = M('users')->where(['user_id'=>$user_id])->value('head_pic');

        $logic = new ShareLogic();
        $ticket = $logic->get_ticket($user_id);

        
        if( strlen($ticket) < 3){
            $this->error("ticket不能为空");
            exit;
        }
        $url= "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;

        $url222 = '/www/wwwroot/www.dchqzg1688.com/public/share/code/'.$user_id.'.jpg';
        if( @fopen( $url222, 'r' ) )
        {
            //已经有二维码了
        	$url_code = '/www/wwwroot/www.dchqzg1688.com/public/share/code/'.$user_id.'.jpg';
        }else{
            //还没有二维码
            $re = $logic->getImage($url,'/www/wwwroot/www.dchqzg1688.com/public/share/code', $user_id.'.jpg');
            $url_code = $re['save_path'];
        }
        

        $head_url = '/www/wwwroot/www.dchqzg1688.com/public/share/head/'.$user_id.'.jpg';
        if( @fopen( $head_url, 'r' ) )
        {
            //已经有二维码了
        	$url_head_pp = '/www/wwwroot/www.dchqzg1688.com/public/share/head/'.$user_id.'.jpg';
        }else{
            //还没有二维码
            $re = $logic->getImage($head_pic_url,'/www/wwwroot/www.dchqzg1688.com/public/share/head', $user_id.'.jpg');
            $url_head_pp = $re['save_path'];
        }
        

        // dump($url_head_pp);

        //得到二维码的绝对路径

        $pic = "/www/wwwroot/www.dchqzg1688.com/public/share/picture_ok44/'.$user_id.'.jpg";
        if( @fopen( $pic, 'r' ) )
        {
        	$pic = "/share/picture_ok44/".$uid.".jpg";
        }
        else
        {
        	$image = \think\Image::open('/www/wwwroot/www.dchqzg1688.com/public/share/bg1.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_code,\think\Image::DCHQZG)->save('/www/wwwroot/www.dchqzg1688.com/public/share/picture_ok44/'.$user_id.'.jpg');
        	
        	$pic = "/public/share/picture_ok44/".$user_id.".jpg";
        }
    

        //再次叠加

        $pic111 = "/www/wwwroot/www.dchqzg1688.com/public/share/picture_888/".$user_id.".jpg";
        if( @fopen( $pic111, 'r' ) )
        {
        	$picture = "/share/picture_888/".$uid.".jpg";
        }
      
        else
        {
           
        	$image = \think\Image::open('/www/wwwroot/www.dchqzg1688.com/public/share/picture_ok44/'.$user_id.'.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_head_pp,\think\Image::TOUXIANG)->save('/www/wwwroot/www.dchqzg1688.com/public/share/picture_888/'.$user_id.'.jpg');
          
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }


        
       dump($picture);

      
    }


}