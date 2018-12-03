<div class="social-registration-out-block">
  <h1>Вы успешно авторизовались !</h1>
  <?php
      if ($data['auth_first'])
        echo '<p class="informer">Ваш пароль: '.$data['secret'].'</p>
              <p class="faq">Запомните пароль и не показывайте его никому</p>';
  ?>
  <span class="Info">Для завершения авторизации, пожалуйста, укажите:</span>
  <form action="/sociallogin" method="post" class="real-form">
    <ul class="form-list">
      <li>
        Email:
        <span class="red-star">*</span>
      </li>
      <li>
        <input type="text" class="input-real-email" name="real-email" maxlength="29" value="">
        <button type="submit" class="default-btn" name="user-info" value="<?php echo $data['user-info']; ?>">Ок</button>
      </li>
    </ul>
  </form>
  <p class="informer">*Если вы уже зарегистрированны у нас на сайте, вы можете активировать комбинированный режим</p>
  <p class="faq">Комбинированный режим позволяет выполнить вход из соц сетей в личный кабинет, который был ранее зарегистрирован на сайте</p>
</div>