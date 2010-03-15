<?php

$GLOBALS['templates']['vars']['title'] = 'Plugins';
$GLOBALS['templates']['vars']['subtext'] = $GLOBALS['plugins']['admin_plugins']['description'];

include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'header.php';

$recommended = array('select', 'list', 'search');
$required = array('core', 'index', 'login');
foreach($GLOBALS['plugins'] as $key => $plugin)
{
?>
<tr>
    <td class="title"><?php echo $GLOBALS['plugins'][$key]['name']; ?> (<?php echo $key; ?>)</td>
    <td>
    <?php
	$plugin_en = true;
    if(in_array($key, $required))
    {
    ?>
    <select disabled="disabled">
            <option>Enabled (Required)</option>
        </select>
    <?php
    }
    else
    {
    ?>
    <select name="<?php echo strtoupper($key); ?>_ENABLE">
            <option value="true" <?php echo ($plugin_en == true)?'selected="selected"':''; ?>>Enabled <?php echo in_array($key, $recommended)?'(Recommended)':'(Optional)'; ?></option>
            <option value="false" <?php echo ($plugin_en == false)?'selected="selected"':''; ?>>Disabled</option>
        </select>
    <?php
    }
    ?>
</td>
    <td class="desc">
    <ul>
        <li><?php echo $GLOBALS['plugins'][$key]['description']; ?></li>
        <li>Choose whether or not to select the <?php echo $GLOBALS['plugins'][$key]['name']; ?> plugin.</li>
    </ul>
    </td>
</tr>
<?php
}

include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'footer.php';
?>