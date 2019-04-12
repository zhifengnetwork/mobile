var level = $('#level').val();
var province_id,city_id,district_id,province_name,city_name,district_name;
locationInitialize();
ajaxProvince();

//选择省 start
$('body').on('click', '.province-list p', function () {
  province_id = $(this).attr('data-id');
  province_name = $(this).text();
  if(level != 3){
    ajaxCity(province_id);
  }else{
    delCookie('province_id');
    delCookie('province_name');
    setCookies('province_id', province_id, 30 * 24 * 60 * 60 * 1000);
    setCookies('province_name', province_name, 30 * 24 * 60 * 60 * 1000);
    $('.container').hide();
    $('body').css('overflow','inherit');
    if(typeof(select_area_callback)=='function'){
        //采用回调方式传地址信息, fix: 修复手机端选择地址时有时无法正常获取选择的地址信息
        select_area_callback(province_name ,'' ,'',province_id,'','');
    }
    undercover();
    $('.mask-filter-div').css('z-index','inherit');
  }
});

//选择市 start
$('body').on('click', '.city-list p', function () {
  city_id = $(this).attr('data-id');
  city_name = $(this).text();
  if(level != 2){
    ajaxDistrict(city_id);
  }else{
    delCookie('province_id');
    delCookie('city_id');
    delCookie('province_name');
    delCookie('city_name');
    setCookies('province_id', province_id, 30 * 24 * 60 * 60 * 1000);
    setCookies('city_id', city_id, 30 * 24 * 60 * 60 * 1000);
    setCookies('province_name', province_name, 30 * 24 * 60 * 60 * 1000);
    setCookies('city_name', city_name, 30 * 24 * 60 * 60 * 1000);
    $('.city-list').hide();
    $('.province-list').show();
    $('.container').hide();
    $('body').css('overflow','inherit');
    if(typeof(select_area_callback)=='function'){
        //采用回调方式传地址信息, fix: 修复手机端选择地址时有时无法正常获取选择的地址信息
        select_area_callback(province_name ,city_name ,'',province_id,city_id,'');
    }
    undercover();
    $('.mask-filter-div').css('z-index','inherit');
  }
  
});

//选择区 start
$('body').on('click', '.area-list p', function () {
  district_id = $(this).attr('data-id');
  district_name = $(this).text();
  delCookie('province_id');
  delCookie('city_id');
  delCookie('district_id');
  delCookie('province_name');
  delCookie('city_name');
  delCookie('district_name');
  setCookies('province_id', province_id, 30 * 24 * 60 * 60 * 1000);
  setCookies('city_id', city_id, 30 * 24 * 60 * 60 * 1000);
  setCookies('district_id', district_id, 30 * 24 * 60 * 60 * 1000);
  setCookies('province_name', province_name, 30 * 24 * 60 * 60 * 1000);
  setCookies('city_name', city_name, 30 * 24 * 60 * 60 * 1000);
  setCookies('district_name', district_name, 30 * 24 * 60 * 60 * 1000);
  $('.area-list').hide();
  $('.province-list').show();
  $('.container').hide();
  $('body').css('overflow','inherit');
    if(typeof(select_area_callback)=='function'){
        //采用回调方式传地址信息, fix: 修复手机端选择地址时有时无法正常获取选择的地址信息
        select_area_callback(province_name ,city_name ,district_name,province_id,city_id,district_id);
    }
  undercover();
  $('.mask-filter-div').css('z-index','inherit');
});
//标记字体颜色
$('.container').on('click','p',function(){
  $(this).addClass('co_current').siblings().removeClass('co_current');
})
//高度
$(function(){
  var whei = $(window).height();
  $('.container .city').height(whei).css('padding-bottom','15%');
})

/**
 * ajax加载省
 */
function ajaxProvince(){
  $.ajax({
    type : "get",
    url: "/index.php?m=Home&c=Api&a=getProvince",
    dataType:"json",
    success: function(data){
      var province_html = '';
      if(data.status == 1){
        var province_count = data.result.length;
        for(var i = 0;i < province_count;i++){
          province_html += '<p data-id="'+data.result[i].id+'">'+data.result[i].name+'</p>';
        }
      }
      $(".province-list").empty().append(province_html);
    }
  });
} 

/**
 * ajax根据省id加载市
 */
function ajaxCity(parent_id) {
  $.ajax({
    type: "get",
    url: "/index.php?m=Home&c=Api&a=getRegionByParentId",
    dataType: "json",
    data: {parent_id: parent_id},
    success: function (data) {
      var city_html = '';
      if (data.status == 1) {
        var city_count = data.result.length;
        for (var i = 0; i < city_count; i++) {
          city_html += '<p data-id="' + data.result[i].id + '">' + data.result[i].name + '</p>';
        }
      }
      $(".city-list").empty().append(city_html);
      $('.province-list').hide();
      $('.city-list').show();
    }
  });
}

/**
 * ajax根据省id加载市
 */
function ajaxDistrict(parent_id) {
  $.ajax({
    type: "get",
    url: "/index.php?m=Home&c=Api&a=getRegionByParentId",
    dataType: "json",
    data: {parent_id: parent_id},
    success: function (data) {
      var district_html = '';
      if (data.status == 1) {
        var district_count = data.result.length;
        for (var i = 0; i < district_count; i++) {
          district_html += '<p data-id="' + data.result[i].id + '">' + data.result[i].name + '</p>';
        }
      }
      $('.area-list').empty().append(district_html);
      $('.city-list').hide();
      $('.area-list').show();
    }
  });
}
/**
 * 初始化操作
 */
function locationInitialize()
{
  province_id = getCookieByName('province_id');
  city_id = getCookieByName('city_id');
  district_id = getCookieByName('district_id');
  province_name = getCookieByName('province_name');
  city_name = getCookieByName('city_name');
  district_name = getCookieByName('district_name');
  if(province_id==null || city_id==null || district_id==null || province_name==null || city_name==null || district_name==null){
    province_id = 1;
    city_id = 2;
    district_id = 3;
    province_name = '北京市';
    city_name = '市辖区';
    district_name = '东城区';
  }
  $('#address').text(province_name+city_name+district_name);
}
