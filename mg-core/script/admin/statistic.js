/**
 * Модуль для  раздела "Статистика".
 */
$(".ui-autocomplete").css('z-index', '1000');
$.datepicker.regional['ru'] = {
  closeText: lang.CLOSE,
  prevText: lang.PREV,
  nextText: lang.NEXT,
  currentText: lang.TODAY,
  monthNames: [lang.MONTH_1 , lang.MONTH_2 , lang.MONTH_3 , lang.MONTH_4 , lang.MONTH_5 , lang.MONTH_6 , lang.MONTH_7 , lang.MONTH_8 , lang.MONTH_9 , lang.MONTH_10 , lang.MONTH_11 , lang.MONTH_12],
  monthNamesShort: [lang.MONTH_SHORT_1 , lang.MONTH_SHORT_2 , lang.MONTH_SHORT_3 , lang.MONTH_SHORT_4 , lang.MONTH_SHORT_5 , lang.MONTH_SHORT_6 , lang.MONTH_SHORT_7 , lang.MONTH_SHORT_8 , lang.MONTH_SHORT_9 , lang.MONTH_SHORT_10 , lang.MONTH_SHORT_11 , lang.MONTH_SHORT_12],
  dayNames: [lang.DAY_1 , lang.DAY_2 , lang.DAY_3 , lang.DAY_4 , lang.DAY_5 , lang.DAY_6 , lang.DAY_7],
  dayNamesShort: [lang.DAY_SHORT_1 , lang.DAY_SHORT_2 , lang.DAY_SHORT_3 , lang.DAY_SHORT_4 , lang.DAY_SHORT_5 , lang.DAY_SHORT_6 , lang.DAY_SHORT_7],
  dayNamesMin: [lang.DAY_MIN_1 , lang.DAY_MIN_2 , lang.DAY_MIN_3 , lang.DAY_MIN_4 , lang.DAY_MIN_5 , lang.DAY_MIN_6 , lang.DAY_MIN_7],
  dateFormat: 'dd.mm.yy',
  firstDay: 1,
  isRTL: false
};
$.datepicker.setDefaults($.datepicker.regional['ru']);

var statistic = {
    
  data: {},
  days: {},

  init: function() {   

    statistic.prepareData();

    $('body').on('click' ,'.changeData' ,function() {
      var request = $(".paramToChart").formSerialize();
      admin.show("statistic.php", cookie("type"), request, statistic.callBack);
    });

  },

  prepareData: function() {
    statistic.data = JSON.parse($('#info-for-chart').val());
    statistic.days = JSON.parse($('#info-for-chart-days').val());
  },

  drawChart: function() {
    var ctx = document.getElementById("statisticChart").getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: statistic.days,
        datasets: [{
          label: lang.EARNED_SUM,
          data: statistic.data,
          borderColor: [
            $('.header-top').css('background-color')
          ],
          backgroundColor: [
            // '#fff',
            'rgba(0,0,0,0)'
          ],
          borderWidth: 2,
          pointBorderWidth: 3,
          pointRadius: 2
        }]
      },
      options: {
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero:true
            }
          }]
        },
        legend: {
          display: false
        }
      }
    });
  },

  callBack: function() {
    statistic.init();
    statistic.drawChart();
    $('.section-statistic .to-date').datepicker({dateFormat: "dd.mm.yy"});
    $('.section-statistic .from-date').datepicker({dateFormat: "dd.mm.yy"});   
  },

};

// инициализация модуля при подключении
statistic.init();