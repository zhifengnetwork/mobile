$(document).ready(function(){
	/*Tab切换*/
	$('.tabWrap_com .tabBox_com').on('click',function(){
		console.log($(this).index('.tabBox_com'));
		var thisInd = $(this).index('.tabBox_com');
		/*Tab切换*/
		$('.tabWrap_com .tabBox_com').removeClass('tabBox_com_active');
		$('.tabWrap_com .tabBox_com').eq(thisInd).addClass('tabBox_com_active');
		/*内容 wrap*/
		$('.content .contentTabWrap').hide();
		$('.content .contentTabWrap').eq(thisInd).show();
	})
})
