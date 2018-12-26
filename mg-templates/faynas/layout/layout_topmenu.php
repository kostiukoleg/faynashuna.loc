
        <div class="left">
                <?php foreach($data['pages'] as $page):?>
                <?php if($page['invisible']=="1"){continue;}?>
                <?php if(URL::getUrl()==$page['link']||URL::getUrl()==$page['link'].'/'){$active = 'active';} else {$active = '';}?>
                <?php if(isset($page['child'])):?>

                <?php $slider = 'slider'; $noUl = 1; foreach($page['child'] as $pageLevel1){$noUl *= $pageLevel1['invisible']; } if($noUl){$slider='';}?>
                    <a class="mainleveltopunder" href="<?php echo $page['link']; ?>">
                            <?php echo MG::contextEditor('page', $page['title'], $page["id"], "page"); ?>
                    </a>
                    <span class="undermenuborder"></span>

                    <?php  if($noUl){$slider=''; continue;} ?>

                        <?php foreach($page['child'] as $pageLevel1):?>
                        <?php if($pageLevel1['invisible']=="1"){continue;}?>
                        <?php if(isset($pageLevel1['child'])):?>
                        <?php $slider = 'slider'; $noUl = 1; foreach($pageLevel1['child'] as $pageLevel2){$noUl *= $pageLevel2['invisible']; } if($noUl){$slider='';}?>

                            <a class="mainleveltopunder" href="<?php echo $pageLevel1['link']; ?>">
                                    <?php echo MG::contextEditor('page', $pageLevel1['title'], $pageLevel1["id"], "page"); ?>
                            </a>

                            <?php  if($noUl){$slider=''; continue;} ?>
                                <?php foreach($pageLevel1['child'] as $pageLevel2):?>
                                        <a class="mainleveltopunder" href="<?php echo $pageLevel2['link']; ?>">
                                            <?php echo MG::contextEditor('page', $pageLevel2['title'], $pageLevel2["id"], "page");?>
                                        </a>
                                <?php endforeach;?>

                        <?php else:?>
                            <a class="mainleveltopunder" href="<?php echo $pageLevel1['link']; ?>">
                                <?php echo MG::contextEditor('page', $pageLevel1['title'], $pageLevel1["id"], "page");  ?>
                            </a>
                        <?php endif;?>

                      <?php endforeach;?>

                <?php else:?>
                    <a class="mainleveltopunder" href="<?php echo $page['link']; ?>">
                        <?php echo MG::contextEditor('page', $page['title'], $page["id"], "page"); ?>
                    </a>
                <?php endif;?>

                <?php endforeach;?>
        </div>