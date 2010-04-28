<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_encode()
{
	return array(
		'name' => 'Audio/Video Transcoder',
		'description' => 'Encode video files to the selected output.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true,
		'configurable' => array('encode_path', 'encode_args'),
	);
}

/**
 * Checks for encode path
 * @ingroup install
 */
function configure_encode($request)
{
	$request['encode_path'] = validate_encode_path($request);
	$request['encode_args'] = validate_encode_args($request);
	
	$options = array();
	
	if(file_exists($request['encode_path']))
	{
		$options['encode_path'] = array(
			'name' => 'Encode Path',
			'status' => '',
			'description' => '<li>An encoder has been set and detected, you may change this path to specify a new encoder.</li>
			<li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
			<li>The encoder detected is "' . basename($request['encode_path']) . '".</li>',
			'input' => '<input type="text" name="encode_path" value="' . htmlspecialchars($request['encode_path']) . '" />'
		);
	}
	else
	{
		$options['encode_path'] = array(
			'name' => 'Encode Path',
			'status' => 'fail',
			'description' => '<li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
			<li>This encoder could be VLC or FFMPEG.</li>',
			'input' => '<input type="text" name="encode_path" value="' . htmlspecialchars($request['encode_path']) . '" />'
		);
	}
	
	$options['encode_args'] = array(
		'name' => 'Encode Arguments',
		'status' => '',
		'description' => '<li>Specify the string of arguments to pass to the encoder.</li>
		<li>Certain keys in the argument string will be replaced with dynamic values by the encode plugin:
		%IF - Input file, the filename that will be inserted for transcoding<br />
		%VC - Video Codec to be used in the conversion<br />
		%AC - Audio Codec<br />
		%VB - Video Bitrate<br />
		%AB - Audio Bitrate<br />
		%SR - Sample Rate<br />
		%SR - Scale<br />
		%CH - Number of Channels<br />
		%MX - Muxer to use for encapsulating the streams<br />
		%TO - Time Offset for resumable listening and moving time position<br />
		%FS - Frames per Second<br />
		%OF - Output file if necissary
		</li>',
		'input' => '<input type="text" name="encode_args" value="' . htmlspecialchars($request['encode_args']) . '" />'
	);
	
	return $options;
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return The default install path for VLC on windows or linux based on validate_SYSTEM_TYPE
 */
function validate_encode_path($request)
{
	if(isset($request['encode_path']) && is_file($request['encode_path']))
		return $request['encode_path'];
	else
	{
		if(setting('system_type') == 'win')
			return 'C:\Program Files\VideoLAN\VLC\vlc.exe';
		else
			return '/usr/bin/vlc';
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return The entire arg string for further validation by the configure() function
 */
function validate_encode_args($request)
{
	if(isset($request['encode_args']) && is_file($request['encode_args']))
		return $request['encode_args'];
	else
	{
		if(setting('system_type') == 'win')
			return '"%IF" :sout=#transcode{vcodec=%VC,acodec=%AC,vb=%VB,ab=%AB,samplerate=%SR,channels=%CH,audio-sync,scale=%SC,fps=%FS}:std{mux=%MX,access=file,dst=-} vlc://quit';
		else
			return '-I dummy - --start-time=%TO :sout=\'#transcode{vcodec=%VC,acodec=%AC,vb=%VB,ab=%AB,samplerate=%SR,channels=%CH,audio-sync,scale=%SC,fps=%FS}:std{mux=%MX,access=file,dst=-}\' vlc://quit';
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return default mp3, if not defined, this function uses other request information to determine the best format to use
 */
function validate_encode($request)
{
	if(!isset($request['encode']))
	{
		if(preg_match('/.*(windows-media-player|NSplayer|WMPCE).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			$request['encode'] = 'wmv';
		}
		elseif(preg_match('/.*(iTunes).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			$request['encode'] = 'mpg';
		}
		elseif(preg_match('/.*(mobile).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			$request['encode'] = 'mpg';
		}
		elseif(preg_match('/.*(vlc).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			$request['encode'] = 'mp4';
		}
		elseif(isset($request['filename']))
		{
			$request['encode'] = strtoupper(getExt($request['filename']));
		}
	}
	
	$request['encode'] = strtolower($request['encode']);
	
	if(in_array($request['encode'], array('mp4', 'mpg', 'wmv', 'mp4a', 'mp3', 'wma')))
		return $request['encode'];
	else
		return 'mp3';
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return dummy by default, based on validate_encode
 */
function validate_vcodec($request)
{
	if(!isset($request['vcodec']) || !in_array($request['vcodec'], array('mp4v', 'mpgv', 'WMV2', 'DIV3','dummy')))
	{
		$request['encode'] = validate_encode($request);
		switch($request['encode'])
		{
			case 'mp4':
				return 'mp4v';
				break;
			case 'mpg':
				return 'mpgv';
				break;
			case 'wmv':
				return 'WMV2';
				break;
			case 'mp4a':
				return 'dummy';
				break;
			case 'mp3':
				return 'dummy';
				break;
			case 'wma':
				return 'dummy';
				break;
		}
	}
	return $request['vcodec'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return mp3 by default, based on validate_encode
 */
function validate_acodec($request)
{
	if(!isset($request['acodec']) || !in_array($request['acodec'], array('mp4a', 'mpga', 'mp3', 'wma2', 'dummy')))
	{
		$request['encode'] = validate_encode($request);
		switch($request['encode'])
		{
			case 'mp4':
				return 'mp3';
				break;
			case 'mpg':
				return 'mpga';
				break;
			case 'wmv':
				return 'mp3';
				break;
			case 'mp4a':
				return 'mp4a';
				break;
			case 'mp3':
				return 'mp3';
				break;
			case 'wma':
				return 'wma2';
				break;
		}
	}
	return $request['acodec'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return zero by default based on validate_encode, accepts any positive number
 */
function validate_vbitrate($request)
{
	if(!isset($request['vbitrate']) || !is_numeric($request['vbitrate']) || $request['vbitrate'] < 0)
	{
		$request['encode'] = validate_encode($request);
		switch($request['encode'])
		{
			case 'mp4':
				return 512;
				break;
			case 'mpg':
				return 512;
				break;
			case 'wmv':
				return 512;
				break;
			case 'mp4a':
				return 0;
				break;
			case 'mp3':
				return 0;
				break;
			case 'wma':
				return 0;
				break;
		}
	}
	return $request['vbitrate'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 160 by default, any positive number
 */
function validate_abitrate($request)
{
	if(!isset($request['abitrate']) || !is_numeric($request['abitrate']) || $request['abitrate'] < 0)
		return 160;
	return $request['abitrate'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 44100 by default, accepts any positive number
 */
function validate_samplerate($request)
{
	if(!isset($request['samplerate']) || !is_numeric($request['samplerate']) || $request['samplerate'] < 0)
		return 44100;
	return $request['samplerate'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 1 by default, accepts any number
 */
function validate_scalar($request)
{
	if(!isset($request['scalar']) || !is_numeric($request['scalar']))
		return 1;
	return $request['scalar'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 2 by default, accepts any number greater than zero
 */
function validate_channels($request)
{
	if(!isset($request['channels']) || !is_numeric($request['channels']) || $request['channels'] <= 0)
		return 2;
	return $request['channels'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return dummy by default, based on validate_encode
 */
function validate_muxer($request)
{
	if(!isset($request['muxer']) || !in_array($request['muxer'], array('ts', 'ps', 'mpeg1', 'asf', 'mp4', 'ogg', 'dummy')))
	{
		$request['encode'] = validate_encode($request);
		switch($request['encode'])
		{
			case 'mp4':
				return 'asf';
				break;
			case 'mpg':
				return 'ts';
				break;
			case 'wmv':
				return 'asf';
				break;
			case 'mp4a':
				return 'ts';
				break;
			case 'mp3':
				return 'dummy';
				break;
			case 'wma':
				return 'asf';
				break;
		}
	}
	return $request['muxer'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return zero by default, based on validate_encode
 */
function validate_framerate($request)
{
	if(!isset($request['framerate']) || !is_numeric($request['framerate']))
	{
		$request['encode'] = validate_encode($request);
		switch($request['encode'])
		{
			case 'mp4':
				return 15;
				break;
			case 'mpg':
				return 0;
				break;
			case 'wmv':
				return 15;
				break;
			case 'mp4a':
				return 0;
				break;
			case 'mp3':
				return 0;
				break;
			case 'wma':
				return 0;
				break;
		}
	}
	return $request['framerate'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return zero by default, accepts any positive number
 */
function validate_timeoffset($request)
{
	if(isset($request['timeoffset']) && is_numeric($request['timeoffset']) && $request['timeoffset'] >= 0)
		return $request['timeoffset'];
	else
		return 0;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_encode($request)
{
	
	set_time_limit(0);
	ignore_user_abort(1);
	session_cache_limiter("nocache");
	
	$fp = fopen('/tmp/test.txt', 'a');
	fwrite($fp, var_export($_SERVER, true));
	fclose($fp);
	
	if(!isset($request['encode']))
	{
		// set file
		if(isset($request['id'])) register_output_vars('id', $request['id']);
		if(isset($request['filename'])) register_output_vars('filename', $request['filename']);
		
		// since we usually have to make a call to find the file, 
		//   just call output_select so we can display a file selector
		output_select(array(
			'dir' => validate_dir($request),
			'search_Filemime' => '/audio\/|video\//',
			'search_operator' => 'OR',
			'search_Filetype' => 'FOLDER',
			'start' => validate_start($request),
			'limit' => validate_limit($request)
		));
		
		// show template for manually setting encoding options
		theme('encode');
		
		return;
	}
	
	$request['encode'] = validate_encode($request);
	$request['vcodec'] = validate_vcodec($request);
	$request['acodec'] = validate_acodec($request);
	$request['vbitrate'] = validate_vbitrate($request);
	$request['abitrate'] = validate_abitrate($request);
	$request['samplerate'] = validate_samplerate($request);
	$request['scalar'] = validate_scalar($request);
	$request['channels'] = validate_channels($request);
	$request['muxer'] = validate_muxer($request);
	$request['framerate'] = validate_framerate($request);
	$request['timeoffset'] = validate_timeoffset($request);
	$request['cat'] = validate_cat($request);

	// get the file path from the database
	$files = call_user_func_array($request['cat'] . '::get', array($request, &$count));
	
	if(count($files) > 0)
	{
		// the ids handler will do the replacement of the ids
		$files = db_ids::get(array('cat' => $_REQUEST['cat']), $tmp_count, $files);
		
		$tmp_request = array();
		$tmp_request['file'] = $files[0]['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);
	
		// get all the information incase we need to use it
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			if($handler != $request['cat'] && constant($handler . '::INTERNAL') == false && call_user_func_array($handler . '::handles', array($files[0]['Filepath'])))
			{
				$return = call_user_func_array($handler . '::get', array($tmp_request, &$tmp_count));
				if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
			}
		}
			
		// fix the encode type
		if(db_audio::handles($files[0]['Filepath']))
		{
			if($request['encode'] == 'mp4') $request['encode'] = 'mp4a';
			elseif($request['encode'] == 'mpg') $request['encode'] = 'mp3';
			elseif($request['encode'] == 'wmv') $request['encode'] = 'wma';
		}
		elseif(db_video::handles($files[0]['Filepath']))
		{
			if($request['encode'] == 'mp4a') $request['encode'] = 'mp4';
			elseif($request['encode'] == 'mp3') $request['encode'] = 'mpg';
			elseif($request['encode'] == 'wma') $request['encode'] = 'wmv';
		}
		
		// set the file variable
		$request['efile'] = $files[0]['Filepath'];
	}
	
	// set the headers
	switch($request['encode'])
	{
		case 'mp4':
			header('Content-Type: video/mp4');
			break;
		case 'mpg':
			header('Content-Type: video/mpg');
			break;
		case 'wmv':
			header('Content-Type: video/x-ms-wmv');
			break;
		case 'mp4a':
			header('Content-Type: audio/mp4');
			break;
		case 'mp3':
			header('Content-Type: audio/mpeg');
			break;
		case 'wma':
			header('Content-Type: audio/x-ms-wma');
			break;
	}
	
	// make up some header to takes the length of the media into consideration
	if(isset($files[0]['Length']))
	{
		$files[0]['Filesize'] = ceil($files[0]['Length'] * $request['vbitrate'] * 128 + $files[0]['Length'] * $request['abitrate'] * 128);
	}
	
	if(isset($files[0]['Filesize']))
	{				
		// check for range request
		if(isset($_SERVER['HTTP_RANGE']))
		{
			list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
	
			if ($size_unit == 'bytes')
			{
				// multiple ranges could be specified at the same time, but for simplicity only serve the first range
				// http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
				if(strpos($range_orig, ',') !== false)
					list($range, $extra_ranges) = explode(',', $range_orig, 2);
				else
					$range = $range_orig;
			}
			else
			{
				$range = '-';
			}
		}
		else
		{
			$range = '-';
		}
		
		// figure out download piece from range (if set)
		list($seek_start, $seek_end) = explode('-', $range, 2);
	
		// set start and end based on range (if set), else set defaults
		// also check for invalid ranges.
		$seek_end = (empty($seek_end)) ? ($files[0]['Filesize'] - 1) : min(abs(intval($seek_end)),($files[0]['Filesize'] - 1));
		//$seek_end = $file['Filesize'] - 1;
		$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
		
		// Only send partial content header if downloading a piece of the file (IE workaround)
		if ($seek_start > 0 || $seek_end < ($files[0]['Filesize'] - 1))
		{
			header('HTTP/1.1 206 Partial Content');
		}
	
		header('Accept-Ranges: bytes');
		header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $files[0]['Filesize']);
	
		// headers for IE Bugs (is this necessary?)
		header("Cache-Control: no-cache, must-revalidate");  
		header("Pragma: public");
	
		header('Content-Length: ' . ($seek_end - $seek_start + 1));
	}
		
	// close session so the client can continue browsing the site
	if(isset($_SESSION)) session_write_close();
	
	// replace the argument string with the contents of $_REQUEST
	//  without validation this is VERY DANGEROUS!
	$cmd = basename(setting('encode_path')) . ' ' . str_replace(array(
		'%IF', 
		'%VC',
		'%AC', 
		'%VB', 
		'%AB', 
		'%SR', 
		'%SC', 
		'%CH', 
		'%MX', 
		'%FS', 
		'%TO'
	), array(
		$request['efile'],
		$request['vcodec'],
		$request['acodec'],
		$request['vbitrate'],
		$request['abitrate'],
		$request['samplerate'],
		$request['scalar'],
		$request['channels'],
		$request['muxer'],
		$request['framerate'],
		$request['timeoffset']
	), setting('encode_args'));
	
	$descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	   2 => array("pipe", "w"),  // stderr is a pipe that the child will write to
	);
	
	// start process
	$process = proc_open($cmd, $descriptorspec, $pipes, dirname(setting('encode_path')), NULL); //array('binary_pipes' => true, 'bypass_shell' => true));
	
	stream_set_blocking($pipes[0], 0);
	stream_set_blocking($pipes[1], 0);
	stream_set_blocking($pipes[2], 0);
	
	$fp = call_user_func_array($request['cat'] . '::out', array($request['efile']));
	//$fp = fopen($_REQUEST['%IF'], 'rb');
	$php_out = fopen('php://output', 'wb');
	
	// if %IF is not in the arguments, it is reading from stdin so use pipe
	// output file
	if(is_resource($process) && is_resource($fp))
	{
		// don't use the file at all if the %IF field exists in the ARGS sections
		//   the reason for this is because it won't be reading from STDIN
		$file_closed = (strpos(setting('encode_args'), '%IF') !== false);
		$read_count = 0;
		$write_count = 0;
		$in_buffer = '';
		$out_buffer = '';
		while(true)
		{
			// if the connection was interrupted then stop looping
			if(connection_status()!=0)
			{
				fclose($fp);
				fclose($pipes[0]);
				fclose($pipes[1]);
				fclose($php_out);
				proc_terminate($process);
				break;
			}
			
			// if the file is not already closed, then close the file when it hits eof
			if(!$file_closed && feof($fp))
			{
				$file_closed = true;
				fclose($fp);
				fclose($pipes[0]);
			}
			
			// if the pipe is eof then we are finished
			if(feof($pipes[1]))
			{
				// write out what is left
				if(isset($length))
				{
					if($read_count < $length)
						fwrite($php_out, sprintf('[%0' . ($length - $read_count) . 's]', 0));
				}
				fclose($pipes[1]);
				fclose($php_out);
				break;
			}
			
			// set up the pipes to be checked
			$read = array($pipes[1]);
			$write = (!$file_closed)?array($pipes[0]):array();
			$except = NULL;
	
			// select the pipes that are available for reading and writing
			stream_select($read, $write, $except, NULL);
			
			// if we can read then read more and send it out to php
			if(in_array($pipes[1], $read))
			{
				$count = fwrite($php_out, fread($pipes[1], setting('buffer_size')));
				$read_count += $count;
			}
			
			// if we can write then write more from the input stream
			if(!$file_closed && in_array($pipes[0], $write))
			{
				if(strlen($in_buffer) == 0)
				{
					$in_buffer = fread($fp, setting('buffer_size'));
				}
				$count = fwrite($pipes[0], $in_buffer);
				$in_buffer = substr($in_buffer, $count);
				$write_count += $count;
			}
		}
		
		$status = proc_get_status($process);
		kill9('vlc', $status['pid']);
		
		$return_value = proc_close($process);
	}
}
