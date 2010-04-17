<?php

function register_live_encode()
{
	return array(
		'name' => 'Encoding',
	);
}

function theme_live_encode()
{
	$GLOBALS['templates']['vars']['selector'] = false;
	
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Encoding</h1>
			<span class="subText">Use the form below to select the encoding options for a given file.</span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php

	?>
	<script language="javascript">
	function set(vcodec, acodec, vbitrate, abitrate, samplerate, scalar, channels, muxer, framerate, timeoffset)
	{
		for(var i = 0; i < document.forms[1].vcodec.length; i++)
		{
			if(document.forms[1].vcodec.options[i].value == vcodec)
			{
				document.forms[1].vcodec.selectedIndex = i;
				break;
			}
		}
	}
	</script>
	<form name="encode_form" action="<?php print $GLOBALS['templates']['vars']['get']; ?>" method="get">
		<?php
		if(!isset($GLOBALS['templates']['vars']['id']))
		{
			?>Select a File:<br /><?php
			
			theme('select_block');
		}
		else
		{
			?>
			<input type="hidden" value="<?php print $GLOBALS['templates']['html']['id']; ?>" name="id" />
			Selected file: [<?php print $GLOBALS['templates']['html']['id']; ?>]<br />
			<?php
			
			if(isset($GLOBALS['templates']['html']['filename']))
				print $GLOBALS['templates']['html']['filename'];
			?><br /><a href="<?php print url('plugin=encode'); ?>">Select a different file</a><?php
		}
		
		?><br />
		Presets: <select name="encode">
		<?php
		foreach(array('mp4', 'mpg', 'wmv', 'mp4a', 'mp3', 'wma') as $i => $encode)
		{
			$request['vcodec'] = validate_vcodec(array('encode' => $encode));
			$request['acodec'] = validate_acodec(array('encode' => $encode));
			$request['vbitrate'] = validate_vbitrate(array('encode' => $encode));
			$request['abitrate'] = validate_abitrate(array('encode' => $encode));
			$request['samplerate'] = validate_samplerate(array('encode' => $encode));
			$request['scalar'] = validate_scalar(array('encode' => $encode));
			$request['channels'] = validate_channels(array('encode' => $encode));
			$request['muxer'] = validate_muxer(array('encode' => $encode));
			$request['framerate'] = validate_framerate(array('encode' => $encode));
			$request['timeoffset'] = validate_timeoffset(array('encode' => $encode));
			
			?><option value="<?php print $encode; ?>" onclick="set(<?php
									print '\'' . $request['vcodec'] . '\', ' . 
											'\'' . $request['acodec'] . '\', ' . 
											'\'' . $request['vbitrate'] . '\', ' . 
											'\'' . $request['abitrate'] . '\', ' . 
											'\'' . $request['samplerate'] . '\', ' . 
											'\'' . $request['scalar'] . '\', ' . 
											'\'' . $request['channels'] . '\', ' . 
											'\'' . $request['muxer'] . '\', ' . 
											'\'' . $request['framerate'] . '\', ' . 
											'\'' . $request['timeoffset'] . '\''; ?>);"><?php print $encode; ?></option><?php
		}
		?>
		</select><br />
		Video Codec: <select name="vcodec">
		<?php
		foreach(array('mp4v', 'mpgv', 'WMV2', 'DIV3','dummy') as $i => $vcodec)
		{
			?><option value="<?php print $vcodec; ?>"><?php print $vcodec; ?></option><?php
		}
		?>
		</select>
	</form>
	<?php
	
	?><div class="titlePadding"></div>
	</div><?php

	theme('footer');
}

