<?php /* Smarty version 2.6.19, created on 2009-03-04 03:44:36
         compiled from C:%5Cwamp%5Cwww%5Cmediaserver%5Ctemplates%5Cdefault%5Csearch.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'stripslashes', 'C:\\wamp\\www\\mediaserver\\templates\\default\\search.html', 28, false),array('modifier', 'htmlspecialchars', 'C:\\wamp\\www\\mediaserver\\templates\\default\\search.html', 28, false),)), $this); ?>
<?php 
$modules = $this->get_template_vars('modules');
$modules_named = array();
foreach($modules as $i => $module)
{
	$modules_named[] = constant($module . '::NAME');
}
array_multisort($modules_named, SORT_STRING, $modules);
$this->assign('modules', $modules);
$this->assign('modules_named', $modules_named);
 ?>
<?php if ($_REQUEST['detail'] < 4): ?>
	<div id="builder">
		Use this form to generate the list of files you would like to view:
		<br />
		<form name="search" action="<?php echo $this->_tpl_vars['search_str']; ?>
" method="get">
            <?php $_from = $_GET; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['v']):
?>
            <?php if ($this->_tpl_vars['k'] != 'search' && $this->_tpl_vars['k'] != 'cat'): ?>
                <input type="hidden" name="<?php echo $this->_tpl_vars['k']; ?>
" value="<?php echo $this->_tpl_vars['v']; ?>
" />
            <?php endif; ?>
            <?php endforeach; endif; unset($_from); ?>
			<select name="cat">
			<?php $_from = $this->_tpl_vars['modules']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['i'] => $this->_tpl_vars['module']):
?>
				<option value="<?php echo $this->_tpl_vars['module']; ?>
" <?php if (isset ( $_REQUEST['cat'] ) && $_REQUEST['cat'] == $this->_tpl_vars['module']): ?>selected<?php endif; ?>><?php echo $this->_tpl_vars['modules_named'][$this->_tpl_vars['i']]; ?>
</option>
			<?php endforeach; endif; unset($_from); ?>
			</select>
			<br />
			Contains <input name="search" type="text" value="<?php if (isset ( $_REQUEST['search'] )): ?><?php echo ((is_array($_tmp=((is_array($_tmp=$_REQUEST['search'])) ? $this->_run_mod_handler('stripslashes', true, $_tmp) : stripslashes($_tmp)))) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
<?php endif; ?>" /> (Enter keywords)
			<br />
			Submit to apply filter.
			<br />
			<input type="submit" value="Search" />
		</form>
	</div>
<?php elseif ($_REQUEST['detail'] >= 4): ?>
	<div id="builder">
		Use this form to generate the list of files you would like to view:
		<br />
		<form name="search" action="<?php echo $_SERVER['PHP_SELF']; ?>
" method="get">
			<select name="cat">
			<?php unset($this->_sections['module']);
$this->_sections['module']['name'] = 'module';
$this->_sections['module']['loop'] = is_array($_loop=$this->_tpl_vars['modules']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['module']['show'] = true;
$this->_sections['module']['max'] = $this->_sections['module']['loop'];
$this->_sections['module']['step'] = 1;
$this->_sections['module']['start'] = $this->_sections['module']['step'] > 0 ? 0 : $this->_sections['module']['loop']-1;
if ($this->_sections['module']['show']) {
    $this->_sections['module']['total'] = $this->_sections['module']['loop'];
    if ($this->_sections['module']['total'] == 0)
        $this->_sections['module']['show'] = false;
} else
    $this->_sections['module']['total'] = 0;
if ($this->_sections['module']['show']):

            for ($this->_sections['module']['index'] = $this->_sections['module']['start'], $this->_sections['module']['iteration'] = 1;
                 $this->_sections['module']['iteration'] <= $this->_sections['module']['total'];
                 $this->_sections['module']['index'] += $this->_sections['module']['step'], $this->_sections['module']['iteration']++):
$this->_sections['module']['rownum'] = $this->_sections['module']['iteration'];
$this->_sections['module']['index_prev'] = $this->_sections['module']['index'] - $this->_sections['module']['step'];
$this->_sections['module']['index_next'] = $this->_sections['module']['index'] + $this->_sections['module']['step'];
$this->_sections['module']['first']      = ($this->_sections['module']['iteration'] == 1);
$this->_sections['module']['last']       = ($this->_sections['module']['iteration'] == $this->_sections['module']['total']);
?>
				<?php $this->assign('module', $this->_tpl_vars['modules'][$this->_sections['module']['index']]); ?>
				<option value="<?php echo $this->_tpl_vars['modules'][$this->_sections['module']['index']]; ?>
" <?php if (isset ( $this->_tpl_vars['search']['cat'] ) && $this->_tpl_vars['search']['cat'] == $this->_tpl_vars['modules'][$this->_sections['module']['index']]): ?>selected<?php endif; ?>><?php 
					$module = $this->get_template_vars('module');
					echo constant($module . '::NAME');
				 ?></option>
			<?php endfor; endif; ?>
			</select>
			<br />
			Contains <input name="search" type="text" value="<?php if (isset ( $this->_tpl_vars['search']['search'] )): ?><?php echo $this->_tpl_vars['search']['search']; ?>
<?php endif; ?>" /> (Enter keywords)
			<br />
			Directory <input name="dir" type="text" value="<?php if (isset ( $this->_tpl_vars['search']['dir'] )): ?><?php echo $this->_tpl_vars['search']['dir']; ?>
<?php endif; ?>" /> (Directory filter)
			<br />
			Order by <select name="order_by">
				<?php unset($this->_sections['order_by']);
$this->_sections['order_by']['name'] = 'order_by';
$this->_sections['order_by']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['order_by']['show'] = true;
$this->_sections['order_by']['max'] = $this->_sections['order_by']['loop'];
$this->_sections['order_by']['step'] = 1;
$this->_sections['order_by']['start'] = $this->_sections['order_by']['step'] > 0 ? 0 : $this->_sections['order_by']['loop']-1;
if ($this->_sections['order_by']['show']) {
    $this->_sections['order_by']['total'] = $this->_sections['order_by']['loop'];
    if ($this->_sections['order_by']['total'] == 0)
        $this->_sections['order_by']['show'] = false;
} else
    $this->_sections['order_by']['total'] = 0;
if ($this->_sections['order_by']['show']):

            for ($this->_sections['order_by']['index'] = $this->_sections['order_by']['start'], $this->_sections['order_by']['iteration'] = 1;
                 $this->_sections['order_by']['iteration'] <= $this->_sections['order_by']['total'];
                 $this->_sections['order_by']['index'] += $this->_sections['order_by']['step'], $this->_sections['order_by']['iteration']++):
$this->_sections['order_by']['rownum'] = $this->_sections['order_by']['iteration'];
$this->_sections['order_by']['index_prev'] = $this->_sections['order_by']['index'] - $this->_sections['order_by']['step'];
$this->_sections['order_by']['index_next'] = $this->_sections['order_by']['index'] + $this->_sections['order_by']['step'];
$this->_sections['order_by']['first']      = ($this->_sections['order_by']['iteration'] == 1);
$this->_sections['order_by']['last']       = ($this->_sections['order_by']['iteration'] == $this->_sections['order_by']['total']);
?>
				<option value="<?php echo $this->_tpl_vars['columns'][$this->_sections['order_by']['index']]; ?>
" <?php if (isset ( $this->_tpl_vars['search']['order_by'] ) && $this->_tpl_vars['search']['order_by'] == $this->_tpl_vars['columns'][$this->_sections['order_by']['index']]): ?>selected<?php endif; ?>><?php echo $this->_tpl_vars['columns'][$this->_sections['order_by']['index']]; ?>
</option>
				<?php endfor; endif; ?>
			</select>
			<br />
			Submit to apply filter.
			<br />
			<input type="submit" value="Search" />
		</form>
	</div>
<?php endif; ?>