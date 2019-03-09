<?php

namespace app\mobile\controller;

use think\Db;
use app\common\model\WxNews;
 
class Article extends MobileBase
{
    /**
     * 文章内容页
     */
    public function detail()
    {
        $article_id = input('article_id/d', 1);
        $article = Db::name('article')->where("article_id", $article_id)->find();
        $this->assign('article', $article);
        return $this->fetch();
    }

    public function news()
    {
        $id = input('id');
        if (!$news = WxNews::get($id)) {
            $this->error('文章不存在了~', null, '', 100);
        }

        $news->content = htmlspecialchars_decode($news->content);
        $this->assign('news', $news);
        return $this->fetch();
    }
    public function news_detail()
    {
        $id = input('article_id/d',0);
        if(!$id){
            $id = input('news_id/d',0);
        }
        $news = Db::name('news')->find($id);
        if (!$news) {
            $this->error('文章不存在了~', null, '', 100);
        }
        if($news['publish_time'] > time()){
            $this->error('该文章未到发布时间~', null, '', 100);
        }
        $this->assign('news', $news);
        return $this->fetch();
    }
    public function agreement(){
    	$doc_code = I('doc_code','agreement');
    	$article = Db::name('system_article')->where('doc_code',$doc_code)->find();
    	if(empty($article)) $this->error('抱歉，您访问的页面不存在！');
    	$this->assign('article',$article);
    	return $this->fetch();
    }
    
}