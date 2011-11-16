<?php

/**
 * @name Version Information
 * DO NOT CHANGE!
 * @{ 
 * @enum VERSION set version for stuff to reference
 * @enum VERSION_NAME set the name for a text representation of the version
 */
define('VERSION', 			      '0.9.3');
define('VERSION_NAME', 			  'Atlas');


/**
 * Recursively convert an array of string information to htmlspecialchars(), used by #register_output_vars()
 * @return a multilevel array with all strings converted to HTML compatible
 */
function traverse_array($input)
{
	if(is_string($input))
	{
		if(strlen($input) < setting('buffer_size'))
			return htmlspecialchars($input, ENT_QUOTES);
		else
			return;
	}
	elseif(is_array($input))
	{
		foreach($input as $key => $value)
		{
			$input[$key] = traverse_array($value);
		}
		return $input;
	}
	else
		return htmlspecialchars((string)$input, ENT_QUOTES);
}



/**
 * Function for checking in libraries are installed, specifically PEAR which likes to use /local/share/php5/
 * @param filename the library filename from the scope of the expected include_path
 * @return the full, real path of the library, or false if it is not found in any include path
 */
function include_path($filename)
{
	// Check for absolute path
	if (realpath($filename) == $filename) {
		return $filename;
	}
	
	// Otherwise, treat as relative path
	$paths = get_include_paths();
	foreach ($paths as $path)
	{
		if (substr($path, -1) == DIRECTORY_SEPARATOR)
			$fullpath = $path . $filename;
		else
			$fullpath = $path . DIRECTORY_SEPARATOR . $filename;
		
		if (file_exists($fullpath))
		{
			return $fullpath;
		}
	}
	
	return false;
}

function get_include_paths()
{
	$paths = explode(PATH_SEPARATOR, get_include_path());
	$paths[] = setting_local_root() . 'includes';
	foreach($paths as $i => $path)
	{
		if(($path = realpath($path)) !== false)
			$paths[$i] = $path;
	}
	
	return $paths;
}


/**
 * tokenize a string, assumed to be a filepath, in various different ways
 * remove useless words like 'and' and 'a' and 'of'
 * @param string the string to tokenize
 * @return An assosiative array with each variation of removed terms
 */
function tokenize($string, $only_get = NULL)
{
	$return = array();
	
	$string = strtolower($string);
	$valid_pieces = array();
	$pieces = preg_split('/[^a-zA-Z0-9]/i', $string);
	$return['All'] = $pieces;
	$return['Unique'] = array_unique($pieces);
	for($i = 0; $i < count($pieces); $i++)
	{
		// remove single characters and common words
		if(strlen($pieces[$i]) > 1 && !in_array(strtolower($pieces[$i]), array('and', 'the', 'of', 'an')))
		{
			$valid_pieces[] = $pieces[$i];
		}
	}
	
	$return['Most'] = $valid_pieces;
	
	// remove common edition words
	foreach($valid_pieces as $i => $piece)
	{
		if(in_array(strtolower($valid_pieces[$i]), array(
			// remove different release information
			'version', 'unknown', 'compilation', 'compilations', 'remastered', 'itunes', 'music', 'lp',
			// remove different format information
			'flac', 'lossless', 'vbr', 'v0', 'v1', 'v2', 'mp3', '320', '192', '128', 'q8', 'aac', '256', 'ogg', 'vorbis', '24bit', 'ac3', 'dt3', 'dts', '768', '448'
		)))
		{
			unset($valid_pieces[$i]);
		}
	}
	$valid_pieces = array_values($valid_pieces);
	
	$return['Some'] = $valid_pieces;
	
	// remove common other common words
	foreach($valid_pieces as $i => $piece)
	{
		if(in_array(strtolower($valid_pieces[$i]), array('album', 'artist', 'single', 'clean', 'box', 'boxed', 'set', 'live', 'band', 'hits', 'other', 'disk', 'disc', 'volume', 'retail', 'edition')))
		{
			unset($valid_pieces[$i]);
		}
	}
	$valid_pieces = array_values($valid_pieces);
	
	$return['Few'] = $valid_pieces;
	
	return $return;
}

function tokenize_groups($strings, $group_by = 'Some')
{
	$groups = array();
	foreach($strings as $i => $title)
	{
		$tokenized = tokenize($title);
		$title = implode(' ', $tokenized[$group_by]);
		$result = get_closest($title, array_keys($groups));
		if($result && levenshtein($title, $result['value']) < max(strlen($title), strlen($result['value'])) * .20)
		{
//print $title . "\n";
//print levenshtein($title, $result['value']) . ' - ' . max(strlen($title), strlen($result['value'])) * .70;
//print_r($result);
			$groups[$result['value']][] = $i;
		}
		elseif(!isset($groups[$title]))
			$groups[$title] = array($i);
		else
			$groups[$title][] = $i;
	}
	
	return $groups;
}

/**
 * sorting function for terms in a keyword search
 * @param a the first item to compare
 * @param b the second item to compare
 * @return 1 for comes after, -1 for comes before, 0 for equal
 */
function termSort($a, $b)
{
	if(($a[0] == '+' && $a[0] == $b[0]) || ($a[0] == '-' && $a[0] == $b[0]) || ($a[0] != '+' && $a[0] != '-' && $b[0] != '+' && $b[0] != '-'))
	{
		if(strlen($a) > strlen($b))
			return -1;
		elseif(strlen($a) < strlen($b))
			return 1;
		else
			return 0;
	} elseif($a[0] == '+') {
		return -1;
	} elseif($b[0] == '+') {
		return 1;
	} elseif($a[0] == '-') {
		return -1;
	} else {
		return 1;
	}
}

function get_closest($search_text, $titles)
{
	if(count($titles) == 0)
		return false;
	
	$keys = array_keys($titles);
	$distances = array_map('levenshtein', $titles, array_fill(0, count($titles), $search_text));
	asort($distances);
	reset($distances);
	$closest = key($distances);
	
	// return false if levenshtein is greater than the length of the search string
	if($distances[$closest] == max(max(array_map('strlen', $titles)), strlen($search_text)))
		return false;
	
	return array(
		$titles[$keys[$closest]],
		$keys[$closest],
		'value' => $titles[$keys[$closest]],
		'key' => $keys[$closest],
	);
}

/**
 * @defgroup dependency Dependency Functions
 * All functions that allow the system to check other system settings for module dependencies
 * @param settings the settings for dependencies to take advantage of, but not necissarily contrained to
 * @return Usually true or false, in some cases, returns status information about the dependency
 * for example, the db_code handler depends on a syntax highlighter, it can use either PEAR or Geshi
 * for highlighting code, so the dependency function would return false, if neither is installed, 
 * but may return a path to the library is one of them is installed, or true if both are installed.
 * This can be interpreted by the configuration page and offer an option if both are installed, or fail if it is false
 * @{
 */
 
/**
 * Abstracted to prevent errors and display debug information, this is minimally useful since modules call their own dependencies
 * @param dependency Either the name of a module, or the name of a dependency
 * @return A dependency, unless the input is a module, then it returns true or false if the module's dependencies are all satisfied
 */
function dependency($dependency, $ignore_setting = false, $already_checked = array())
{
	// check if the caller is trying to verify the dependencies of another module
	if(is_module($dependency))
	{
		$depends = get_dependencies($dependency);
		
		// now loop through the modules dependencies only, and make sure they are all met
		foreach($depends as $i => $depend)
		{
			//  uses a backup check to prevent recursion and errors if there is
			if(in_array($depend, $already_checked))
			{
				raise_error('Dependency \'' . $depend . '\' already checked when checking the dependencies of \'' . $dependency . '\'.', E_VERBOSE);
				
				// checking it twice is unnessicary since it should have failed already
				continue;
			}

			// check for false strictly, anything else should be taken as status information
			//  this is also recursive so that if one module fails everything that depends on it will fail
			$already_checked[] = $depend;
			// only ignore first dependency request
			if(dependency($depend, false, $already_checked) === false)
			{
				// no need to continue as it only takes 1 to fail
				return false;
			}
		}
	
		// return false if a module is disabled
		if(!setting($dependency . '_enable') && !$ignore_setting)
			return false;
		
		// if it has gotten this far through all the disproofs then it must be satisfied
		return true;
	}

	// call dependency function
	if(function_exists('dependency_' . $dependency))
		return call_user_func_array('dependency_' . $dependency, array($GLOBALS['settings']));
	elseif(isset($GLOBALS['dependency_' . $dependency]) && is_callable($GLOBALS['dependency_' . $dependency]))
		return call_user_func_array($GLOBALS['dependency_' . $dependency], array($GLOBALS['settings']));
	else
		raise_error('Dependency \'' . $dependency . '\' not defined!', E_DEBUG);
		
	return false;
}

/**
 * @}
 */

function urlencode_path($file)
{
	$dirs = explode('/', $file);
	if($dirs[0] == '' || $dirs[0] == dirname(realpath('/')))
		array_shift($dirs);
	array_walk($dirs, create_function('&$item,$key', '$item = urlencode($item);'));
	return implode('/', $dirs);
}

/**
 * This function takes a request as input, and converts it to a pretty url, or makes no changes if mod_rewrite is off <br />
 * this function also supports PATH_INFO, and will convert any available path info into a path
 * @param request The request information in QUERY_STRING format or as an associative array
 * @param not_special If it is set to false, it will not automatically encode the path to HTML
 * @param include_domain will append the website's domain to the path, useful for feeds that are downloaded
 * @param return_array will return the parsed request from any time of inputted request for further formatting
 * @return Returns a htmlspecialchars() string for a specified request
 */
function url($request = array(), $not_special = false, $include_domain = false, $return_array = false)
{
	// check if the link is offsite, if so just make sure it is valid and return
	if(is_string($request) && strpos($request, '://') !== false)
	{
		if(parse_url($request) !== false)
			return $request;
		else
			raise_error('The path \'' . $request . '\' is invalid!', E_DEBUG);
	}
	
	// if the link is a string, we need to convert it to an array for processing
	if(is_string($request))
	{
		// get query part if there it one for processing
		$query = generic_validate_query_str(array('query' => $request), 'query');

		// get the path part of the request
		$path = urldecode(generic_validate_urlpath(array('path' => $request), 'path'));
		
		// remove html_root from url
		if(substr($path, 0, strlen(setting('html_root'))) == setting('html_root'))
			$path = substr($path, strlen(setting('html_root')));
		
		// get the menu path
		$menu = get_menu_entry($path);

		// if path is not set, show an error so it can be fixed
		if(!isset($menu))
		{
			$menu = validate(array(), 'path_info');
			raise_error('Malformed URL!', E_DEBUG);
		}
		
		// invoke rewriting
		if(module_implements('rewrite', $GLOBALS['menus'][$menu]['module']))
			$request = invoke_module('rewrite', $GLOBALS['menus'][$menu]['module'], array($path));
		else
			$request = invoke_module('rewrite', 'default', array($path));
		
		// merge current request with the query string
		//  query string overrides request
		$request = array_merge($request, extract_query($query));
	}
	else
	{
		// remove urlencoding from array
		foreach($request as $key => $value)
		{
			$request[$key] = urldecode((string)$value);
		}
		
		// if pathinfo is set in the request, get the menu
		if(isset($request['path_info'])) // && setting('modrewrite'))
		{
			$path_info = $request['path_info'];
			unset($request['path_info']);
		}
	}
	
	// check with mod rewrite if paths can actually be printed out as directories
	//if(setting('modrewrite') && isset($menu))
	if(isset($menu))
		$path_info = get_path($request, $menu);
	// if we aren't using mod_rewrite, add the path info to the query string
	//elseif(isset($menu))
	//	$request['path_info'] = get_path($request, $menu);
	
	// if the caller functionality would like an array returned for further processing such as in theme() return now
	if($return_array)
	{
		if(isset($path_info))
			$request['path_info'] = $path_info;
		return $request;
	}

	// generate query string
	$query = '?';
	foreach($request as $key => $value)
	{
		$query .= (($query != '?')?'&':'') . $key . '=' . urlencode($value);
	}
	
	// generate a link, with optional domain the html root and path info prepended
	// also switch to HTTPS if the setting calls for it
	$link = (($include_domain || (setting('enable_https') && strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https')) === NULL) ? setting('html_domain') : '') . 
		setting('html_root') . 
		(isset($path_info)?urlencode_path($path_info):'') . (($query != '?')?$query:'');
	
	// optionally return a non html special chars converted URL
	if($not_special)
		return $link;
	else
		return htmlspecialchars($link, ENT_QUOTES);
}

function extract_query($query)
{
	$request = array();
	
	// process the query part
	//   this is done here because fragment takes precedence over path
	//   this allows for calling an output function modified request input
	$arr = explode('&', $query);
	if(count($arr) == 1 && $arr[0] == '')
		$arr = array();
	
	// loop through all the query string and generate our new request array
	foreach($arr as $i => $value)
	{
		// split each part of the query string into name value pairs
		$x = explode('=', $value, 2);
		
		// set each part of the query string in our new request array
		$request[$x[0]] = urldecode(isset($x[1])?$x[1]:'');
	}
	
	return $request;
}


/**
 * Change the header location to the specified request
 * @param request The string or array of request variables containing the location to go to
 */
function location($request)
{
	if(!headers_sent())
	{
		// check if we are forwarding to the same domain
		if(is_string($request) && strpos($request, '://') !== false)
		{
			header('Location: ' . $request);
		}
		// if so, verify all request variables
		else
		{
			header('Location: ' . url($request, true));
		}
		
	}
	else
	{
		register_output_vars('redirect', url($request, true));
	
		theme('redirect');	
	}
	
	// exit now so the page is redirected
	exit;
}

function ext($file)
{
	if(strpos(basename($file), '.') !== false)
		return strtolower(substr(basename($file), strrpos(basename($file), '.') + 1));
	else
		return '';
}

function array_search_key($key, $haystack, $_prefix = '')
{
    // only attempt if it is an array
    if(is_array($haystack))
    {
        foreach($haystack as $index => $value)
        {
            // return the found key
            if($index == $key && ($index !== 0 || $index === $key))
                return array(
                    0 => $value,
                    1 => $_prefix . '/' . $index,
                    'value' => $value,
                    'address' => $_prefix . '/' . $index,
                );

			if(is_array($value))
            {
                // search recursively
                $result = array_search_key($key, $value, $_prefix . '/' . $index);
				if(isset($result['value']))
					return $result;
            }
        }
    }

    // key not found
    return array(
        0 => NULL,
        1 => NULL,
        'value' => NULL,
        'address' => NULL,
    );
}

/**
 * get mime type based on file extension
 * @param ext the extension or filename to get the mime type of
 * @return a mime type based on the UNIX mime.types file
 */
function mime($ext)
{
	if(strpos($ext, '.') !== false)
	{
		$ext = ext($ext);
	}
	
	$ext = strtolower($ext);
	
	if(isset($GLOBALS['ext_to_mime'][$ext]))
	{
		return $GLOBALS['ext_to_mime'][$ext];
	}
	else
	{
		return '';
	}
}

function mime_type($ext)
{
	$mime = mime($ext);
	
	$type = explode('/', $mime);
	
	return $type[0];
}


/**
 * parses the path inside of a file, useful to handlers like archive and diskimage
 * @param file The filepath to search for the inside part of
 * @param last_path The part of the path that exists on disk
 * @param inside_path The inner part of the path that exists within the directory
 * return none
 */
function get_inner_path($file)
{
	$paths = explode('/', $file);
	$last_path = '';
	foreach($paths as $i => $tmp_file)
	{
		if(file_exists($last_path . $tmp_file) || $last_path == '')
		{
			$last_path = $last_path . $tmp_file;
			if($last_path == '' || $last_path[strlen($last_path)-1] != '/')
				$last_path .= '/';
		} else {
			if(file_exists($last_path))
				break;
		}
	}
	
	$inside_path = substr($file, strlen($last_path));
	if($last_path[strlen($last_path)-1] == '/') $last_path = substr($last_path, 0, strlen($last_path)-1);
	
	return array(
		'file' => $last_path,
		'subfile' => $inside_path,
		0 => $last_path,
		1 => $inside_path,
	);
}

/**
 * get our file types, stuff the website can handle
 * @param file The file to get the type of
 * @return 'FOLDER' if the input file is a directory, 
 * the extension of the file in uppercase format, 
 * 'FILE' if there is no extension
 */
function type($file)
{
	if( file_exists($file) )
	{
		if( is_dir($file) )
		{
			return '';
		}
		elseif( is_file($file) )
		{
			$ext = ext($file);
			if( $ext == '' )
			{
				return '';
			}
			else
			{
				return strtoupper($ext);
			}
		}
	}
	else
	{
		return false;
	}
}

function machine($text)
{
	return preg_replace('/[^a-z0-9_\[\]]/i', '_', $text);
}

/**
 * rounds the filesize and adds the extension
 * @param dirsize the number to round
 * @return a rounded number with a GB/MB/KB suffix
 */
function roundFileSize($dirsize)
{
	$dirsize = ( $dirsize < 1024 )
		?
		($dirsize . " B")
		:
		(
			( $dirsize < 1048576 )
			?
			(round($dirsize / 1024, 2) . " KB")
			:
			(
				( $dirsize < 1073741824 )
				?
				(round($dirsize / 1048576, 2) . " MB")
				:
				(
					( $dirsize < 1099511627776 )
					?
					(round($dirsize / 1073741824, 2) . " GB")
					:
					(round($dirsize / 1099511627776, 2) . " TB")
				)
			)
		);
	return $dirsize;
}


function format_file($file)
{
	foreach($file as $column => $value)
	{
		if($column == 'Filepath')
		{
			$file['Filepath'] = urlencode_path($file['Filepath']);
		}
		/*elseif(isset($GLOBALS['output']['search_regexp']) && 
			isset($GLOBALS['output']['search_regexp'][$column])
		)
		{
			$file[$column] = preg_replace($GLOBALS['output']['search_regexp'][$column], '\'<strong style="background-color:#990;">\' . htmlspecialchars(\'$0\') . \'</strong>\'', $file[$column]);
		
			continue;
		}*/
		//$file[$column] = preg_replace('/([^ ]{25})/i', '$1<br />', $file[$column]);
		elseif($column == 'Filesize')
			$file['Filesize'] = roundFileSize($file['Filesize']);
		elseif($column == 'Uncompressed')
			$file['Uncompressed'] = roundFileSize($file['Uncompressed']);
		elseif($column == 'Bitrate')
			$file['Bitrate'] = round($file['Bitrate'] / 1000, 1) . ' kbs';
		elseif($column == 'Length')
			$file['Length'] = floor($file['Length'] / 60) . ' minutes ' . floor($file['Length'] % 60) . ' seconds';
	}
	return $file;
}


function output_ranges($filesize)
{
	//-------------------- THIS IS ALL RANAGES STUFF --------------------
	// range can only be used when the filesize is known
	
	// if the filesize is still not known, just output the stream without any fancy stuff
	if(isset($filesize))
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
		$seek_end = (empty($seek_end)) ? ($filesize - 1) : min(abs(intval($seek_end)),($filesize - 1));
		//$seek_end = $file['Filesize'] - 1;
		$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
		
		// Only send partial content header if downloading a piece of the file (IE workaround)
		if ($seek_start > 0 || $seek_end < ($filesize - 1))
		{
			header('HTTP/1.1 206 Partial Content');
		}

		header('Accept-Ranges: bytes');
		header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $filesize);
	
		//headers for IE Bugs (is this necessary?)
		//header("Cache-Control: cache, must-revalidate");  
		//header("Pragma: public");
	
		header('Content-Length: ' . ($seek_end - $seek_start + 1));
	}
	
	//-------------------- END RANAGES STUFF --------------------
	
	return array(
		0 => $seek_start,
		1 => $seek_end,
		'start' => $seek_start,
		'end' => $seek_end
	);
}

function output_stream($if, $of, $kill_with_connection = true)
{
	stream_set_blocking($if, 0);
	stream_set_blocking($of, 0);
	
	while(!feof($if))
	{
		if($kill_with_connection && connection_status()!=0)
		{
			fclose($if);
			fclose($of);
			break;
		}
		
		$buffer = fread($if, setting('buffer_size'));
		fwrite($of, $buffer);
		fflush($of);
	}
	fclose($if);
	fclose($of);
}

function output_input_stream(&$if, &$is, &$os, &$of, $kill_with_connection = true)
{
	stream_set_blocking($if, 0);
	stream_set_blocking($is, 0);
	stream_set_blocking($os, 0);
	stream_set_blocking($of, 0);
	
	$in_buffer = '';
	$if_closed = false;
	$out_buffer = '';
	$count = 0;
	while(!feof($os) || (!$if_closed && !feof($if)))
	{
		// if the connection was interrupted then stop looping
		if($kill_with_connection && connection_status()!=0)
		{
			fclose($if);
			fclose($is);
			fclose($os);
			fclose($of);
			break;
		}
		
		if(!$if_closed && feof($if))
		{
			$if_closed = true;
		}
		
		if(feof($os) || $count == 1000)
		{
			break;
		}
		
		// set up the pipes to be checked
		$read = array($os);
		$write = array($is);
		$except = NULL;

		// select the pipes that are available for reading and writing
		stream_select($read, $write, $except, NULL);

		// if we can read then read more and send it out to php
		if(in_array($os, $read))
		{
			$out_buffer = fread($os, setting('buffer_size'));
			fwrite($of, $out_buffer);
			fflush($of);
		}
		// if the count gets to high and nothing can be read then exit
		//else
		//{
		//	sleep(1);
		//	$count++;
		//}
		
		// if we can write then write more from the input stream
		if(in_array($is, $write))
		{
			if(!$if_closed)
				$in_buffer .= fread($if, setting('buffer_size') - strlen($in_buffer));
			$in_count = fwrite($is, $in_buffer);
			if($in_count < strlen($in_buffer))
				$in_buffer = substr($in_buffer, $in_count);
			else
				$in_buffer = '';
			fflush($is);
		}
	}
	fclose($if);
	fclose($is);
	fclose($os);
	fclose($of);
}

/**
 * kill a process on linux, for some reason closing the streams isn't working
 * @param command the command to be killed
 * @param startpid the estimate PID of the process to kill
 * @param limit the limit for how many process back to kill 
 */
function kill9($command, $startpid, $limit = 2)
{
	$ps = `ps -u www-data --sort=pid -o comm= -o pid=`;
	$ps_lines = explode("\n", $ps);
	
	$pattern = "/(\S{1,})(\s{1,})(\d{1,})/";
	
	foreach($ps_lines as $line)
	{
		if(preg_match($pattern, $line, $matches))
		{
			//this limits us to finding the command within $limit pid's of the parent;
			//eg, if ppid = 245, limit = 3, we won't search past 248
			if($matches[3] > $startpid + $limit)
				break;
	
			//try to match a ps line where the command matches our search
			//at a higher pid than our parent
			if($matches[1] == $command && $matches[3] > $startpid)
			{
				system('/bin/kill -9 ' . $matches[3]);
			}
		}
	}
}

/**
 * stolen from PEAR, 
 * DSN parser for use internally
 * @return an associative array of parsed DSN information
 */
function parseDSN($dsn)
{
	$parsed = array();
	if (is_array($dsn)) {
		$dsn = array_merge($parsed, $dsn);
		if (!$dsn['dbsyntax']) {
			$dsn['dbsyntax'] = $dsn['phptype'];
		}
		return $dsn;
	}

	// Find phptype and dbsyntax
	if (($pos = strpos($dsn, '://')) !== false) {
		$str = substr($dsn, 0, $pos);
		$dsn = substr($dsn, $pos + 3);
	} else {
		$str = $dsn;
		$dsn = null;
	}

	// Get phptype and dbsyntax
	// $str => phptype(dbsyntax)
	if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
		$parsed['phptype']  = $arr[1];
		$parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
	} else {
		$parsed['phptype']  = $str;
		$parsed['dbsyntax'] = $str;
	}

	if (!count($dsn)) {
		return $parsed;
	}

	// Get (if found): username and password
	// $dsn => username:password@protocol+hostspec/database
	if (($at = strrpos($dsn,'@')) !== false) {
		$str = substr($dsn, 0, $at);
		$dsn = substr($dsn, $at + 1);
		if (($pos = strpos($str, ':')) !== false) {
			$parsed['username'] = rawurldecode(substr($str, 0, $pos));
			$parsed['password'] = rawurldecode(substr($str, $pos + 1));
		} else {
			$parsed['username'] = rawurldecode($str);
		}
	}

	// Find protocol and hostspec

	// $dsn => proto(proto_opts)/database
	if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
		$proto       = $match[1];
		$proto_opts  = $match[2] ? $match[2] : false;
		$dsn         = $match[3];

	// $dsn => protocol+hostspec/database (old format)
	} else {
		if (strpos($dsn, '+') !== false) {
			list($proto, $dsn) = explode('+', $dsn, 2);
		}
		if (   strpos($dsn, '//') === 0
			&& strpos($dsn, '/', 2) !== false
			&& $parsed['phptype'] == 'oci8'
		) {
			//oracle's "Easy Connect" syntax:
			//"username/password@[//]host[:port][/service_name]"
			//e.g. "scott/tiger@//mymachine:1521/oracle"
			$proto_opts = $dsn;
			$dsn = substr($proto_opts, strrpos($proto_opts, '/') + 1);
		} elseif (strpos($dsn, '/') !== false) {
			list($proto_opts, $dsn) = explode('/', $dsn, 2);
		} else {
			$proto_opts = $dsn;
			$dsn = null;
		}
	}

	// process the different protocol options
	$parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
	$proto_opts = rawurldecode($proto_opts);
	if (strpos($proto_opts, ':') !== false) {
		list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
	}
	if ($parsed['protocol'] == 'tcp') {
		$parsed['hostspec'] = $proto_opts;
	} elseif ($parsed['protocol'] == 'unix') {
		$parsed['socket'] = $proto_opts;
	}

	// Get dabase if any
	// $dsn => database
	if ($dsn) {
		// /database
		if (($pos = strpos($dsn, '?')) === false) {
			$parsed['database'] = $dsn;
		// /database?param1=value1&param2=value2
		} else {
			$parsed['database'] = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 1);
			if (strpos($dsn, '&') !== false) {
				$opts = explode('&', $dsn);
			} else { // database?param1=value1
				$opts = array($dsn);
			}
			foreach ($opts as $opt) {
				list($key, $value) = explode('=', $opt);
				if (!isset($parsed[$key])) {
					// don't allow params overwrite
					$parsed[$key] = rawurldecode($value);
				}
			}
		}
	}

	return $parsed;
}
