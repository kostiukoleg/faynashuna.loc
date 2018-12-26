<?php mgAddMeta('<script src="' . SCRIPT . 'standard/js/layout.search.js"></script>'); ?>

<div class="search">
    <form class="c-search__form c-form" method="GET" action="<?php echo SITE ?>/catalog">
        <div id="inputsearch">
        <input class="inputsearch" id="keyword" type="search" autocomplete="off" name="search" placeholder="<?php echo lang('searchPh'); ?>" value="<?php if (isset($_GET['search'])) {echo $_GET['search'];} ?>">
        </div>
        <div id="inputsearchbutton">
        <button type="submit" class="inputsearchbutton">
        </button>
        </div>
	<div class="searchexdiv">Например, <span class="searchex" id="searchex" onclick="return false;">225/50 R17</span></div>
    </form>
    <div id="searchres"></div>
</div>