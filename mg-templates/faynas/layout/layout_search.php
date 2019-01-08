<?php mgAddMeta('<script src="' . SCRIPT . 'standard/js/layout.search.js"></script>'); ?>

<div class="mg-search-block">
    <form class="search-form" method="GET" action="<?php echo SITE ?>/catalog">
        <input type="search" autocomplete="off" name="search" class="search-field" placeholder="<?php echo lang('searchPh'); ?>" value="<?php if (isset($_GET['search'])) {echo $_GET['search'];} ?>">
        <button type="submit" class="search-button"></button>
    </form>
    <div class="wraper-fast-result" style="display: none;">
        <div class="fastResult">

        </div>
    </div>
</div> 