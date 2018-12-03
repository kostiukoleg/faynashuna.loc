var mgTimerSale = (function() {
  return {
    init: function() {
      $('.mg-timer-sale').each(function(){
      if ($(this).data('time-finish')) {
        mgTimerSale.timer($(this).attr('id'),$(this).data('time-finish'));
      }
    })
  },
  timer: function(id, times) {
    var clock = times.trim();
    var time = clock, hours;    
    var hour = 0;
    var min = 0;
    var sec = 0;
    if (clock.indexOf(' ') > 0) {
      time = clock.split(' ');
      hours = time[1].split(':');
      hour = hours[0] ? hours[0] : 0;
      min = hours[1] ? hours[1] : 0;
      sec = hours[2] ? hours[2] : 0;
      time = time[0];
    }
    var until = time.split('.');
    var date = new Date(until[2], (parseInt(until[1], 10)-1), until[0], hour, min, sec);
    $('.mg-timer-sale#' + id).countdown({until: date,
    layout: '<div class="number-wrap"><span>{dl}</span><div class="number">{d10}{d1}</div></div>' +
        ' <div class="number-wrap"><span>Час</span><div class="number">{h10}{h1}</div></div> ' +
        '<div class="number-wrap"><span>Мин</span><div class="number">{m10}{m1}</div></div> ' +
        '<div class="number-wrap"><span>Сек</span><div class="number last">{s10}{s1}</div></div> '});
  }
}
})();

$(document).ready(function() {
  mgTimerSale.init();
});