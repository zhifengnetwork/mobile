/*动态创建对话框*/
/**第一参数:把动态标签放在那里。
 * 第二参数:提示文本。
 * **/
function suredAlert(_butShow,_text) {
	var str = '';
	str += '<div class="maskWrap">';
		str += '<div class="signOutBox">';
			str += '<div class="signOutBox-text paddingOneRem">';
				str += '<p class="alertBoxTipsText fontSizieThree">' + _text + '</p>';
			str += '</div>';
			str += '<div class="signOutBox-button">';
				str += '<p class="signOut-confirm sure-middle" onclick="thisButD(true)">确认</p>';
			str += '</div>';
		str += '</div>';
	str += '</div>';
	
	/*放在最外边框的第一位*/
	_butShow.prepend(str);
	/*获取(手机)遮罩层的height*/
	$('.maskWrap').css({
		'height': $(window).height(),
	});
	
	return false;
}
/**
 * 弹框=>确认按钮
 * judge: 确定（true）||取消（false）
 * **/
var judge = null;
function thisButD(_judge) {
	console.log(111);
	judge = _judge;
	/*隐藏 => 弹框*/
	/*$('.maskWrap').hide();*/
	/*删除 => 弹框*/
	$('.maskWrap').remove();
	return _judge;
}
/*动态创建对话框*/
/**第一参数:把动态标签放在那里。
 * 第二参数:提示的标题。
 * 第三、四参数:（对应）按钮提示的title，点击跳转的路径。
 * 第四、五参数:（对应）按钮提示的title，点击跳转的路径。
 * **/
function receiveAlert(_butShow,_title,_urlTitleOne,_urlTitleOneUrl,_urlTitleTwo,_urlTitleTwoUrl,) {
	var strR = '';
	strR += '<div class="maskWrap">';
		strR += '<div class="signOutBox">';
			strR += '<div class="signOutBox-title">';
				strR += '<span>'+ _title +'</span>';
				strR += '<p class="closeImgBox" onclick="closeCBut()">';
					strR += '<img class="closeImg" src="/template/mobile/rainbow/static/images/public_lb/closeIcon.png"/>';
				strR += '</p>';
			strR += '</div>';
			strR += '<div class="signOutBox-buttonC">';
				strR += '<p class="signOut-collar" onclick="window.location.href = '+ _urlTitleOneUrl +'">'+ _urlTitleOne +'</p>';
			strR += '</div>';
			strR += '<div class="signOutBox-buttonC">';
				strR += '<p class="signOut-collar" onclick="window.location.href = '+ _urlTitleTwoUrl +'">'+ _urlTitleTwo +'</p>';
			strR += '</div>';
		strR += '</div>';
	strR += '</div>';
	
	/*放在最外边框的第一位*/
	_butShow.prepend(strR);
	/*获取(手机)遮罩层的height*/
	$('.maskWrap').css({
		'height': $(window).height(),
	});
	
	return false;
}
/**
 * 弹框=>领取产品
 * **/
function closeCBut() {
	console.log('领取产品');
	/*删除 => 弹框*/
	$('.maskWrap').remove();
	return false;
}

