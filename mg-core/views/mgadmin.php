<!DOCTYPE html>
<html class="mg-admin-html<?php if(!USER::isAuth() && (USER::access('admin_zone') == 0)): ?> auth-page<?php endif;?>">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" >
<!-- <link href="<?php echo SITE?>/mg-admin/design/css/reset.css" rel="stylesheet" type="text/css"> -->
<link href="<?php echo SITE?>/mg-admin/design/css/tipTip.css" rel="stylesheet" type="text/css">
<link href="<?php echo SITE?>/mg-admin/design/css/datepicker.css" rel="stylesheet" type="text/css">
<link href="<?php echo SITE?>/mg-admin/design/css/toggles.css" rel="stylesheet" type="text/css">
<!--  -->
<!-- <link href="<?php echo SITE?>/mg-admin/design/css/style-old.css" rel="stylesheet" type="text/css"> -->
<!--  -->
<link href="<?php echo SITE?>/mg-admin/design/css/vendors.min.css" rel="stylesheet">
<link href="<?php echo SITE?>/mg-admin/design/css/style.css?<?php echo filemtime(ADMIN_DIR.'/design/css/style.css') ?>" rel="stylesheet" type="text/css">

<?php
  if(unserialize(stripslashes(getOption('interface')))) {
    include_once ADMIN_DIR.'/design/css/user.css.php';
  } 
?>

<link rel="stylesheet" href="<?php echo SITE?>/mg-core/script/codemirror/lib/codemirror.css">
<link type="text/css" href="<?php echo SITE?>/mg-core/script/codemirror/addon/search/matchesonscrollbar.css" rel="stylesheet"/> 
<link type="text/css" href="<?php echo SITE?>/mg-core/script/codemirror/addon/dialog/dialog.css" rel="stylesheet"/>
<link type="text/css" href="<?php echo SITE?>/mg-core/script/codemirror/addon/scroll/simplescrollbars.css" rel="stylesheet"/>

<!--[if lte IE 9]>
    <link href="<?php echo SITE?>/mg-admin/design/css/ie.css" rel="stylesheet" type="text/css">
    <script src="<?php echo SITE?>/mg-core/script/css3-mediaqueries.js"></script>
<![endif]-->
<title>Панель управления | Moguta.CMS</title>

<?php 

if(USER::isAuth() && (USER::access('admin_zone') == 1)): ?>
<?php MG::resetAdminCurrency(); ?>

 <?php MG::titlePage($lang['ADMIN_BAR']);?>

    <script>var phoneMask =  "<?php echo MG::getSetting('phoneMask');?>" </script>
    <script>var SITE = "<?php echo SITE; ?>";</script>
    <!-- <script src="<?php echo SITE?>/mg-core/script/jquery-1.10.2.min.js"></script> -->
    <script src="<?php echo SITE?>/mg-core/script/jquery-3.2.1.min.js"></script>
    <!-- <script src="<?php echo SITE?>/mg-core/script/jquery-migrate-3.0.0.js"></script> -->
    <!-- <script src="<?php echo SITE?>/mg-core/script/jquery-ui-1.10.3.custom.min.js"></script> -->
    <script src="<?php echo SITE?>/mg-core/script/jquery-ui.min.js"></script>

    <script src="<?php echo SITE?>/mg-core/script/vendors.min.js"></script>
    <script src="<?php echo SITE?>/mg-core/script/frontend.min.js"></script>

    <!-- <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"></script> -->
    <script src="<?php echo SITE?>/mg-core/script/jquery-ui.min.js"></script>
    <script src="<?php echo SITE?>/mg-core/script/admin/admin.js?protocol=<?php echo PROTOCOL; ?>&amp;mgBaseDir=<?php echo SITE; ?>&amp;currency=<?php echo MG::getSetting('currency'); ?>&amp;lang=<?php echo MG::getSetting('languageLocale');?>"></script>
    
</head>
 <?php
   $oldIe = false;
   if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')||strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')||strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')){
     $oldIe = true;
   };

  if ($data['themeBackground'] == 'customBackground') {
    $style = "zoom: 1; background-image: url('".SITE."/uploads/customAdmin/".MG::getOption('customBackground')."');";
  }
  else{
    $style = "zoom: 1; background-image: url('".SITE."/mg-admin/design/images/bg_textures/".$data['themeBackground'].".png');";
  }

  if (MG::getOption('bgfullscreen') == 'true') {
    $style .= " background-position-x: center; 
    background-position-y: center; 
    background-repeat-x: no-repeat; 
    background-repeat-y: no-repeat; 
    background-attachment: fixed; 
    -moz-background-size: cover; 
    -webkit-background-size: cover; 
    -o-background-size: cover; 
    background-size: cover;"; 
  }

 ?>

<body class="mg-admin-body <?php if($oldIe): ?>old-ie<?php endif;?>" style="<?php echo $style; ?>">
    <?php 
    if($oldIe): ?>
        <div class="old-browser">
            <h1>ВНИМАНИЕ! Вы используете устаревший браузер Internet Explorer</h1>
            <p>Панель управления <b>MOGUTA.CMS</b> построена на передовых, современных технологиях и не поддерживает устаревшие браузеры Internet Explorer!.

                Настоятельно Вам рекомендуем выбрать и установить любой из современных браузеров. Это бесплатно и займет всего несколько минут.</p>
            <table class="brows">
                <tbody>
                    <tr>
                      <td width='120'></td>
                      <td><a href="http://www.google.com/chrome"><img src="<?php echo SITE?>/mg-admin/design/images/browsers/gc.jpg" alt="Google Chrome"></a></td>
                        <td><a href="http://www.mozilla.com/firefox/"><img src="<?php echo SITE?>/mg-admin/design/images/browsers/mf.jpg" alt="Mozilla Firefox"></a></td>
                        <td><a href="http://www.opera.com/download/"><img src="<?php echo SITE?>/mg-admin/design/images/browsers/op.jpg" alt="Opera Browser"></a></td>
                        <td><a href="http://www.apple.com/safari/download/"><img src="<?php echo SITE?>/mg-admin/design/images/browsers/as.jpg" alt="Apple Safari"></a></td>
                    </tr>
                    <tr class="brows_name">
                        <td></td>
                        <td><a href="http://www.google.com/chrome">Google Chrome</a></td>
                        <td><a href="http://www.opera.com/download/">Opera Browser</a></td>
                        <td><a href="http://www.mozilla.com/firefox/">Mozilla Firefox</a></td>                    
                        <td><a href="http://www.apple.com/safari/download/">Apple Safari</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </body>
    <?php 
    exit();
    endif;?>
    
    <div class="wrapper no-print">
      
        <!--
        <div class="notice-block top-position" style="height:30px;">     
            <div  class="message_information inform" style="background:#D8D8D8; margin: 0;border-bottom: 2px solid rgb(165, 161, 161);"> Желаете установить Moguta.CMS на свой хостинг? <b><a style="color:#28BB1D" href="http://moguta.ru">Скачать бесплатно!</a></b></div>
        </div>-->
        <?php 
          if(MG::getSetting('notifInfo') != '') { 
            echo MG::getSetting('notifInfo');
          } else {
            if($newVersion){ ?> 
              <div id ="newVersion" class="message_information inform">
                  <?php echo($lang['NEW_VER'].' - '.$newVersion);?>
              </div>
            <?php }
          }
        ?>

		<?php if($fakeKey){ ?>
        <div style="background-color:#fffce5;text-align:center;padding:5px;" onclick="javascript:void(0);" class="message_information inform">
          <?php echo $fakeKey;?>          
        </div>
		<?php }?>



        <?php 
          $adminLogo = MG::getOption('customAdminLogo');
          if (is_file(URL::getDocumentRoot().'uploads'.DIRECTORY_SEPARATOR.'customAdmin'.DIRECTORY_SEPARATOR.$adminLogo)) {

            $adminLogo = SITE.'/uploads/customAdmin/'.$adminLogo;
            $styleLogo = '';
          }
          else{

            $adminLogo = SITE.'/mg-admin/design/images/logo.svg';
            $styleLogo = 'style="height: 35px; wight:205px;"';
	
          }
        ?>

        <header class="header">
          <div class="header-top info-panel">
            <div class="row">
              <div class="large-12 columns">
                <div class="header-left fl-left clearfix"><a class="logo" target="_blank" href="<?php echo SITE?>/mg-admin/"><img <?php echo $styleLogo ?> src="<?php echo $adminLogo; ?>"><span class="success badge"><?php echo VER ?></span></a>
                  <ul class="buttons-list clearfix">
                    <?php echo $data['informerPanel']; ?>
                  </ul>
                </div>
                <div class="header-right fl-right clearfix">
                  <ul class="buttons-list clearfix">
                    <li><a class="tip-bottom not-use" href="<?php echo SITE?>/" title="<?php echo($lang['BACK_TO_SITE']);?>"><i class="fa fa-external-link" aria-hidden="true"></i></a></li>
                  </ul>
                  <div class="drop-down"><a class="drop-down-btn" href="javascript:void(0);"><i class="fa fa-globe"></i>
                    <?php
                      switch ($data['languageLocale']) {
                        case 'ru_RU':
                          $langList = "Язык";
                          break;
                        case 'en_EN':
                          $langList = "Language";
                          break;
                        case 'ua_UA':
                          $langList = "Мова";
                          break;
                      }
                    ?>
                    <div class="drop-down-text"><?php echo $langList; ?></div></a>
                    <ul class="drop-down-list language-list-wrapper">
                      <li><a class="ru_RU" href="javascript:void(0);"><img src="<?php echo SITE?>/mg-admin/design/images/flag_ru_RU.png" style="height:20px;margin-right:7px;">Русский</a></li>
                      <li><a class="en_EN" href="javascript:void(0);"><img src="<?php echo SITE?>/mg-admin/design/images/flag_en_EN.png" style="height:20px;margin-right:7px;">English</a></li>
                      <li><a class="ua_UA" href="javascript:void(0);"><img src="<?php echo SITE?>/mg-admin/design/images/flag_ua_UA.png" style="height:20px;margin-right:7px;">Украинский</a></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php
            $plugins = '';
            foreach ($pluginsList as $item) {
              if(PM::isHookInReg($item['folderName'])&& $item['Active']){ 
                $plugins .= '<li><a href="javascript:void(0)" class="'.$item['folderName'].'">'.$item['PluginName'].'</a></li>';
              } 
            }
          ?>
          <div class="header-nav">
            <div class="row">
              <div class="large-12 columns">
                <div class="top-menu clearfix">
                  <div class="menu-toggle"><span class="toggle-wrapper"><span class="toggle"></span></span><span class="text">Меню</span></div>
                  <ul class="nav-list main-list">
                    <?php if(USER::access('product') > 0) {?>  <li><a id="catalog" href="javascript:void(0);" title="<?php echo($lang['T_TIP_PROD']);?>"><i class="fa fa-shopping-cart" aria-hidden="true"></i> <?php echo($lang['PRODUCTS']);?></a></li><?php }?>
                    <?php if(USER::access('category') > 0) {?> <li><a id="category" href="javascript:void(0);" title="<?php echo($lang['T_TIP_CAT']);?>"><i class="fa fa-list-ol" aria-hidden="true"></i> <?php echo($lang['CATEGORIES']);?></a></li><?php }?>
                    <?php if(USER::access('page') > 0) {?> <li><a id="page" href="javascript:void(0);" title="<?php echo($lang['T_TIP_PAGE']);?>"><i class="fa fa-file-text-o" aria-hidden="true"></i> <?php echo($lang['PAGES']);?></a></li><?php }?>
                    <?php if(USER::access('order') > 0) {?> <li><a id="orders" href="javascript:void(0);" title="<?php echo($lang['T_TIP_ORDR']);?>"><i class="fa fa-shopping-basket" aria-hidden="true"></i> <?php echo($lang['ORDERS']);?></a>
                    </li><?php }?>
                    <?php if(USER::access('user') > 0) {?><li><a id="users" href="javascript:void(0);" title="<?php echo($lang['T_TIP_USER']);?>"><i class="fa fa-users" aria-hidden="true"></i> <?php echo($lang['USERS']);?></a></li><?php }?>
                    <?php if(USER::access('plugin') > 0) { ?><li class="<?php if($plugins != '') echo 'has-menu'; ?> plugins-list-menu"><a id="plugins" href="javascript:void(0);" title="<?php echo($lang['T_TIP_PLUG']);?>"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> <?php echo($lang['PLUGINS']);?></a>
                      <?php 
                        if(USER::access('plugin') > 1) { 
                          if($plugins != '') {
                            echo '<ul class="sub-list plugins-dropdown-menu">';
                            echo $plugins;
                            echo '</ul>';
                          }
                        }
                      ?>
                    </li><?php } ?>
                    <?php if(USER::access('setting') > 0) {?> <li><a id="settings" href="javascript:void(0);" title="<?php echo($lang['T_TIP_SETT']);?>" class="tool-tip-bottom"><i class="fa fa-cogs" aria-hidden="true"></i> <?php echo($lang['SETTINGS']);?></a><span class="double-border"></span></li> <?php }?>
                    <?php if(USER::access('setting') > 0) {?> <li style="display: none;"><a style="display: none;" id="integrations" href="javascript:void(0);"></a></li> <?php }?>
                    <?php if(USER::access('order') > 0) {?> <li  style="display: none;"><a id="statistic" href="javascript:void(0);" title="<?php echo($lang['T_TIP_SETT']);?>" class="tool-tip-bottom"></a><span class="double-border"></span></li> <?php }?>
                  </ul>
                  <ul class="nav-list exit">
                    <li><a href="javascript:void(0);" title="<?php echo($lang['QUIT']);?>" class="logout-button"><i class="fa fa-sign-out" aria-hidden="true"></i> Выйти</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </header>

        <div class="notice-block row" style="display:block;">
            
            <div class="mailLoader fl-left" style="margin-right:10px;"></div>
            
            <?php if($fakeKey){ ?>
                <div id ="fakeKey" class="message_information inform button alert fl-left">
                    <?php echo($fakeKey);?>
                </div>
            <?php }?>
        </div>

        <div id="thisHostName" style="display:none"><?php echo SITE; ?></div>
        <div id="currency" style="display:none"><?php echo MG::getSetting('currency'); ?></div>
        <div id="color-theme" style="display:none"><?php echo $data['themeColor']; ?></div>
        <div id="bg-theme" style="display:none"><?php echo $data['themeBackground']; ?></div>
        <div id="staticMenu" style="display:none"><?php echo $data['staticMenu']; ?></div>
        <div id="protocol" style="display:none"><?php echo PROTOCOL; ?></div>
        <div id="currency-iso" style="display:none"><?php echo MG::getSetting('currencyShopIso'); ?></div>
        <div id="max-count-cart" style="display:none"><?php echo MAX_COUNT_CART; ?></div>
        <div id="enabledStorage" style="display:none"><?php echo MG::enabledStorage(); ?></div>         
        
        <div class="admin-center">
            <?php MG::printNotification(); ?>
            <div class="data">
                <!-- Контент раздела -->
            </div>
        </div>
        <div class="admin-h-height"></div>
    </div>

    <div class="block-print">
       <!-- В этот блок будет вставляться контент для печати -->
    </div>

    <?php 
    switch (EDITION) {
      case 'gipermarket':
        $versionText = 'Гипермаркет '.VER;
        break;
      case 'market':
        $versionText = 'Маркет '.VER;
        break;
      case 'minimarket':
        $versionText = 'Минимаркет '.VER;
        break;
      case 'vitrina':
        $versionText = 'Витрина '.VER;
        break;
      
      default:
        $versionText = '';
        break;
    }?>

    <footer class="footer no-print">
      <div class="row">
        <div class="small-7 columns">
          <div class="copy">&copy; Все права защищены <a href="http://moguta.ru?mg=admftr" target="_blank">Moguta.CMS™</a> <?php echo $versionText;?></div>
        </div>
        <div class="small-5 columns text-right"><a href="http://wiki.moguta.ru/panel-upravleniya?mg=admdoc" target="_blank"><i class="fa fa-info-circle" aria-hidden="true"></i> Документация</a></div>
      </div>
    </footer>

        
    </body>

    <?php else:?>

    <?php 
        if ($data['themeBackground'] == 'customBackground') {
          $style = "zoom: 1; background-image: url('".SITE."/uploads/customAdmin/".MG::getOption('customBackground')."');";
        }
        else{
          $style = "zoom: 1; background-image: url('".SITE."/mg-admin/design/images/bg_textures/".$data['themeBackground'].".png');";
        }

        if (MG::getOption('bgfullscreen') == 'true') {
          $style .= " background-position-x: center; 
          background-position-y: center; 
          background-repeat-x: no-repeat; 
          background-repeat-y: no-repeat; 
          background-attachment: fixed; 
          -moz-background-size: cover; 
          -webkit-background-size: cover; 
          -o-background-size: cover; 
          background-size: cover;"; 
        }

        $adminLogo = MG::getOption('customAdminLogo');
        if (is_file(URL::getDocumentRoot().'uploads'.DIRECTORY_SEPARATOR.'customAdmin'.DIRECTORY_SEPARATOR.$adminLogo)) {

          $adminLogo = SITE.'/uploads/customAdmin/'.$adminLogo;
        }
        else{

          $adminLogo = SITE.'/mg-admin/design/images/logo-black.svg';
        }
        
    ?>  
    </head>
    <body style="<?php echo $style; ?>">
        <div class="mg-enter">
            <?php if (MG::getSetting('trialVersionStart')):?>
        <div class="mg-error-public">
            <?php echo MG::getSetting('trialVersion').' Если Вы администратор сайта и у вас возникли вопросы обращайтесь на info@moguta.ru'?>
        </div>
        <?php endif;?>
            <div class="enter-header">
                <div class="enter-logo"><img src="<?php echo $adminLogo; ?>"></div>
            </div>
             <?php echo!empty($data['msgError'])?$data['msgError']:'' ?>
            <div class="enter-body">
                <h2>Вход в панель управления</h2>
                <div class="enter-form">                   
                    <form action="<?php echo SITE?>/enter" method="POST" class="login">
                        <ul class="login-list">
                            <li><input type="text" placeholder="Email" name="email" value="" class="login-input"></li>
                            <li><input type="password" placeholder="Пароль" name="pass" value="" class="pass-input"></li>
                        </ul>

                        <input type="hidden" name="location" value="/mg-admin" />
                        <?php echo !empty($data['checkCapcha']) ? $data['checkCapcha'] : '' ?>
                        <button type="submit" class="enter-button">Войти</button>
                    </form>
                </div>
                <div class="link-holder">
                    <a href="<?php echo SITE ?>/forgotpass" class="forgot-link">Забыли пароль?</a>
                </div>
            </div>
        </div>
        <footer class="footer fixed no-print">
          <div class="row">
            <div class="small-7 columns">
              <div class="copy">&copy; Все права защищены Moguta.CMS™ <a href="http://moguta.ru?mg=admftr" target="_blank">moguta.ru</a></div>
            </div>
            <div class="small-5 columns text-right"><a href="http://wiki.moguta.ru/panel-upravleniya?mg=admdoc" target="_blank"><i class="fa fa-info-circle" aria-hidden="true"></i> Документация</a></div>
          </div>
        </footer>    
    </body>
<?php endif;?>
</html>
<!-- VER <?php echo VER;?><?php $tmp = new Actioner();if (method_exists($tmp, 'preDownload') && class_exists('Controllers_Compare')) {echo '-full';}elseif (method_exists($tmp, 'preDownload') && !class_exists('Controllers_Compare')) {echo '-free';}elseif (!method_exists($tmp, 'preDownload') && class_exists('Controllers_Compare')) {echo '-mini';}?> -->