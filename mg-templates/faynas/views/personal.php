<?php
/**
 *  Файл представления Personal - выводит сгенерированную движком информацию на странице личного кабинета.
 *  В этом файле доступны следующие данные:
 *   <code>
 *     $data['error'] => Сообщение об ошибке.
 *     $data['message'] =>  Информационное сообщение.
 *     $data['status'] => Статус пользователя.
 *     $data['userInfo'] => Информация о пользователе.
 *     $data['orderInfo'] => Информация о заказе.
 *     $data['currency'] => $settings['currency'],
 *     $data['paymentList'] => $paymentList,
 *     $data['meta_title'] => Значение meta тега для страницы,
 *     $data['meta_keywords'] => Значение meta_keywords тега для страницы,
 *     $data['meta_desc'] => Значение meta_desc тега для страницы
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['userInfo']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['message']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику логику.
 *   @author Авдеев Марк <mark-avdeev@mail.ru>
 *   @package moguta.cms
 *   @subpackage Views
 */
// Установка значений в метатеги title, keywords, description.
mgSEO($data);
?>

<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/datepicker.css" rel="stylesheet"/>'); ?>
<?php mgAddMeta('<script src="'.SCRIPT.'jquery.maskedinput.min.js"></script>'); ?>

<div class="person-page">
    <?php switch($data['status']){ case 1: ?>

        <div class="msgError"><?php echo lang('personalBlocked'); ?></div>

        <?php break; case 2: ?>

        <div class="msgError"><?php echo lang('personalNotActivated'); ?></div>

        <form class="c-form" action="<?php echo SITE ?>/registration" method="POST">
            <div class="c-form__row">
                <input type="text" name="activateEmail" placeholder="Email" required>
            </div>
            <div class="c-form__row">
                <input type="submit" class="c-button" name="reActivate" value="<?php echo lang('send'); ?>">
            </div>
        </form>

        <?php break; case 3: $userInfo = $data['userInfo'] ?>

        <h1 class="new-products-title"><?php echo lang('personalAccount'); ?> "<?php echo $userInfo->name ?>"</h1>
        <p class="custom-text">В своем кабинете Вы сможете следить за статусами Ваших заказов, так же изменять свои личные данные.</p>
        <?php if($data['message']): ?>
            <div class="mg-success"><?php echo $data['message'] ?></div>
        <?php endif; ?>

        <?php if($data['error']): ?>
            <div class="mg-error"><?php echo $data['error'] ?></div>
        <?php endif; ?>

            <div id="tabs" class="personal-tabs">
                <ul class="personal-tabs-list">    
                    <li><a href="#orders-history"><?php echo lang('personalTab3'); ?></a></li>
                    <li><a href="#personal"><?php echo lang('personalTab1'); ?></a></li>
                    <li><a href="#change-pass"><?php echo lang('personalTab2'); ?></a></li>
                    <li><a href="<?php echo SITE?>/enter?logout=1"><?php echo lang('personalTab4'); ?></a></li>            
                </ul>
                
                <div class="personal-tabs-content">
                    <!-- c-tab__data - start -->
                    <div id="personal">
                        <p class="change-pass-title">Личные данные</p>
                        <form class="" action="<?php echo SITE ?>/personal" method="POST">                               
                            <ul class="form-list">
                                <li><span>Email: <i class="normal-text"><?php echo $userInfo->email ?></i></span></li>
                                <li><span><?php echo lang('personalRegisterDate'); ?> <i><?php echo date('d.m.Y', strtotime($userInfo->date_add)) ?></i></span></li>
                                <li><span><?php echo lang('fname'); ?>:</span><input type="text" name="name" value="<?php echo $userInfo->name ?>" placeholder="<?php echo lang('fname'); ?>"></li>
                                <li><span><?php echo lang('lname'); ?>:</span><input type="text" name="sname" value="<?php echo $userInfo->sname ?>" placeholder="<?php echo lang('lname'); ?>"></li>
                                <li><span><?php echo lang('personalBirthday'); ?>:</span><input class="birthday" type="text" name="birthday" value="<?php echo $userInfo->birthday?date('d.m.Y', strtotime($userInfo->birthday)):'' ?>" placeholder="<?php echo lang('personalBirthday'); ?>"></li>
                                <li><span><?php echo lang('phone'); ?>:</span><input type="text" name="phone" value="<?php echo $userInfo->phone ?>" placeholder="<?php echo lang('phone'); ?>"></li>
                                <li><span><?php echo lang('orderPhAdres'); ?>:</span><textarea class="address-area" name="address" placeholder="<?php echo lang('orderPhAdres'); ?>"><?php echo $userInfo->address ?></textarea></li>
                                <li>
                                    <select name="customer">
                                        <?php $selected = $userInfo->inn?'selected':''; ?>
                                        <option value="fiz"><?php echo lang('orderFiz'); ?></option>
                                        <option value="yur" <?php echo $selected ?>><?php echo lang('orderYur'); ?></option>
                                    </select>
                                </li>
                            </ul>
                            <?php if(!$userInfo->inn){$style = 'style="display:none"'; } ?>
                            <ul class="form-list yur-field" <?php echo $style ?>>
                                <li><span><?php echo lang('orderPhNameyur'); ?></span><input type="text" name="nameyur" value="<?php echo $userInfo->nameyur ?>" placeholder="<?php echo lang('orderPhNameyur'); ?>"></li>
                                <li><span><?php echo lang('orderPhAdress'); ?></span><input type="text" name="adress" value="<?php echo $userInfo->adress ?>" placeholder="<?php echo lang('orderPhAdress'); ?>"></li>
                                <li><span><?php echo lang('orderPhInn'); ?></span> <input type="text" name="inn" value="<?php echo $userInfo->inn ?>" placeholder="<?php echo lang('orderPhInn'); ?>"></li>
                                <li><span><?php echo lang('orderPhKpp'); ?></span><input type="text" name="kpp" value="<?php echo $userInfo->kpp ?>" placeholder="<?php echo lang('orderPhKpp'); ?>"></li>
                                <li><span><?php echo lang('orderPhBank'); ?></span><input type="text" name="bank" value="<?php echo $userInfo->bank ?>" placeholder="<?php echo lang('orderPhBank'); ?>"></li>
                                <li><span><?php echo lang('orderPhBik'); ?></span><input type="text" name="bik" value="<?php echo $userInfo->bik ?>" placeholder="<?php echo lang('orderPhBik'); ?>"></li>
                                <li><span><?php echo lang('orderPhKs'); ?></span><input type="text" name="ks" value="<?php echo $userInfo->ks ?>" placeholder="<?php echo lang('orderPhKs'); ?>"></li>
                                <li><span><?php echo lang('orderPhRs'); ?></span><input type="text" name="rs" value="<?php echo $userInfo->rs ?>" placeholder="<?php echo lang('orderPhRs'); ?>"></li>
                            </ul>
                            <div class="clearfix">
                                <button type="submit" class="save-btn default-btn" name="userData" value="save"><?php echo lang('save'); ?></button>
                            </div>
                        </form>
                    </div>
                    <!-- c-tab__data - end -->

                    <!-- c-tab__password - start -->
                    <div id="change-pass">
                        <p class="change-pass-title">Сменить пароль</p>
                        <form class="" action="<?php echo SITE ?>/personal" method="POST">
                            <p class=""><span class="red-star">*</span>Поля отмеченные красной звездочкой, обязательны к заполнению.</p>
                            <ul class="form-list">
                                <li><span><?php echo lang('personalOldPass'); ?></span><input type="password" name="pass" placeholder="<?php echo lang('personalOldPass'); ?>" required></li>
                                <li><span><?php echo lang('forgotPass1'); ?></span><input type="password" name="newPass" placeholder="<?php echo lang('forgotPass1'); ?>" required></li>
                                <li><span><?php echo lang('personalPassRepeat'); ?></span><input type="password" name="pass2" placeholder="<?php echo lang('personalPassRepeat'); ?>" required></li>
                            </ul>
                            <div class="clearfix">
                                <button type="submit" class="save-btn default-btn" name="chengePass" value="save"><?php echo lang('save'); ?></button>
                            </div>
                        </form>
                    </div>
                    <!-- c-tab__password - end -->

                    <!-- c-tab__orders - start -->
                    <div id="orders-history">
                        <?php if($data['orderInfo']): ?>
                        <div class="order-history-list">
                            <p class="change-pass-title">История заказов</p>
                            <?php $currencyShort = MG::getSetting('currencyShort'); $currencyShopIso = MG::getSetting('currencyShopIso'); foreach($data['orderInfo'] as $order): ?>
                            <div class="order-history" id="<?php echo $order['id'] ?>">
                                <div class="order-number">
                                    <strong><?php echo $order['number']!=''?$order['number']:$order['id'] ?></strong> от <?php echo date('d.m.Y', strtotime($order['add_date'])) ?>
                                    <span class="order-status">
                                        <span class="c-history__status <?php echo (empty($data['assocStatusClass'][$order['status_id']]) ? 'customStatus' : $data['assocStatusClass'][$order['status_id']])?>" 
                                        <?php 
                                            echo ' style="';
                                            if (isset($data['orderColors'][$order['status_id']]['bgColor'])) {
                                            echo 'background-color:'.$data['orderColors'][$order['status_id']]['bgColor'].';';
                                            }
                                            if (isset($data['orderColors'][$order['status_id']]['textColor'])) {
                                            echo 'color:'.$data['orderColors'][$order['status_id']]['textColor'].';';
                                            }
                                            echo '"';
                                        ?>
                                        ><?php echo $order['string_status_id'] ?></span>
                                    </span>
                                </div>
                                    <table class="status-table">
                                        <?php 
                                            $perOrder['currency_iso'] = $perOrder['currency_iso']?$perOrder['currency_iso']:$currencyShopIso;
                                            $perCurrencyShort = MG::getSetting('currency');
                                            $perOrders = unserialize(stripslashes($order['order_content']));
                                        ?>
                                        <?php if(!empty($perOrders)) foreach($perOrders as $perOrder): ?>
                                            <?php 
                                            $perCurrencyShort = $currencyShort[$perOrder['currency_iso']]?$currencyShort[$perOrder['currency_iso']]:MG::getSetting('currency');
                                            $coupon = $perOrder['coupon'];
                                            $res = DB::query("SELECT `".PREFIX."product`.id, `".PREFIX."category`.unit
                                                            FROM `".PREFIX."product`
                                                            LEFT JOIN `".PREFIX."category` ON `".PREFIX."product`.cat_id = `".PREFIX."category`.id
                                                            WHERE `".PREFIX."product`.id = ".DB::quoteInt($perOrder['id']));
                                            $row = DB::fetchAssoc($res);
                                            $unit = $row['unit'];
                                            if (strlen($unit) < 1) {
                                                $unit = 'шт.';
                                            }
                                        ?>
                                        <tr>
                                            <th>Товар</th>
                                            <th>Артикул</th>
                                            <th>Цена</th>
                                            <th>Количество</th>
                                            <th>Сумма</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <a class="" href="<?php echo $perOrder['url'] ?>" target="_blank">
                                                    <?php echo $perOrder['name'] ?>
                                                    <?php echo htmlspecialchars_decode(str_replace('&amp;', '&', $perOrder['property'])) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="">
                                                    Код: <?php echo $perOrder['code'] ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="">
                                                    <?php echo MG::numberFormat(($perOrder['price'])).'  '.$perCurrencyShort.'/'.$unit; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="">
                                                    <?php echo $perOrder['count'].' '.$unit ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="">
                                                    <?php echo MG::numberFormat(($perOrder['price']*$perOrder['count'])).'  '.$perCurrencyShort; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </table>
                                <div class="clear"></div>
                                <div class="order-settings">
                                    <form method="POST" action="<?php echo SITE ?>/order">
                                        <input type="hidden" name="orderID" value="<?php echo $order['id'] ?>">
                                        <input type="hidden" name="orderSumm" value="<?php echo $order['summ'] ?>">
                                        <input type="hidden" name="paymentId" value="<?php echo $order['payment_id'] ?>">
                                        <?php if($order['payment_id']!=3): ?>
                                        <button type="submit" class="default-btn" name="pay" value="go"><?php echo lang('orderFinalButton'); ?></button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                <?php if(($order['status_id']==2||$order['status_id']==5)&&MG::getSetting('useElectroLink')!='false'): ?>
                                <div class="c-history__row">
                                    <a class="c-history__download download-link" href="<?php echo SITE.'/order?getFileToOrder='.$order['id'] ?>">
                                        <svg class="icon icon--download"><use xlink:href="#icon--download"></use></svg>
                                        <?php echo lang('orderDownloadDigital'); ?>
                                    </a>
                                </div>
                                <?php endif; ?>

                                <?php $yurInfo = unserialize(stripslashes($order['yur_info'])); if(!empty($yurInfo['inn'])): ?>
                                <div class="c-history__row">
                                    <a class="c-history__download download-link" href="<?php echo SITE.'/order?getOrderPdf='.$order['id'] ?>">
                                        <svg class="icon icon--download"><use xlink:href="#icon--download"></use></svg>
                                        <?php echo lang('orderDownloadPdf'); ?>
                                    </a>
                                </div>
                                <?php endif; ?>

                                <?php if($order['status_id']<2): ?>
                                <div class="order-settings">
                                    <button href="#openModal" class="close-order" id="<?php echo $order['id'] ?>" date="<?php echo date('d.m.Y', strtotime($order['add_date'])) ?>" data-number="<?php echo $order['number']!=''?$order['number']:$order['id']; ?>">
                                        <?php echo lang('orderCancel'); ?>
                                    </button>
                                    <?php $urInfo = unserialize(stripcslashes($order['yur_info'])); if(empty($urInfo['inn'])) { ?>
                                    <button href="#changePayment" class="change-payment" id="<?php echo $order['id'] ?>" date="<?php echo date('d.m.Y', strtotime($order['add_date'])) ?>" data-number="<?php echo $order['number']!=''?$order['number']:$order['id']; ?>">
                                        <?php echo lang('orderChangePayment'); ?>
                                    </button>
                                    <?php } ?>
                                </div>
                                <?php endif; ?>

                                <?php if(!empty($order['comment'])): ?>
                                    <div class="alert-info">
                                        <?php echo $order['comment']; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="order-total">
                                    <ul class="total-list">
                                        <?php if($coupon): ?>
                                        <li>
                                            <?php echo lang('orderFinalCoupon'); ?> <span title="<?php echo $coupon ?>"><?php echo MG::textMore($coupon, 20) ?></span>
                                        </li>
                                        <?php endif; ?>

                                        <li>
                                            <?php echo lang('orderFinalTotal'); ?> <span class="total-summ"><?php echo MG::numberFormat($order['summ']).'  '.$perCurrencyShort ?></span>
                                        </li>

                                        <?php if($order['description']): ?>
                                        <li>
                                            <?php echo lang('orderFinalDeliv'); ?> <span><?php echo $order['description'] ?></span>
                                        </li>
                                        <?php endif; ?>

                                        <?php if($order['date_delivery']): ?>
                                        <li>
                                            <?php echo lang('orderFinalDelivDate'); ?> <span><?php echo date('d.m.Y', strtotime($order['date_delivery'])) ?></span>
                                        </li>
                                        <?php endif; ?>

                                        <li>
                                            <?php echo lang('orderFinalPayment'); ?> <span class="paymen-name-to-history"><?php echo $order['name'] ?></span>
                                        </li>

                                        <?php $totSumm = $order['summ']+$order['delivery_cost']; ?>
                                        <?php if($order['delivery_cost']): ?>
                                            <li>
                                                <?php echo lang('orderFinalDeliv'); ?> <span class="delivery-price"><?php echo MG::numberFormat($order['delivery_cost']).'  '.$perCurrencyShort; ?></span>
                                            </li>
                                        <?php endif; ?>

                                        <li>
                                            <?php echo lang('orderFinalPay'); ?> <span class="total-order-summ"><?php echo MG::numberFormat($totSumm).'  '.$perCurrencyShort; ?></span>
                                        </li>

                                        <?php if(2>$order['status_id']): ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="clear"></div>
                                <div class="clear">&nbsp;</div>
                            </div> <!-- order-history -->
                            <?php endforeach; ?>

                            <!-- change payment - start -->

                            <div class="change-payment">
                                <div class="close-reason-wrapper" id="changePayment">
                                    <p class="order-number">
                                        <?php echo lang('personalOrderFrom1'); ?> <strong name="orderId" class="orderId"></strong> <?php echo lang('personalOrderFrom2'); ?> <span class="orderDate"></span>
                                    </p>
                                    <select class="order-changer-pay">
                                        <?php
                                            foreach($data['paymentList'] as $item){ if(empty($item)){ continue; }
                                            $delivery = json_decode($item['deliveryMethod']);
                                            if($delivery->{$order['delivery_id']}){ echo "<option value='".$item['id']."'>".$item['name'].'</option>'; }}
                                        ?>
                                    </select>
                                    <button type="submit" class="change-payment-btn default-btn" ><?php echo lang('apply'); ?></button>
                                </div>
                            </div>

                            <!-- change payment - end -->

                            <!-- reason - start -->
                            <div class="close-reason">
                                <div class="successful-closure" id="successModal">
                                    <div class="close-reason-wrapper" id="openModal">
                                        <p class="order-number" style="display: none;"><?php echo lang('personalOrderClose2'); ?><strong name="orderId" class="orderId"></strong> <?php echo lang('personalOrderClose3'); ?> <span class="orderDate"></span></p>
                                        <p class="custom-text">Укажите причину закрытия заказа:</p>
                                        <textarea class="reason-text" name="comment_textarea" placeholder="<?php echo lang('personalOrderClose1'); ?>"></textarea>
                                        <button type="submit" class="close-order-btn default-btn"><?php echo lang('send'); ?></button>
                                        <a class="close-order" href="#successModal" name="next"></a>
                                        <a class="close-order" href="#errorModal" name="error"></a>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <div class="successful-closure" id="successModal">
                                    <?php echo lang('personalOrderClose4'); ?>
                                </div>
                                <div class="successful-closure" id="errorModal">Ошибка</div>
                            </div>
                            <!-- reason - end -->
                        </div> <!-- order-history-list -->

                        <?php else: ?>
                            <div class="alert-info msgError"><?php echo lang('personalNoOrders'); ?></div>
                        <?php endif ?>

                        <div class="c-pagination">
                            <?php echo $data['pagination'];?>
                        </div>
                    </div>
                    <!-- c-tab__orders - end -->
                </div>
                <!-- personal-tabs-content - end -->
            </div>
            <!-- ui-widget-content - end -->
        <?php break; default : ?>

        <div class="msgError"><?php echo lang('personalNotAuthorised'); ?></div>

    <?php } ?>
</div> <!-- END person-page -->


<script>
    $(document).ready(function() {


        // показать содержимое заказа
        // ------------------------------------------------------------
        $('body').on('click', '.c-history__header', function() {
            $(this).parent().toggleClass('c-history__item--active');
        });


        // выбор физ./юр. лицо
        // ------------------------------------------------------------
        $('select[name="customer"]').change(function() {
            if($(this).val() == 'fiz'){
                $('.yur-field').hide();
            }
            if($(this).val() == 'yur'){
                $('.yur-field').show();
            }
        });


        // показать форму закрытия заказов
        // ------------------------------------------------------------
        $('.close-order, .change-payment').click(function() {
            var a = $(this).parents('.order-history').find('.paymen-name-to-history').text();

            $('select.order-changer-pay option:contains("'+a+'")').prop('selected', true);
            $('.reason-text').val('');
            $('strong.orderId').attr('data-id-order', $(this).attr('id'));
            $('strong[class=orderId]').text($(this).attr('data-number'));
            $('span[class=orderDate]').text($(this).attr('date'));
        });


        // закрытие заказа из личного кабинета
        // ------------------------------------------------------------
        $('.close-order-btn').click(function() {
            var id = $(this).parents('#openModal').find('strong[name=orderId]').data('id-order');
            var comm = $('.reason-text').val();

            $.ajax({
                type: "POST",
                url: mgBaseDir + "/personal",
                data: {
                    delOK: "OK",
                    delID: id,
                    comment: comm
                },
                cache: false,
                dataType: 'json',
                success: function(response){
                    if(response.status) {
                        $('#openModal').hide();
                        $('#successModal').show();
                        $('.order-history#' + id + ' .order-number .order-status span').text(response.orderStatus);
                        $('.order-history#' + id + ' .order-number .order-status span').attr('class', 'dont-paid').addClass('c-history__status');
                        $('.order-history#' + id + ' .order-settings').remove();
                    }
                }
            });
        });


        // смена способа оплаты
        // ------------------------------------------------------------
        $('body').on('click', '.change-payment-btn', function() {
            var paymetId = $(this).parents('#changePayment').find('.order-changer-pay').val();
            var paymetName = $(this).parents('#changePayment').find('.order-changer-pay option:selected').text();
            var id = $(this).parents('#changePayment').find('strong.orderId').attr('data-id-order');

            $('.order-history#' + id).find('input[name=paymentId]').val(paymetId);
            $('.order-history#' + id).find('.paymen-name-to-history').text(paymetName);

            $.ajax({
                type: "POST",
                url: mgBaseDir + "/personal",
                data: {
                    changePaymentId: paymetId,
                    orderId: id,
                },
                cache: false,
                dataType: 'json',
                success: function(response){
                    location.reload();
                    var deliveryPrice = $('.order-history#' + id).find('.delivery-price').text();
                    deliveryPrice.replace(' <?php echo addslashes($perCurrencyShort);?>');
                    $('.order-history#' + id).find('.total-summ').text(response.summ+' <?php echo addslashes($perCurrencyShort);?>');
                    var orderSumm = response.summ;
                    if(deliveryPrice) {
                        orderSumm += parseFloat(deliveryPrice);
                    }
                    $('.order-history#' + id).find('.total-order-summ').text(orderSumm+' <?php echo addslashes($perCurrencyShort);?>');
                    $('.c-modal').removeClass('c-modal--open');
                    $('html').removeClass('c-modal--scroll');
                }
            });
        });


        // дата рождения
        // ------------------------------------------------------------
        $('.birthday').datepicker({
            dateFormat: "dd.mm.yy",
            changeMonth: true,
            changeYear: true,
            yearRange: '-90:+0'
        });

        $.datepicker.regional['ru'] = {
            closeText: 'Закрыть',
            prevText: '&#x3c;Пред',
            nextText: 'След&#x3e;',
            currentText: 'Сегодня',
            monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
            ],
            monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
                'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'
            ],
            dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
            dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
            dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
            dateFormat: 'dd.mm.yy',
            firstDay: 1,
            isRTL: false
        };
        $.datepicker.setDefaults($.datepicker.regional['ru']);


    }); // end ready
</script>