<?php
//$_GET[test]=stripslashes($_GET[test]);

$form='<form action='. $_SERVER['PHP_SELF'].' method=post>
	<input name="search" value=\''.htmlspecialchars($_GET[test],ENT_QUOTES).'\' type="text"> 
</form>';
die($form);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

$dh = opendir('/home/share/Music/Bad Religion/New Maps of Hell/');

while (($file = readdir($dh)) !== false)
{
	if(strlen($file) > 1 && substr($file, 0, 2) == '17')
	{
		for($i = 0; $i < strlen($file); $i++)
		{
			print $file[$i] . ', ' . ord($file[$i]) . '<br />';
		}
		var_dump('/home/share/Music/Bad Religion/New Maps of Hell/' . $file);
		var_dump(file_exists('/home/share/Music/Bad Religion/New Maps of Hell/' . $file));
	}
}

closedir($dh);
/*
$GLOBALS['database'] = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$files = $GLOBALS['database']->query(array('SELECT' => 'files', 'WHERE' => 'Filepath REGEXP "^/home/share/Music/38 Special/Live at Sturgis/09"'));

if(count($files) > 0)
{
	for($i = 0; $i < strlen($files[0]['Filepath']); $i++)
	{
		print $files[0]['Filepath'][$i] . ', ' . ord($files[0]['Filepath'][$i]) . '<br />';
	}
}

var_dump(file_exists($files[0]['Filepath']));

$_REQUEST['debug'] = true;
$_REQUEST['log_sql'] = true;

db_file::cleanup_remove($files[0]);
*/
?>