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

	static function parseInner($file, &$last_path, &$inside_path)
	{
		$paths = split('/', $file);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path . $tmp_file)) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . '/';
			} else {
				if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
					break;
			}
		}
		
		$inside_path = substr($file, strlen($last_path));
		if($last_path[strlen($last_path)-1] == '/') $last_path = substr($last_path, 0, strlen($last_path)-1);
	}

	// return whether or not this module handles trhe specified type of file
	static function handles($file, $internals = false)
	{
		$file = str_replace('\\', '/', $file);
		
		if(USE_DATABASE && $internals == false)
		{
			return false;
		}
		else
		{
			if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)) || is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
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
	}
		
	static function getInfo($file)
	{
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$fileinfo = array();
		$fileinfo['id'] = bin2hex($file);
		$fileinfo['Filepath'] = str_replace('\\', '/', $file);
		$fileinfo['Filename'] = basename($file);
		$fileinfo['Filesize'] = filesize($file);
		$fileinfo['Filemime'] = getMime($file);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
		$fileinfo['Filetype'] = getFileType($file);
		
		return $fileinfo;
	}
	
	// output provided file to given stream
	static function out($database, $file)
	{
		$file = str_replace('\\', '/', $file);
		
		// check to make sure file is valid
		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
		{
			if($fp = fopen($file, 'rb'))
			{
				return $fp;
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
					$file = str_replace('\\', '/', @pack('H*', $id));
					if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
					{
						if(call_user_func($module . '::handles', $file))
						{
							$info = call_user_func($module . '::getInfo', $file);
							
							// make some modifications
							if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= '/';
							$files[] = $info;
						}
					}
				}
			}
			
			if(isset($request['file']))
			{
				$request['file'] = str_replace('\\', '/', $request['file']);
				if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $request['file'])))
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
				$request['dir'] = str_replace('\\', '/', $request['dir']);
					
				// check to make sure is it a valid directory before continuing
				if (is_dir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])))
				{
					// scandir - read in a list of the directory content
					$tmp_files = scandir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir']));
					$count = count($tmp_files);
					
					// parse out all the files that this module doesn't handle, just like a filter
					//  but only if we are not called by internals
					for($j = 0; $j < $count; $j++)
						if(!call_user_func($module . '::handles', $request['dir'] . $tmp_files[$j], $internals)) unset($tmp_files[$j]);
						
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
						if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= '/';
						
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