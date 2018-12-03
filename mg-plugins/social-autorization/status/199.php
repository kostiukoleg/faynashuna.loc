<div class="social-registration-out-block">
  <h1>Вы успешно авторизовались !</h1>
  <p class="informer">Ваш пароль: <?php echo $data['secret']; ?></p>
  <p class="faq">Запомните пароль и не показывайте его никому</p>
  <form action="/sociallogin" method="post" class="real-form">
    <button type="submit" class="default-btn" name="auth_success" value="<?php echo $data['user-info']; ?>">Ок</button>
  </form>                
</div>