<?php
/**
 *  Файл представления Enter - выводит сгенерированную движком информацию на странице сайта авторизации пользователей.
 *  В этом файле доступны следующие данные:
 *   <code>
 *    $data['msgError'] => Сообщение об ошибке авторизации,
 *    $data['meta_title'] => 'Значение meta тега для страницы '
 *    $data['meta_keywords'] => 'Значение meta_keywords тега для страницы '
 *    $data['meta_desc'] => 'Значение meta_desc тега для страницы '
 *   </code>
 *
 *   Получить подробную информацию о каждом элементе массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php viewData($data['msgError']); ?>
 *   </code>
 *
 *   Вывести содержание элементов массива $data, можно вставив следующую строку кода в верстку файла.
 *   <code>
 *    <?php echo $data['msgError']; ?>
 *   </code>
 *
 *   <b>Внимание!</b> Файл предназначен только для форматированного вывода данных на страницу магазина. Категорически не рекомендуется выполнять в нем запросы к БД сайта или реализовывать сложную программную логику логику.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Views
 */
// Установка значений в метатеги title, keywords, description.
mgSEO($data);
?>

<?php if (class_exists('BreadCrumbs')): ?>[brcr]<?php endif; ?>

<h1 class="new-products-title"><?php echo lang('enterTitle'); ?></h1>

<?php echo !empty($data['msgError']) ? '<div class="l-col min-0--12"><div class="c-alert c-alert--red">'.$data['msgError']. '</div></div>' : '' ?>
<div class="user-login box">
    <div class="box-header">Зарегистрированный пользователь</div>
    <div class="box-body">
    <p class="custom-text">Если Вы уже зарегистрированы у нас в интернет-магазине, пожалуйста авторизуйтесь.</p>
        <form class="c-form c-form--width" action="<?php echo SITE ?>/enter" method="POST">
            <ul class="form-list">
            <li>
                <span>Email:<i class="red-star">*</i></span>
                <input type="text" name="email" placeholder="Email" value="<?php echo !empty($_POST['email']) ? $_POST['email'] : '' ?>" required>
            </li>
            <li>
                <span>Пароль:<i class="red-star">*</i></span>
                <input type="password" name="pass" placeholder="<?php echo lang('enterPass'); ?>" required>
            </li>

            <?php echo !empty($data['checkCapcha']) ? $data['checkCapcha'] : '' ?>
            <?php if (!empty($_REQUEST['location'])) : ?>
                <input type="hidden" name="location" value="<?php echo $_REQUEST['location']; ?>"/>
            <?php endif; ?>
            </ul>
            <div class="clearfix">
                <button type="submit" class="enter-btn default-btn"><?php echo lang('enterEnter'); ?></button>
                <a class="forgot-link" href="<?php echo SITE ?>/forgotpass"><?php echo lang('enterForgot'); ?></a>
            </div>
            <div class="text-center">
                <a class="register-link" href="<?php echo SITE ?>/registration"><?php echo lang('enterRegister'); ?></a>
            </div>
        </form>
    </div>
</div>