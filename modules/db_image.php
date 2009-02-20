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

	static function handle($mysql, $file)
	{

		if(db_image::handles($file))
		{
			// check to see if it is in the database
			$db_image = $mysql->query(array(
					'SELECT' => db_image::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_image) == 0 )
			{
				$fileid = db_image::add($mysql, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $mysql->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = db_image::add($mysql, $file, $db_image[0]['id']);
				}
				
			}

		}
		
	}
	
	static function getInfo($file)
	{
		$priority = array_reverse(db_image::PRIORITY());
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
		//$fileinfo['Thumbnail'] = addslashes(db_image::makeThumbs($file));
		
		return $fileinfo;
	}

	static function add($mysql, $file, $image_id = NULL)
	{
		$fileinfo = db_image::getInfo($file);
	
		if( $image_id != NULL )
		{
			print 'Modifying image: ' . $file . "\n";
			
			// update database
			$id = $mysql->query(array('UPDATE' => 'image', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $image_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding image: ' . $file . "\n";
			
			// add to database
			$id = $mysql->query(array('INSERT' => 'image', 'VALUES' => $fileinfo));
			
			return $id;
		}

		flush();
			
	}
	
	
	// generate three different size thumbnails
	// returns an array of thumbnails
	static function makeThumbs($file)
	{
		$tmp_name = TMP_DIR . md5($file) . '.jpg';
		if(file_exists($tmp_name)) $tmp_name = $tmp_name = TMP_DIR . md5($file . microtime()) . '.jpg';
		
		// first make highest size thumb
		$cmd = CONVERT . ' "' . $file . '[0]" -resize "512x512" -format jpeg:-';
		exec($cmd, $out, $ret);
		
		if($ret != 0)
		{
			print 'Error: Cannot create thumbnail (' . $file . ' -> ' . $tmp_name . ').';
			return '';
		}
		
		// read in image into array
		$fp = fopen($tmp_name, 'r');
		$output = fread($fp, filesize($tmp_name));
		fclose($fp);

		// delete tmp file
		unlink($tmp_name);
		
		print 'Created thumbs: ' . $file . "\n";

		return $output;
	}
	
	
	static function get($mysql, $request, &$count, &$error)
	{
		return parent::get($mysql, $request, $count, $error, get_class());
	}

	
	static function cleanup($mysql, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($mysql, $watched, $ignored, get_class());
	}

}

?>