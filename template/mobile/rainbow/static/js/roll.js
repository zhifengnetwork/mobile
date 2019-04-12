$(function () {

    var html
    var box = $('.box')
    var num = 0;
    var arr = []; 
    var list;
    $.ajax({
        type: "get",
        url: "/index.php?m=Api&c=Index&a=virtual_order",
        success: function (data) {

            if (data.status == 0) {
             
                list = data.data;
                
                for (var i in list){ 
                    //完成后加到大数组 
                    arr.push(list[i]); 
                }
                s()
                add()
            } else {
                list = [];
            }
        }
    });


    //    var list = [
    //        {name:"往事如风,10分钟前",img:"d058ccbf6c81800aedd20eb5b43533fa828b4752.jpg"},
    //        {name:"网上冲浪,1111分钟前",img:"goumai@3x.png"},
    //        {name:"王思聪,111分钟前",img:"d058ccbf6c81800aedd20eb5b43533fa828b4752.jpg"},
    //        {name:"王铁柱,11分钟前",img:"goumai@3x.png"},
    //        {name:"顺流,12分钟前",img:"d058ccbf6c81800aedd20eb5b43533fa828b4752.jpg"}
    //     ]

   

    function add() {
        setInterval(s, 5000)
    }


    function s() {
        if (num == arr.length) {
            num = 0;
        }
        else if (num >= 0) {
            html = `<div class="danmu" >
                        <img src="${arr[num].head_pic}">
                        <p>
                            <span class="dan_mc">${arr[num].content}  </span>
                            
                        </p>
               </div>`
            box.html(html);
            num++
        }
        $('.danmu').animate({
            opacity: '1',
            top: '5.8rem',
        },
            1000, function () {
                setTimeout(
                    function () {
                        $('.danmu').animate({
                            opacity: '0',
                            top: '10%',
                        }, 1000)
                    }, 3000
                )
            })
    }
   
})