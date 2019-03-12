$(function(){
	
	var step=20
	document.getElementById("myprice").onclick=function(){
		
		$('#mask').show();
		
	};
	
	document.getElementById("tobuy").onclick=function(){
		
		$('#mask').hide();
		$('#mask2').show();
		$("#mask3").hide()
	
	};
	document.getElementById("tobuy1").onclick=function(){
		
		
		$("#mask3").hide()
	
	};
	document.getElementById("jian").onclick=function(){
		
		
		
        $("#mmer").val($("#mmer").val()*1-step);
        if($("#mmer").val()*1<0){
			
		    $("#mmer").val(0);	
			
		}
       
	};
	document.getElementById("jia").onclick=function(){
		
        $("#mmer").val($("#mmer").val()*1+step);
       
	};
	
	
	
	
	
	
		
})

            
	   

     
     
     



