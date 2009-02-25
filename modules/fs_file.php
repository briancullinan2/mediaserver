<?php

// the basic file type

class fs_file
{
	// most of these methods should just be static, no need to intantiate the class
	// just good for organization purposes
	const NAME = 'Files on Filesystem';
	
	// this function specifies the level of detail for the array of file info, ORDER matters!
	static function columns()
	{
		return array('id', 'Filename', 'Filemime', 'Filesize', 'Filedate', 'Filetype', 'Filepath');
	}

	// return whether or not this module handles trhe specified type of file
	static function handles($file)
	{
		if(is_dir($file) || is_file($file))
		{
			$filename = basename($file);

			// make sure it isn't a hidden file
			if($filename[0] != '.')
				return true;
			else
				return false;
		} else {
			return false;
		}
	}
		
	static function getInfo($file)
	{
		$fileinfo = array();
		$fileinfo['id'] = bin2hex($file);
		$fileinfo['Filepath'] = $file;
		$fileinfo['Filename'] = basename($file);
		$fileinfo['Filesize'] = filesize($file);
		$fileinfo['Filemime'] = getMime($file);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
		$fileinfo['Filetype'] = getFileType($file);
		
		return $fileinfo;
	}
	
	// output provided file to given stream
	static function out($database, $file, $stream)
	{
		// check to make sure file is valid
		if(is_file($file))
		{
			
			if(is_string($stream))
				$op = fopen($stream, 'wb');
			else
				$op = $stream;
			
			if($op !== false)
			{
				if($fp = fopen($file, 'rb'))
				{
					if(isset($_SESSION)) session_write_close();
						
					// set up some general headers
					header('Content-Transfer-Encoding: binary');
					header('Content-Type: ' .  getMime($file));
					header('Content-Disposition: attachment; filename="' . basename($file) . '"');
					
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
					$seek_end = (empty($seek_end)) ? (filesize($file) - 1) : min(abs(intval($seek_end)),(filesize($file) - 1));
					//$seek_end = $file['Filesize'] - 1;
					$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
					
					// Only send partial content header if downloading a piece of the file (IE workaround)
					if ($seek_start > 0 || $seek_end < (filesize($file) - 1))
					{
						header('HTTP/1.1 206 Partial Content');
					}
			
					header('Accept-Ranges: bytes');
					header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . filesize($file));
				
					//headers for IE Bugs (is this necessary?)
					//header("Cache-Control: cache, must-revalidate");  
					//header("Pragma: public");
				
					header('Content-Length: ' . ($seek_end - $seek_start + 1));
					
					// seek to start of missing part
					fseek($fp, $seek_start);
					
					while (!feof($fp)) {
						fwrite($op, fread($fp, BUFFER_SIZE));
					}				
					fclose($fp);
					fclose($op);
					return true;
				}
			}
		}
		return false;
	}
	
	
	// the mysql can be left null to get the files from a directory, in which case a directory must be specified
	// if the mysql is provided, then the file listings will be loaded from the database
	// don't use $internals = true
	static function get($database, $request, &$count, &$error, $internals = false)
	{
		$files = array();
		
		if(!is_bool($internals))
		{
			$module = $internals;
			$internals = false;
		}
		else
		{
			$module = get_class();
		}
		
		// only allow this section to run if the database is not being used
		//  otherwise it could be vulnerable to people accessing any file even on a system restricted to database access
		if(!USE_DATABASE || $internals)
		{
			// do validation! for the fields we use
			if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
				$request['start'] = 0;
			if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
				$request['limit'] = 15;
			if( isset($request['id']) )
				$request['item'] = $request['id'];
				
			getIDsFromRequest($request, $request['selected']);

			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				foreach($request['selected'] as $i => $id)
				{
					$file = pack('H*', $id);
					if(is_file($file))
					{
						if(call_user_func($module . '::handles', $file))
						{
							$info = call_user_func($module . '::getInfo', $file);
							// make some modifications
							if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
							$files[] = $info;
						}
					}
				}
			}
			
			if(isset($request['file']))
			{
				if(is_file($request['file']))
				{
					if(call_user_func($module . '::handles', $request['file']))
					{
						return array(0 => call_user_func($module . '::getInfo', $request['file']));
					}
					else{ $error = 'Invalid file!'; }
				}
				else{ $error = 'File does not exist!'; }
			}
			else
			{
				// set a directory if one isn't set already
				if(!isset($request['dir']))
					$request['dir'] = realpath('/');
				// check to make sure is it a valid directory before continuing
				if (is_dir($request['dir']))
				{
					// scandir - read in a list of the directory content
					$tmp_files = scandir($request['dir']);
					$count = count($tmp_files);
					// parse out all the files that this module doesn't handle, just like a filter
					for($j = 0; $j < $count; $j++)
						if(!call_user_func($module . '::handles', $request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					// get the values again, this will reset all the indices
					$tmp_files = array_values($tmp_files);
					// set the count to the total length of the file list
					$count = count($tmp_files);
					// start the information getting and combining of file info
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						// get the information from the module for 1 file
						$info = call_user_func($module . '::getInfo', $request['dir'] . $tmp_files[$i]);
						// make some modifications
						if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
						// set the informations in the total list of files
						$files[] = $info;
					}
					return $files;
				}
				else{ $error = 'Directory does not exist!'; return false; }
			}
		}
			
		return $files;
	}
		
}

?>