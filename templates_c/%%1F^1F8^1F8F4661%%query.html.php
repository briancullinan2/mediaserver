<?php /* Smarty version 2.6.19, created on 2009-03-04 03:44:36
         compiled from C:%5Cwamp%5Cwww%5Cmediaserver%5Ctemplates%5Cdefault%5Cquery.html */ ?>
<?php if ($_REQUEST['detail'] < 5): ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @HTML_NAME; ?>
: Query</title>
<?php if ($_REQUEST['detail'] == 3): ?>
<?php echo '
<style>
span.column {
	white-space:pre;
	font-family:"Courier New", Courier, monospace;
}

</style>
'; ?>

<?php elseif ($_REQUEST['detail'] == 4): ?>
<?php echo '
<style>
table.select_list {
	white-space:nowrap;
}

table.select_list thead {
	font-weight:bold;
}

table.select_list thead a {
	text-decoration:none;
}

</style>
'; ?>

<?php endif; ?>
</head>

<body>
<span style="font-weight:bold; color:#F00;"><?php echo $this->_tpl_vars['error']; ?>
</span>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_SEARCH'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_SELECT'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_TYPE'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_DISPLAY'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
</body>
</html>
<?php elseif ($_REQUEST['detail'] == 5): ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Media Server Query</title>
</head>

<body>
<span style="font-weight:bold; color:#F00;"><?php echo $this->_tpl_vars['error']; ?>
</span>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_ADDRESS'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_SEARCH'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_SELECT'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_TYPE'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_DISPLAY'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
</body>
</html>
<?php endif; ?>
