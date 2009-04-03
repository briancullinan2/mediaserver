<?php /* Smarty version 2.6.19, created on 2009-02-20 14:02:48
         compiled from /var/www/mediaserver/templates/mobile/select.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'htmlspecialchars', '/var/www/mediaserver/templates/mobile/select.html', 5, false),array('modifier', 'cat', '/var/www/mediaserver/templates/mobile/select.html', 10, false),array('modifier', 'constant', '/var/www/mediaserver/templates/mobile/select.html', 10, false),)), $this); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo htmlspecialchars(@HTML_NAME); ?>
</title>
</head>

<body>
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
    <a href="?cat=$modules[module]"><?php echo constant(((is_array($_tmp=$this->_tpl_vars['modules'][$this->_sections['module']['index']])) ? $this->_run_mod_handler('cat', true, $_tmp, '::NAME') : smarty_modifier_cat($_tmp, '::NAME'))); ?>
</a>
<?php endfor; endif; ?>

</body>
</html>