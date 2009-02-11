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
	
	const NAME = 'Image';

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
		if(file_exists($file))
		{
			$ext = getExt($file);
			$type = getExtType($ext);
			
			if( $type == 'image' )
			{
				return true;
			}
		}
		
		return false;

	}

	static function handle($mysql, $file)
	{

		if(db_image::handles($file))
		{
			// check to see if it is in the database
			$db_image = $mysql->get('image',
				array(
					'SELECT' => 'id',
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
				$db_file = $mysql->get('files',
					array(
						'SELECT' => 'Filedate',
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
		$fileinfo = db_file::getInfo($file);
	
		if( $image_id != NULL )
		{
			print 'Modifying image: ' . $file . "\n";
			
			// update database
			$id = $mysql->set('image', $fileinfo, array('id' => $image_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding image: ' . $file . "\n";
			
			// add to database
			$id = $mysql->set('image', $fileinfo);
			
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
		// do validation! for the fields we use
		if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
			$request['start'] = 0;
		if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
			$request['limit'] = 15;
		if( !isset($request['order_by']) || !in_array($request['order_by'], db_image::columns()) )
			$request['order_by'] = 'Title';
		if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
			$request['direction'] = 'ASC';
		if( isset($request['id']) )
			$request['item'] = $request['id'];
		getIDsFromRequest($request, $request['selected']);

		$files = array();
		
		if($mysql == NULL)
		{
			if(isset($request['file']))
			{
				if(is_file($request['file']))
				{
					if(db_image::handles($request['file']))
					{
						return array(0 => db_image::getInfo($request['file']));
					}
					else{ $error = 'Invalid ' . db_image::NAME . ' file!'; }
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
						if(!db_image::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					$tmp_files = array_values($tmp_files);
					$count = count($tmp_files);
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						$info = db_image::getInfo($request['dir'] . $tmp_files[$i]);
						$info['Filepath'] = stripslashes($info['Filepath']);
						if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
						$files[] = $info;
					}
					return $files;
				}
				else{ $error = 'Directory does not exist!'; }
			}
		}
		else
		{
			$props = array();
			
			$props['OTHER'] = ' ORDER BY ' . $request['order_by'] . ' ' . $request['direction'] . ' LIMIT ' . $request['start'] . ',' . $request['limit'];
			
			// select an array of ids!
			if( isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = 'id=' . join(' OR id=', $selected);
				unset($props['OTHER']);
			}
			
			// add where includes
			if(isset($request['includes']) && $request['includes'] != '')
			{
				$props['WHERE'] = '';
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true) $request['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['includes']);
				$regexp = addslashes(addslashes($request['includes']));
				
				// add a regular expression matching for each column in the table being searched
				$props['WHERE'] .= '(';
				foreach($columns as $i => $column)
				{
					$columns[$i] .= ' REGEXP "' . $regexp . '"';
				}
				$props['WHERE'] .= join(' OR ', $columns) . ')';
			}
			
			// add dir filter to where
			if(isset($request['dir']))
			{
				if($request['dir'] == '') $request['dir'] = DIRECTORY_SEPARATOR;
				if($request['dir'][0] == '/' || $request['dir'][0] == '\\') $request['dir'] = realpath('/') . substr($request['dir'], 1);
				if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
				if(realpath($request['dir']) !== false && is_dir(realpath($request['dir'])))
				{
					$dirs = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					if($request['dir'] == realpath('/') || count($dirs) > 0)
					{
						if(!isset($props['WHERE'])) $props['WHERE'] = '';
						elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
						if(!isset($request['includes']))
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '?$"';
						}
						else
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '([^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ')*$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '"';
						}
					} else { $error = 'Directory does not exist!'; }
				} else { $error = 'Directory does not exist!'; }
			}
			
			// add file filter to where
			if(isset($request['file']))
			{
				if($request['file'][0] == '/' || $request['file'][0] == '\\') $request['file'] = realpath('/') . substr($request['file'], 1);
				if(USE_ALIAS == true) $request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
				if(realpath($request['file']) !== false && is_file(realpath($request['file'])))
				{
					if(!isset($props['WHERE'])) $props['WHERE'] = '';
					elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
					$props['WHERE'] .= ' Filepath = "' . addslashes($request['file']) . '"';
				} else { $error = 'File does not exist!'; }
			}
		
			$props['SELECT'] = db_image::columns();

			// get directory from database
			$files = $mysql->get(db_image::DATABASE, $props);
			
			// this is how we get the count of all the items
			unset($props['OTHER']);
			$props['SELECT'] = 'count(*)';
			
			$result = $mysql->get(db_image::DATABASE, $props);
			
			$count = intval($result[0]['count(*)']);
		}
			
		return $files;
	}

	
	static function cleanup($mysql, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($mysql, $watched, $ignored, constant(get_class() . '::DATABASE'));
	}

}

?>