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
	$('.chooseStyleBox .chooseStyleBoxTerm').removeClass('chooseActive');
	/*当前*/
	$('.chooseStyleBox .chooseStyleBoxTerm').eq(_index).addClass('chooseActive');
	var buy_type = $('#buy_type').val();
	if(buy_type == 1){
		var price = $('.chooseStyleBox .chooseStyleBoxTerm').eq(_index).attr('price');
		$('.hidden_price').html(price);
	}
	
}

$(document).ready(function(){
	/*购买的数量(后台)*/
	var count = $('#commNumber_lb').html();
	/*收藏 按钮*/
	$('.collectionFun').on('click',function(){
		var collect = $('.collectionImgL').attr('item');
		var goodsid = $('.collectionImgL').attr('goodsid');
		if(collect == 0){
			$.post("collect",{goods_id:goodsid},function(res){
				if(res){
					res = JSON.parse(res);
					if(res.status == 1){
						var src = $('.collectionImgL').attr('src');
						src = src.replace('collectionIcon_lb', 'collectionYes_lb');
						$('.collectionImgL').attr('src',src);
						$('.collectionImgL').attr('item',1);
					}
				}

			})
		}
	})

	/* 点击购买，拼单；弹出选择款式 */
	$('.purchasePublicNum').on('click', function(){
		var item = $(this).attr('item');
		if(item == 1){
			var price = $('#hidden_buy_price').val();
		}else{
			var price = $('#hidden_groupbuy_price').val();
		}
		$('#buy_type').val(item);
		$('.hidden_price').html(price);
		$('.choiceStyleWrap').show();
		$('.wrapL').eq(0).css({'overflow':'hidden'})
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
		var buy_type = $('#buy_type').val();
		var buy_num = Number($('#commNumber_lb').html());
		var team_id = $('#team_id').val();
		var url = "/shop/groupbuy/submit?"+
			"buy_type="+buy_type+
			"&team_id="+team_id+
			"&buy_num="+buy_num
		window.location.href = url;
		return false;
		// $('.choiceStyleWrap').hide();
		// $('.wrapL').eq(0).css({'overflow':''});
	})
	
	/*add number*/
	$('.commNumberL .commNumberAdd').on('click',function(){
		var thisNumAdd = Number($('#commNumber_lb').html());
		var buy_limit = $('#buy_limit').val();
		if(buy_limit > 0){
			if(thisNumAdd > buy_limit || thisNumAdd + 1 > buy_limit){
				alert('限购：'+buy_limit);
				return false;
			}else{
				count = thisNumAdd+1;
			}
		}else{
			if(thisNumAdd >= 9999){
				count = 999;
				alert('上限');
			}
			count = thisNumAdd+1;
		}
		$('#commNumber_lb').html(count);
		
	})
	/*reduce number*/
	$('.commNumberL .commNumberReduce').on('click',function(){
		var thisNumReduce = Number($('#commNumber_lb').html());
		if(thisNumReduce <= 1){
			count = 1;
			alert('最低购买1件');
		}else {
			count = thisNumReduce-1;
		}
		$('#commNumber_lb').html(count);
	})

	
})
