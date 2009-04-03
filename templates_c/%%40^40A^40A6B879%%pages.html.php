<?php /* Smarty version 2.6.19, created on 2009-03-04 03:44:36
         compiled from C:%5Cwamp%5Cwww%5Cmediaserver%5Ctemplates%5Cdefault%5Cpages.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'cat', 'C:\\wamp\\www\\mediaserver\\templates\\default\\pages.html', 4, false),array('modifier', 'floor', 'C:\\wamp\\www\\mediaserver\\templates\\default\\pages.html', 23, false),)), $this); ?>
<?php $this->assign('page_count', $_REQUEST['limit']); ?>
<?php $this->assign('page_str', ''); ?>
<?php $_from = $_GET; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['v']):
?>
<?php if ($this->_tpl_vars['page_str'] != ''): ?><?php $this->assign('page_str', ((is_array($_tmp=$this->_tpl_vars['page_str'])) ? $this->_run_mod_handler('cat', true, $_tmp, '&amp;') : smarty_modifier_cat($_tmp, '&amp;'))); ?><?php endif; ?>
<?php if ($this->_tpl_vars['k'] != 'start'): ?>
<?php $this->assign('page_str', ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['page_str'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['k']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['k'])))) ? $this->_run_mod_handler('cat', true, $_tmp, '=') : smarty_modifier_cat($_tmp, '=')))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['v']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['v']))); ?>
<?php endif; ?>
<?php endforeach; endif; unset($_from); ?>
<?php $this->assign('page_str', ((is_array($_tmp=((is_array($_tmp=$_SERVER['PHP_SELF'])) ? $this->_run_mod_handler('cat', true, $_tmp, '?') : smarty_modifier_cat($_tmp, '?')))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['page_str']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['page_str']))); ?>
Page <?php if ($this->_tpl_vars['page'] > 0): ?>
		<?php if ($this->_tpl_vars['page'] > $this->_tpl_vars['page_count']): ?>
			<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=0">First</a>
			<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=<?php echo $this->_tpl_vars['page']-$this->_tpl_vars['page_count']; ?>
">Prev</a>
		<?php else: ?>
			<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=0">First</a>
		<?php endif; ?>
		|
	<?php endif; ?>
<?php $this->assign('page_int', $this->_tpl_vars['page']/$this->_tpl_vars['page_count']); ?>
<?php $this->assign('lower', $this->_tpl_vars['page_int']-8); ?>
<?php $this->assign('upper', $this->_tpl_vars['page_int']+8); ?>
<?php $this->assign('total_count', $this->_tpl_vars['total_count']-1); ?>
<?php $this->assign('pages', ((is_array($_tmp=$this->_tpl_vars['total_count']/$this->_tpl_vars['page_count'])) ? $this->_run_mod_handler('floor', true, $_tmp) : floor($_tmp))); ?>
<?php if ($this->_tpl_vars['lower'] < 0): ?><?php $this->assign('upper', $this->_tpl_vars['upper']-$this->_tpl_vars['lower']); ?><?php $this->assign('lower', 0); ?><?php endif; ?>
<?php if ($this->_tpl_vars['upper'] > $this->_tpl_vars['pages']): ?><?php $this->assign('upper', $this->_tpl_vars['pages']); ?><?php $this->assign('lower', $this->_tpl_vars['pages']-$this->_tpl_vars['page_count']-1); ?><?php endif; ?>
<?php if ($this->_tpl_vars['lower'] < 0): ?><?php $this->assign('lower', 0); ?><?php endif; ?>
<?php unset($this->_sections['page_select']);
$this->_sections['page_select']['name'] = 'page_select';
$this->_sections['page_select']['start'] = (int)$this->_tpl_vars['lower'];
$this->_sections['page_select']['loop'] = is_array($_loop=$this->_tpl_vars['upper']+1) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['page_select']['step'] = ((int)1) == 0 ? 1 : (int)1;
$this->_sections['page_select']['show'] = true;
$this->_sections['page_select']['max'] = $this->_sections['page_select']['loop'];
if ($this->_sections['page_select']['start'] < 0)
    $this->_sections['page_select']['start'] = max($this->_sections['page_select']['step'] > 0 ? 0 : -1, $this->_sections['page_select']['loop'] + $this->_sections['page_select']['start']);
else
    $this->_sections['page_select']['start'] = min($this->_sections['page_select']['start'], $this->_sections['page_select']['step'] > 0 ? $this->_sections['page_select']['loop'] : $this->_sections['page_select']['loop']-1);
if ($this->_sections['page_select']['show']) {
    $this->_sections['page_select']['total'] = min(ceil(($this->_sections['page_select']['step'] > 0 ? $this->_sections['page_select']['loop'] - $this->_sections['page_select']['start'] : $this->_sections['page_select']['start']+1)/abs($this->_sections['page_select']['step'])), $this->_sections['page_select']['max']);
    if ($this->_sections['page_select']['total'] == 0)
        $this->_sections['page_select']['show'] = false;
} else
    $this->_sections['page_select']['total'] = 0;
if ($this->_sections['page_select']['show']):

            for ($this->_sections['page_select']['index'] = $this->_sections['page_select']['start'], $this->_sections['page_select']['iteration'] = 1;
                 $this->_sections['page_select']['iteration'] <= $this->_sections['page_select']['total'];
                 $this->_sections['page_select']['index'] += $this->_sections['page_select']['step'], $this->_sections['page_select']['iteration']++):
$this->_sections['page_select']['rownum'] = $this->_sections['page_select']['iteration'];
$this->_sections['page_select']['index_prev'] = $this->_sections['page_select']['index'] - $this->_sections['page_select']['step'];
$this->_sections['page_select']['index_next'] = $this->_sections['page_select']['index'] + $this->_sections['page_select']['step'];
$this->_sections['page_select']['first']      = ($this->_sections['page_select']['iteration'] == 1);
$this->_sections['page_select']['last']       = ($this->_sections['page_select']['iteration'] == $this->_sections['page_select']['total']);
?>
	<?php if ($this->_sections['page_select']['index'] == $this->_tpl_vars['page_int']): ?>
	<b><?php echo $this->_tpl_vars['page_int']+1; ?>
</b>
	<?php else: ?>
	<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=<?php echo $this->_sections['page_select']['index']*$this->_tpl_vars['page_count']; ?>
"><?php echo $this->_sections['page_select']['index']+1; ?>
</a>
	<?php endif; ?>
<?php endfor; endif; ?>
<?php if ($this->_tpl_vars['page'] <= $this->_tpl_vars['total_count']-$this->_tpl_vars['page_count']): ?>
	|
	<?php $this->assign('last_page', ((is_array($_tmp=$this->_tpl_vars['total_count']/$this->_tpl_vars['page_count'])) ? $this->_run_mod_handler('floor', true, $_tmp) : floor($_tmp))); ?>
	<?php if ($this->_tpl_vars['page'] < $this->_tpl_vars['total_count']-$this->_tpl_vars['page_count']-$this->_tpl_vars['page_count']): ?>
		<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=<?php echo $this->_tpl_vars['page']+$this->_tpl_vars['page_count']; ?>
">Next</a>
		<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=<?php echo $this->_tpl_vars['last_page']*$this->_tpl_vars['page_count']; ?>
">Last</a>
	<?php else: ?>
		<a href="<?php echo $this->_tpl_vars['page_str']; ?>
&start=<?php echo $this->_tpl_vars['last_page']*$this->_tpl_vars['page_count']; ?>
">Last</a>
	<?php endif; ?>
<?php endif; ?>