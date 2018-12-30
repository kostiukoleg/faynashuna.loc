<div class="j-catalog__nav j-offcanvas">
    <div class="j-accordion-menu__title">
        <?php echo lang('menuCatalog'); ?>
    </div>
    <nav class="c-nav" id="c-nav__catalog">
        <div class="c-nav__menu">
            <ul class="j-accordion-menu j-offcanvas__menu">

                <?php foreach ($data['categories'] as $category): ?>
                <?php if ($category['invisible'] == "1") { continue;} ?>
                <?php if (SITE.URL::getClearUri() === $category['link']) { $active = 'active'; } else { $active = ''; } ?>
                <?php if (isset($category['child'])): ?>
                <?php $slider = 'slider'; $noUl = 1; foreach($category['child'] as $categoryLevel1){$noUl *= $categoryLevel1['invisible']; } if($noUl){$slider='';}?>

                <li class="level-1 parent">
                    <span class="j-accordion-menu__parent">
                        <svg class="icon icon--arrow" version="1.2" baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve">
                            <polygon points="179.1,-0.3 81.1,97.1 238.9,255.9 80,413.7 177.3,511.7 434.2,256.6"></polygon>
                        </svg>
                    </span>
                    <a class="level-1__a" href="<?php echo $category['link']; ?>">
 						<?php if(!empty($category['menu_icon'])): ?>
                            <div class="c-nav__img c-catalog__img"><img src="<?php echo SITE.$category['menu_icon'];?>" alt="<?php echo $category['title']; ?>"></div>
                        <?php endif; ?>                    	
                        <span class="level-1__a__span">
                            <?php echo MG::contextEditor('category', $category['title'], $category["id"], "category"); ?>
                        </span>
                        <?php if (MG::getSetting('showCountInCat')=='true'):?>
                            <span class="j-accordion-menu__count"><?php echo $category['insideProduct']?''.$category['insideProduct'].'':''; ?></span>
                        <?php endif;?>

                    </a>

                    <?php if($noUl){$slider=''; continue;} ?>

                    <ul class="">

                        <?php foreach ($category['child'] as $categoryLevel1): ?> <?php if ($categoryLevel1['invisible'] == "1") { continue; } ?>
                        <?php if (SITE.URL::getClearUri() === $categoryLevel1['link']) { $active = 'active'; } else { $active = ''; } ?>
                        <?php if (isset($categoryLevel1['child'])): ?>
                        <?php $slider = 'slider'; $noUl = 1; foreach($categoryLevel1['child'] as $categoryLevel2){$noUl *= $categoryLevel2['invisible']; } if($noUl){$slider='';}?>

                        <li class="level-2">
                            <a class="level-2__a" href="<?php echo $categoryLevel1['link']; ?>">
                                 <?php if(!empty($categoryLevel1['menu_icon'])): ?>
                                        <div class="mg-cat-img">
                                        <img src="<?php echo SITE.$categoryLevel1['menu_icon'];?>"  alt="<?php echo $categoryLevel1['title']; ?>" title="<?php echo $categoryLevel1['title']; ?>">
                                        </div>
                                 <?php endif; ?>     
                                <span class="level-2__a__span">
                                    <?php echo MG::contextEditor('category', $categoryLevel1['title'], $categoryLevel1["id"], "category"); ?>
                                </span>
                                <?php if (MG::getSetting('showCountInCat')=='true'):?>
                                    <span class="j-accordion-menu__count"><?php echo $categoryLevel1['insideProduct']?''.$categoryLevel1['insideProduct'].'':''; ?></span>
                                <?php endif;?>
                            </a>

                            <?php  if($noUl){$slider=''; continue;} ?>

                            <ul class="">
                                <?php foreach ($categoryLevel1['child'] as $categoryLevel2): ?>
                                <?php if ($categoryLevel2['invisible'] == "1") { continue; } ?>
                                <?php if (SITE.URL::getClearUri() === $categoryLevel2['link']) { $active = 'active'; } else { $active = ''; } ?>
                                <?php if (isset($categoryLevel2['child'])): ?>
                                <?php $slider = 'slider'; $noUl = 1; foreach($categoryLevel2['child'] as $categoryLevel3){$noUl *= $categoryLevel3['invisible']; } if($noUl){$slider='';}?>

                                <li class="level-3">
                                    <a class="level-3__a" href="<?php echo $categoryLevel2['link']; ?>">
                                        <?php if(!empty($categoryLevel2['menu_icon'])): ?>
                                            <div class="mg-cat-img">
                                            <img src="<?php echo SITE.$categoryLevel2['menu_icon'];?>"  alt="<?php echo $categoryLevel2['title']; ?>" title="<?php echo $categoryLevel2['title']; ?>">
                                            </div>
                                        <?php endif; ?>      
                                        <span class="level-3__a__span">
                                            <?php echo MG::contextEditor('category', $categoryLevel2['title'], $categoryLevel2["id"], "category"); ?>
                                        </span>
                                        <?php if (MG::getSetting('showCountInCat')=='true'):?>
                                            <span class="j-accordion-menu__count"><?php echo $categoryLevel2['insideProduct']?''.$categoryLevel2['insideProduct'].'':''; ?></span>
                                        <?php endif;?>
                                    </a>

                                    <?php  if($noUl){$slider=''; continue;} ?>
                                </li>

                                <?php else: ?>

                                <li class="level-3">
                                    <a class="level-3__a" href="<?php echo $categoryLevel2['link']; ?>">
                                        <?php if(!empty($categoryLevel2['menu_icon'])): ?>
                                            <div class="mg-cat-img">
                                            <img src="<?php echo SITE.$categoryLevel2['menu_icon'];?>"  alt="<?php echo $categoryLevel2['title']; ?>" title="<?php echo $categoryLevel2['title']; ?>">
                                            </div>
                                        <?php endif; ?>                                            
                                        <span class="level-3__a__span">
                                            <?php echo MG::contextEditor('category', $categoryLevel2['title'], $categoryLevel2["id"], "category"); ?>
                                        </span>
                                        <?php if (MG::getSetting('showCountInCat')=='true'):?>
                                            <span class="j-accordion-menu__count"><?php echo $categoryLevel2['insideProduct']?''.$categoryLevel2['insideProduct'].'':''; ?></span>
                                        <?php endif;?>
                                    </a>
                                </li>

                                <?php endif; ?>
                                <?php endforeach; ?>  
                            </ul>
                        </li>

                        <?php else: ?>

                        <li class="level-2">
                            <a class="level-2__a" href="<?php echo $categoryLevel1['link']; ?>">
                                 <?php if(!empty($categoryLevel1['menu_icon'])): ?>
                                    <div class="mg-cat-img">
                                    <img src="<?php echo SITE.$categoryLevel1['menu_icon'];?>"  alt="<?php echo $categoryLevel1['title']; ?>" title="<?php echo $categoryLevel1['title']; ?>">
                                     </div>
                                 <?php endif; ?>     
                                <span class="level-2__a__span">
                                    <?php echo MG::contextEditor('category', $categoryLevel1['title'], $categoryLevel1["id"], "category"); ?>
                                </span>
                                <?php if (MG::getSetting('showCountInCat')=='true'):?>
                                    <span class="j-accordion-menu__count"><?php echo $categoryLevel1['insideProduct']?''.$categoryLevel1['insideProduct'].'':''; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <?php else: ?>

                <li class="level-1">
                    <a class="level-1__a" href="<?php echo $category['link']; ?>">
						<?php if(!empty($category['menu_icon'])): ?>
                            <div class="c-nav__img c-catalog__img">
                                <img src="<?php echo SITE.$category['menu_icon'];?>" alt="<?php echo $category['title']; ?>" title="<?php echo $category['title']; ?>">
                            </div>
                        <?php endif; ?>                       
                        <span class="level-1__a__span">
                            <?php echo MG::contextEditor('category', $category['title'], $category["id"], "category"); ?>
                        </span>
                        <?php if (MG::getSetting('showCountInCat')=='true'):?>
                            <span class="j-accordion-menu__count"><?php echo $category['insideProduct']?''.$category['insideProduct'].'':''; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</div>