<?php
  // Получаем данные для пользователя
  $res = DB::query('SELECT * FROM `'.PREFIX.'user` WHERE `email` = '.DB::quote($data['email']));
  if ($row = DB::fetchObject($res))
  {
    $_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
    $_SESSION['user'] = $row;
  
    $redirect = $_SESSION['social_login_auth_last_page'];
    $type = MG::getOption('socialLoginRedirect');
    unset($_SESSION['social_login_auth_last_page']);
  
    // Перенаправляем пользователя
    switch($type)
    {
      case 'personal':
        MG::redirect('/personal');
        break;
      case 'catalog':
        MG::redirect('/catalog');
        break;
      case 'main':
        MG::redirect('/');
        break;
      case 'cart':
        MG::redirect('/cart');
        break;        
      case 'old':
        header('Location: '.$redirect);
        exit;
        break;      
      default:
        MG::redirect('/');
        break;
    }
  }
  // Произошла техническая ошибка
  echo '<div class="social-registration-out-block">
          <h1>Уупс ! Ошибка !</h1>
        </div>';