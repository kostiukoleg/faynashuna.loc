<?php mgSEO($data); $prodIds = array(); $propTable = array(); ?>
<?php if (class_exists('BreadCrumbs')): ?>[brcr]<?php endif; ?>
<div class="mg-compare-products">
<div class="title-panel clearfix">
    <h1 class="new-products-title"><?php echo lang('compareProduct'); ?></h1>
</div>

<!-- compare - start -->
<?php if(!empty($data['catalogItems'])){ ?>
<div class="compare-content">
    <a href="<?php echo SITE; ?>/catalog" class="more-products">
        <span class="info">
            <img src="<?php echo SITE; ?>/mg-templates/faynas/images/add-icon.png" alt="" width="150">
            <span class="text">Добавить к сравнению</span>
        </span>
    </a>
    <!-- top - start -->
    <div class="mg-compare-left-side">
        <?php if(!empty($_SESSION['compareList'])){ ?>

        <div class="mg-category-list-compare">
            <a class="mg-clear-compared-products" href="<?php echo SITE ?>/compare?delCompare=1" >
                <?php echo lang('compareClean'); ?>
            </a>&nbsp;
            <a class="mg-clear-compared-products" href="<?php echo SITE ?>">
                <?php echo lang('compareBack'); ?>
            </a>
            <?php if(MG::getSetting('compareCategory')!='true'){ ?>
            <form class="c-form c-form--width">
                <select name="viewCategory" onChange="this.form.submit()">
                    <?php foreach($data['arrCategoryTitle'] as $id => $value): ?>
                    <option value ='<?php echo $id ?>' <?php
                        if($_GET['viewCategory']==$id){
                        echo "selected=selected";
                        }
                    ?> ><?php echo $value ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <!-- top - end -->

    <!-- center block - start -->
    <div class="mg-compare-center" id="doublescroll">
        <!-- items - start -->
        <div class="mg-compare-product-wrapper">
            <div class="mg-inner-scroll">
                <?php if(!empty($data['catalogItems'])){ foreach($data['catalogItems'] as $item){ ?>
                    <div class="mg-compare-product product-wrapper" itemscope itemtype="http://schema.org/Product">
                        <div class="remove-block">
                            <a class="mp-remove-compared-product" href="<?php echo SITE ?>/compare?delCompareProductId=<?php echo $item['id'] ?>">
                            Удалить
                            </a>
                        </div>
                        <div class="mg-compare-product-inner">
                            <div class="product-image">
                                <a class="" href="<?php echo $item['link'] ?>">
                                    <?php echo mgImageProduct($item); ?>
                                </a>
                            </div>
                            <div class="product-name">
                                <a class="" href="<?php echo $item['link'] ?>" itemprop="name" content="<?php echo $item["title"] ?>">
                                    <?php echo $item['title'] ?>
                                </a>
                            </div>
                            <div class="product-footer clearfix">
                                <div class="product-price">
                                    <?php if($item["old_price"]!=""): ?>
                                        <span class="product-old-price" <?php echo (!$item['old_price'])?'style="display:none"':'' ?>>
                                            <?php echo str_replace(' ', '', trim($item['old_price']))." ".$item['currency']; ?>
                                        </span>
                                    <?php endif; ?>
                                        <?php echo $item['price'] ?> <?php echo $item['currency']; ?>
                                </div>
                            </div>
                        </div>
                        <?php echo $item['propertyForm'] ?>
                        <?php foreach($item['stringsProperties'] as $key => $val){ $propTable[$key][$item['id']] = $val; } ?>
                    </div>
                <?php $prodIds[] = $item['id']; } } ?>
            </div>
        </div>
        <!-- items - end -->

        <?php foreach($propTable as $key => $prop){ foreach($prodIds as $id){ if(empty($prop[$id])){ $propTable[$key][$id] = '-'; ksort($propTable[$key]); } } } ?>

        <!-- right table - start -->
        <div class="mg-compare-fake-table-right">
            <?php foreach($propTable as $key => $prop){ ?>
            <div class="mg-compare-fake-table-row">
                <?php foreach($prop as $prodId => $val){ ?>
                <div class="mg-compare-fake-table-cell">
                    <?php echo $val ?>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
        <!-- right table - end -->

    </div>
    <!-- center block - end -->

    <!-- left table - start -->
    <div class="mg-compare-fake-table">
        <div class="mg-compare-fake-table-left <?php echo $data['moreThanThree'] ?>">
            <?php foreach($propTable as $key => $prop){ ?>
            <div class="mg-compare-fake-table-cell <?php if(trim($data['property'][$key])!=='') : ?>with-tooltip<?php endif; ?>">
                <?php if(trim($data['property'][$key])!=='') : ?>
                    <div class="mg-tooltip">?<div class="mg-tooltip-content" style="display:none;"><?php echo $data['property'][$key] ?></div></div>
                <?php endif; ?>
                <div class="compare-text" title="<?php echo $key ?>">
                    <?php echo $key ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <!-- left table - end -->
</div>
<?php } else { ?>
<div class="alert-info"><?php echo lang('compareProductEmpty'); ?></div>
<?php } ?>
<!-- compare - end -->
</div>