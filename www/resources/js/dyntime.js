(function($){
	var startval = 'épp most';
	var timePad = function(i){if(!isNaN(i)){i=parseInt(i);if (i<10&&i>=0)i='0'+i;else if(i<0)i='-0'+Math.abs(i);else i=i.toString()}return i};
	var months = [ undefined,
		"január", 
		"február",
		"március",
		"április",
		"május",
		"június",
		"július",
		"augusztus",
		"szeptember",
		"október",
		"november",
		"december"
	];
	var weekdays = [
		"Vasárnap",
		"Hétfő",
		"Kedd",
		"Szerda",
		"Csütörtök",
		"Péntek",
		"Szombat",
	];
	var dateformat = {
		order: '{{wd}}, {{y}}. {{mo}} {{d}}. {{h}}:{{mi}}:{{s}}',
		day: function(date){ return date },
		weekday: function(wd){ return weekdays[parseInt(wd)] },
		month: function(m){ return months[parseInt(m)] },
		year: function(y){ return y },
	};
	var snLnArr = [
		's:másodperce',
		'mi:perce',
		'h:órája',
		'd:napja',
		'w:hete',
		'mo:hónapja',
		'y:éve',
	];
	var snLnObj = {
		s:'másodperce',
		mi:'perce',
		h:'órája',
		d:'napja',
		w:'hete',
		mo:'hónapja',
		y:'éve',
	};
	var timeparts = function(key, num){
		if (typeof snLnObj[key] !== 'undefined') return num+' '+snLnObj[key];
		else return undefined;
	};
	var update = function(){
		$('time').each(function(){
			var $this = $(this),
				date = $this.attr('datetime'),
				timestamp = new Date(date);
			if (typeof date !== 'string') throw new TypeError('Invalid date data type: "'+(typeof date)+'"');
			if (isNaN(timestamp.getTime())) throw new Error('Invalid date format: "'+date+'"');
				
			if (!$this.hasClass('dynt')) $this.addClass('dynt');

			var orderstr = (typeof $this.data('order') !== 'undefined') ? $this.data('order') : dateformat.order;

			var date = {
				d: dateformat.day(timestamp.getDate()),
				y: dateformat.year(timestamp.getFullYear()),
				mo: dateformat.month(timestamp.getMonth()+1),
				wd: dateformat.weekday(timestamp.getDay()),
				h: timePad(timestamp.getHours()),
				mi: timePad(timestamp.getMinutes()),
				s: timePad(timestamp.getSeconds()),
				order: orderstr,
			}
			var keys = Object.keys(date);
			keys.splice(keys.indexOf('order'),1);
			
			for (var i=0,l=keys.length; i<l; i++) date.order = date.order.replace(new RegExp('\{\{'+keys[i]+'\}\}'),date[keys[i]]);
			
			var now = new Date(), justnow = startval;
			if (typeof $this.data('justnow') !== 'undefined') justnow = $this.data('justnow');
			var timestr = createTimeStr(timeDifference(now,timestamp)).replace(/^$/,justnow);
			
			var $elapsedHolder = $this.parent().children('.dynt-el');
			if ($elapsedHolder.length > 0){
				$this.html(date.order);
				$elapsedHolder.html(timestr);
			}
			else {
				$this.attr('title', date.order);
				$this.html(timestr);
			}
		});
	};
	/**
		Time difference function (modified)
		source: http://psoug.org/snippet/Javascript-Calculate-time-difference-between-two-dates_116.htm
		
		I did not create this function entirely by myself, and I'm taking no credit for the parts I didn't write.
	**/
	function timeDifference(n,e) {
		var d = {
			time: n.getTime() - e.getTime()
		};
		
		if (d.time < 0) d.time = 0;
		
		d.day = Math.floor(d.time/1000/60/60/24);
		d.time -= d.day*1000*60*60*24;
		
		d.hour = Math.floor(d.time/1000/60/60);
		d.time -= d.hour*1000*60*60;
		
		d.minute = Math.floor(d.time/1000/60);
		d.time -= d.minute*1000*60;
		
		d.second = Math.floor(d.time/1000);
		
		if (d.day >= 7){
			d.week = Math.floor(d.day/7);
			d.day -= d.week*7;
		}
		if (d.week >= 4){
			d.month = Math.floor(d.week/4);
			d.week -= d.month*4;
		}
		if (d.month >= 12){
			d.year = Math.floor(d.month/12);
			d.month -= d.year*12;
		}
		
		return d;
	}
	function createTimeStr(obj){
		if (typeof obj !== 'object' || $.isArray(obj)) return false;
		if (obj.time > 0) delete obj.time;
		
		var keys = Object.keys(obj), returnStr = '';
		for (var i=0,l=keys.length; i<l; i++) if (keys[i] !== 'second' && obj[keys[i]] < 1) delete obj[keys[i]];
		
		if (obj.year > 0) returnStr = timeparts('y',obj.year);
		else if (obj.month > 0) returnStr = timeparts('mo',obj.month);
		else if (obj.week > 0) returnStr = timeparts('w',obj.week);
		else if (obj.day > 0) returnStr = timeparts('d',obj.day);
		else if (obj.hour > 0) returnStr = timeparts('h',obj.hour);
		else if (obj.minute > 0) returnStr = timeparts('mi',obj.minute);
		else if (obj.second > 0) returnStr = timeparts('s',obj.second);
		
		return returnStr;
	}
	update();
	window.updateTimesF = function(){
		update.apply(update,arguments);
	};
	if (window.noAutoUpdateTimes !== true) window.updateTimes = setInterval(update,10000);
})(jQuery);