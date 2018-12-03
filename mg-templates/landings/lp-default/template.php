<!-- Файл template.php является каркасом шаблона, содержит основную верстку шаблона. -->
<!DOCTYPE html>
<html>
<head>
    <?php mgMeta(); ?>
    <meta name="viewport" content="width=device-width">
    <?php mgAddMeta('<script src="' . PATH_SITE_TEMPLATE . '/js/script.js"></script>'); ?>
</head>
<body <?php backgroundSite(); ?>>
<div class="wrapper <?php echo isIndex() ? 'main-page' : ''; echo isCatalog() && !isSearch() ? 'catalog-page' : ''; ?>">
    <!--Центральная часть сайта-->
    <div class="container">
        <?php if (!isCatalog() && !isIndex()) : ?>
            <div class="main-block">
                <?php layout('content'); ?>
            </div>
        <?php endif; ?>
    </div>
    <!--/Центральная часть сайта-->
</div>
<svg xmlns="http://www.w3.org/2000/svg" style="display: none">

    <symbol id="icon--arrow-left" viewBox="0 0 512 512">
        <path d="M354.1 512l59.8-59.7L217.6 256 413.9 59.7 354.1 0l-256 256"></path>
    </symbol>

    <symbol id="icon--arrow-right" viewBox="0 0 512 512">
        <path d="M157.9 0L98.1 59.7 294.4 256 98.1 452.3l59.8 59.7 256-256"></path>
    </symbol>
</svg>
</body>
</html>