<?php if($thisUser = $data['thisUser']): ?>
    <a id="fs-login" href="<?php echo SITE?>/personal" id="loginbtn" class="login"><?php echo lang('authAccount'); ?></a>
<?php else: ?>
    <a id="fs-login" href="<?php echo SITE?>/enter" id="loginbtn" class="login"><?php echo lang('authEnter'); ?></a>
<?php endif; ?>
