<?php
/**
 *  Файл представления Registration - выводит сгенерированную движком информацию на странице регистрации нового пользователя.
 *  В этом файле доступны следующие данные:
 *   <code>
 *    $data['error'] => Сообщение об ошибке.
 *    $data['message'] => Информационное сообщение.
 *    $data['form'] =>  Отображение формы,
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
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */
// Установка значений в метатеги title, keywords, description

mgSEO($data);
?>
<script src="<?php echo SITE ?>/mg-core/script/jquery.maskedinput.min.js"></script>

<?php if ($data['form']){?>
        <h1 class="new-products-title"><?php echo lang('registrationTitle'); ?></h1>
        
        <?php if ($data['message']): ?>
            <span class="mg-success">
                <span class="succes-reg"><?php echo $data['message'] ?></span>
            </span>
        <?php endif; ?>

        <?php if ($data['error']): ?>
            <span class="msgError">
                <span class="msgError"><?php echo $data['error'] ?></span>
            </span>
        <?php endif; ?>

        <div class="create-user-account-form box">
            <div class="box-header">Новый пользователь</div>
            <div class="box-body">
                <p class="custom-text">Заполните форму ниже, чтобы получить дополнительные возможности в нашем интерент-магазине.</p>
                <form class="c-form c-form--width" action="<?php echo SITE ?>/registration" method="POST">
                    <ul class="form-list">
                        <li>
                            <span><?php echo lang('email'); ?>:<i class="red-star">*</i></span>
                            <input type="text" name="email" placeholder="<?php echo lang('email'); ?>" value="<?php echo $_POST['email'] ?>" required>
                        </li>
                        <li>
                            <span><?php echo lang('enterPass'); ?>:<i class="red-star">*</i></span>
                            <input type="password" placeholder="<?php echo lang('enterPass'); ?>" name="pass" required>
                        </li>
                        <li>
                            <span><?php echo lang('fname'); ?>:<i class="red-star">*</i></span>
                            <input type="text" placeholder="<?php echo lang('fname'); ?>" name="name" value="<?php echo $_POST['name'] ?>" required>
                        </li>
                        <li>
                            <input type="hidden" placeholder="<?php echo lang('fname'); ?>" name="ip" value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" required>
                        </li>
                        <?php if (MG::getSetting('useCaptcha') == "true" && MG::getSetting('useReCaptcha') != 'true'):?>
                            <li>
                                <b><?php echo lang('captcha'); ?></b>
                            </li>
                            <li>
                                <img style="background: url('<?php echo PATH_TEMPLATE ?>/images/cap.png');" src="captcha.html" width="140" height="36">
                            </li>
                            <li>
                                <input type="text" name="capcha" class="captcha" required>
                            </li>
                        <?php endif; ?>
                        <?php echo MG::printReCaptcha(); ?>
                    </ul>
                    <div class="clearfix">
                        <button type="submit" class="register-btn default-btn" name="registration"><?php echo lang('registrationButton'); ?></button>
                    </div>
                </form>
            </div>
        </div>

    <?php } else { ?>
        <?php if ($data['message']): ?>
            <span class="mg-success">
                <span class="succes-reg"><?php echo $data['message'] ?></span>
            </span>
        <?php endif; ?>
 <?php } ?>
