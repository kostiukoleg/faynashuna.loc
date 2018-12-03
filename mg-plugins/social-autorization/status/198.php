<div class="social-registration-out-block">
  <h1>Готово ! Вам осталось подтвердить ваш email!</h1>
  <p class="informer">Для подтверждения email Вам необходимо перейти по ссылке высланной на Ваш электронный адрес <?php echo isset($data['bad_form']) ? '' : $data['email']; ?></p>
  <p class="informer">*Если вы ввели свой email не правильно, введите правильный email в форму ниже</p>
  <form action="/sociallogin" method="post" class="activate-form">
    <input type="hidden" name="activateSecret" value="<?php echo $data['user-info']; ?>">
    <?php
      if(isset($data['bad_form']))
      {
        echo '<p class="error">Введена не правильная форма email</p>';
        echo '<input type="text" name="activateEmail" value="'.$data['bad_form'].'">';        
      }
      else
        echo '<input type="text" name="activateEmail" value="">';
    ?>
    <button type="submit" class="default-btn" name="reActivate" value="ok">Ок</button>
  </form>
</div>