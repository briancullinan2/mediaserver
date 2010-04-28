<?php
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_image extends db_file
{
	const DATABASE = 'image';
	
	const NAME = 'Images from Database';

	static function init()
	{
		// include the id handler
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
		
		// set up id3 reader incase any files need it
		$GLOBALS['getID3'] = new getID3();
	}

	static function columns()
	{
		return array_keys(self::struct());
	}
	
	static function struct()
	{
		return array(
			'Filepath' 		=> 'TEXT',
			'Height'		=> 'INT',
			'Width'			=> 'INT',
			'Make'			=> 'TEXT',
			'Model'			=> 'TEXT',
			'Comments'		=> 'TEXT',
			'Keywords'		=> 'TEXT',
			'Title'			=> 'TEXT',
			'Author'		=> 'TEXT',
			'ExposureTime'	=> 'TEXT'
		);
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
		$file = str_replace('\\', '/', $file);
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
				
		// get file extension
		$type = getExtType($file);
		
		if( $type == 'image' )
		{
			return true;
		}
		
		return false;

	}

	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_image = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			, false);
			
			// try to get image information
			if( count($db_image) == 0 )
			{
				return self::add($file);
			}
			elseif($force)
			{
				return self::add($file, $db_image[0]['id']);
			}

		}
		return false;
	}
	
	static function getInfo($file)
	{
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$priority = array_reverse(self::PRIORITY());
		$info = $GLOBALS['getID3']->analyze($file);
		
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $file));
		
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

	static function add($file, $image_id = NULL)
	{
		if(!isset($GLOBALS['getID3']))
			self::init();
		
		$fileinfo = self::getInfo($file);
	
		if( $image_id == NULL )
		{
			PEAR::raiseError('Adding image: ' . $file, E_DEBUG);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
			
			return $id;
		}
		else
		{
			PEAR::raiseError('Modifying image: ' . $file, E_DEBUG);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $image_id), false);
		
			return $image_id;
		}
			
	}
	
	static function get($request, &$count)
	{
		return parent::get($request, $count, get_class());
	}
	
	static function remove($file)
	{
		parent::remove($file, get_class());
	}
	
	static function cleanup()
	{
		// call default cleanup function
		parent::cleanup(get_class());
	}

}

?>