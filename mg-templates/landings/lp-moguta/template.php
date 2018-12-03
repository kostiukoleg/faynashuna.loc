<!-- Файл template.php является каркасом шаблона, содержит основную верстку шаблона. -->
<!DOCTYPE html>
<html>
	<head>
		<?php mgMeta(); ?>
      	<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700" rel="stylesheet">
		<meta name="viewport" content="width=device-width">
	</head>
	<body <?php backgroundSite(); ?>>

			<?php if (!isCatalog() && !isIndex()) : ?>
			   
					<?php layout('content'); ?>
			   
			<?php endif; ?>

	</body>
</html>
