<?php
 class Controllers_SocialLogin extends BaseController { public static $pluginName = 'social-autorization'; public static $coderKey = 'social'; public static $STATUS_SUCCESS = '200'; public static $STATUS_ERROR = '101'; public static $STATUS_INTERCEPTION_ERROR = '102'; public static $STATUS_INTERCEPTION_SUCCESS = '103'; public static $STATUS_GO_EMAIL = '198'; public static $STATUS_SEND_PASSWORD = '199'; public static $STATUS_CONFIRM_COMBINED = '201'; public static $STATUS_GET_E_P = '202'; public static $STATUS_GET_R_E_P = '203'; public static $STATUS_GET_E_E_P = '204'; public static $STATUS_BLOCKED = '205'; function __construct() { function curlGet($url, $params, $headers = array(), $agent = '', $timeout = 10) {return Controllers_SocialLogin::curlGet($url, $params, $headers = array(), $agent = '', $timeout = 10);} function curlPost($url, $params, $headers = array(), $agent = '', $timeout = 10) {return Controllers_SocialLogin::curlPost($url, $params, $headers = array(), $agent = '', $timeout = 10);} function genRandomString($length, $small = false, $numbers = true) {return Controllers_SocialLogin::genRandomString($length, $small, $numbers);} function encodeText($text, $key) {return Controllers_SocialLogin::encodeText($text, $key);} function decodeText($text, $key) {return Controllers_SocialLogin::decodeText($text, $key);} $socials = $this->getSocialHandlers(); $_IN = empty($_POST) ? $_GET : $_POST; $data_status = self::$STATUS_ERROR; $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-core'.DIRECTORY_SEPARATOR.'controllers', '', dirname(__FILE__)); if (!empty($_IN['auth']) && !empty($_IN['metod']) && !empty($_IN['location'])) { $file = $realDocumentRoot.DIRECTORY_SEPARATOR.PLUGIN_DIR.self::$pluginName.'/services/'.$_IN['auth'].'.php'; if(file_exists($file)) { if(!isset($_SESSION['social_login_auth_service'])) $_SESSION += array('social_login_auth_service' => $_IN['auth']); else $_SESSION['social_login_auth_service'] = $_IN['auth']; if(!isset($_SESSION['social_login_auth_metod'])) $_SESSION += array('social_login_auth_metod' => $_IN['metod']); else $_SESSION['social_login_auth_metod'] = $_IN['metod']; if(!isset($_SESSION['social_login_auth_last_page'])) $_SESSION += array('social_login_auth_last_page' => $_IN['location']); else $_SESSION['social_login_auth_last_page'] = $_IN['location']; require_once($file); call_user_func_array ( array ( $_IN['auth'], 'redirection' ), array ( $socials[$_IN['auth']]['setting'] ) ); return; } } if (isset($_SESSION['social_login_auth_service']) && isset($_SESSION['social_login_auth_metod'])) { if($_SESSION['social_login_auth_metod'] == 'poup') { if(!isset($_SESSION['social_login_interception'])) { $_SESSION += array('social_login_interception' => $_IN); $data_status = self::$STATUS_INTERCEPTION_SUCCESS; $this->data = array('status_code' => $data_status); return; } if ($_SESSION['social_login_interception'] != $_IN) { var_dump($_IN); var_dump($_SESSION['social_login_interception']); $data_status = self::$STATUS_INTERCEPTION_ERROR; $this->data = array('status_code' => $data_status); unset($_SESSION['social_login_auth_service']); unset($_SESSION['social_login_interception']); unset($_SESSION['social_login_auth_metod']); return; } } $_IN += array('service' => $_SESSION['social_login_auth_service']); unset($_SESSION['social_login_auth_service']); unset($_SESSION['social_login_interception']); unset($_SESSION['social_login_auth_metod']); $file = $realDocumentRoot.DIRECTORY_SEPARATOR.PLUGIN_DIR.self::$pluginName.'/services/'.$_IN['service'].'.php'; if(file_exists($file)) { $result = array(); require_once($file); call_user_func_array ( array ( $_IN['service'], 'data' ), array ( $socials[$_IN['service']]['setting'], $_IN, &$result ) ); $result += array ( 'service' => $_IN['service'] ); } if(isset($result['id'])) { $gen_email = $this->genSocialEmail($result['service'], $result['id']); if(isset($_SESSION['social_login_secure_key'])) $_SESSION['social_login_secure_key'] = $gen_email; else $_SESSION += array('social_login_secure_key' => $gen_email); $SocialUserInfo = $this->getSocialUserByInfo($result); if ($row = DB::fetchAssoc($SocialUserInfo)) $SocialUserInfo = $row; else $SocialUserInfo = null; if(empty($SocialUserInfo['blocked']) || $SocialUserInfo['blocked'] == '0') { $result += array('gen_email' => $gen_email); $pass = $this->genRandomString(8); $auth_first = false; $login_id = 0; $arr = array( 'email' => $result['email'], 'pass' => $pass, 'name' => $result['first_name'], 'sname' => $result['last_name'], 'address' => $result['address'], 'phone' => '', 'blocked' => 0, 'activity' => 1, 'role' => 2 ); if(!empty($result['email'])) { if(empty($SocialUserInfo['gen_email'])) { $is_user = User::getUserInfoByEmail($result['email']); if (!empty($is_user)) { $login_id = $is_user->id; $this->addSocialUser($result, $login_id, 0); $_SESSION['social_login_secure_key'] = $result['gen_email']; $data_status = self::$STATUS_CONFIRM_COMBINED; } else { $login_id = User::add($arr); $this->addSocialUser($result, $login_id, 1); $auth_first = true; $data_status = self::$STATUS_SEND_PASSWORD; } } else { $data_status = $SocialUserInfo['combined'] == 1 ? self::$STATUS_SUCCESS : self::$STATUS_CONFIRM_COMBINED; } } else { if($SocialUserInfo['combined'] == 1 || !empty($SocialUserInfo['email'])) { $arr['email'] = $SocialUserInfo['email']; $data_status = self::$STATUS_SUCCESS; } else { $arr['email'] = $result['gen_email']; if(!isset($SocialUserInfo['id']) && !User::getUserInfoByEmail($SocialUserInfo['email']) && !User::getUserInfoByEmail($result['gen_email'])) { $login_id = User::add($arr); $this->addSocialUser($result, $login_id); $auth_first = true; } $data_status = self::$STATUS_GET_E_P; } } $this->data = array ( 'status_code' => $data_status, 'email' => $arr['email'], 'user-info' => $this->encodeText($result['gen_email'], self::$coderKey), 'auth_first' => $auth_first, 'secret' => $arr['pass'], 'user-data' => array ( 'engine' => $arr, 'social' => $result ) ); } else { $data_status = self::$STATUS_BLOCKED; $this->data = array('status_code' => $data_status, 'user-info' => $SocialUserInfo['blocked']); } } } if (!empty($_IN['real-email']) && !empty($_IN['user-info'])) { $tmp_email = $_IN['real-email']; $tmp_info = $this->decodeText($_IN['user-info'], self::$coderKey); if($this->checkSecret($tmp_info)) { if($this->checkEmailForm($tmp_email)) { $user = User::getUserInfoByEmail($tmp_email); if(empty($user->id)) { $activity = 1; $data_status = self::$STATUS_SUCCESS; if(MG::getOption('checkSocialEmail') == 'true') { $activity = 0; $this->checkEmail($tmp_email); $data_status = self::$STATUS_GO_EMAIL; } DB::query('UPDATE  `'.PREFIX.'user` SET `email` = '.DB::quote($tmp_email).', `activity` = '.$activity.' WHERE `email` = '.DB::quote($tmp_info)); DB::query('UPDATE `'.PREFIX.self::$pluginName.'_users` SET `email` = '.DB::quote($tmp_email).' WHERE `gen_email` = '.DB::quote($tmp_info)); } else $data_status = self::$STATUS_CONFIRM_COMBINED; } else $data_status = self::$STATUS_GET_E_E_P; $this->data = array('status_code' => $data_status, 'user-info' => $_IN['user-info'], 'email' => $tmp_email); } } if (!empty($_IN['password']) && !empty($_IN['email']) && !empty($_IN['user-info'])) { $tmp_email = $_IN['email']; $tmp_pass = $_IN['password']; $tmp_info = $this->decodeText($_IN['user-info'], self::$coderKey); if($this->checkSecret($tmp_info)) { $login_info = User::getUserInfoByEmail($tmp_email); if($login_info->pass == crypt($tmp_pass, $login_info->pass)) { DB::query('UPDATE `'.PREFIX.self::$pluginName.'_users` SET `user_id` = '.$login_info->id.', `combined` = 1, `email` = '.DB::quote($tmp_email).' WHERE `gen_email` = '.DB::quote($tmp_info)); if($login_info->email != $tmp_info) DB::query('DELETE FROM `'.PREFIX.'user` WHERE `email` = '.DB::quote($tmp_info)); $data_status = self::$STATUS_SUCCESS; } else $data_status = self::$STATUS_GET_R_E_P; $this->data = array('status_code' => $data_status, 'user-info' => $_IN['user-info'], 'email' => $tmp_email); } } if(!empty($_IN['auth_success'])) { $tmp_info = $this->decodeText($_IN['auth_success'], self::$coderKey); if($this->checkSecret($tmp_info)) { $user = $this->getSocialUserByGen($tmp_info, true); if(isset($user['email'])) { $data_status = self::$STATUS_SUCCESS; $this->data = array('status_code' => $data_status, 'user-info' => $_IN['auth_success'], 'email' => $user['email']); } else { $data_status = self::$STATUS_ERROR; } } } if(isset($_IN['reActivate'])) { $tmp_email = $_IN['activateEmail']; $tmp_info = $this->decodeText($_IN['activateSecret'], self::$coderKey); if($this->checkSecret($tmp_info)) { if($this->checkEmailForm($tmp_email)) { if(!User::getUserInfoByEmail($tmp_email)) { $data_status = self::$STATUS_GO_EMAIL; DB::query('UPDATE  `'.PREFIX.'user` SET `email` = '.DB::quote($tmp_email).' WHERE `email` = '.DB::quote($tmp_info)); DB::query('UPDATE `'.PREFIX.self::$pluginName.'_users` SET `email` = '.DB::quote($tmp_email).' WHERE `gen_email` = '.DB::quote($tmp_info)); $this->checkEmail($tmp_email); } else $data_status = self::$STATUS_CONFIRM_COMBINED; $this->data = array('status_code' => $data_status, 'user-info' => $_IN['activateSecret'], 'email' => $tmp_email); } else { $data_status = self::$STATUS_GO_EMAIL; $this->data = array('status_code' => $data_status, 'user-info' => $_IN['activateSecret'], 'email' => $tmp_email, 'bad_form' => $tmp_email); } } } if($data_status == self::$STATUS_SUCCESS) { unset($_SESSION['social_login_secure_key']); } else if ($data_status == self::$STATUS_ERROR || $data_status == self::$STATUS_INTERCEPTION_ERROR) { unset($_SESSION['social_login_secure_key']); unset($_SESSION['social_login_auth_last_page']); } } public static function checkEmail($userEmail) { $fPass = new Models_Forgotpass; $userId = USER::getUserInfoByEmail($userEmail)->id; $hash = $fPass->getHash($userEmail); $fPass->sendHashToDB($userEmail, $hash); $siteName = MG::getOption('sitename'); $link = '<a href="'.SITE.'/registration?sec='.$hash.'&id='.$userId.'" target="blank">'.SITE.'/registration?sec='.$hash.'&id='.$userId.'</a>'; $paramToMail = array ( 'siteName' => $siteName, 'userEmail' => $userEmail, 'link' => $link, ); $message = MG::layoutManager('email_registry',$paramToMail); $emailData = array ( 'nameFrom' => $siteName, 'emailFrom' => MG::getSetting('noReplyEmail'), 'nameTo' => 'Пользователю сайта '.$siteName, 'emailTo' => $userEmail, 'subject' => 'Активация пользователя на сайте '.$siteName, 'body' => $message, 'html' => true ); $fPass->sendUrlToEmail($emailData); } public static function encodeText($text, $key) { $td = mcrypt_module_open ("tripledes", '', 'cfb', ''); $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND); if (mcrypt_generic_init ($td, $key, $iv) != -1) { $enc_text=base64_encode(mcrypt_generic ($td,$iv.$text)); mcrypt_generic_deinit ($td); mcrypt_module_close ($td); return $enc_text; } } public function decodeText($text, $key) { $td = mcrypt_module_open ("tripledes", '', 'cfb', ''); $iv_size = mcrypt_enc_get_iv_size ($td); $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND); if (mcrypt_generic_init ($td, $key, $iv) != -1) { $decode_text = substr(mdecrypt_generic ($td, base64_decode($text)),$iv_size); mcrypt_generic_deinit ($td); mcrypt_module_close ($td); return $decode_text; } } public static function checkEmailForm($email) { return preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $email); } public static function checkSecret($in) { return $_SESSION['social_login_secure_key'] == $in ? true : false; } public static function curlPost($url, $params, $headers = array(), $agent = '', $timeout = 10) { $curl = curl_init(); curl_setopt($curl, CURLOPT_URL, $url); curl_setopt($curl, CURLOPT_POST, 1); curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); if(!empty($agent)) curl_setopt($curl, CURLOPT_USERAGENT, $agent); curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout); curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params))); curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); $result = curl_exec($curl); curl_close($curl); return $result; } public static function curlGet($url, $params, $headers = array(), $agent = '', $timeout = 10) { $curl = curl_init(); curl_setopt($curl, CURLOPT_URL, $url.'?'.urldecode(http_build_query($params))); curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); if(!empty($agent)) curl_setopt($curl, CURLOPT_USERAGENT, $agent); curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout); curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); $result = curl_exec($curl); curl_close($curl); return $result; } public static function genRandomString($length, $small = false, $numbers = true, $gener = '0123456789QqWwEeRrTtYyUuIiOoPpaAsSdDFfGgHhJjKkLlZzXxCcVvBbNnMm') { if($small) $gener = '0123456789qwertyuiopasdfghjklzxcvbnm'; if(!$numbers) $gender = substr($gender, 10, 0); $len_gener = strlen($gener); $string = ''; for ($i = 0; $i < $length; $i++) { $index = mt_rand(0, $len_gener - 1); $string .= $gener[$index]; } return $string; } public static function getSocialHandlers() { $db_data = DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'`'); $socials[] = array(); while($row = DB::fetchAssoc($db_data)) { $socials += array ( $row['abbreviation'] => array ( 'abbreviation' => $row['abbreviation'], 'setting' => explode('|', $row['setting']), 'version' => $row['version'], 'active' => $row['active'] ) ); } return $socials; } public static function checkSocialUserBlocked($info) { $tmp = DB::query('SELECT `blocked` FROM `'.PREFIX.self::$pluginName.'_users` WHERE `service` = '.DB::quote($info['service']).' AND `uid` = '.DB::quote($info['id'])); if($t = DB::fetchAssoc($tmp)) if($t['blocked'] != '0') return true; return false; } public static function checkSocialUser($gen_email) { $tmp = DB::query('SELECT `id` FROM `'.PREFIX.self::$pluginName.'_users` WHERE `gen_email` = '.DB::quote($gen_email)); if($t = DB::fetchAssoc($tmp)) if($t['id'] != null) return true; return false; } public static function genSocialEmail($service, $id) { $result = $id.'@'.$service; if(strlen($result) > 28) $result = substr($result, 0, 28); return $result; } public static function getSocialUserByEmail($email, $arr = false) { $db = DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'_users` WHERE `email` = '.DB::quote($email)); if($arr) { if($row = DB::fetchAssoc($db)) { if(empty($row)) return false; return $row; } return false; } return $db; } public static function getSocialUserByGen($gen_email, $arr = false) { $db = DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'_users` WHERE `gen_email` = '.DB::quote($gen_email)); if($arr) { if($row = DB::fetchAssoc($db)) { if(empty($row)) return false; return $row; } return false; } return $db; } public static function getSocialUserByInfo($info) { return DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'_users` WHERE `service` = '.DB::quote($info['service']).' AND `uid` = '.DB::quote($info['id'])); } public static function addSocialUser($info, $id, $combined=0, $blocked='0') { return DB::query( "INSERT INTO `".PREFIX.self::$pluginName."_users`
       (
         `id`,
         `user_id`,
         `service`,
         `uid`,
         `full_name`,
         `first_name`,
         `last_name`,
         `address`,
         `sex`,
         `birthday`,
         `gen_email`,
         `email`,
         `combined`,
         `blocked`
       )
      VALUES
       (
         0,
         ".$id.", " .DB::quote($info['service']).", " .DB::quote($info['id']).", " .DB::quote($info['full_name']).",  " .DB::quote($info['first_name']).", " .DB::quote($info['last_name']).", " .DB::quote($info['address']).",  " .DB::quote($info['sex']).", " .DB::quote($info['birthday']).", " .DB::quote($info['gen_email']).", " .DB::quote($info['email']).", " .$combined.", " .DB::quote($blocked)."
       );" ); } } 