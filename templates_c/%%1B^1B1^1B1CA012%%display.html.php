<?php /* Smarty version 2.6.19, created on 2009-03-04 03:44:37
         compiled from C:%5Cwamp%5Cwww%5Cmediaserver%5Ctemplates%5Cdefault%5Cdisplay.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'in_array', 'C:\\wamp\\www\\mediaserver\\templates\\default\\display.html', 14, false),)), $this); ?>
<?php if ($_REQUEST['detail'] < 3): ?>
<div id="display">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>
" method="post">
		Detail <select name="detail">
			<?php unset($this->_sections['detail']);
$this->_sections['detail']['name'] = 'detail';
$this->_sections['detail']['start'] = (int)0;
$this->_sections['detail']['loop'] = is_array($_loop=6) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['detail']['step'] = ((int)1) == 0 ? 1 : (int)1;
$this->_sections['detail']['show'] = true;
$this->_sections['detail']['max'] = $this->_sections['detail']['loop'];
if ($this->_sections['detail']['start'] < 0)
    $this->_sections['detail']['start'] = max($this->_sections['detail']['step'] > 0 ? 0 : -1, $this->_sections['detail']['loop'] + $this->_sections['detail']['start']);
else
    $this->_sections['detail']['start'] = min($this->_sections['detail']['start'], $this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] : $this->_sections['detail']['loop']-1);
if ($this->_sections['detail']['show']) {
    $this->_sections['detail']['total'] = min(ceil(($this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] - $this->_sections['detail']['start'] : $this->_sections['detail']['start']+1)/abs($this->_sections['detail']['step'])), $this->_sections['detail']['max']);
    if ($this->_sections['detail']['total'] == 0)
        $this->_sections['detail']['show'] = false;
} else
    $this->_sections['detail']['total'] = 0;
if ($this->_sections['detail']['show']):

            for ($this->_sections['detail']['index'] = $this->_sections['detail']['start'], $this->_sections['detail']['iteration'] = 1;
                 $this->_sections['detail']['iteration'] <= $this->_sections['detail']['total'];
                 $this->_sections['detail']['index'] += $this->_sections['detail']['step'], $this->_sections['detail']['iteration']++):
$this->_sections['detail']['rownum'] = $this->_sections['detail']['iteration'];
$this->_sections['detail']['index_prev'] = $this->_sections['detail']['index'] - $this->_sections['detail']['step'];
$this->_sections['detail']['index_next'] = $this->_sections['detail']['index'] + $this->_sections['detail']['step'];
$this->_sections['detail']['first']      = ($this->_sections['detail']['iteration'] == 1);
$this->_sections['detail']['last']       = ($this->_sections['detail']['iteration'] == $this->_sections['detail']['total']);
?>
			<option value="<?php echo $this->_sections['detail']['index']; ?>
" <?php if (isset ( $this->_tpl_vars['display']['detail'] ) && $this->_tpl_vars['display']['detail'] == $this->_sections['detail']['index']): ?>selected<?php endif; ?>><?php if ($this->_sections['detail']['index'] == 0): ?>None<?php else: ?><?php echo $this->_sections['detail']['index']; ?>
<?php endif; ?></option>
			<?php endfor; endif; ?>			
		</select><br />
		<input type="checkbox" name="column_Filepath" value="on" checked="checked" disabled="disabled" />Filepath<br />
		<?php if ($_REQUEST['detail'] == 2): ?>
			<?php unset($this->_sections['column']);
$this->_sections['column']['name'] = 'column';
$this->_sections['column']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['column']['show'] = true;
$this->_sections['column']['max'] = $this->_sections['column']['loop'];
$this->_sections['column']['step'] = 1;
$this->_sections['column']['start'] = $this->_sections['column']['step'] > 0 ? 0 : $this->_sections['column']['loop']-1;
if ($this->_sections['column']['show']) {
    $this->_sections['column']['total'] = $this->_sections['column']['loop'];
    if ($this->_sections['column']['total'] == 0)
        $this->_sections['column']['show'] = false;
} else
    $this->_sections['column']['total'] = 0;
if ($this->_sections['column']['show']):

            for ($this->_sections['column']['index'] = $this->_sections['column']['start'], $this->_sections['column']['iteration'] = 1;
                 $this->_sections['column']['iteration'] <= $this->_sections['column']['total'];
                 $this->_sections['column']['index'] += $this->_sections['column']['step'], $this->_sections['column']['iteration']++):
$this->_sections['column']['rownum'] = $this->_sections['column']['iteration'];
$this->_sections['column']['index_prev'] = $this->_sections['column']['index'] - $this->_sections['column']['step'];
$this->_sections['column']['index_next'] = $this->_sections['column']['index'] + $this->_sections['column']['step'];
$this->_sections['column']['first']      = ($this->_sections['column']['iteration'] == 1);
$this->_sections['column']['last']       = ($this->_sections['column']['iteration'] == $this->_sections['column']['total']);
?>
				<?php $this->assign('column_key', $this->_tpl_vars['columns'][$this->_sections['column']['index']]); ?>
				<?php if (isset ( $_SESSION['columns'] )): ?>
					<?php $this->assign('column_selected', ((is_array($_tmp=$this->_tpl_vars['column_key'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['columns']) : in_array($_tmp, $_SESSION['columns']))); ?>
				<?php else: ?>
					<?php $this->assign('column_selected', false); ?>
				<?php endif; ?>
				<?php if ($this->_tpl_vars['column_key'] != 'Filepath'): ?>
				<input type="checkbox" name="column[<?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
]" value="on" <?php if ($this->_tpl_vars['column_selected']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
<br />
				<?php endif; ?>
			<?php endfor; endif; ?>		
		<?php endif; ?>	
		Apply Settings.
		<input type="submit" name="display" value="Go" />
	</form>
</div>
<?php elseif ($_REQUEST['detail'] == 3): ?>
<div id="display">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>
" method="post">
		Detail <select name="detail">
			<?php unset($this->_sections['detail']);
$this->_sections['detail']['name'] = 'detail';
$this->_sections['detail']['start'] = (int)0;
$this->_sections['detail']['loop'] = is_array($_loop=6) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['detail']['step'] = ((int)1) == 0 ? 1 : (int)1;
$this->_sections['detail']['show'] = true;
$this->_sections['detail']['max'] = $this->_sections['detail']['loop'];
if ($this->_sections['detail']['start'] < 0)
    $this->_sections['detail']['start'] = max($this->_sections['detail']['step'] > 0 ? 0 : -1, $this->_sections['detail']['loop'] + $this->_sections['detail']['start']);
else
    $this->_sections['detail']['start'] = min($this->_sections['detail']['start'], $this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] : $this->_sections['detail']['loop']-1);
if ($this->_sections['detail']['show']) {
    $this->_sections['detail']['total'] = min(ceil(($this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] - $this->_sections['detail']['start'] : $this->_sections['detail']['start']+1)/abs($this->_sections['detail']['step'])), $this->_sections['detail']['max']);
    if ($this->_sections['detail']['total'] == 0)
        $this->_sections['detail']['show'] = false;
} else
    $this->_sections['detail']['total'] = 0;
if ($this->_sections['detail']['show']):

            for ($this->_sections['detail']['index'] = $this->_sections['detail']['start'], $this->_sections['detail']['iteration'] = 1;
                 $this->_sections['detail']['iteration'] <= $this->_sections['detail']['total'];
                 $this->_sections['detail']['index'] += $this->_sections['detail']['step'], $this->_sections['detail']['iteration']++):
$this->_sections['detail']['rownum'] = $this->_sections['detail']['iteration'];
$this->_sections['detail']['index_prev'] = $this->_sections['detail']['index'] - $this->_sections['detail']['step'];
$this->_sections['detail']['index_next'] = $this->_sections['detail']['index'] + $this->_sections['detail']['step'];
$this->_sections['detail']['first']      = ($this->_sections['detail']['iteration'] == 1);
$this->_sections['detail']['last']       = ($this->_sections['detail']['iteration'] == $this->_sections['detail']['total']);
?>
			<option value="<?php echo $this->_sections['detail']['index']; ?>
" <?php if (isset ( $this->_tpl_vars['display']['detail'] ) && $this->_tpl_vars['display']['detail'] == $this->_sections['detail']['index']): ?>selected<?php endif; ?>><?php if ($this->_sections['detail']['index'] == 0): ?>None<?php else: ?><?php echo $this->_sections['detail']['index']; ?>
<?php endif; ?></option>
			<?php endfor; endif; ?>			
		</select><br />
		<?php unset($this->_sections['column']);
$this->_sections['column']['name'] = 'column';
$this->_sections['column']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['column']['show'] = true;
$this->_sections['column']['max'] = $this->_sections['column']['loop'];
$this->_sections['column']['step'] = 1;
$this->_sections['column']['start'] = $this->_sections['column']['step'] > 0 ? 0 : $this->_sections['column']['loop']-1;
if ($this->_sections['column']['show']) {
    $this->_sections['column']['total'] = $this->_sections['column']['loop'];
    if ($this->_sections['column']['total'] == 0)
        $this->_sections['column']['show'] = false;
} else
    $this->_sections['column']['total'] = 0;
if ($this->_sections['column']['show']):

            for ($this->_sections['column']['index'] = $this->_sections['column']['start'], $this->_sections['column']['iteration'] = 1;
                 $this->_sections['column']['iteration'] <= $this->_sections['column']['total'];
                 $this->_sections['column']['index'] += $this->_sections['column']['step'], $this->_sections['column']['iteration']++):
$this->_sections['column']['rownum'] = $this->_sections['column']['iteration'];
$this->_sections['column']['index_prev'] = $this->_sections['column']['index'] - $this->_sections['column']['step'];
$this->_sections['column']['index_next'] = $this->_sections['column']['index'] + $this->_sections['column']['step'];
$this->_sections['column']['first']      = ($this->_sections['column']['iteration'] == 1);
$this->_sections['column']['last']       = ($this->_sections['column']['iteration'] == $this->_sections['column']['total']);
?>
			<?php $this->assign('column_key', $this->_tpl_vars['columns'][$this->_sections['column']['index']]); ?>
			<?php if (isset ( $_SESSION['columns'] )): ?>
				<?php $this->assign('column_selected', ((is_array($_tmp=$this->_tpl_vars['column_key'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['columns']) : in_array($_tmp, $_SESSION['columns']))); ?>
			<?php else: ?>
				<?php $this->assign('column_selected', false); ?>
			<?php endif; ?>
			<input type="checkbox" name="column[<?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
]" value="on" <?php if ($this->_tpl_vars['column_selected']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
<br />
		<?php endfor; endif; ?>		
		Apply Settings.
		<input type="submit" name="display" value="Go" />
	</form>
</div>
<?php elseif ($_REQUEST['detail'] == 4): ?>
<div id="display">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>
" method="post">
		<input type="hidden" name="off" value="_All_" />
		Detail <select name="detail">
			<?php unset($this->_sections['detail']);
$this->_sections['detail']['name'] = 'detail';
$this->_sections['detail']['start'] = (int)0;
$this->_sections['detail']['loop'] = is_array($_loop=6) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['detail']['step'] = ((int)1) == 0 ? 1 : (int)1;
$this->_sections['detail']['show'] = true;
$this->_sections['detail']['max'] = $this->_sections['detail']['loop'];
if ($this->_sections['detail']['start'] < 0)
    $this->_sections['detail']['start'] = max($this->_sections['detail']['step'] > 0 ? 0 : -1, $this->_sections['detail']['loop'] + $this->_sections['detail']['start']);
else
    $this->_sections['detail']['start'] = min($this->_sections['detail']['start'], $this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] : $this->_sections['detail']['loop']-1);
if ($this->_sections['detail']['show']) {
    $this->_sections['detail']['total'] = min(ceil(($this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] - $this->_sections['detail']['start'] : $this->_sections['detail']['start']+1)/abs($this->_sections['detail']['step'])), $this->_sections['detail']['max']);
    if ($this->_sections['detail']['total'] == 0)
        $this->_sections['detail']['show'] = false;
} else
    $this->_sections['detail']['total'] = 0;
if ($this->_sections['detail']['show']):

            for ($this->_sections['detail']['index'] = $this->_sections['detail']['start'], $this->_sections['detail']['iteration'] = 1;
                 $this->_sections['detail']['iteration'] <= $this->_sections['detail']['total'];
                 $this->_sections['detail']['index'] += $this->_sections['detail']['step'], $this->_sections['detail']['iteration']++):
$this->_sections['detail']['rownum'] = $this->_sections['detail']['iteration'];
$this->_sections['detail']['index_prev'] = $this->_sections['detail']['index'] - $this->_sections['detail']['step'];
$this->_sections['detail']['index_next'] = $this->_sections['detail']['index'] + $this->_sections['detail']['step'];
$this->_sections['detail']['first']      = ($this->_sections['detail']['iteration'] == 1);
$this->_sections['detail']['last']       = ($this->_sections['detail']['iteration'] == $this->_sections['detail']['total']);
?>
			<option value="<?php echo $this->_sections['detail']['index']; ?>
" <?php if (isset ( $this->_tpl_vars['display']['detail'] ) && $this->_tpl_vars['display']['detail'] == $this->_sections['detail']['index']): ?>selected<?php endif; ?>><?php if ($this->_sections['detail']['index'] == 0): ?>None<?php else: ?><?php echo $this->_sections['detail']['index']; ?>
<?php endif; ?></option>
			<?php endfor; endif; ?>			
		</select><br />
		Count <select name="count">
			<?php unset($this->_sections['count']);
$this->_sections['count']['name'] = 'count';
$this->_sections['count']['start'] = (int)5;
$this->_sections['count']['loop'] = is_array($_loop=51) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['count']['step'] = ((int)5) == 0 ? 1 : (int)5;
$this->_sections['count']['show'] = true;
$this->_sections['count']['max'] = $this->_sections['count']['loop'];
if ($this->_sections['count']['start'] < 0)
    $this->_sections['count']['start'] = max($this->_sections['count']['step'] > 0 ? 0 : -1, $this->_sections['count']['loop'] + $this->_sections['count']['start']);
else
    $this->_sections['count']['start'] = min($this->_sections['count']['start'], $this->_sections['count']['step'] > 0 ? $this->_sections['count']['loop'] : $this->_sections['count']['loop']-1);
if ($this->_sections['count']['show']) {
    $this->_sections['count']['total'] = min(ceil(($this->_sections['count']['step'] > 0 ? $this->_sections['count']['loop'] - $this->_sections['count']['start'] : $this->_sections['count']['start']+1)/abs($this->_sections['count']['step'])), $this->_sections['count']['max']);
    if ($this->_sections['count']['total'] == 0)
        $this->_sections['count']['show'] = false;
} else
    $this->_sections['count']['total'] = 0;
if ($this->_sections['count']['show']):

            for ($this->_sections['count']['index'] = $this->_sections['count']['start'], $this->_sections['count']['iteration'] = 1;
                 $this->_sections['count']['iteration'] <= $this->_sections['count']['total'];
                 $this->_sections['count']['index'] += $this->_sections['count']['step'], $this->_sections['count']['iteration']++):
$this->_sections['count']['rownum'] = $this->_sections['count']['iteration'];
$this->_sections['count']['index_prev'] = $this->_sections['count']['index'] - $this->_sections['count']['step'];
$this->_sections['count']['index_next'] = $this->_sections['count']['index'] + $this->_sections['count']['step'];
$this->_sections['count']['first']      = ($this->_sections['count']['iteration'] == 1);
$this->_sections['count']['last']       = ($this->_sections['count']['iteration'] == $this->_sections['count']['total']);
?>
			<option value="<?php echo $this->_sections['count']['index']; ?>
" <?php if (isset ( $this->_tpl_vars['display']['count'] ) && $this->_tpl_vars['display']['count'] == $this->_sections['count']['index']): ?>selected<?php endif; ?>><?php echo $this->_sections['count']['index']; ?>
</option>
			<?php endfor; endif; ?>			
		</select><br />
		Order <select name="order">
			<?php unset($this->_sections['order']);
$this->_sections['order']['name'] = 'order';
$this->_sections['order']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['order']['show'] = true;
$this->_sections['order']['max'] = $this->_sections['order']['loop'];
$this->_sections['order']['step'] = 1;
$this->_sections['order']['start'] = $this->_sections['order']['step'] > 0 ? 0 : $this->_sections['order']['loop']-1;
if ($this->_sections['order']['show']) {
    $this->_sections['order']['total'] = $this->_sections['order']['loop'];
    if ($this->_sections['order']['total'] == 0)
        $this->_sections['order']['show'] = false;
} else
    $this->_sections['order']['total'] = 0;
if ($this->_sections['order']['show']):

            for ($this->_sections['order']['index'] = $this->_sections['order']['start'], $this->_sections['order']['iteration'] = 1;
                 $this->_sections['order']['iteration'] <= $this->_sections['order']['total'];
                 $this->_sections['order']['index'] += $this->_sections['order']['step'], $this->_sections['order']['iteration']++):
$this->_sections['order']['rownum'] = $this->_sections['order']['iteration'];
$this->_sections['order']['index_prev'] = $this->_sections['order']['index'] - $this->_sections['order']['step'];
$this->_sections['order']['index_next'] = $this->_sections['order']['index'] + $this->_sections['order']['step'];
$this->_sections['order']['first']      = ($this->_sections['order']['iteration'] == 1);
$this->_sections['order']['last']       = ($this->_sections['order']['iteration'] == $this->_sections['order']['total']);
?>
			<option value="<?php echo $this->_tpl_vars['columns'][$this->_sections['order']['index']]; ?>
" <?php if (isset ( $this->_tpl_vars['display']['order'] ) && $this->_tpl_vars['display']['order'] == $this->_tpl_vars['columns'][$this->_sections['order']['index']]): ?>selected<?php endif; ?>><?php echo $this->_tpl_vars['columns'][$this->_sections['order']['index']]; ?>
</option>
			<?php endfor; endif; ?>
		</select>
		<br />
		<?php unset($this->_sections['column']);
$this->_sections['column']['name'] = 'column';
$this->_sections['column']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['column']['show'] = true;
$this->_sections['column']['max'] = $this->_sections['column']['loop'];
$this->_sections['column']['step'] = 1;
$this->_sections['column']['start'] = $this->_sections['column']['step'] > 0 ? 0 : $this->_sections['column']['loop']-1;
if ($this->_sections['column']['show']) {
    $this->_sections['column']['total'] = $this->_sections['column']['loop'];
    if ($this->_sections['column']['total'] == 0)
        $this->_sections['column']['show'] = false;
} else
    $this->_sections['column']['total'] = 0;
if ($this->_sections['column']['show']):

            for ($this->_sections['column']['index'] = $this->_sections['column']['start'], $this->_sections['column']['iteration'] = 1;
                 $this->_sections['column']['iteration'] <= $this->_sections['column']['total'];
                 $this->_sections['column']['index'] += $this->_sections['column']['step'], $this->_sections['column']['iteration']++):
$this->_sections['column']['rownum'] = $this->_sections['column']['iteration'];
$this->_sections['column']['index_prev'] = $this->_sections['column']['index'] - $this->_sections['column']['step'];
$this->_sections['column']['index_next'] = $this->_sections['column']['index'] + $this->_sections['column']['step'];
$this->_sections['column']['first']      = ($this->_sections['column']['iteration'] == 1);
$this->_sections['column']['last']       = ($this->_sections['column']['iteration'] == $this->_sections['column']['total']);
?>
			<?php $this->assign('column_key', $this->_tpl_vars['columns'][$this->_sections['column']['index']]); ?>
			<?php if (isset ( $_SESSION['columns'] )): ?>
				<?php $this->assign('column_selected', ((is_array($_tmp=$this->_tpl_vars['column_key'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['columns']) : in_array($_tmp, $_SESSION['columns']))); ?>
			<?php else: ?>
				<?php $this->assign('column_selected', false); ?>
			<?php endif; ?>
			<input type="checkbox" name="columns_on[]" value="<?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
" <?php if ($this->_tpl_vars['column_selected']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
<br />
		<?php endfor; endif; ?>		
		Apply Settings.
		<input type="submit" name="display" value="Go" />
	</form>
</div>
<?php elseif ($_REQUEST['detail'] == 5): ?>
<div id="display">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>
" method="post">
		<input type="hidden" name="off" value="_All_" />
		Detail <select name="detail">
			<?php unset($this->_sections['detail']);
$this->_sections['detail']['name'] = 'detail';
$this->_sections['detail']['start'] = (int)0;
$this->_sections['detail']['loop'] = is_array($_loop=6) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['detail']['step'] = ((int)1) == 0 ? 1 : (int)1;
$this->_sections['detail']['show'] = true;
$this->_sections['detail']['max'] = $this->_sections['detail']['loop'];
if ($this->_sections['detail']['start'] < 0)
    $this->_sections['detail']['start'] = max($this->_sections['detail']['step'] > 0 ? 0 : -1, $this->_sections['detail']['loop'] + $this->_sections['detail']['start']);
else
    $this->_sections['detail']['start'] = min($this->_sections['detail']['start'], $this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] : $this->_sections['detail']['loop']-1);
if ($this->_sections['detail']['show']) {
    $this->_sections['detail']['total'] = min(ceil(($this->_sections['detail']['step'] > 0 ? $this->_sections['detail']['loop'] - $this->_sections['detail']['start'] : $this->_sections['detail']['start']+1)/abs($this->_sections['detail']['step'])), $this->_sections['detail']['max']);
    if ($this->_sections['detail']['total'] == 0)
        $this->_sections['detail']['show'] = false;
} else
    $this->_sections['detail']['total'] = 0;
if ($this->_sections['detail']['show']):

            for ($this->_sections['detail']['index'] = $this->_sections['detail']['start'], $this->_sections['detail']['iteration'] = 1;
                 $this->_sections['detail']['iteration'] <= $this->_sections['detail']['total'];
                 $this->_sections['detail']['index'] += $this->_sections['detail']['step'], $this->_sections['detail']['iteration']++):
$this->_sections['detail']['rownum'] = $this->_sections['detail']['iteration'];
$this->_sections['detail']['index_prev'] = $this->_sections['detail']['index'] - $this->_sections['detail']['step'];
$this->_sections['detail']['index_next'] = $this->_sections['detail']['index'] + $this->_sections['detail']['step'];
$this->_sections['detail']['first']      = ($this->_sections['detail']['iteration'] == 1);
$this->_sections['detail']['last']       = ($this->_sections['detail']['iteration'] == $this->_sections['detail']['total']);
?>
			<option value="<?php echo $this->_sections['detail']['index']; ?>
" <?php if (isset ( $this->_tpl_vars['display']['detail'] ) && $this->_tpl_vars['display']['detail'] == $this->_sections['detail']['index']): ?>selected<?php endif; ?>><?php if ($this->_sections['detail']['index'] == 0): ?>None<?php else: ?><?php echo $this->_sections['detail']['index']; ?>
<?php endif; ?></option>
			<?php endfor; endif; ?>			
		</select><br />
		Count <select name="count">
			<?php unset($this->_sections['count']);
$this->_sections['count']['name'] = 'count';
$this->_sections['count']['start'] = (int)5;
$this->_sections['count']['loop'] = is_array($_loop=51) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['count']['step'] = ((int)5) == 0 ? 1 : (int)5;
$this->_sections['count']['show'] = true;
$this->_sections['count']['max'] = $this->_sections['count']['loop'];
if ($this->_sections['count']['start'] < 0)
    $this->_sections['count']['start'] = max($this->_sections['count']['step'] > 0 ? 0 : -1, $this->_sections['count']['loop'] + $this->_sections['count']['start']);
else
    $this->_sections['count']['start'] = min($this->_sections['count']['start'], $this->_sections['count']['step'] > 0 ? $this->_sections['count']['loop'] : $this->_sections['count']['loop']-1);
if ($this->_sections['count']['show']) {
    $this->_sections['count']['total'] = min(ceil(($this->_sections['count']['step'] > 0 ? $this->_sections['count']['loop'] - $this->_sections['count']['start'] : $this->_sections['count']['start']+1)/abs($this->_sections['count']['step'])), $this->_sections['count']['max']);
    if ($this->_sections['count']['total'] == 0)
        $this->_sections['count']['show'] = false;
} else
    $this->_sections['count']['total'] = 0;
if ($this->_sections['count']['show']):

            for ($this->_sections['count']['index'] = $this->_sections['count']['start'], $this->_sections['count']['iteration'] = 1;
                 $this->_sections['count']['iteration'] <= $this->_sections['count']['total'];
                 $this->_sections['count']['index'] += $this->_sections['count']['step'], $this->_sections['count']['iteration']++):
$this->_sections['count']['rownum'] = $this->_sections['count']['iteration'];
$this->_sections['count']['index_prev'] = $this->_sections['count']['index'] - $this->_sections['count']['step'];
$this->_sections['count']['index_next'] = $this->_sections['count']['index'] + $this->_sections['count']['step'];
$this->_sections['count']['first']      = ($this->_sections['count']['iteration'] == 1);
$this->_sections['count']['last']       = ($this->_sections['count']['iteration'] == $this->_sections['count']['total']);
?>
			<option value="<?php echo $this->_sections['count']['index']; ?>
" <?php if (isset ( $this->_tpl_vars['display']['count'] ) && $this->_tpl_vars['display']['count'] == $this->_sections['count']['index']): ?>selected<?php endif; ?>><?php echo $this->_sections['count']['index']; ?>
</option>
			<?php endfor; endif; ?>			
		</select><br />
		Order <select name="order">
			<?php unset($this->_sections['order']);
$this->_sections['order']['name'] = 'order';
$this->_sections['order']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['order']['show'] = true;
$this->_sections['order']['max'] = $this->_sections['order']['loop'];
$this->_sections['order']['step'] = 1;
$this->_sections['order']['start'] = $this->_sections['order']['step'] > 0 ? 0 : $this->_sections['order']['loop']-1;
if ($this->_sections['order']['show']) {
    $this->_sections['order']['total'] = $this->_sections['order']['loop'];
    if ($this->_sections['order']['total'] == 0)
        $this->_sections['order']['show'] = false;
} else
    $this->_sections['order']['total'] = 0;
if ($this->_sections['order']['show']):

            for ($this->_sections['order']['index'] = $this->_sections['order']['start'], $this->_sections['order']['iteration'] = 1;
                 $this->_sections['order']['iteration'] <= $this->_sections['order']['total'];
                 $this->_sections['order']['index'] += $this->_sections['order']['step'], $this->_sections['order']['iteration']++):
$this->_sections['order']['rownum'] = $this->_sections['order']['iteration'];
$this->_sections['order']['index_prev'] = $this->_sections['order']['index'] - $this->_sections['order']['step'];
$this->_sections['order']['index_next'] = $this->_sections['order']['index'] + $this->_sections['order']['step'];
$this->_sections['order']['first']      = ($this->_sections['order']['iteration'] == 1);
$this->_sections['order']['last']       = ($this->_sections['order']['iteration'] == $this->_sections['order']['total']);
?>
			<option value="<?php echo $this->_tpl_vars['columns'][$this->_sections['order']['index']]; ?>
" <?php if (isset ( $this->_tpl_vars['display']['order'] ) && $this->_tpl_vars['display']['order'] == $this->_tpl_vars['columns'][$this->_sections['order']['index']]): ?>selected<?php endif; ?>><?php echo $this->_tpl_vars['columns'][$this->_sections['order']['index']]; ?>
</option>
			<?php endfor; endif; ?>
		</select>
		<br />
		<?php unset($this->_sections['column']);
$this->_sections['column']['name'] = 'column';
$this->_sections['column']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['column']['show'] = true;
$this->_sections['column']['max'] = $this->_sections['column']['loop'];
$this->_sections['column']['step'] = 1;
$this->_sections['column']['start'] = $this->_sections['column']['step'] > 0 ? 0 : $this->_sections['column']['loop']-1;
if ($this->_sections['column']['show']) {
    $this->_sections['column']['total'] = $this->_sections['column']['loop'];
    if ($this->_sections['column']['total'] == 0)
        $this->_sections['column']['show'] = false;
} else
    $this->_sections['column']['total'] = 0;
if ($this->_sections['column']['show']):

            for ($this->_sections['column']['index'] = $this->_sections['column']['start'], $this->_sections['column']['iteration'] = 1;
                 $this->_sections['column']['iteration'] <= $this->_sections['column']['total'];
                 $this->_sections['column']['index'] += $this->_sections['column']['step'], $this->_sections['column']['iteration']++):
$this->_sections['column']['rownum'] = $this->_sections['column']['iteration'];
$this->_sections['column']['index_prev'] = $this->_sections['column']['index'] - $this->_sections['column']['step'];
$this->_sections['column']['index_next'] = $this->_sections['column']['index'] + $this->_sections['column']['step'];
$this->_sections['column']['first']      = ($this->_sections['column']['iteration'] == 1);
$this->_sections['column']['last']       = ($this->_sections['column']['iteration'] == $this->_sections['column']['total']);
?>
			<?php $this->assign('column_key', $this->_tpl_vars['columns'][$this->_sections['column']['index']]); ?>
			<?php if (isset ( $_SESSION['columns'] )): ?>
				<?php $this->assign('column_selected', ((is_array($_tmp=$this->_tpl_vars['column_key'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['columns']) : in_array($_tmp, $_SESSION['columns']))); ?>
			<?php else: ?>
				<?php $this->assign('column_selected', false); ?>
			<?php endif; ?>
			<input type="checkbox" name="columns_on[]" value="<?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
" <?php if ($this->_tpl_vars['column_selected']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_tpl_vars['columns'][$this->_sections['column']['index']]; ?>
<br />
		<?php endfor; endif; ?>		
		Apply Settings.
		<input type="submit" name="display" value="Go" />
	</form>
</div>
<?php endif; ?>