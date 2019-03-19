 //倒计时js
//定义方法  
	    function GetRTime(end_time){
	    
	    // var month=month-1;
	    // //定义参数可返回当天的日期和时间
	    // var startTime = new Date();
	    // //调用设置年份
	    // startTime.setFullYear(year,month,day);
	    // //调用设置指定的时间的小时字段
	    // startTime.setHours(23);
	    // //调用设置指定时间的分钟字段
	    // startTime.setMinutes(59);
	    // //调用设置指定时间的秒钟字段
	    // startTime.setSeconds(59);
	    // //调用置指定时间的毫秒字段
	    // startTime.setMilliseconds(999);
	    // //定义参数可返回距 1970 年 1 月 1 日之间的毫秒数
	    // var EndTime=startTime.getTime();
	    	
	  
	    	
	        //定义参数可返回当天的日期和时间  
	        var NowTime = new Date();  
	        //定义参数 EndTime减去NowTime参数获得返回距 1970 年 1 月 1 日之间的毫秒数  
	        var nMS = (end_time * 1000) - NowTime.getTime();
	        //定义参数 获得天数  
	        var nD = Math.floor(nMS/(1000 * 60 * 60 * 24));  
	        //定义参数 获得小时  
	        var nH = Math.floor(nMS/(1000*60*60)) % 24;  
	        //定义参数 获得分钟  
	        var nM = Math.floor(nMS/(1000*60)) % 60;  
	        //定义参数 获得秒钟  
	        var nS = Math.floor(nMS/1000) % 60;
			//定义参数 获得毫秒钟
			var nI = Math.floor(nMS/100)%10;
			//如果秒钟大于0
	        if (nMS < 0){
	        	 //获得天数隐藏  
	            $("#dao").hide();  
	            //获得活动截止时间展开  
	            $("#daoend").show(); 
	           document.getElementById("myprice").onclick=function(){
		       $('#mask').hide();
		       $('#mask2').hide();
		       $("#mask3").show()
		
	         };
	            
	            
	            
	            
	        //否则  
	        }else{  
	           //天数展开  
	           $("#dao").show();  
	           //活动截止时间隐藏  
	           $("#daoend").hide();  
	           //显示天数  
	           $("#RemainD").text(nD);  
	           //显示小时  
	           $("#RemainH").text(nH);  
	           //显示分钟  
	           $("#RemainM").text(nM);  
	           //显示秒钟  
	           $("#RemainS").text(nS);
				//显示毫秒钟
				$("#RemainI").text(nI);


			}
	    }  