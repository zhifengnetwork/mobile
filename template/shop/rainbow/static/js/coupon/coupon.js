/*
 coupon.js
*/
 $(function(){
            /* tabar 导航操作*/
            $(".selection-item").click(function(){
                var index=$(this).index()
                if($(this).next() || $(this).prev()){
                    $(this).addClass("active").siblings().removeClass("active")
                    $(".copon-listBox ").children().eq(index).show().siblings('').hide().last().addClass("hide")
                }
            })
            
           /* 立即使用 弹出二维码卷*/
           
           $(".btn").click(function(){
               if($(".cover").is(":hidden")){
                $(".cover").removeClass("hide")
               }else{
                $(".cover").addClass("hide")
               }
           })
           /* 隐藏 二维码卷*/ 
           $(".copon-codeBox").click(function(){
            if($(".cover").is(":visible")){
                $(".cover").addClass("hide")
               }else{
                $(".cover").removeClass("hide")
               }
           })
         })