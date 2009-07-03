<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<title><?php echo HTML_NAME?>: Reports</title>
</head>
<body>
	View different types of reports by selecting the link and following the instructions.<br />
	<?php
	foreach($reports as $section => $report)
	{
		foreach($report as $order => $lines)
		{
			foreach($lines as $key => $line)
			{
				$type = intval(substr($key, 0, strpos($key, '-')));
				$title = substr($key, strpos($key, '-')+1);
				if(!isset($previous_section)) $previous_section = $section;
				if((isset($_REQUEST['show'.$section]) && $_REQUEST['show'.$section] == true) || ($type & TYPE_HEADING) > 0 || $section < 0)
				{
					if(($type & TYPE_HEADING) > 0)
					{
						print '<br /><span style="font-weight:bold;' . 'color:rgb(' . ((($type&TYPE_R)>0)?'255,':'0,') . ((($type&TYPE_G)>0)?'255,':'0,') . ((($type&TYPE_B)>0)?'255':'0') . ');"><a href="?show' . $section . '=' . (isset($_REQUEST['show'.$section])?!$_REQUEST['show'.$section]:true) . '">' . $title . '</a>: ' . ((($type&TYPE_ENTIRE)==0)?'</span>':'') . $line . ((($type&TYPE_ENTIRE)>0)?'</span>':'') . "<br />\n";
					}
					else
					{
						print '<span style="' . ((($type&TYPE_BOLD)>0)?'font-weight:bold;':'') . 'color:rgb(' . ((($type&TYPE_R)>0)?'255,':'0,') . ((($type&TYPE_G)>0)?'255,':'0,') . ((($type&TYPE_B)>0)?'255':'0') . ');">' . $title . ': ' . ((($type&TYPE_ENTIRE)==0)?'</span>':'') . $line . ((($type&TYPE_ENTIRE)>0)?'</span>':'') . "<br />\n";
					}
				}
			}
		}
	}
	?>
</body>
</html>
