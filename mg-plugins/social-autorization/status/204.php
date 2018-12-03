<div class="social-registration-out-block">
  <h1>Вы успешно авторизовались !</h1>
  <form action="/sociallogin" method="post" class="real-form">
    <span class="Info">Для завершения авторизации введите, пожалуйста, ваш рабочий email:</span>
    <p class="login-error">Вы ввели не правильную форму Email</p>
    <input type="text" class="input-real-email" size="29" maxlength="29" name="real-email" value = ""/>
    <button type="submit" class="default-btn" name="user-info" value="<?php echo $data['user-info']; ?>">Ок</button>
  </form>
  <p class="informer">*Если вы уже зарегистрированны у нас на сайте, вы можете активировать комбинированный режим</p>
  <p class="faq">Комбинированный режим позволяет выполнить вход из соц сетей в личный кабинет, который был ранее зарегистрирован у на сайте</p>
</div>