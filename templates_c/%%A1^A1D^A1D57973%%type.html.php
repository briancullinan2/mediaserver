<?php /* Smarty version 2.6.19, created on 2009-03-04 03:44:36
         compiled from C:%5Cwamp%5Cwww%5Cmediaserver%5Ctemplates%5Cdefault%5Ctype.html */ ?>
<div id="type">
	Get the list:
	<br />
	<form action="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
list.php" method="get">
		Type <select name="list">
			<?php $_from = $this->_tpl_vars['types']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['name'] => $this->_tpl_vars['type']):
?>
			<option value="<?php echo $this->_tpl_vars['name']; ?>
"><?php echo $this->_tpl_vars['type']['name']; ?>
</option>
			<?php endforeach; endif; unset($_from); ?>
		</select>
		<input type="submit" value="Go" />
	</form>
</div>