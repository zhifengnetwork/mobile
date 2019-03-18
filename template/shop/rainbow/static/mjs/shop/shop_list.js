$(function(){
	
	$(".menu li").click(function() {
		$(".menu li").eq($(this).index()).addClass("active").siblings().removeClass("active");
		$(".branchList").hide().eq($(this).index()).show();

	})
	
	
});
