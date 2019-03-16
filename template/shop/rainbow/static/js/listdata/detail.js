/*Initialize Swiper*/
var swiper = new Swiper('.swiper-container_lb', {
	/*方向*/
	direction: 'horizontal',
	/*轮播项-循环*/
	loop: true,
	//设置自动循环播放
	autoplay: {
		/*时间间隔*/
		delay: 3000,
		/*允许客户操作后，自动轮播*/
		disableOnInteraction: false,
	},
	/*分页器*/
	pagination: {
		el: '.swiper-pagination',
//		el: 'swiper-pagination_lb',
	},
});

/*选择款式Fun*/
function butStyleFun(_index){
	console.log(_index);
	$('.chooseStyleBox .chooseStyleBoxTerm').removeClass('chooseActive');
	/*当前*/
	$('.chooseStyleBox .chooseStyleBoxTerm').eq(_index).addClass('chooseActive');
}

$(document).ready(function(){
	/*购买的数量(后台)*/
	var count = $('#commNumber_lb').html();
	/*收藏 按钮*/
	$('.collectionFun').on('click',function(){
		alert(123);
		// $(this).find('.collectionImgL').attr('src','__STATIC__/images/public_lb/collectionIcon_lb.png');
		// $(this).find('.collectionImgL').attr('src','__STATIC__/images/public_lb/collectionYse_lb.png');
	})
	/*关闭-拼单-弹框*/
	$('.asHeadCIconBox').on('click',function(){
		$('.assembleAlertWrap').hide();
		$('.wrapL').eq(0).css({'overflow':''})
	})

	/*点击拼单-拼单弹框 */
	$('.pellListTermBut').on('click',function(){
		$('.assembleAlertWrap').show();
		$('.wrapL').eq(0).css({'overflow':'hidden'})
	})
	
	/*关闭-选择样式-弹框*/
	$('.choiceStyleIconBox').on('click',function(){
		$('.choiceStyleWrap').hide();
		$('.wrapL').eq(0).css({'overflow':''})
	})
	/*点击拼单（弹框里的） */
	$('.spellListAlertBut').on('click',function(){
		$('.assembleAlertWrap').hide();
		$('.choiceStyleWrap').show();
		$('.wrapL').eq(0).css({'overflow':'hidden'})
	})
	
	/*确定按钮*/
	$('.chooseConfirmL').on('click',function(){
		$('.choiceStyleWrap').hide();
		$('.wrapL').eq(0).css({'overflow':''});
		console.log('购买件数:',count);
	})
	
	/*add number*/
	$('.commNumberL .commNumberAdd').on('click',function(){
		var thisNumAdd = Number($('#commNumber_lb').html());
		console.log('加');
		if(thisNumAdd >= 9999){
			count = 999;
			alert('上限');
		}else {
			count = thisNumAdd+1;
		}
		$('#commNumber_lb').html(count);
		
	})
	/*reduce number*/
	$('.commNumberL .commNumberReduce').on('click',function(){
		var thisNumReduce = Number($('#commNumber_lb').html());
		console.log('减',thisNumReduce);
		if(thisNumReduce <= 1){
			count = 1;
			alert('最低购买1件');
		}else {
			count = thisNumReduce-1;
			console.log(count,666);
		}
		$('#commNumber_lb').html(count);
	})

	
})
