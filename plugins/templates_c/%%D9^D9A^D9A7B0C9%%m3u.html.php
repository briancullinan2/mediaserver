<?php /* Smarty version 2.6.19, created on 2009-02-03 15:25:40
         compiled from /var/www/mediaserver/templates/default/m3u.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'cat', '/var/www/mediaserver/templates/default/m3u.html', 6, false),array('modifier', 'split', '/var/www/mediaserver/templates/default/m3u.html', 30, false),)), $this); ?>
<?php if (! isset ( $_REQUEST['m3u_type'] )): ?>
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
<?php if (! isset ( $this->_tpl_vars['ids'] )): ?>
<?php $this->assign('ids', $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']); ?>
<?php else: ?>
<?php $this->assign('ids', ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['ids'])) ? $this->_run_mod_handler('cat', true, $_tmp, ',') : smarty_modifier_cat($_tmp, ',')))) ? $this->_run_mod_handler('cat', true, $_tmp, $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']) : smarty_modifier_cat($_tmp, $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']))); ?>
<?php endif; ?>
<?php endfor; endif; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo @SITE_NAME; ?>
: M3U List</title>
</head>
<body>
Note: All non-media types will be filtered out using this list type.<br />
Select your audio/video format:<br />
<a href="/<?php echo @SITE_PLUGINS; ?>
list/<?php echo $this->_tpl_vars['ids']; ?>
/MP4/Files.m3u">mp4</a>
: <a href="/<?php echo @SITE_PLUGINS; ?>
list/<?php echo $this->_tpl_vars['ids']; ?>
/MPG/Files.m3u">mpg/mp3</a>
: <a href="/<?php echo @SITE_PLUGINS; ?>
list/<?php echo $this->_tpl_vars['ids']; ?>
/WM/Files.m3u">wmv/wma</a>
</body>
</html>
<?php elseif ($_REQUEST['m3u_type'] == 'MP4'): ?>
<?php 
header('Content-Type: audio/x-mpegurl');
header('Content-Disposition: attachment; filename="' . (isset($_REQUEST['filename'])?$_REQUEST['filename']:constant($_REQUEST['cat'] . '::NAME')) . '.m3u"'); 
 ?>
#EXTM3U
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
<?php if ($this->_tpl_vars['type'] == 'video'): ?>
#EXTINF:0,<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>

<?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
encode/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MP4/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
.mp4
<?php elseif ($this->_tpl_vars['type'] == 'audio'): ?>
#EXTINF:0,<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>

<?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
encode/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MP4A/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
.mp4
<?php endif; ?>
<?php endfor; endif; ?>
<?php elseif ($_REQUEST['m3u_type'] == 'WM'): ?>
#EXTM3U
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
<?php if ($this->_tpl_vars['type'] == 'video'): ?>
#EXTINF:0,<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>

<?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
encode/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/WMV/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
.wmv
<?php elseif ($this->_tpl_vars['type'] == 'audio'): ?>
#EXTINF:0,<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>

<?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
encode/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/WMA/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
.wma
<?php endif; ?>
<?php endfor; endif; ?>
<?php elseif ($_REQUEST['m3u_type'] == 'MPG'): ?>
#EXTM3U
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
<?php if ($this->_tpl_vars['type'] == 'video'): ?>
#EXTINF:0,<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>

<?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
encode/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MPG/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
.mpg
<?php elseif ($this->_tpl_vars['type'] == 'audio'): ?>
#EXTINF:0,<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['Filename']; ?>

<?php echo @SITE_HTMLPATH; ?>
<?php echo @SITE_PLUGINS; ?>
encode/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
/MP3/<?php echo $this->_tpl_vars['files'][$this->_sections['file']['index']]['id']; ?>
.mp3
<?php endif; ?>
<?php endfor; endif; ?>
<?php endif; ?>