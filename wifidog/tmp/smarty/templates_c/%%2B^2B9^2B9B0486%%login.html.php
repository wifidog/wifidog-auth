<?php /* Smarty version 2.6.3, created on 2004-08-02 23:40:14
         compiled from local_content/default/login.html */ ?>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['header_file'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
      <div class='content'>
	<h1>Greetings! Welcome to<br clear=all>
	  <img src='<?php echo $this->_tpl_vars['hotspot_logo_url']; ?>
' alt='<?php echo $this->_tpl_vars['hotspot_name']; ?>
 logo'><br clear=all>
	  <?php echo $this->_tpl_vars['hotspot_name']; ?>

	  
	</h1>
	</div>
      
      <div class='content'>	
	<form method="post">
	  <input type="hidden" name="gw_address" value="<?php echo $this->_tpl_vars['gw_address']; ?>
">
	  <input type="hidden" name="gw_port" value="<?php echo $this->_tpl_vars['gw_port']; ?>
">
	  <input type="hidden" name="gw_id" value="<?php echo $this->_tpl_vars['gw_id']; ?>
">
	  <p class=warning><?php echo $this->_tpl_vars['login_failed_message']; ?>
</p>
	<table>
	  <tr>
	    <td>Username:</td>
	    <td><input type="text" name="user" value="<?php echo $this->_tpl_vars['previous_username']; ?>
"></td>
	    <td rowspan=3><img src='<?php echo $this->_tpl_vars['wifidog_logo_url']; ?>
' alt='WifiDog logo'></td>
	  </tr>
	  <tr>
	    <td>Password:</td>
	    <td><input type="password" name="pass" value="<?php echo $this->_tpl_vars['previous_password']; ?>
"></td>
	  </tr>
	  <tr>
	    <td></td>
	    <td><input type="submit" value='Login'></td>
</tr>
	</table>
	<p><a href='<?php echo $this->_tpl_vars['user_management_url']; ?>
'>Help me</a> create a free account on <?php echo $this->_tpl_vars['hotspot_network_name']; ?>
 or recover from a lost username or password.</p>
      </form>
      </div>
      <div class='content'>
	<h2><?php echo $this->_tpl_vars['hotspot_name']; ?>
 is part of <br clear=all>
	  <img src='<?php echo $this->_tpl_vars['network_logo_url']; ?>
' alt='<?php echo $this->_tpl_vars['hotspot_network_name']; ?>
 logo'><br clear=all>
	  <a href='<?php echo $this->_tpl_vars['hotspot_network_url']; ?>
'><?php echo $this->_tpl_vars['hotspot_network_name']; ?>
</a>
	  
	</h2>

      </div>

      <div id='navLeft'>
<?php echo $this->_tpl_vars['user_management_menu']; ?>

      </div>
      <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['footer_file'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>