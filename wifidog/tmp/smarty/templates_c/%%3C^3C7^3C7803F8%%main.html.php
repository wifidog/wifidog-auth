<?php /* Smarty version 2.6.3, created on 2004-08-03 00:08:55
         compiled from main.html */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'html_options', 'main.html', 13, false),)), $this); ?>
<html>
<head>
<title>Admin Interface</title>
</head>
<body>

<form method="post">
<table>
<tr>
	<td><b>Users:</b></td>
	<td>
	<select name='user_id'>
		<?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['users_array']), $this);?>

	</select>
	</td>
</tr>
<tr>
	<td></td>
	<td><input type="submit"></td>
</tr>
</form>

</body>
</html>
