<?php echo $this->Html->docType(); ?>
<?php echo $this->Html->layout('meta'); ?>
<html>
	<head>
		<title><?php echo (isset($title_for_layout) && $title_for_layout) ? $title_for_layout : "Page"; ?></title>
		<?php echo $this->Html->charset('utf-8'); ?>
	</head>
	<body>
		<?php echo $this->Html->layout('css'); ?>

		<?php echo $this->Session->flash(); ?>
		<?php echo $content_for_layout; ?>

		<?php echo $this->Html->layout('js'); ?>
	</body>
</html>