<?php

echo '<div class="row">' . "\n" . '  <div class="large-12 columns" style="margin: 0 10px;">' . "\n" . '    <h4>Система Moguta.CMS Гипермаркет NULLED ';
echo VER;
echo '</h4>' . "\n" . '    <ul class="accordion" data-accordion="" data-multi-expand="true" data-allow-all-closed="true" style="margin-bottom: 10px">' . "\n" . '      ';

if (1 < USER::access('setting')) {
	echo '      <li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">';
	echo $lang['BACKUP_TITLE'];
	echo ' (<span style="color: #357b38;">BETA</span>)</a>' . "\n" . '       <div class="accordion-content" data-tab-content="">' . "\n" . '          <div class="backup large-12">' . "\n" . '            <button class="button success createNewBackup fl-left"><i class="fa fa-file-archive-o"></i> ';
	echo $lang['BACKUP_BUTTON_CREATE'];
	echo '</button>' . "\n" . '            <button class="button primary uploadNewBackup fl-left"><i class="fa fa-upload"></i> ';
	echo $lang['BACKUP_BUTTON_UPLOAD'];
	echo '</button>' . "\n" . '            <button class="button secondary spaceDisk restoreRecentBackup fl-left"><i class="fa fa-undo"></i> ';
	echo $lang['BACKUP_BUTTON_RESTORE'];
	echo '</button>' . "\n" . '            <div class="clearfix"></div>' . "\n" . '            <div class="warnings">' . "\n" . '              <div class="fl-left alert-block warning text-center dumpSizeResult" style="display: none;">' . "\n" . '                ';

	if (!class_exists('Backup')) {
		include URL::getDocumentRoot() . 'mg-admin' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'backup.php';
	}

	echo $lang['BACKUP_SIZE_1'] . '<span class="number"></span>' . $lang['BACKUP_SIZE_2'];
	echo '                <br>По техническим причинам не поддерживается создание резервных копий сайтов, размером более 2 GB' . "\n" . '              </div>' . "\n" . '              <div class="fl-left alert-block text-center warning dumpSizeCalculating" style="display: none;">' . "\n" . '                ';
	echo $lang['BACKUP_SIZE_CALCULATING'];
	echo '              </div>' . "\n" . '              <div class="fl-left alert-block warning dumpSizePH">' . "\n" . '                ';
	echo $lang['BACKUP_SIZE_PH'];
	echo '                <a class="calcDumpSize fl-right">';
	echo $lang['BACKUP_SIZE_BTN'];
	echo '</a>' . "\n" . '                <br>По техническим причинам не поддерживается создание резервных копий сайтов, размером более 2 GB' . "\n" . '              </div>' . "\n" . '            </div>' . "\n" . '            <div class="clearfix"></div>' . "\n" . '            <h3 class="header_table">';
	echo $lang['BACKUP_TABLE_TITLE'];
	echo '</h3>' . "\n" . '            <h3 class="header_create" style="display: none;">';
	echo $lang['BACKUP_TITLE_CREATE'];
	echo '</h3>' . "\n" . '            <h3 class="header_restore" style="display: none;">';
	echo $lang['BACKUP_TITLE_RESTORE'];
	echo '</h3>' . "\n" . '            <button class="button stopNewBackup" style="display: none;"><i class="fa fa-times"></i> ';
	echo $lang['BACKUP_BUTTON_STOP'];
	echo '</button>' . "\n" . '            <div class="table-wrapper">' . "\n" . '              <table class="backupTable main-table">' . "\n" . '                <thead>' . "\n" . '                  <tr>' . "\n" . '                    <td>';
	echo $lang['BACKUP_TABLE_HEAD_1'];
	echo '</td>' . "\n" . '                    <td>';
	echo $lang['BACKUP_TABLE_HEAD_2'];
	echo '</td>' . "\n" . '                    <td>';
	echo $lang['BACKUP_TABLE_HEAD_3'];
	echo '</td>' . "\n" . '                    <td>';
	echo $lang['BACKUP_TABLE_HEAD_4'];
	echo '</td>' . "\n" . '                    <td>';
	echo $lang['BACKUP_TABLE_HEAD_5'];
	echo '</td>' . "\n" . '                    <td>';
	echo $lang['BACKUP_TABLE_HEAD_6'];
	echo '</td>' . "\n" . '                  </tr>' . "\n" . '                </thead>' . "\n" . '                <tbody>' . "\n" . '                  ';
	echo Backup::DrawTable();
	echo '                </tbody>' . "\n" . '              </table>' . "\n" . '            </div>' . "\n" . '            <form class="backupInputForm" method="post" noengine="true" enctype="multipart/form-data">' . "\n" . '              <input type="file" class="backupInput" id="backupInput" name="backupInput" style="display: none;">' . "\n" . '            </form>' . "\n" . '            <div class="row">' . "\n" . '              <div class="large-12 columns" style="padding: 10px 6px;width: 60.2%">' . "\n" . '                <div class="progress" role="progressbar" tabindex="0" aria-valuenow="20" aria-valuemin="0" aria-valuetext="25 percent" aria-valuemax="100" style="height:3rem;position:relative; display: none;">' . "\n" . '                  <span class="progress-meter percentWidth" style="width: 0"></span>' . "\n" . '                  <p class="progress-meter-text echoPercent" style="font-size:2rem;position:absolute;top:15px;left:50%;">0%</p>' . "\n" . '                </div>' . "\n" . '              </div>' . "\n" . '            </div>' . "\n" . '            <textarea class="backupLog" style="height:250px; display: none;width: 60%;" disabled="disabled"></textarea>' . "\n" . '          </div>' . "\n" . '        </div>' . "\n" . '        <span class="maxUploadSize" style="display: none;">';
	echo min(str_replace(array('M', 'm'), '', ini_get('post_max_size')), str_replace(array('M', 'm'), '', ini_get('upload_max_filesize')));
	echo '</span>' . "\n" . '      </li>' . "\n" . '      ';
}

echo '      <li class="accordion-item is-active updateAccordion" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">';
echo $lang['BACKUP_SYSTEM_TITLE'];
echo '</a>' . "\n" . '        <div class="accordion-content" data-tab-content="">' . "\n" . '          <div class="tab-inner">' . "\n\n" . '            ';

if ($newFirstVersiov) {
	echo '                <div class="alert-block success step-info" style="display:none;">';
	echo $lang['SYSTEM_SETTINGS_2'];
	echo '<strong>';
	echo $newFirstVersiov;
	echo '</strong> <a href="javascript:void(0);" class="button" onclick="$(\'#go\').click();"><i class="fa fa-download" aria-hidden="true"></i>';
	echo $lang['SYSTEM_SETTINGS_3'];
	echo '</a></div>' . "\n" . '            ';
}

echo "\n" . '            ';

if ($newFirstVersiov) {
	echo "\n" . '            <div class="row">' . "\n" . '              <div class="small-12 columns">' . "\n" . '                <ul class="step-form" style="margin-top: 0!important;">' . "\n" . '                  <li class="step-update-li-1" >' . "\n" . '                    <span class="corner"></span>' . "\n" . '                    <h2>';
	echo $lang['SYSTEM_SETTINGS_4'];
	echo ' 1</h2>' . "\n" . '                    <strong>';
	echo $lang['SYSTEM_SETTINGS_5'];
	echo '</strong>' . "\n" . '                    <img style="display: none" class="loading-update-step-1 loader" src="';
	echo SITE;
	echo '/mg-admin/design/images/small-loader.gif" class="loader" width="16" height="16" alt=""/>' . "\n" . '                  </li>' . "\n" . '                  <li class="step-update-li-2 current">' . "\n" . '                    <span class="corner"></span>' . "\n" . '                    <h2>';
	echo $lang['SYSTEM_SETTINGS_4'];
	echo ' 2</h2>' . "\n" . '                    <strong>';
	echo $lang['SYSTEM_SETTINGS_6'];
	echo '</strong>' . "\n" . '                    <img style="display:none" class="loading-update-step-2 loader" src="';
	echo SITE;
	echo '/mg-admin/design/images/small-loader.gif" class="loader" width="16" height="16" alt=""/>' . "\n" . '                  </li>' . "\n" . '                  <li class="step-update-li-3 current">' . "\n" . '                    <span class="corner"></span>' . "\n" . '                    <h2>';
	echo $lang['SYSTEM_SETTINGS_4'];
	echo ' 3</h2>' . "\n" . '                    <strong>';
	echo $lang['SYSTEM_SETTINGS_7'];
	echo '</strong>' . "\n" . '                  </li>' . "\n" . '                </ul>' . "\n" . '              </div>' . "\n" . '              ' . "\n" . '              <div>' . "\n\n" . '                <div class="step-block">' . "\n" . '                  <div class="step1">' . "\n" . '                    <div style="display:none" class="step-process-info link-result"></div>' . "\n" . '                    <div class="step-1-info link-result">' . "\n" . '                      <ul class="system-version-list">' . "\n" . '                        <li>' . "\n" . '                          <strong>';
	echo $lang['SYSTEM_SETTINGS_8'];
	echo '</strong>' . "\n" . '                          ';

	if ($newVersionMsg) {
		echo $newVersionMsg;
	}

	echo '                        </li>' . "\n" . '                      </ul>' . "\n" . '                    <div style="display:none" class="step-eror-info link-fail" style="margin-bottom:5px;"></div>' . "\n" . '                      <button rel="preDownload" class="update-now tool-tip-bottom button primary';
	echo $updataOpacity;
	echo '" title="';
	echo '" ';
	echo $updataDisabled;
	echo ' >' . "\n" . '                          <span id="go">';
	echo $lang['SYSTEM_SETTINGS_3'];
	echo ' ';
	echo strip_tags($newFirstVersiov);
	echo '</span>' . "\n" . '                      </button>' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n" . '                  <div class="step2" style="display:none">' . "\n" . '                    <div style="display:none" class="step-process-info link-result"></div>' . "\n" . '                    <div class="step-2-info link-result">' . "\n" . '                      <ul class="system-version-list">' . "\n" . '                        <li>' . "\n" . '                          ';
	echo $lang['SYSTEM_SETTINGS_9'];
	echo '                          <div style="display:none" class="step-eror-info link-fail" style="margin-bottom:5px;"></div>' . "\n" . '                          <button style="display:none" rel="preDownload" class="update-archive button">' . "\n" . '                            <span id="go">';
	echo $lang['APPLY_UPDATE'];
	echo '</span>' . "\n" . '                          </button>' . "\n" . '                        </li>' . "\n" . '                      </ul>' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n" . '                </div>' . "\n" . '              </div>' . "\n" . '            </div>' . "\n\n" . '                ';
}
else {
	echo '              <div class="row">' . "\n" . '              <div class="small-12 columns">' . "\n" . '                  <div class="row">' . "\n" . '                    <div class="small-12 columns">' . "\n" . '                      <ul class="step-form" style="margin-top: 0!important;">' . "\n\n" . '                        <li class="step-update-li-1 current completed" >' . "\n" . '                            <span class="corner"></span>' . "\n" . '                            <h2>';
	echo $lang['SYSTEM_SETTINGS_4'];
	echo ' 1</h2>' . "\n" . '                            <strong>';
	echo $lang['SYSTEM_SETTINGS_5'];
	echo '</strong>' . "\n" . '                        </li>' . "\n" . '                        <li class="step-update-li-2 current completed">' . "\n" . '                            <span class="corner"></span>' . "\n" . '                            <h2>';
	echo $lang['SYSTEM_SETTINGS_4'];
	echo ' 2</h2>' . "\n" . '                            <strong>';
	echo $lang['SYSTEM_SETTINGS_6'];
	echo '</strong>' . "\n" . '                        </li>' . "\n" . '                       <li class="step-update-li-3">' . "\n" . '                            <h2>';
	echo $lang['SYSTEM_SETTINGS_4'];
	echo ' 3</h2>' . "\n" . '                            <strong>';
	echo $lang['SYSTEM_SETTINGS_10'];
	echo VER;
	echo '!</strong>' . "\n" . '                        </li>' . "\n" . '                      </ul>' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n" . '                </div>' . "\n" . '              </div>' . "\n" . '                ';
}

echo '                  <div class="row">' . "\n" . '                    <div class="large-7 columns">' . "\n" . '                      <div class="row">' . "\n" . '                        <div class="small-10 medium-5 columns">' . "\n" . '                          <label class="middle">';
echo $lang['CONSENT_DATA'];
echo ':</label>' . "\n" . '                        </div>' . "\n" . '                        <div class="small-2 medium-7 columns">' . "\n" . '                          <div class="checkbox margin">' . "\n" . '                            ';
$consentData = $data['setting-system']['options']['consentData']['value'];
$checked = '';
$value = 'value="false"';

if ($consentData == 'true') {
	$checked = 'checked="checked"';
	$value = 'value="' . $consentData . '"';
}

echo '                            <input id="r2" type="checkbox" class="option downtime-check" ';
echo $value;
echo ' ';
echo $checked;
echo ' name="consentData">' . "\n" . '                            <label for="r2"></label>' . "\n" . '                          </div>' . "\n" . '                        </div>' . "\n" . '                      </div>' . "\n" . '                      <div class="row">' . "\n" . '                        <div class="small-10 medium-5 columns">' . "\n" . '                          <label class="middle">';
echo $lang['SYSTEM_SETTINGS_11'];
echo '</label>' . "\n" . '                        </div>' . "\n" . '                        <div class="small-2 medium-7 columns">' . "\n" . '                          <div class="checkbox margin">' . "\n" . '                            ';
$downtime = $data['setting-system']['options']['downtime']['value'];
$checked = '';
$value = 'value="false"';

if ($downtime == 'true') {
	$checked = 'checked="checked"';
	$value = 'value="' . $downtime . '"';
}

echo '                            <input id="r1" type="checkbox" class="option downtime-check" ';
echo $value;
echo ' ';
echo $checked;
echo ' name="downtime">' . "\n" . '                            <label for="r1"></label>' . "\n" . '                          </div>' . "\n" . '                        </div>' . "\n" . '                      </div>' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n\n" . '                  <div class="row">' . "\n" . '                    <div class="large-7 columns">' . "\n" . '                      <div class="row">' . "\n" . '                        <div class="small-12 medium-5 columns">' . "\n" . '                          <label class="middle">';
echo $lang['SYSTEM_SETTINGS_12'];
echo '</label>' . "\n" . '                        </div>' . "\n" . '                        <div class="small-12 medium-7 columns">' . "\n\n" . '                          ';
$displayKey = 'display:inline-block';

if ($data['setting-system']['options']['licenceKey']['value']) {
	$displayKey = 'display:none';
}

echo '                            <div class="add-key">' . "\n" . '                              <input style="';
echo $displayKey;
echo '" placeholder="';
echo $lang['SYSTEM_SETTINGS_16'];
echo '" type="text"  name="licenceKey" ' . "\n" . '                              class="settings-input option licenceKey" value="';
echo $data['setting-system']['options']['licenceKey']['value'];
echo '">' . "\n" . '                              <button style="';
echo $displayKey;
echo '" class="save-button save-settings save-settings-system button success">' . "\n" . '                                <i class="fa fa-floppy-o"></i> <span>';
echo $lang['SYSTEM_SETTINGS_13'];
echo '</span>' . "\n" . '                              </button>' . "\n" . '                            </div>' . "\n" . '                          ';

if ($displayKey == 'display:none') {
	echo '                            <a href="javascript:void(0);" class ="edit-key edit-row" >' . "\n" . '                              ';
	echo $data['setting-system']['options']['licenceKey']['value'];
	echo '                            </a>' . "\n" . '                          ';
}

echo "\n" . '                          <div class="error-key" style="color:red;padding-top:5px;display: ';
echo $updataDisabled != 'disabled' ? 'none' : 'block';
echo '">' . "\n" . '                            ';
echo $lang['SETTING_LOCALE_1'];
echo '                          </div>' . "\n" . '                        </div>' . "\n" . '                      </div>' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n" . '                  <div class="row">' . "\n" . '                    <div class="large-7 columns">' . "\n" . '                      <div class="row">' . "\n" . '                        <div class="small-12 medium-5 columns">' . "\n" . '                          <label class="middle">';
echo $lang['SYSTEM_SETTINGS_14'];
echo '</label>' . "\n" . '                        </div>' . "\n" . '                        <div class="small-12 medium-7 columns">' . "\n" . '                          ';
$dateActivate = MG::getOption('dateActivateKey');

if ($dateActivate != '0000-00-00 00:00:00') {
	$now_date = strtotime($dateActivate);
	$future_date = strtotime(date('Y-m-d'));
	$dayActivate = 365 - floor(($future_date - $now_date) / 86400);

	if ($dayActivate <= 0) {
		$dayActivate = 0;
		$extend = ' [<a target=\'blank\' href=\'' . MG::getSetting('licenceKey') . '\'>Продлить</a>]';
	}

	$activeDate = '<span class=\'key-days-number\'><b>NULLED релиз</b>, не редактировать ключ.</span>' . $extend;
}
else {
	$activeDate = ' <span class=\'key-days-number\'><b>NULLED релиз</b>, не редактировать ключ.</span>';
}

echo '                          <div class="margin">';
echo $activeDate;
echo '</div>' . "\n" . '                        </div>' . "\n" . '                      </div>' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n" . '                  <div class="row">' . "\n" . '                    <div class="large-6 columns">' . "\n" . ' ';
echo '' . "\n" . '                    </div>' . "\n" . '                  </div>' . "\n" . '                <!-- </div>' . "\n" . '              </div> -->' . "\n" . '          </div>' . "\n" . '          <table style="display:none" class="main-settings-list-table">' . "\n" . '            <tr>' . "\n" . '              <td>' . "\n" . '                <dl>' . "\n" . '                  <dt>';
echo $lang['STNG_CUR_VER'];
echo '<span>';
echo VER;
echo '</span></dt>' . "\n" . '                  <dd id="updataMsg">' . "\n" . '                    ';

if (!$errorUpdata) {
	if ($newVersionMsg) {
		echo $newVersionMsg;
		echo '                        <span class="custom-text" style="color:red">';
		echo $lang['SETTING_LOCALE_3'];
		echo '</span>' . "\n" . '                        <br/><button rel="preDownload" class="update-now tool-tip-bottom ';
		echo $updataOpacity;
		echo '" title="';
		echo '" ';
		echo $updataDisabled;
		echo ' >' . "\n" . '                        <span id="go">';
		echo $lang['SETTING_LOCALE_5'];
		echo '</span>' . "\n" . '                      </button>' . "\n" . '                      ';
	}
	else {
		echo '                        <strong><span style="color:green;">';
		echo $lang['SETTING_LOCALE_6'];
		echo '</span></strong>' . "\n" . '                        (<a href="javascript:void(0);" class="clearLastUpdate">';
		echo $lang['SETTING_LOCALE_7'];
		echo '</a> )' . "\n" . '                      ';
	}

	echo '                    ';
}
else {
	echo '                      <span style="color:red">' . "\n" . '                        ';
	echo $errorUpdata;
	echo ' ';
	echo $lang['SETTING_LOCALE_8'];
	echo '                      </span>' . "\n" . '                    ';
}

echo '                  </dd>' . "\n" . '                </dl>' . "\n" . '              </td>' . "\n" . '            </tr>' . "\n" . '          </table>' . "\n" . '        </div>' . "\n" . '      </li>' . "\n" . '      <li class="accordion-item" data-accordion-item=""><a class="accordion-title" href="javascript:void(0);">';
echo $lang['BACKUP_INFO_TITLE'];
echo '</a>' . "\n" . '        <div class="accordion-content" data-tab-content="">' . "\n" . '          ';

if (!class_exists('Backup')) {
	include URL::getDocumentRoot() . 'mg-admin' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'backup.php';
}

echo Backup::checkSystem();
echo '        </div>' . "\n" . '      </li>' . "\n" . '    </ul>' . "\n" . '  </div>' . "\n" . '</div>';

?>