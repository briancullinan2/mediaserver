<?php

// load previous recorded computers
$fp = @fopen('computers.serialize', 'r');
if($fp !== false)
{
	$computers_str = fread($fp, filesize('computers.serialize'));
	fclose($fp);
	$computers = unserialize($computers_str);
}
else
{
	$computers = array();
}

// record incoming computer
$new_computer = array(
	'name' => gethostbyaddr($_SERVER['REMOTE_ADDR']),
	'ip' => $_SERVER['REMOTE_ADDR'],
	'nick' => '',
	'cn' => @$_REQUEST['cn'],
	'os' => @$_REQUEST['os']
);

// check if there is the same computer name, or same dns name and replace IP
$found = false;
foreach($computers as $i => $computer)
{
	if($computer['cn'] == $new_computer['cn'])
	{
		$computers[$i]['ip'] = $new_computer['ip'];
		$computers[$i]['name'] = $new_computer['name'];
		$computers[$i]['os'] = $new_computer['os'];
		$found = true;
		break;
	}
	if($computer['name'] == $new_computer['name'])
	{
		$computers[$i]['ip'] = $new_computer['ip'];
		if($new_computer['os'] != '')
		$computers[$i]['os'] = $new_computer['os'];
		if($new_computer['cn'] != '')
			$computers[$i]['cn'] = $new_computer['cn'];
		$found = true;
		break;
	}
}
if($found == false)
{
	$computers[] = $new_computer;
}

if(count($_POST) > 0)
{
	foreach($_POST as $key => $value)
	{
		if(substr($key, 0, 4) == 'Del_')
		{
			unset($computers[substr($key, 4)]);
			$computer = array_values($computers);
		}
		if(substr($key, 0, 5) == 'Name_')
		{
			$computers[substr($key, 5)]['nick'] = $_POST['Nick_' . substr($key, 5)];
		}
	}
}

// save computers
$fp = @fopen('computers.serialize', 'w');
if($fp !== false)
{
	fwrite($fp, serialize($computers));
	fclose($fp);
}

if(count($_POST) > 0)
{
	header('Location: ' . $_SERVER['REQUEST_URI']);
	exit();
}

print '<form action="" method="post"><table><tr><th>DNS</th><th>IP</th><th>Name</th><th>OS</th><th>Nickname</th><th>Actions</th></tr>';
foreach($computers as $i => $computer)
{
	print '<tr><td>';
	print $computer['name'];
	print '</td><td>';
	print $computer['ip'];
	print '</td><td>';
	print $computer['cn'];
	print '</td><td>';
	print $computer['os'];
	print '</td><td>';
	print '<input type="text" name="Nick_' . $i . '" value="' . $computer['nick'] . '" />';
	print '</td><td>';
	print '<input type="submit" name="Name_' . $i . '" value="Save" />';
	print '<input type="submit" name="Del_' . $i . '" value="Del" />';
	print '</td></tr>';
}
print '</table></form>';

?>