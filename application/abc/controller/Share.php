<?php
namespace app\abc\controller;

use think\Db;
use think\Controller;

use app\common\logic\ShareLogic;


class Share extends Controller
{
    
    public function index(){

        $user_id = 3;
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
        

        //判断图片大小
        $logo_url = \think\Image::open($url_code);
        $logo_url_logo_width = $logo_url->height();
        $logo_url_logo_height = $logo_url->width();
  
         if($logo_url_logo_height > 420 || $logo_url_logo_width > 420){
             //压缩图片
              $url_code = '/www/wwwroot/www.dchqzg1688.com/public/share/code/'.$user_id.'.jpg';
              $logo_url->thumb(410, 410)->save($url_code , null, 100);
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
        

         //判断图片大小
         $logo = \think\Image::open($url_head_pp);
         $logo_width = $logo->height();
         $logo_height = $logo->width();
 
        if($logo_height > 200 || $logo_width > 200){
            //压缩图片
             $url_head_file = '/www/wwwroot/www.dchqzg1688.com/public/share/head/'.$user_id.'.jpg';
             $logo->thumb(132, 132)->save($url_head_file , null, 100);
        }

        //得到二维码的绝对路径

        $pic = "/www/wwwroot/www.dchqzg1688.com/public/share/picture_ok44/'.$user_id.'.jpg";
        if( @fopen( $pic, 'r' ) )
        {
        	$pic = "/share/picture_ok44/".$user_id.".jpg";
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
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }
        else
        {
           
        	$image = \think\Image::open('/www/wwwroot/www.dchqzg1688.com/public/share/picture_ok44/'.$user_id.'.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_head_pp,\think\Image::TOUXIANG)->save('/www/wwwroot/www.dchqzg1688.com/public/share/picture_888/'.$user_id.'.jpg');
          
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }

        $picture = $picture.'?v='.time();

        dump($picture);

    }

}