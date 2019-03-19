$(function(){


	document.getElementById("tobuy1").onclick=function(){

        var goods_id = $('input[name="goods_id"]').val();
        $.ajax({
            type: "POST",
            url: "/index.php?m=Mobile&c=Auction&a=alreadyRead",//+tab,
            data: {aid:goods_id},
            dataType: "json",
            success: function (data) {
                if (data.status == 1) {
                    $("#mask3").hide();
                    // return false;
                }

            }
        });
	
	};

		
})

