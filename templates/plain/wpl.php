<?php

function register_plain_wpl()
{
	return array(
		'name' => 'Windows Play List',
		'file' => __FILE__,
		'encoding' => 'XML'
	);
}

function theme_plain_wpl()
{
	?>
	<smil>
		<head>
			<meta name="Generator" content="Microsoft Windows Media Player -- 11.0.5721.5230"/>
			<meta name="ContentPartnerListID"/>
			<meta name="ContentPartnerNameType"/>
			<meta name="ContentPartnerName"/>
			<meta name="Subtitle"/>
			<author/>
			<title><?php print setting('html_name');?> - <?php print $GLOBALS['module']; ?></title>
		</head>
		<body>
			<seq>
				<?php
				foreach($files as $i => $file)
				{
					?><media src="<?php print url('module=file&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&filename=' . urlencode($file['Filename']), false, true); ?>" /><?php
				}
				?>
			</seq>
		</body>
	</smil>
	<?php
}
