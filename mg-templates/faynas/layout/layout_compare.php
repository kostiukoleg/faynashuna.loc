<?php mgAddMeta('<script src="' . PATH_SITE_TEMPLATE . '/js/layout.compare.js"></script>'); ?>

<div class="mg-product-to-compare">
<a href="<?php echo SITE ?>/compare" title="<?php echo lang('compareToList'); ?>">
    <div class="mg-compare-count" style="<?php echo ($_SESSION['compareCount']) ? 'display:block;' : 'display:none;'; ?>"><?php if (isset($_SESSION['compareCount'])) {echo $_SESSION['compareCount'];} else{echo 0;}?></div>
</a>
<div class="text"><?php echo lang('compareCompare'); ?></div>
</div>

