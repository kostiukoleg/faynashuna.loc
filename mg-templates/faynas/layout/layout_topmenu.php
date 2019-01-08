
<div class="j-page-menu">
    <div class="j-container">
        <nav id="j-page__nav" class="j-page__nav j-offcanvas">	
            <!--Вывод верхнего меню-->
            <ul class="j-page__ul j-offcanvas__menu">
            <?php foreach($data['pages'] as $page):?>
            <?php if($page['invisible']=="1"){continue;}?>
            <?php if(URL::getUrl()==$page['link']||URL::getUrl()==$page['link'].'/'){$active = 'active';} else {$active = '';}?>
            <?php if(isset($page['child'])):?>

            <?php $slider = 'slider'; $noUl = 1; foreach($page['child'] as $pageLevel1){$noUl *= $pageLevel1['invisible']; } if($noUl){$slider='';}?>
            <li class="level-1 parent">
                <a href="<?php echo $page['link']; ?>">
                    <?php echo MG::contextEditor('page', $page['title'], $page["id"], "page"); ?>
                </a>

                <?php  if($noUl){$slider=''; continue;} ?>

                <ul class="">
                    <?php foreach($page['child'] as $pageLevel1):?>
                    <?php if($pageLevel1['invisible']=="1"){continue;}?>
                    <?php if(isset($pageLevel1['child'])):?>
                    <?php $slider = 'slider'; $noUl = 1; foreach($pageLevel1['child'] as $pageLevel2){$noUl *= $pageLevel2['invisible']; } if($noUl){$slider='';}?>

                    <li class="level-2 parent">
                        <a class="" href="<?php echo $pageLevel1['link']; ?>">
                            <span>
                                <?php echo MG::contextEditor('page', $pageLevel1['title'], $pageLevel1["id"], "page"); ?>
                            </span>
                            <div class="c-nav__icon c-menu__icon">
                                <svg class="icon icon--arrow-right"><use xlink:href="#icon--arrow-right"></use></svg>
                            </div>
                        </a>

                        <?php  if($noUl){$slider=''; continue;} ?>
                        <ul class="">
                            <?php foreach($pageLevel1['child'] as $pageLevel2):?>
                                <?php if($pageLevel2['invisible']=="1"){continue;}?>
                                <li class="level-2">
                                    <a class="" href="<?php echo $pageLevel2['link']; ?>">
                                        <?php echo MG::contextEditor('page', $pageLevel2['title'], $pageLevel2["id"], "page");?>
                                    </a>
                                </li>
                            <?php endforeach;?>
                        </ul>
                    </li>

                    <?php else:?>

                    <li class="level-2">
                        <a class="" href="<?php echo $pageLevel1['link']; ?>">
                            <?php echo MG::contextEditor('page', $pageLevel1['title'], $pageLevel1["id"], "page");  ?>
                        </a>
                    </li>
                    <?php endif;?>

                    <?php endforeach;?>
                </ul>
            </li>

            <?php else:?>
            <li class="level-1">
                <a class="" href="<?php echo $page['link']; ?>">
                    <?php echo MG::contextEditor('page', $page['title'], $page["id"], "page"); ?>
                </a>
            </li>
            <?php endif;?>

            <?php endforeach;?>
            </ul>
        </nav>			
    </div>
</div>