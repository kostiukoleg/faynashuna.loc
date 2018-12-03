$(document).ready(function() {
    $('body').on('click', '.addToWishList', function(e) {
        e.preventDefault();

        product_id = $(this).attr('data-item-id');

        $.ajax({
            type: "POST",
            url: "/ajaxrequest",
            data: {
                pluginHandler: 'desires',
                actionerClass: 'Desires',
                action: "addProduct",
                product_id: product_id,
            },
            dataType: "json",
            cache: false,
            success: function(response) {
                alert(response.msg);
                /*$.ajax({
                    type: 'POST',
                    url: '/ajaxrequest',
                    data: {
                        pluginHandler: 'desires',
                        actionerClass: 'Desires',
                        action: 'getCount',
                    },
                    dataType: 'json',
                    cache: false,
                    success: function(response) {
                        if (response.data.count == -1) $('#desires .count').remove();
                        $('#desires .count').html(response.data.count);
                    }
                });*/
            }
        });
    });

    $('body').on('click', '.desire-delete', function(e) {
        e.preventDefault();

        id = $(this).attr('data-id');

        if (!confirm('Вы действительно хотите удалить товар из вашего списка желаний?')) {
            return false;
        }

        $.ajax({
            type: "POST",
            url: "/ajaxrequest",
            data: {
                pluginHandler: 'desires',
                actionerClass: 'Desires',
                action: "delete",
                desire_id: id,
            },
            dataType: "json",
            cache: false,
            success: function(response) {
                alert(response.msg);
                $('.desires-container tr[data-id=' + id + ']').fadeOut(300, function() {
                    $(this).remove();
                });
                /*$.ajax({
                    type: 'POST',
                    url: '/ajaxrequest',
                    data: {
                        pluginHandler: 'desires',
                        actionerClass: 'Desires',
                        action: 'getCount',
                    },
                    dataType: 'json',
                    cache: false,
                    success: function(response) {
                        if (response.data.count == -1) $('#desires .count').remove();
                        $('#desires .count').html(response.data.count);
                    }
                });*/
            }
        });
    });

    $('.desiresEnableTimer').each(function() {
        desiresEnableTimer($(this), $(this).data('time'));
    });

    /*var html = '<div id="desires" title="Мои желания"><img src="' + mgBaseDir + '/mg-plugins/desires/images/desire.png' + '" /><span class="count"></span></div>';
    $(html).prependTo('body');

    $.ajax({
        type: 'POST',
        url: '/ajaxrequest',
        data: {
            pluginHandler: 'desires',
            actionerClass: 'Desires',
            action: 'getCount',
        },
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response.data.count == -1) $('#desires .count').remove();
            $('#desires .count').html(response.data.count);
        }
    });*/
});

function desiresEnableTimer(timer, time) {
    // Узнаём текущее время
    var timeOnStart = new Date().getTime();

    var timeLeft = time;

    var clockDigits = new Array(
        timer.find('.clockDays'),
        timer.find('.clockHours'),
        timer.find('.clockMinutes'),
        timer.find('.clockSeconds')
    );

    setInterval(function() {
        // Текущее время
        var timeCurrent = new Date().getTime();
        // Сколько вемени прошло с момента открытия страницы, сразу переводим из миллисекунд в секунды
        var timePassed = Math.floor((timeCurrent - timeOnStart) * 0.001);
        // Текущее количество секунд до конца отсчёта
        var timeCurrentLeft = timeLeft - timePassed;

        if (timeCurrentLeft > 0) {
            // Переменная, в которой будут храниться остатки, нужна для выделения из оставшихся секунд минут, часов и дней
            var rest = new Array();

            // Действие % — остаток от деления
            rest[0] = timeCurrentLeft % 60;
            rest[1] = (timeCurrentLeft - rest[0]) % 3600;
            rest[2] = (timeCurrentLeft - rest[1] - rest[0]) % 86400;
            rest[3] = (timeCurrentLeft - rest[2] - rest[1] - rest[0]);

            clockDigits[0].html(rest[3] / 86400);
            clockDigits[1].html((rest[2] < 36000 ? '0' : '') + rest[2] / 3600);
            clockDigits[2].html((rest[1] < 600 ? '0' : '') + rest[1] / 60);
            clockDigits[3].html((rest[0] < 10 ? '0' : '') + rest[0]);
        } else {
            // Действие, которое выполняется, когда отсчёт окончен
            //clockDigits[0].innerHTML = '0';
            //clockDigits[1].innerHTML  = clockDigits[2].innerHTML  = clockDigits[3].innerHTML = '00';
            timer.find('span').html('00');
            clockDigits[0].html('0');
        }
    }, 1000);
}