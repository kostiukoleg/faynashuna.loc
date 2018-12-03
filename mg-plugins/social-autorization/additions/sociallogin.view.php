<?php
 mgAddMeta('<link rel="stylesheet" href="/'.PLUGIN_DIR.'social-autorization/css/form.css" type="text/css" />'); $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-core'.DIRECTORY_SEPARATOR.'views', '', dirname(__FILE__)); $status_file = $realDocumentRoot.DIRECTORY_SEPARATOR.PLUGIN_DIR.'social-autorization'.DIRECTORY_SEPARATOR.'status'.DIRECTORY_SEPARATOR.$data['status_code'].'.php'; if(file_exists($status_file)) include($status_file); else echo '<div class="social-registration-out-block">
            <h1>Уупс ! Ошибка !</h1>
          </div>';