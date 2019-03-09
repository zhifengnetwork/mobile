//滚动加载更多
$(window).scroll(
    function() {
        var scrollTop = parseInt($(this).scrollTop());
        var scrollHeight = parseInt($(document).height());
        var windowHeight = parseInt($(this).height());
        var chazhi = (scrollTop + windowHeight ) - (scrollHeight-60);
        if ( chazhi >= -800 ){
            ajax_sourch_submit();//调用加载更多
        }
    }
);