<?php /* Smarty version 2.6.19, created on 2008-07-31 04:51:55
         compiled from /var/www/mediaserver/templates/extjs/select.xml */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'utf8_encode', '/var/www/mediaserver/templates/extjs/select.xml', 25, false),array('modifier', 'htmlspecialchars', '/var/www/mediaserver/templates/extjs/select.xml', 25, false),array('modifier', 'split', '/var/www/mediaserver/templates/extjs/select.xml', 26, false),array('modifier', 'urlencode', '/var/www/mediaserver/templates/extjs/select.xml', 37, false),array('modifier', 'truncate', '/var/www/mediaserver/templates/extjs/select.xml', 38, false),array('modifier', 'htmlentities', '/var/www/mediaserver/templates/extjs/select.xml', 38, false),)), $this); ?>
<?php 
global $smarty;
$ext_icons = array();
$ext_icons['FOLDER'] = '/' . SITE_HTMLROOT . SITE_TEMPLATE . 'images/filetypes/folder_96x96.png';
$ext_icons['FILE'] = '/' . SITE_HTMLROOT . SITE_TEMPLATE . 'images/filetypes/file_96x96.png';

$type_icons = array();
$type_icons['audio'] = '/' . SITE_HTMLROOT . SITE_TEMPLATE . 'images/filetypes/music_96x96.png';

$smarty->assign('ext_icons', $ext_icons);
$smarty->assign('type_icons', $type_icons);
 ?>
<?php echo '<?xml'; ?>
 version="1.0" encoding="utf-8"<?php echo '?>'; ?>

<request>
<?php if (! isset ( $this->_tpl_vars['error'] ) || $this->_tpl_vars['error'] == ''): ?>
	<count><?php echo $this->_tpl_vars['total_count']; ?>
</count>
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
	<file>
		<?php $this->assign('index', $this->_sections['file']['index']+$_REQUEST['start']); ?>
		<index><?php echo $this->_tpl_vars['index']; ?>
</index>
		<id><?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
</id>
		<name><?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename'])) ? $this->_run_mod_handler('utf8_encode', true, $_tmp) : utf8_encode($_tmp)))) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
</name>
<?php $this->assign('type_arr', split('/', $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filemime'])); ?>
<?php $this->assign('type', $this->_tpl_vars['type_arr']['0']); ?>
<?php $this->assign('ext', $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filetype']); ?>
		<icon><?php if (isset ( $this->_tpl_vars['ext_icons'][$this->_tpl_vars['ext']] )): ?><?php echo $this->_tpl_vars['ext_icons'][$this->_tpl_vars['ext']]; ?>
<?php elseif (isset ( $this->_tpl_vars['type_icons'][$this->_tpl_vars['type']] )): ?><?php echo $this->_tpl_vars['type_icons'][$this->_tpl_vars['type']]; ?>
<?php else: ?><?php echo $this->_tpl_vars['ext_icons']['FILE']; ?>
<?php endif; ?></icon>
		<ext><?php echo ((is_array($_tmp=$this->_tpl_vars['ext'])) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
</ext>
		<tip>
<?php $_from = $this->_tpl_vars['columns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['column_value']):
?>
<?php if (isset ( $this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']] )): ?><?php echo $this->_tpl_vars['column_value']; ?>
: <?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']][$this->_tpl_vars['column_value']])) ? $this->_run_mod_handler('utf8_encode', true, $_tmp) : utf8_encode($_tmp)))) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
&lt;br /&gt;<?php endif; ?>
<?php endforeach; endif; unset($_from); ?>
		</tip>
		<path><?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['Filepath'])) ? $this->_run_mod_handler('utf8_encode', true, $_tmp) : utf8_encode($_tmp)))) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
</path>
		<link><?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
file/<?php echo $_REQUEST['cat']; ?>
/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename'])) ? $this->_run_mod_handler('utf8_encode', true, $_tmp) : utf8_encode($_tmp)))) ? $this->_run_mod_handler('urlencode', true, $_tmp) : urlencode($_tmp)))) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
</link>
		<short><?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename'])) ? $this->_run_mod_handler('truncate', true, $_tmp, 13, "...", true) : smarty_modifier_truncate($_tmp, 13, "...", true)))) ? $this->_run_mod_handler('htmlentities', true, $_tmp, 'ENT_COMPAT', -8) : htmlentities($_tmp, 'ENT_COMPAT', -8)); ?>
</short>
	</file>
<?php endfor; endif; ?>
<?php else: ?>
	<success>false</success>
	<error><?php echo $this->_tpl_vars['error']; ?>
</error>
<?php endif; ?>
</request>