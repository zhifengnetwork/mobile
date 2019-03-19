/*当前Tab切换的效果（跳转页面，重新渲染）*/
var tabnumber = Number($('.headWrap_lb .headTab_lb').eq(0).attr('data-tab'));
// console.log('当前Tab的num:', tabnumber);
if(tabnumber == 0){
	$('.headWrap_lb .headTab_lb .headTabTerm_lb').eq(0).addClass('activeTab_lb');
}else if(tabnumber == 1){
	$('.headWrap_lb .headTab_lb .headTabTerm_lb').eq(1).addClass('activeTab_lb');
}else if(tabnumber == 2){
	$('.headWrap_lb .headTab_lb .headTabTerm_lb').eq(2).addClass('activeTab_lb');
}