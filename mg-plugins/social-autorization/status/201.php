<div class="social-registration-out-block">
  <h1>Возможно вы уже зарегистрированы у нас ?</h1>
  <p class="informer">*Если вы уже совершали вход через другие сервисы авторизации, введите указанный вами email</p>
  <form action="/sociallogin" method="post" class="combined-form">
    <ul class="form-list">
      <li>
        Email:
        <span class="red-star">*</span>
      </li>
      <li>
        <input type="text" class="email" name="email" maxlength="29" value="<?php echo $data['email']; ?>">
      </li>
      <li>
        Пароль:
        <span class="red-star">*</span>
      </li>
      <li>
        <input type="password" class="password" maxlength="254" name="password" value = ""/>
      </li>
    </ul>
    <button type="submit" class="default-btn" name="user-info" value="<?php echo $data['user-info']; ?>">Ок</button>
  </form>
</div>