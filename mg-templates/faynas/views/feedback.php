<?php
/**
 *  Файл представления Feedback - выводит сгенерированную движком информацию на странице обратной связи.
 *  В этом файле доступны следующие данные:
 *   <code>
 *    $data['message'] => Сообщение,
 *    $data['dislpayForm'] => Флаг скрывающий форму,
 *    $data['meta_title'] => 'Значение meta тега для страницы '
 *    $data['meta_keywords'] => 'Значение meta_keywords тега для страницы '
 *    $data['meta_desc'] => 'Значение meta_desc тега для страницы '
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['message']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['message']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */

// Установка значений в метатеги title, keywords, description.
mgSEO($data);
?>

        <div class="title-row clearfix">
            <h1 class="new-products-title fl-left"><?php echo lang('feedbackTitle'); ?></h1>
            <a href="<?php echo SITE?>/catalog" class="go-back fl-right">Вернуться к покупкам</a>
        </div>

        <?php if (!empty($data['error'])): ?>
            <div class="msgError">
                <?php echo $data['error']; ?>
            </div>
        <?php endif; ?>

        <?php if ($data['dislpayForm']) { ?>
            <?php if (!empty($data['html_content']) && $data['html_content'] != '&nbsp;'):?>
                <div class="pade-desc">
                    <?php echo $data['html_content'] ?>
                </div>
            <?php endif; ?>

            <div class="feedback-form-wrapper box">
                <img src="http://dress2.template.moguta.ru/mg-templates/mg-boutique/images/woman.png" alt="" class="woman-img">
                <div class="box-header"><?php echo lang('feedbackTitle'); ?></div>
                <div class="box-body">
                    <form class="c-form c-form--width" action="" method="post" name="feedback">
                        <ul class="form-list">
                        <li>
                            <input type="text" name="fio" placeholder="<?php echo lang('fio'); ?>" value="<?php echo !empty($_POST['fio']) ? $_POST['fio'] : '' ?>">
                        </li>
                        <li>
                            <input type="text" name="email" placeholder="Email" value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>">
                        </li>
                        <li>
                            <textarea class="address-area" placeholder="<?php echo lang('feedbackMessage'); ?>" name="message"><?php echo !empty($_REQUEST['message']) ? $_REQUEST['message'] : '' ?></textarea>
                        </li>
                        <?php if (MG::getSetting('useCaptcha') == "true" && MG::getSetting('useReCaptcha') != 'true'): ?>
                            <div class="c-form__row">
                                <b><?php echo lang('captcha'); ?></b>
                            </div>
                            <div class="c-form__row">
                                <img src="captcha.html" width="140" height="36">
                            </div>
                            <div class="c-form__row">
                                <input type="text" name="capcha" class="captcha">
                            </div>
                        <?php endif; ?>
                        <?php echo MG::printReCaptcha(); ?>
                        </ul>
                        <input type="submit" name="send" class="default-btn" value="<?php echo lang('send'); ?>">
                    </form>
                </div>
            </div>

            <?php mgFormValid('feedback', 'feedback'); ?>

        <?php } else { ?>

            <div class="successSend">
                <?php echo $data['message'] ?>
            </div>

        <?php }; ?>