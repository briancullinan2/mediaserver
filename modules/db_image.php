<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_image extends db_file
{
	const DATABASE = 'image';
	
	const NAME = 'Images from Database';

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

	static function handle($database, $file)
	{
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['HARD']['alias_regexp'], $GLOBALS['HARD']['paths'], $file);

		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_image = $database->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get image information
			if( count($db_image) == 0 )
			{
				$fileid = self::add($database, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $database->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = self::add($database, $file, $db_image[0]['id']);
				}
				
			}

		}
		
	}
	
	static function getInfo($file)
	{
		$priority = array_reverse(self::PRIORITY());
		$info = $GLOBALS['getID3']->analyze($file);
		
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
		
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
		//$fileinfo['Thumbnail'] = addslashes(self::makeThumbs($file));
		
		return $fileinfo;
	}

	static function add($database, $file, $image_id = NULL)
	{
		$fileinfo = self::getInfo($file);
	
		if( $image_id != NULL )
		{
			log_error('Modifying image: ' . $file);
			
			// update database
			$id = $database->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $image_id));
		
			return $image_id;
		}
		else
		{
			log_error('Adding image: ' . $file);
			
			// add to database
			$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			
			return $id;
		}
			
	}
	
	static function get($database, $request, &$count, &$error)
	{
		return parent::get($database, $request, $count, $error, get_class());
	}

	
	static function cleanup($database, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($database, $watched, $ignored, get_class());
	}

}

?>