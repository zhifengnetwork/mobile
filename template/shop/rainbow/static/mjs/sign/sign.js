/*签到的数据(后台传的年份（编辑后）)*/
var signData = [];

$(function() {
	$('.date').dateMarker();
});
/*
 * 留学日历
 * 可以选择年月
 * 指定日期传入标签
 * initDate 格式为YYYY/M/D （不要在月份和日期前加0）
 */


(function($) {
	$.fn.dateTags = function(opts) {

		var defaults = {
				'months': ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
				'weeks': ['日', '一', '二', '三', '四', '五', '六'],
				// 'title': '2015年托福考试时间',
				'initDate': '',
				'tags': []
			},
			settings = $.extend({}, defaults, opts);

		//获取标题
		function getTitle(text) {
			return '<h6>' + text + '</h6>';
		}

		/*
		 * 初始日期
		 * 如果指定初始日期，使用指定日期
		 * 没有指定初始日期，使用第一个标签日期
		 * 没有指定日期，无标签，使用当前日期
		 */

		function setInitDate() {
			var _date = new Date(),
				_year = _date.getFullYear(),
				_month = _date.getMonth() + 1,
				_text = '';
			if(settings.initDate != '') {
				return;
			} else if(settings.tags.length) {
				_text = settings.tags[0].time;
			} else {
				_text = _year + '/' + _month + '/1';
			}

			settings.initDate = _text;

		}
		setInitDate();

		//获取月份
		function getMonthSelect(arr) {
			var _html = '<div class="date-month"><select class="date-month-select">';
			$.each(arr, function(i, n) {
				_html += '<option value="' + (i + 1) + '">' + n + '</option>';
			});
			return _html + '</select></div>'
		}

		//获取星期
		function getWeek(arr) {
			var _html = '<ul class="date-ul">';
			$.each(arr, function(i, n) {
				_html += '<li>' + n + '</li>';
			});
			return _html;
		}

		//获取空行
		function getEmpty(len) {
			var _html = '',
				i = 0;
			for(; i < len; i++) {
				_html += '<li></li>';
			}
			return _html;
		}
		//获取日数
		function getDay(year, month) {
			var _year = year,
				_month = month,
				_date = _year + '/' + _month + '/1',
				_dayLength = new Date(_year, _month, 0).getDate(),
				_html = getWeek(settings.weeks) + getEmpty(new Date(_date).getDay()),
				i = 1;
			for(; i < _dayLength + 1; i++) {
				_html += '<li data-date="' + _year + '/' + _month + '/' + i + '">' + i + '</li>';
			}
			return _html + '</ul>';
		}
		//切换月份
		function changeMonth(target, year) {
			target.on('change', '.date-month-select', function() {
				var _month = this.value;
				target.find('.date-ul').html(getDay(year, _month));
				setTags(target);
			});
		}

		//设置标签
		function setTags(target) {
			$.each(settings.tags, function() {
				var _that = this;
				var _em = '<em>' + _that.tag + '</em>';
				target.find('[data-date="' + _that.time + '"]').append(_em).addClass('active');
			});
		}

		//设置日期
		function setDate(target, year, month) {
			target.html(
				getTitle(settings.title) + getMonthSelect(settings.months) + getDay(year, month)
			);
			setInitMonth(target, month);
			setTags(target);
		}

		//设置初始月份
		function setInitMonth(target, month) {
			var _options = target.find('option');
			$.each(_options, function(i, n) {
				if(month === n.value) {
					n.selected = 'selected';
				}
			});

		}
		//初始化日历
		function init(target) {
			var _date = settings.initDate.split('/'),
				_year = _date[0],
				_month = _date[1];
			changeMonth(target, _year);
			setDate(target, _year, _month);
		}

		return this.each(function() {
			init($(this));
		});

	};

}(window.jQuery));