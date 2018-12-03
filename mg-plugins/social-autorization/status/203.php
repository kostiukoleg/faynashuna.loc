<div class="social-registration-out-block">
  <h1>Активация комбинированного режима</h1>
  <p class="informer">*Если вы уже совершали вход через другие сервисы авторизации, укажите секретный ключ выданный при авторизации</p>
  <form action="/sociallogin" method="post" class="combined-form">
    <p class="login-error">Неправильный логин или пароль</p>
    <ul class="form-list">
      <li>
        Email:
        <span class="red-star">*</span>
      </li>
      <li>
        <input type="text" class="email" name="email" size="29" maxlength="29" value="<?php echo $data['email']; ?>">
      </li>
      <li>
        Пароль:
        <span class="red-star">*</span>
      </li>
      <li>
        <input type="password" class="password" size="29" maxlength="254" name="password" value = ""/>
      </li>
    </ul>
    <button type="submit" class="default-btn" name="user-info" value="<?php echo $data['user-info']; ?>">Ок</button>
  </form>
</div>