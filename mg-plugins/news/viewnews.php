<h1 class="newsheader">Новости нашей компании</h1>
<a href="<?php echo SITE."/news/feed";?>" title="rss" class="rss">Подписаться на RSS</a>
<?php
MG::enableTemplate();
MG::titlePage('Новости');
mgAddMeta('<link href="mg-plugins/news/css/style.css" rel="stylesheet" type="text/css">');
if (class_exists('PluginNews')) {
  PluginNews::runNews(3);
} else {
   echo "Плагин новостей не подключен!";
}

