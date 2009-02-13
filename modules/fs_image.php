<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class fs_image extends fs_file
{
	const NAME = 'Images on Filesystem';

	static function columns()
	{
		return array('id', 'Height', 'Width', 'Make', 'Model', 'Title', 'Keywords', 'Author', 'Comments', 'ExposureTime', 'Filepath');
	}
	
	
	// this is the priority of sections to check for picture information
	// from most accurate --> least accurate
	static function PRIORITY()
	{
		return array('COMPUTED', 'WINXP', 'IFD0', 'EXIF', 'THUMBNAIL');
	}

	// COMPUTED usually contains the most accurate height and width values
	// IFD0 contains the make and model we are looking for
	// WINXP contains comments we should copy
	// EXIF contains a cool exposure time
	// THUMBNAIL just incase the thumbnail has some missing information
	
	static function handles($file)
	{
				
		// get file extension
		$ext = getExt(basename($file));
		$type = getExtType($ext);
		
		if( $type == 'image' )
		{
			return true;
		}
		
		return false;

	}

	static function getInfo($file)
	{
		$priority = array_reverse(fs_image::PRIORITY());
		$info = $GLOBALS['getID3']->analyze($file);
		
		// pull information from $info
		$fileinfo = array();
		$fileinfo['id'] = bin2hex($file);
		$fileinfo['Filepath'] = $file;
		
		// get information from sections
		if(isset($info['fileformat']) && isset($info[$info['fileformat']]['exif']))
		{
			$exif = $info[$info['fileformat']]['exif'];
			foreach($priority as $i => $section)
			{
				if(isset($exif[$section]))
				{
					foreach($exif[$section] as $key => $value)
					{
						if($key == 'Height' || $key == 'Width' || $key == 'Make' || $key == 'Model' || $key == 'Comments' || $key == 'Keywords' || $key == 'Title' || $key == 'Author' || $key == 'ExposureTime')
						{
							$fileinfo[$key] = $value;
						}
					}
				}
			}
		}
	
		// do not get thumbnails of image
		//$fileinfo['Thumbnail'] = addslashes(fs_image::makeThumbs($file));
		
		return $fileinfo;
	}

	static function get($mysql, $request, &$count, &$error)
	{
		$files = array();
		
		if(!USE_DATABASE)
		{
			// do validation! for the fields we use
			if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
				$request['start'] = 0;
			if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
				$request['limit'] = 15;
			if( !isset($request['order_by']) || !in_array($request['order_by'], fs_image::columns()) )
				$request['order_by'] = 'Title';
			if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
				$request['direction'] = 'ASC';
			if( isset($request['id']) )
				$request['item'] = $request['id'];
			getIDsFromRequest($request, $request['selected']);

			if(isset($request['selected']))
			{
				foreach($request['selected'] as $i => $id)
				{
					$file = pack('H*', $id);
					if(is_file($file))
					{
						if(fs_image::handles($file))
						{
							$info = fs_image::getInfo($file);
							// make some modifications
							$files[] = $info;
						}
					}
				}
			}
			
			if(isset($request['file']))
			{
				if(is_file($request['file']))
				{
					if(fs_image::handles($request['file']))
					{
						return array(0 => fs_image::getInfo($request['file']));
					}
					else{ $error = 'Invalid ' . fs_image::NAME . ' file!'; }
				}
				else{ $error = 'File does not exist!'; }
			}
			else
			{
				if(!isset($request['dir']))
					$request['dir'] = realpath('/');
				if (is_dir($request['dir']))
				{
					$tmp_files = scandir($request['dir']);
					$count = count($tmp_files);
					for($j = 0; $j < $count; $j++)
						if(!fs_image::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					$tmp_files = array_values($tmp_files);
					$count = count($tmp_files);
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						$info = fs_image::getInfo($request['dir'] . $tmp_files[$i]);
						$files[] = $info;
					}
					return $files;
				}
				else{ $error = 'Directory does not exist!'; }
			}
		}
			
		return $files;
	}

}

?>