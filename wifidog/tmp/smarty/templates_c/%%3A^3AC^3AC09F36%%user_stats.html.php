<?php /* Smarty version 2.6.3, created on 2004-08-03 00:08:58
         compiled from user_stats.html */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'date_format', 'user_stats.html', 30, false),)), $this); ?>
<html>
<head>
<title>User Statistics</title>
</head>
<body>

<table>
<tr>
	<td><b>Online Status:</b></td>
	<td>
    <?php if ($this->_tpl_vars['userinfo']['online_status'] == ONLINE_STATUS_ONLINE): ?>
    <font color="#008000"><b>Online</b></font>
    <?php elseif ($this->_tpl_vars['userinfo']['online_status'] == ONLINE_STATUS_OFFLINE): ?>
    Offline
    <?php else: ?>
    Unknown
    <?php endif; ?>
    </td>
</tr>
<tr>
	<td><b>User email:</b></td>
	<td><?php echo $this->_tpl_vars['userinfo']['email']; ?>
</td>
</tr>
<tr>
	<td><b>User ID:</b></td>
	<td><?php echo $this->_tpl_vars['userinfo']['user_id']; ?>
</td>
</tr>
<tr>
	<td><b>Registered on:</b></td>
	<td><?php echo ((is_array($_tmp=$this->_tpl_vars['userinfo']['reg_date'])) ? $this->_run_mod_handler('date_format', true, $_tmp, "%Y/%m/%d") : smarty_modifier_date_format($_tmp, "%Y/%m/%d")); ?>
</td>
</tr>
<tr>
	<td><b>Account Status:</b></td>
	<td><?php echo $this->_tpl_vars['userinfo']['account_status_description']; ?>
</td>
</tr>
<tr>
	<td><b>Registered on:</b></td>
	<td><?php echo ((is_array($_tmp=$this->_tpl_vars['userinfo']['reg_date'])) ? $this->_run_mod_handler('date_format', true, $_tmp, "%Y/%m/%d") : smarty_modifier_date_format($_tmp, "%Y/%m/%d")); ?>
</td>
</tr>
</table>

<?php if ($this->_tpl_vars['connections']): ?>
<p><b>Connections:</b></p>

<table border="1">
<tr>
	<td><b>Date In</b></td>
	<td><b>Date Out</b></td>
	<td><b>Token status</b></td>
	<td><b>User's MAC</b></td>
	<td><b>User's IP</b></td>
	<td><b>Hotspot ID</b></td>
	<td><b>Hotspot IP</b></td>
	<td><b>Incoming traffic</b></td>
	<td><b>Outgoing traffic</b></td>
</tr>
<?php if (count($_from = (array)$this->_tpl_vars['connections'])):
    foreach ($_from as $this->_tpl_vars['connection']):
?>
<tr>
	<td><?php echo ((is_array($_tmp=$this->_tpl_vars['connection']['timestamp_in'])) ? $this->_run_mod_handler('date_format', true, $_tmp, "%Y/%m/%d %H:%M:%S") : smarty_modifier_date_format($_tmp, "%Y/%m/%d %H:%M:%S")); ?>
</td>
	<td><?php echo ((is_array($_tmp=$this->_tpl_vars['connection']['timestamp_out'])) ? $this->_run_mod_handler('date_format', true, $_tmp, "%Y/%m/%d %H:%M:%S") : smarty_modifier_date_format($_tmp, "%Y/%m/%d %H:%M:%S")); ?>
</td>
	<td><?php echo $this->_tpl_vars['connection']['token_status_description']; ?>
</td>
	<td><?php echo $this->_tpl_vars['connection']['user_mac']; ?>
</td>
	<td><?php echo $this->_tpl_vars['connection']['user_ip']; ?>
</td>
	<td><?php echo $this->_tpl_vars['connection']['hotspot_id']; ?>
</td>
	<td><?php echo $this->_tpl_vars['connection']['hotspot_ip']; ?>
</td>
	<td align="right"><?php echo $this->_tpl_vars['connection']['incoming']; ?>
</td>
	<td align="right"><?php echo $this->_tpl_vars['connection']['outgoing']; ?>
</td>
</tr>
<?php endforeach; unset($_from); endif; ?>
</table>
<?php else: ?>
<p><b>No connections from this user yet!</b></p>
<?php endif; ?>

</body>
</html>
