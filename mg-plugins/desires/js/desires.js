/**
 * Модуль для  раздела "Тикет-система".
 */

var desires = (function() {
    return {
        lang: [], // локаль плагина

        /**
         * Инициализирует обработчики для кнопок и элементов раздела.
         */
        init: function() {
            // установка локали плагина
            admin.ajaxRequest({
                    mguniqueurl: "action/seLocalesToPlug",
                    pluginName: 'desires'
                },
                function(response) {
                    desires.lang = response.data;
                }
            );


            $('.admin-center').on('click', '.section-desires .edit-row', function() {
                if ($(this).hasClass('active')) {
                    admin.indication(false, 'Это желание уже было подтверждено!');
                    return false;
                };
                desires.openModalWindow('discount', $(this).data('id'));
            });

            $('.admin-center').on('click', '.section-desires .show-settings', function() {
                desires.openModalWindow('settings');
            });

            $('.admin-center').on('keyup', '.section-desires .desire-show input[name=discount]', function() {
                discount = parseFloat($(this).val());
                price = parseFloat($('.desire-show input[name=price]').data('price'));

                newprice = price - (price / 100 * discount);
                $('.desire-show input[name=price]').val(newprice);
            });

            // Вызов модального окна при нажатии на кнопку ответа.
            $('.admin-center').on('click', '.section-desires .desire-show .save-button', function() {
                desires.confirmDesire($(this).data('id'), $('.desire-show input[name=discount]').val(), $('.desire-show input[name=discountTimer]').val());
            });

            $('.admin-center').on('click', '.section-desires .desire-show .cancel-button', function() {
                desires.cancelDesire($(this).data('id'));
            });

            $('.admin-center').on('click', '.section-desires .modal-settings .save-button', function() {
                desires.saveSettings();
            });

            $('.admin-center').on('click', '.section-desires .delete-row', function() {
                desires.remove($(this).data('id'));
            });

            $('.admin-center').on('click', '.section-desires .visible', function() {
                //desires.confirmDesire($(this).data('id'));
            });

            $('.admin-center').on('click', '.section-desires .spoiler .title', function() {
                $(this).parent().find('.content').slideToggle();
            });

            // Устанавливает количиство выводимых записей в этом разделе.
            $('.admin-center').on('change', '.section-desires .countPrintRowsPage', function() {
                var count = $(this).val();

                admin.ajaxRequest({
                        pluginHandler: 'desires', // имя папки в которой лежит данный плагин
                        actionerClass: "desires",
                        action: "setCountPrintRowsComments",
                        count: count
                    },
                    function(response) {
                        admin.refreshPanel();
                    });
            });

            $('.admin-center').on('click', '.section-desires .get-csv', function() {
                desires.getCSV();
            });

            $('body').on('click', '.desire-allcheck', function() {
                if ($(this).prop('checked')) {
                    $('.desire-check').prop('checked', true);
                } else {
                    $('.desire-check').prop('checked', false);
                }
            });

            $('body').on('click', '.desire-run-operation', function() {
                ids = [];

                $('.desire-check').each(function() {
                    if ($(this).prop('checked')) {
                        ids.push($(this).data('id'));
                    }
                });

                desires.removeByIds(ids);
            });

        },

        openModalWindow: function(type, id) {
            switch (type) {
                case 'discount':
                    desires.clearFileds();
                    //$('#modalTitle').text('Желание пользователя');
                    desires.discountPage(id);

                    // Вызов модального окна.   
                    admin.openModal('.desire-show');
                    break;

                case 'settings':
                    // Вызов модального окна.   
                    admin.openModal('.modal-settings');
                    desires.settingsPage();
                    break;
            }
        },

        /**
         * Получает данные о новости с сервера и заполняет ими поля в окне.
         */
        discountPage: function(id) {
            admin.ajaxRequest({
                    pluginHandler: 'desires', // имя папки в которой лежит данный плагин
                    actionerClass: 'desires', // класс desires в desires.php - в папке плагина
                    action: "getData",
                    id: id
                },
                desires.fillFileds(id)
            );
        },

        settingsPage: function() {
            admin.ajaxRequest({
                    pluginHandler: 'desires', // имя папки в которой лежит данный плагин
                    actionerClass: 'desires', // класс desires в desires.php - в папке плагина
                    action: "getSettings",
                },
                desires.fillSettingsFileds()
            );
        },

        remove: function(id) {
            admin.ajaxRequest({
                    pluginHandler: 'desires',
                    actionerClass: 'desires',
                    action: "remove",
                    desire_id: id
                },

                function() {
                    $('.section-desires tr[data-id=' + id + ']').remove();
                }
            );
        },

        removeByIds: function(ids) {
            admin.ajaxRequest({
                    pluginHandler: 'desires',
                    actionerClass: 'desires',
                    action: "remove",
                    desire_id: ids
                },

                function() {
                    $.each(ids, function(k, v) {
                        $('.section-desires tr[data-id=' + v + ']').remove();
                    });
                }
            );
        },

        confirmDesire: function(id, discount, timer) {
            admin.ajaxRequest({
                    pluginHandler: 'desires',
                    actionerClass: 'desires',
                    action: "confirm",
                    desire_id: id,
                    discount: discount,
                    timer: timer,
                },

                function(response) {
                    admin.indication(response.status, response.msg);
                    if (response.status) {
                        admin.closeModal($('.desire-show'));
                        $('tr[data-id=' + id + '] .edit-row').addClass('active');
                        desires.updateProductData(id, {
                            closed: '<span style="color:green">' + discount + '%</span>'
                        });
                    }
                }
            );
        },

        cancelDesire: function(id) {
            admin.ajaxRequest({
                    pluginHandler: 'desires',
                    actionerClass: 'desires',
                    action: "cancel",
                    desire_id: id,
                },

                function(response) {
                    admin.indication(response.status, response.msg);
                    admin.closeModal($('.desire-show'));
                    desires.updateProductData(id, {
                        closed: '<span style="color:red">Отклонено</span>'
                    });
                });
        },

        updateProductData: function(id, data) {
            tr = $('.section-desires tr[data-id=' + id + ']');
            $.each(data, function(k, v) {
                tr.find('td.' + k).html(v);
            });
        },

        getCSV: function() {
            admin.ajaxRequest({
                    pluginHandler: 'desires', // имя папки в которой лежит данный плагин
                    actionerClass: 'desires', // класс desires в desires.php - в папке плагина
                    action: "getCSV", // название действия в пользовательском  классе News
                },

                function(data) {
                    document.location.href = data.data.url;
                }
            );
        },

        // меняем индикатор количества новых комментариев
        indicatorCount: function(count) {
            if (count == 0) {
                $('.button-list a[rel=desires]').parents('li').find('.count-wrap').hide();
            } else {
                $('.button-list a[rel=desires]').parents('li').find('.count-wrap').show();
                $('.button-list a[rel=desires]').parents('li').find('.count-wrap').text(count);
            }
        },

        /**
         * Заполняет поля модального окна данными
         */
        fillFileds: function(id) {
            return (function(response) {
                data = response.data.data;
                $('.controlBlock').show();
                $('.desire-show input[name=price]').val(data.price_course);
                $('.desire-show input[name=price]').data('price', data.price_course);

                $('.desire-show .save-button').data('id', id);
                $('.desire-show .cancel-button').data('id', id);
            })
        },

        fillSettingsFileds: function() {
            return (function(response) {
                console.log(response);
                $('.modal-settings input[name=buttonTitle]').val(response.data.settings.buttonTitle);
                $('.modal-settings input[name=timerValue]').val(response.data.settings.timerValue);
                $('.modal-settings textarea[name=emailText]').val(response.data.settings.emailText);
                $('.modal-settings input[name=desiresLcPerPage]').val(response.data.settings.desiresLcPerPage);
                $('.modal-settings select[name=emailTemplate] option[value="' + response.data.settings.emailTemplate + '"]').prop('selected', true);
                $('.modal-settings select[name=enableCounter] option[value="' + response.data.settings.enableCounter + '"]').prop('selected', true);
                $('.modal-settings select[name=enableManyDesires] option[value="' + response.data.settings.enableManyDesires + '"]').prop('selected', true);
                $('.modal-settings select[name=enableLcTimer] option[value="' + response.data.settings.enableLcTimer + '"]').prop('selected', true);
                $('.modal-settings input[name=sendEmail]').val(response.data.settings.sendEmail);
                if (response.data.settings.defaultUse == 'true')
                    $('.modal-settings input[name=defaultUse]').prop('checked', true);
                $('.modal-settings input[name=defaultDiscount]').val(response.data.settings.defaultDiscount);;
                $('.modal-settings input[name=defaultPeriod]').val(response.data.settings.defaultPeriod);;
                if (response.data.settings.roundResult == 'true')
                    $('.modal-settings input[name=roundResult]').prop('checked', true);
                $('.modal-settings select[name=useLinks]').val(response.data.settings.useLinks);
            });
        },

        clearFileds: function() {
            $('.desire-show input[name=discount]').val('0');
            $('.desire-show input[name=price]').val('0');
            $('.desire-show .save-button').data('id', '');
            $('.desire-show .cancel-button').data('id', '');
        },

        saveSettings: function() {
            buttonTitle = $('.modal-settings input[name=buttonTitle]').val();
            timerValue = $('.modal-settings input[name=timerValue]').val();
            emailText = $('.modal-settings textarea[name=emailText]').val();
            emailTemplate = $('.modal-settings select[name=emailTemplate]').val();
            enableCounter = $('.modal-settings select[name=enableCounter]').val();
            enableManyDesires = $('.modal-settings select[name=enableManyDesires]').val();
            enableLcTimer = $('.modal-settings select[name=enableLcTimer]').val();
            desiresLcPerPage = $('.modal-settings input[name=desiresLcPerPage]').val();
            sendEmail = $('.modal-settings input[name=sendEmail]').val();
            defaultUse = $('.modal-settings input[name=defaultUse]').prop('checked');
            defaultDiscount = $('.modal-settings input[name=defaultDiscount]').val();
            defaultPeriod = $('.modal-settings input[name=defaultPeriod]').val();
            roundResult = $('.modal-settings input[name=roundResult]').prop('checked');
            useLinks = $('.modal-settings select[name=useLinks]').val();

            admin.ajaxRequest({
                    pluginHandler: 'desires',
                    actionerClass: 'desires',
                    action: "saveSettings",
                    settings: {
                        buttonTitle: buttonTitle,
                        emailText: emailText,
                        emailTemplate: emailTemplate,
                        enableCounter: enableCounter,
                        enableManyDesires: enableManyDesires,
                        timerValue: timerValue,
                        enableLcTimer: enableLcTimer,
                        desiresLcPerPage: desiresLcPerPage,
                        sendEmail: sendEmail,
                        defaultUse: defaultUse,
                        defaultDiscount: defaultDiscount,
                        defaultPeriod: defaultPeriod,
                        roundResult: roundResult,
                        useLinks: useLinks,
                    },
                },

                function(response) {
                    admin.indication(response.status, response.msg);
                    if (response.status) {
                        admin.closeModal($('.modal-settings'));
                    }
                });
        },
    }
})();

// инициализациямодуля при подключении
desires.init();