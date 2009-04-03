<?php /* Smarty version 2.6.19, created on 2009-03-09 16:31:07
         compiled from /var/www/mediaserver/templates/default/select.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'intval', '/var/www/mediaserver/templates/default/select.html', 11, false),array('modifier', 'count', '/var/www/mediaserver/templates/default/select.html', 12, false),array('modifier', 'split', '/var/www/mediaserver/templates/default/select.html', 20, false),array('modifier', 'in_array', '/var/www/mediaserver/templates/default/select.html', 23, false),array('modifier', 'handles', '/var/www/mediaserver/templates/default/select.html', 30, false),array('modifier', 'cat', '/var/www/mediaserver/templates/default/select.html', 30, false),array('modifier', 'urlencode', '/var/www/mediaserver/templates/default/select.html', 33, false),array('modifier', 'htmlspecialchars', '/var/www/mediaserver/templates/default/select.html', 33, false),array('modifier', 'str_replace', '/var/www/mediaserver/templates/default/select.html', 37, false),array('modifier', 'string_format', '/var/www/mediaserver/templates/default/select.html', 96, false),array('modifier', 'strlen', '/var/www/mediaserver/templates/default/select.html', 142, false),array('modifier', 'substr', '/var/www/mediaserver/templates/default/select.html', 142, false),)), $this); ?>
<?php if (@USE_DATABASE): ?><?php $this->assign('prefix', 'db_'); ?><?php else: ?><?php $this->assign('prefix', 'fs_'); ?><?php endif; ?>
<?php 
$parts = array();
if(isset($_REQUEST['search'])) $parts = split(' ', stripslashes($_REQUEST['search']));
$parts_replace = preg_replace('/.*/', '<b>$0</b>', $parts);
$this->assign('parts', $parts);
$this->assign('parts_replace', $parts_replace);
 ?>
<?php if ($_REQUEST['detail'] == 0 || $_REQUEST['detail'] == 1 || $_REQUEST['detail'] == 2): ?>
	There are <?php echo $this->_tpl_vars['total_count']; ?>
 result(s).<br />
	<?php $this->assign('page', ((is_array($_tmp=$_REQUEST['start'])) ? $this->_run_mod_handler('intval', true, $_tmp) : intval($_tmp))); ?>
	<?php $this->assign('item_count', count($this->_tpl_vars['files'])); ?>
	Displaying items <?php echo $_REQUEST['start']; ?>
 to <?php echo $this->_tpl_vars['page']+$this->_tpl_vars['item_count']; ?>
.
	<form name="select" action="" method="post">
		<input type="submit" name="select" value="All" /><input type="submit" name="select" value="None" /><br />
		<p style="white-space:nowrap">
		Select<br />
		On : Off<br />
		<?php unset($this->_sections['file']);
$this->_sections['file']['name'] = 'file';
$this->_sections['file']['loop'] = is_array($_loop=$this->_tpl_vars['files']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['file']['show'] = true;
$this->_sections['file']['max'] = $this->_sections['file']['loop'];
$this->_sections['file']['step'] = 1;
$this->_sections['file']['start'] = $this->_sections['file']['step'] > 0 ? 0 : $this->_sections['file']['loop']-1;
if ($this->_sections['file']['show']) {
    $this->_sections['file']['total'] = $this->_sections['file']['loop'];
    if ($this->_sections['file']['total'] == 0)
        $this->_sections['file']['show'] = false;
} else
    $this->_sections['file']['total'] = 0;
if ($this->_sections['file']['show']):

            for ($this->_sections['file']['index'] = $this->_sections['file']['start'], $this->_sections['file']['iteration'] = 1;
                 $this->_sections['file']['iteration'] <= $this->_sections['file']['total'];
                 $this->_sections['file']['index'] += $this->_sections['file']['step'], $this->_sections['file']['iteration']++):
$this->_sections['file']['rownum'] = $this->_sections['file']['iteration'];
$this->_sections['file']['index_prev'] = $this->_sections['file']['index'] - $this->_sections['file']['step'];
$this->_sections['file']['index_next'] = $this->_sections['file']['index'] + $this->_sections['file']['step'];
$this->_sections['file']['first']      = ($this->_sections['file']['iteration'] == 1);
$this->_sections['file']['last']       = ($this->_sections['file']['iteration'] == $this->_sections['file']['total']);
?>
            <?php $this->assign('type_arr', split('/', $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filemime'])); ?>
            <?php $this->assign('type', $this->_tpl_vars['type_arr']['0']); ?>
			<?php if (isset ( $_SESSION['selected'] )): ?>
				<?php $this->assign('item_selected', ((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['id'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['selected']) : in_array($_tmp, $_SESSION['selected']))); ?>
			<?php else: ?>
				<?php $this->assign('item_selected', false); ?>
			<?php endif; ?>
			<input type="radio" name="item[<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
]" value="on" <?php if ($this->_tpl_vars['item_selected']): ?>checked="checked"<?php endif; ?> />
			<input type="radio" name="item[<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
]" value="off" <?php if (! $this->_tpl_vars['item_selected']): ?>checked="checked"<?php endif; ?> />
            <?php if ($_REQUEST['detail'] < 2): ?>
                <?php if ($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filetype'] == 'FOLDER' || ( handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'archive') && $_REQUEST['cat'] != ((is_array($_tmp=$this->_tpl_vars['prefix'])) ? $this->_run_mod_handler('cat', true, $_tmp, 'archive') : smarty_modifier_cat($_tmp, 'archive')) ) || ( handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'playlist') && $_REQUEST['cat'] != ((is_array($_tmp=$this->_tpl_vars['prefix'])) ? $this->_run_mod_handler('cat', true, $_tmp, 'playlist') : smarty_modifier_cat($_tmp, 'playlist')) ) || ( handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'diskimage') && $_REQUEST['cat'] != ((is_array($_tmp=$this->_tpl_vars['prefix'])) ? $this->_run_mod_handler('cat', true, $_tmp, 'diskimage') : smarty_modifier_cat($_tmp, 'diskimage')) )): ?><a href="?dir=<?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'])) ? $this->_run_mod_handler('urlencode', true, $_tmp) : urlencode($_tmp)))) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
<?php if (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'archive')): ?>&cat=<?php echo $this->_tpl_vars['prefix']; ?>
archive<?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'diskimage')): ?>&cat=<?php echo $this->_tpl_vars['prefix']; ?>
diskimage<?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'playlist')): ?>&cat=<?php echo $this->_tpl_vars['prefix']; ?>
playlist<?php else: ?>&cat=<?php echo $_REQUEST['cat']; ?>
<?php endif; ?>"><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath']; ?>
</a>
                <?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'archive')): ?><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
archive.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/OUT/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
"><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath']; ?>
</a>
                <?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'diskimage')): ?><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
file.php/<?php echo $this->_tpl_vars['prefix']; ?>
diskimage/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
"><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath']; ?>
</a>
                <?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'image_browser')): ?><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
file.php/<?php echo $this->_tpl_vars['prefix']; ?>
image_browser/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
"><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath']; ?>
</a>
                <?php else: ?><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
file.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
"><?php echo str_replace($this->_tpl_vars['parts'], $this->_tpl_vars['parts_replace'], $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath']); ?>
</a><?php endif; ?>
            <?php elseif ($_REQUEST['detail'] == 2): ?>
				<?php $_from = $_SESSION['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['column_value']):
?>
					<?php if (isset ( $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']] )): ?>
						- <?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']]; ?>

					<?php endif; ?>
				<?php endforeach; endif; unset($_from); ?>
			<?php endif; ?>
            - Download:
            	<a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
zip.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.zip">zip</a> :
                <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
bt.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.torrent">torrent</a>
                <?php if (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'video')): ?>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
encode.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MP4/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.mp4">mp4</a>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
encode.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MPG/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.mpeg">mpg</a>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
encode.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/WMV/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.wmv">wmv</a>
                <?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'audio')): ?>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
encode.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MP4A/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.mp4">mp4</a>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
encode.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MP3/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.mp3">mp3</a>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
encode.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/WMA/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.wma">wma</a>
                <?php elseif (handles($this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'], 'image')): ?>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
convert.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/JPG/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.jpg">jpg</a>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
convert.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/GIF/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.gif">gif</a>
                : <a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
convert.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/PNG/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>
.png">png</a>
                <?php endif; ?>
			<br />
		<?php endfor; endif; ?>
		</p>
		<input name="select" type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_PAGES'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php elseif ($_REQUEST['detail'] == 3): ?>
	There are <?php echo $this->_tpl_vars['total_count']; ?>
 result(s).<br />
	<?php $this->assign('page', ((is_array($_tmp=$_REQUEST['start'])) ? $this->_run_mod_handler('intval', true, $_tmp) : intval($_tmp))); ?>
	<?php $this->assign('item_count', count($this->_tpl_vars['files'])); ?>
	Displaying items <?php echo $_REQUEST['start']; ?>
 to <?php echo $this->_tpl_vars['page']+$this->_tpl_vars['item_count']; ?>
.
	<form name="select" action="" method="post">
		<input type="submit" name="select" value="All" /><input type="submit" name="select" value="None" /><br />
		<p style="white-space:nowrap">
		<?php 
        $files = $this->get_template_vars('files');
		// get the longest column for each row
		$column_lengths = array();
        if(isset($_SESSION['columns']))
        {
            foreach($_SESSION['columns'] as $i => $name)
            {
                $column_lengths[$name] = strlen($name);
                foreach($files as $i => $file)
                {
                    if(strlen($file[$name]) > $column_lengths[$name]) $column_lengths[$name] = strlen($file[$name]);
                }
            }
        }
		$this->assign('column_lengths', $column_lengths);
		 ?>
		<span class="column">On/Off</span>
		<?php $_from = $_SESSION['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['column_key'] => $this->_tpl_vars['column_value']):
?>
            <?php $this->assign('column_len', $this->_tpl_vars['column_lengths'][$this->_tpl_vars['column_value']]); ?>
            <?php $this->assign('format_str', ((is_array($_tmp=((is_array($_tmp="%-")) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['column_len']+2) : smarty_modifier_cat($_tmp, $this->_tpl_vars['column_len']+2)))) ? $this->_run_mod_handler('cat', true, $_tmp, 's') : smarty_modifier_cat($_tmp, 's'))); ?>
            <span class="column"><?php echo ((is_array($_tmp=$this->_tpl_vars['column_value'])) ? $this->_run_mod_handler('string_format', true, $_tmp, $this->_tpl_vars['format_str']) : smarty_modifier_string_format($_tmp, $this->_tpl_vars['format_str'])); ?>
</span>
		<?php endforeach; endif; unset($_from); ?>
		<span class="column">Download</span>
		<br />
		<?php unset($this->_sections['file']);
$this->_sections['file']['name'] = 'file';
$this->_sections['file']['loop'] = is_array($_loop=$this->_tpl_vars['files']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['file']['show'] = true;
$this->_sections['file']['max'] = $this->_sections['file']['loop'];
$this->_sections['file']['step'] = 1;
$this->_sections['file']['start'] = $this->_sections['file']['step'] > 0 ? 0 : $this->_sections['file']['loop']-1;
if ($this->_sections['file']['show']) {
    $this->_sections['file']['total'] = $this->_sections['file']['loop'];
    if ($this->_sections['file']['total'] == 0)
        $this->_sections['file']['show'] = false;
} else
    $this->_sections['file']['total'] = 0;
if ($this->_sections['file']['show']):

            for ($this->_sections['file']['index'] = $this->_sections['file']['start'], $this->_sections['file']['iteration'] = 1;
                 $this->_sections['file']['iteration'] <= $this->_sections['file']['total'];
                 $this->_sections['file']['index'] += $this->_sections['file']['step'], $this->_sections['file']['iteration']++):
$this->_sections['file']['rownum'] = $this->_sections['file']['iteration'];
$this->_sections['file']['index_prev'] = $this->_sections['file']['index'] - $this->_sections['file']['step'];
$this->_sections['file']['index_next'] = $this->_sections['file']['index'] + $this->_sections['file']['step'];
$this->_sections['file']['first']      = ($this->_sections['file']['iteration'] == 1);
$this->_sections['file']['last']       = ($this->_sections['file']['iteration'] == $this->_sections['file']['total']);
?>
			<?php if (isset ( $_SESSION['selected'] )): ?>
				<?php $this->assign('item_selected', ((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['id'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['selected']) : in_array($_tmp, $_SESSION['selected']))); ?>
			<?php else: ?>
				<?php $this->assign('item_selected', false); ?>
			<?php endif; ?>
			<span class="column"> <input type="radio" name="item[<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
]" value="on" <?php if ($this->_tpl_vars['item_selected']): ?>checked="checked"<?php endif; ?> /><input type="radio" name="item[<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
]" value="off" <?php if (! $this->_tpl_vars['item_selected']): ?>checked="checked"<?php endif; ?> /> </span>
			<?php $_from = $_SESSION['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['column_value']):
?>
				<?php if (isset ( $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']] )): ?>
					<?php $this->assign('column_len', $this->_tpl_vars['column_lengths'][$this->_tpl_vars['column_value']]); ?>
					<?php $this->assign('format_str', ((is_array($_tmp=((is_array($_tmp="%-")) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['column_len']+2) : smarty_modifier_cat($_tmp, $this->_tpl_vars['column_len']+2)))) ? $this->_run_mod_handler('cat', true, $_tmp, 's') : smarty_modifier_cat($_tmp, 's'))); ?>
					<span class="column"><?php echo ((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']])) ? $this->_run_mod_handler('string_format', true, $_tmp, $this->_tpl_vars['format_str']) : smarty_modifier_string_format($_tmp, $this->_tpl_vars['format_str'])); ?>
</span>
				<?php endif; ?>
			<?php endforeach; endif; unset($_from); ?>
			<span class="column"><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
zip.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.zip">zip</a>:<a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
bt.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.torrent">torrent</a></span>
			<span style="display:block; float:left; clear:both;"></span><br />
		<?php endfor; endif; ?>
		</p>
		<input name="select" type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_PAGES'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php elseif ($_REQUEST['detail'] == 4): ?>
	There are <?php echo $this->_tpl_vars['total_count']; ?>
 result(s).<br />
	<?php $this->assign('page', ((is_array($_tmp=$_REQUEST['start'])) ? $this->_run_mod_handler('intval', true, $_tmp) : intval($_tmp))); ?>
	<?php $this->assign('item_count', count($this->_tpl_vars['files'])); ?>
	Displaying items <?php echo $_REQUEST['start']; ?>
 to <?php echo $this->_tpl_vars['page']+$this->_tpl_vars['item_count']; ?>
.
	<form name="select" action="" method="post">
		<input type="submit" name="select" value="All" /><input type="submit" name="select" value="None" /><br />
		<table class="select_list">
			<thead>
				<td>On/Off</td>
<?php $this->assign('columns', $_SESSION['columns']); ?><?php $this->assign('column_count', count($this->_tpl_vars['columns'])); ?>
<?php $_from = $this->_tpl_vars['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['columns'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['columns']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['column_value']):
        $this->_foreach['columns']['iteration']++;
?><?php $this->assign('items_after', ''); ?><?php $this->assign('items_before', ''); ?>
<?php unset($this->_sections['link_column']);
$this->_sections['link_column']['name'] = 'link_column';
$this->_sections['link_column']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['link_column']['show'] = true;
$this->_sections['link_column']['max'] = $this->_sections['link_column']['loop'];
$this->_sections['link_column']['step'] = 1;
$this->_sections['link_column']['start'] = $this->_sections['link_column']['step'] > 0 ? 0 : $this->_sections['link_column']['loop']-1;
if ($this->_sections['link_column']['show']) {
    $this->_sections['link_column']['total'] = $this->_sections['link_column']['loop'];
    if ($this->_sections['link_column']['total'] == 0)
        $this->_sections['link_column']['show'] = false;
} else
    $this->_sections['link_column']['total'] = 0;
if ($this->_sections['link_column']['show']):

            for ($this->_sections['link_column']['index'] = $this->_sections['link_column']['start'], $this->_sections['link_column']['iteration'] = 1;
                 $this->_sections['link_column']['iteration'] <= $this->_sections['link_column']['total'];
                 $this->_sections['link_column']['index'] += $this->_sections['link_column']['step'], $this->_sections['link_column']['iteration']++):
$this->_sections['link_column']['rownum'] = $this->_sections['link_column']['iteration'];
$this->_sections['link_column']['index_prev'] = $this->_sections['link_column']['index'] - $this->_sections['link_column']['step'];
$this->_sections['link_column']['index_next'] = $this->_sections['link_column']['index'] + $this->_sections['link_column']['step'];
$this->_sections['link_column']['first']      = ($this->_sections['link_column']['iteration'] == 1);
$this->_sections['link_column']['last']       = ($this->_sections['link_column']['iteration'] == $this->_sections['link_column']['total']);
?>
<?php $this->assign('item_after', $this->_sections['link_column']['index']-1); ?><?php $this->assign('item_before', $this->_sections['link_column']['index']+1); ?>
<?php if ($this->_tpl_vars['columns'][$this->_sections['link_column']['index']] != $this->_tpl_vars['column_value']): ?>
<?php if ($this->_tpl_vars['column_value'] == $this->_tpl_vars['columns'][$this->_tpl_vars['item_after']]): ?><?php $this->assign('items_after', ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ",")))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['column_value']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['column_value'])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?>
<?php else: ?><?php $this->assign('items_after', ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?><?php endif; ?>
<?php if ($this->_tpl_vars['column_value'] == $this->_tpl_vars['columns'][$this->_tpl_vars['item_before']]): ?><?php $this->assign('items_before', ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['column_value']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['column_value'])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ",")))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?>
<?php else: ?><?php $this->assign('items_before', ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?><?php endif; ?>
<?php endif; ?>
<?php endfor; endif; ?>
<?php $this->assign('column_link_len', ((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('strlen', true, $_tmp) : strlen($_tmp))); ?><?php $this->assign('items_before', ((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('substr', true, $_tmp, 0, $this->_tpl_vars['column_link_len']-1) : substr($_tmp, 0, $this->_tpl_vars['column_link_len']-1))); ?><?php $this->assign('column_link_len', ((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('strlen', true, $_tmp) : strlen($_tmp))); ?><?php $this->assign('items_after', ((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('substr', true, $_tmp, 0, $this->_tpl_vars['column_link_len']-1) : substr($_tmp, 0, $this->_tpl_vars['column_link_len']-1))); ?>
				<td><?php if (($this->_foreach['columns']['iteration']-1) > 0): ?><a href="<?php echo $_SERVER['PHP_SELF']; ?>
?column=<?php echo $this->_tpl_vars['items_before']; ?>
&display=">&lt;</a> <?php endif; ?><?php if (isset ( $this->_tpl_vars['display']['order'] ) && $this->_tpl_vars['display']['order'] == $this->_tpl_vars['column_value']): ?><?php echo $this->_tpl_vars['column_value']; ?>
<?php else: ?><a href="<?php echo $_SERVER['PHP_SELF']; ?>
?order=<?php echo $this->_tpl_vars['column_value']; ?>
&display="><?php echo $this->_tpl_vars['column_value']; ?>
</a><?php endif; ?><?php if (($this->_foreach['columns']['iteration']-1) < $this->_tpl_vars['column_count']-1): ?> <a href="<?php echo $_SERVER['PHP_SELF']; ?>
?column=<?php echo $this->_tpl_vars['items_after']; ?>
&display=">&gt;</a><?php endif; ?></td>
<?php endforeach; endif; unset($_from); ?>
				<td>Download</td>
			</thead>
<?php unset($this->_sections['file']);
$this->_sections['file']['name'] = 'file';
$this->_sections['file']['loop'] = is_array($_loop=$this->_tpl_vars['files']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['file']['show'] = true;
$this->_sections['file']['max'] = $this->_sections['file']['loop'];
$this->_sections['file']['step'] = 1;
$this->_sections['file']['start'] = $this->_sections['file']['step'] > 0 ? 0 : $this->_sections['file']['loop']-1;
if ($this->_sections['file']['show']) {
    $this->_sections['file']['total'] = $this->_sections['file']['loop'];
    if ($this->_sections['file']['total'] == 0)
        $this->_sections['file']['show'] = false;
} else
    $this->_sections['file']['total'] = 0;
if ($this->_sections['file']['show']):

            for ($this->_sections['file']['index'] = $this->_sections['file']['start'], $this->_sections['file']['iteration'] = 1;
                 $this->_sections['file']['iteration'] <= $this->_sections['file']['total'];
                 $this->_sections['file']['index'] += $this->_sections['file']['step'], $this->_sections['file']['iteration']++):
$this->_sections['file']['rownum'] = $this->_sections['file']['iteration'];
$this->_sections['file']['index_prev'] = $this->_sections['file']['index'] - $this->_sections['file']['step'];
$this->_sections['file']['index_next'] = $this->_sections['file']['index'] + $this->_sections['file']['step'];
$this->_sections['file']['first']      = ($this->_sections['file']['iteration'] == 1);
$this->_sections['file']['last']       = ($this->_sections['file']['iteration'] == $this->_sections['file']['total']);
?>
			<tr>
<?php if (isset ( $_SESSION['selected'] )): ?><?php $this->assign('item_selected', ((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['id'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['selected']) : in_array($_tmp, $_SESSION['selected']))); ?>
<?php else: ?><?php $this->assign('item_selected', false); ?><?php endif; ?>
				<td><input type="radio" name="item[<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
]" value="on" <?php if ($this->_tpl_vars['item_selected']): ?>checked="checked"<?php endif; ?> /><input type="radio" name="item[<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
]" value="off" <?php if (! $this->_tpl_vars['item_selected']): ?>checked="checked"<?php endif; ?> /></td>
<?php $_from = $_SESSION['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['column_value']):
?>
				<td><?php if (isset ( $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']] )): ?><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']]; ?>
<?php endif; ?></td>
<?php endforeach; endif; unset($_from); ?>
				<td><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
zip.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.zip">zip</a>:<a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
bt.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.torrent">torrent</a></td>
			</tr>
<?php endfor; endif; ?>
		</table>
		<input name="select" type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_PAGES'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php elseif ($_REQUEST['detail'] == 5): ?>
	There are <?php echo $this->_tpl_vars['total_count']; ?>
 result(s).<br />
	<?php $this->assign('page', ((is_array($_tmp=$_REQUEST['start'])) ? $this->_run_mod_handler('intval', true, $_tmp) : intval($_tmp))); ?>
	<?php $this->assign('item_count', count($this->_tpl_vars['files'])); ?>
	Displaying items <?php echo $_REQUEST['start']; ?>
 to <?php echo $this->_tpl_vars['page']+$this->_tpl_vars['item_count']; ?>
.
	<form name="select" action="" method="post">
		<input type="submit" name="select" value="All" /><input type="submit" name="select" value="None" /><br />
		<table class="select_list">
			<thead style="white-space:nowrap">
<?php $this->assign('columns', $_SESSION['columns']); ?><?php $this->assign('column_count', count($this->_tpl_vars['columns'])); ?>
<?php $_from = $this->_tpl_vars['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['columns'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['columns']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['column_value']):
        $this->_foreach['columns']['iteration']++;
?><?php $this->assign('items_after', ''); ?><?php $this->assign('items_before', ''); ?>
<?php unset($this->_sections['link_column']);
$this->_sections['link_column']['name'] = 'link_column';
$this->_sections['link_column']['loop'] = is_array($_loop=$this->_tpl_vars['columns']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['link_column']['show'] = true;
$this->_sections['link_column']['max'] = $this->_sections['link_column']['loop'];
$this->_sections['link_column']['step'] = 1;
$this->_sections['link_column']['start'] = $this->_sections['link_column']['step'] > 0 ? 0 : $this->_sections['link_column']['loop']-1;
if ($this->_sections['link_column']['show']) {
    $this->_sections['link_column']['total'] = $this->_sections['link_column']['loop'];
    if ($this->_sections['link_column']['total'] == 0)
        $this->_sections['link_column']['show'] = false;
} else
    $this->_sections['link_column']['total'] = 0;
if ($this->_sections['link_column']['show']):

            for ($this->_sections['link_column']['index'] = $this->_sections['link_column']['start'], $this->_sections['link_column']['iteration'] = 1;
                 $this->_sections['link_column']['iteration'] <= $this->_sections['link_column']['total'];
                 $this->_sections['link_column']['index'] += $this->_sections['link_column']['step'], $this->_sections['link_column']['iteration']++):
$this->_sections['link_column']['rownum'] = $this->_sections['link_column']['iteration'];
$this->_sections['link_column']['index_prev'] = $this->_sections['link_column']['index'] - $this->_sections['link_column']['step'];
$this->_sections['link_column']['index_next'] = $this->_sections['link_column']['index'] + $this->_sections['link_column']['step'];
$this->_sections['link_column']['first']      = ($this->_sections['link_column']['iteration'] == 1);
$this->_sections['link_column']['last']       = ($this->_sections['link_column']['iteration'] == $this->_sections['link_column']['total']);
?>
<?php $this->assign('item_after', $this->_sections['link_column']['index']-1); ?><?php $this->assign('item_before', $this->_sections['link_column']['index']+1); ?>
<?php if ($this->_tpl_vars['columns'][$this->_sections['link_column']['index']] != $this->_tpl_vars['column_value']): ?>
<?php if ($this->_tpl_vars['column_value'] == $this->_tpl_vars['columns'][$this->_tpl_vars['item_after']]): ?><?php $this->assign('items_after', ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ",")))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['column_value']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['column_value'])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?>
<?php else: ?><?php $this->assign('items_after', ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?><?php endif; ?>
<?php if ($this->_tpl_vars['column_value'] == $this->_tpl_vars['columns'][$this->_tpl_vars['item_before']]): ?><?php $this->assign('items_before', ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['column_value']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['column_value'])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ",")))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?>
<?php else: ?><?php $this->assign('items_before', ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']]) : smarty_modifier_cat($_tmp, $this->_tpl_vars['columns'][$this->_sections['link_column']['index']])))) ? $this->_run_mod_handler('cat', true, $_tmp, ",") : smarty_modifier_cat($_tmp, ","))); ?><?php endif; ?>
<?php endif; ?>
<?php endfor; endif; ?>
<?php $this->assign('column_link_len', ((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('strlen', true, $_tmp) : strlen($_tmp))); ?><?php $this->assign('items_before', ((is_array($_tmp=$this->_tpl_vars['items_before'])) ? $this->_run_mod_handler('substr', true, $_tmp, 0, $this->_tpl_vars['column_link_len']-1) : substr($_tmp, 0, $this->_tpl_vars['column_link_len']-1))); ?><?php $this->assign('column_link_len', ((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('strlen', true, $_tmp) : strlen($_tmp))); ?><?php $this->assign('items_after', ((is_array($_tmp=$this->_tpl_vars['items_after'])) ? $this->_run_mod_handler('substr', true, $_tmp, 0, $this->_tpl_vars['column_link_len']-1) : substr($_tmp, 0, $this->_tpl_vars['column_link_len']-1))); ?>
				<td><?php if (($this->_foreach['columns']['iteration']-1) > 0): ?><a href="<?php echo $_SERVER['PHP_SELF']; ?>
?column=<?php echo $this->_tpl_vars['items_before']; ?>
&display=">&lt;</a> <?php endif; ?><?php if (isset ( $this->_tpl_vars['display']['order'] ) && $this->_tpl_vars['display']['order'] == $this->_tpl_vars['column_value']): ?><?php echo $this->_tpl_vars['column_value']; ?>
<?php else: ?><a href="<?php echo $_SERVER['PHP_SELF']; ?>
?order=<?php echo $this->_tpl_vars['column_value']; ?>
&display="><?php echo $this->_tpl_vars['column_value']; ?>
</a><?php endif; ?><?php if (($this->_foreach['columns']['iteration']-1) < $this->_tpl_vars['column_count']-1): ?> <a href="<?php echo $_SERVER['PHP_SELF']; ?>
?column=<?php echo $this->_tpl_vars['items_after']; ?>
&display=">&gt;</a><?php endif; ?></td>
<?php endforeach; endif; unset($_from); ?>
				<td>Download</td>
			</thead>
<?php unset($this->_sections['file']);
$this->_sections['file']['name'] = 'file';
$this->_sections['file']['loop'] = is_array($_loop=$this->_tpl_vars['files']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['file']['show'] = true;
$this->_sections['file']['max'] = $this->_sections['file']['loop'];
$this->_sections['file']['step'] = 1;
$this->_sections['file']['start'] = $this->_sections['file']['step'] > 0 ? 0 : $this->_sections['file']['loop']-1;
if ($this->_sections['file']['show']) {
    $this->_sections['file']['total'] = $this->_sections['file']['loop'];
    if ($this->_sections['file']['total'] == 0)
        $this->_sections['file']['show'] = false;
} else
    $this->_sections['file']['total'] = 0;
if ($this->_sections['file']['show']):

            for ($this->_sections['file']['index'] = $this->_sections['file']['start'], $this->_sections['file']['iteration'] = 1;
                 $this->_sections['file']['iteration'] <= $this->_sections['file']['total'];
                 $this->_sections['file']['index'] += $this->_sections['file']['step'], $this->_sections['file']['iteration']++):
$this->_sections['file']['rownum'] = $this->_sections['file']['iteration'];
$this->_sections['file']['index_prev'] = $this->_sections['file']['index'] - $this->_sections['file']['step'];
$this->_sections['file']['index_next'] = $this->_sections['file']['index'] + $this->_sections['file']['step'];
$this->_sections['file']['first']      = ($this->_sections['file']['iteration'] == 1);
$this->_sections['file']['last']       = ($this->_sections['file']['iteration'] == $this->_sections['file']['total']);
?>
			<tr style="white-space:nowrap">
<?php if (isset ( $_SESSION['selected'] )): ?><?php $this->assign('item_selected', ((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['id'])) ? $this->_run_mod_handler('in_array', true, $_tmp, $_SESSION['selected']) : in_array($_tmp, $_SESSION['selected']))); ?>
<?php else: ?><?php $this->assign('item_selected', false); ?><?php endif; ?>
<?php $_from = $_SESSION['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['column_value']):
?>
				<td><?php if (isset ( $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']] )): ?><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']]; ?>
<?php endif; ?></td>
<?php endforeach; endif; unset($_from); ?>
				<td><a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
zip.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.zip">zip</a>:<a href="/<?php echo @HTML_ROOT; ?>
<?php echo @HTML_PLUGINS; ?>
bt.php/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/Files.torrent">torrent</a></td>
			</tr>
<?php endfor; endif; ?>
		</table>
		<input name="select" type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['templates']['TEMPLATE_PAGES'], 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><br />
<?php endif; ?>