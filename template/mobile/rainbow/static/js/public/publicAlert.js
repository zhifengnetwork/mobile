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
			//	str +='<p class="numericalValue">'+_number+'</p>';
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
