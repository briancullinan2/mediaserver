<?php

require_once SITE_LOCALROOT . 'modules/db_file.php';

// include the id handler
require_once SITE_LOCALROOT . 'include/ID3/getid3.php';

// set up id3 reader incase any files need it
$getID3 = new getID3();

// music handler
class db_audio extends db_file
{
	const DATABASE = 'audio';
	
	const NAME = 'Audio';

	static function columns()
	{
		return array('id', 'Track', 'Title', 'Artist', 'Album', 'Genre', 'Year', 'Length', 'Bitrate', 'Comments', 'Filepath');
	}
	

	static function handles($file)
	{
				
		// get file extension
		$ext = getExt($file);
		$type = getExtType($ext);
		
		if( $type == 'audio' )
		{
			return true;
		}
		
		return false;

	}

	static function handle($mysql, $file)
	{
			
		if(db_audio::handles($file))
		{
			// check to see if it is in the database
			$db_audio = $mysql->get('audio',
				array(
					'SELECT' => 'id',
					'WHERE' => 'Filepath = "' . $file . '"'
				)
			);
			
			// try to get music information
			if( count($db_audio) == 0 )
			{
				$fileid = db_audio::add($mysql, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $mysql->get('files', 
					array(
						'SELECT' => 'Filedate',
						'WHERE' => 'Filepath = "' . $file . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = db_audio::add($mysql, $file, $db_audio[0]['id']);
				}
				
			}

		}
		
	}

	static function add($mysql, $file, $audio_id = NULL)
	{
		global $getID3;
	
		$info = $getID3->analyze($file);
		getid3_lib::CopyTagsToComments($info);
		
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = $file;
		$fileinfo['Title'] = @$info['comments_html']['title'][0];
		$fileinfo['Artist'] = @$info['comments_html']['artist'][0];
		$fileinfo['Album'] = @$info['comments_html']['album'][0];
		$fileinfo['Track'] = @$info['comments_html']['track'][0];
		$fileinfo['Year'] = @$info['comments_html']['year'][0];
		$fileinfo['Genre'] = @$info['comments_html']['genre'][0];
		$fileinfo['Length'] = @$info['playtime_seconds'];
		$fileinfo['Comments'] = @$info['comments_html']['comments'][0];
		$fileinfo['Bitrate'] = @$info['bitrate'];
	
		if( $audio_id != NULL )
		{
			print 'Modifying audio: ' . $file . "\n";
			
			// update database
			$id = $mysql->set('audio', $fileinfo, array('id' => $audio_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding audio: ' . $file . "\n";
			
			// add to database
			$id = $mysql->set('audio', $fileinfo);
			
			return $id;
		}
		
		usleep(1);
		
		ob_flush();
		
	}
	
	
	static function get($mysql, $props)
	{
		$files = array();
		
		if($mysql == NULL)
		{
			if (is_dir($props['DIR']))
			{
				// if we are over 5.0 use the scandir method
				if( version_compare(phpversion(), "5.0.0") >= 0 )
				{
					return scandir($props['DIR']);
				}
				else
				{
					// create file array
					if ($dh = opendir($props['DIR']))
					{
						while (($file = readdir($dh)) !== false)
						{
							if(db_audio::handles($props['DIR'] . $file))
							{
								$files[] = ($props['DIR'] . $file);
							}
						}
						closedir($dh);
					}
					
				}
				
			}
		}
		else
		{
			// construct where statement
			if(isset($props['DIR']) && !isset($props['WHERE']))
			{
				$props['WHERE'] = 'Filepath REGEXP "^' . $dir . '"';
			}
		
			$props['SELECT'] = db_audio::columns();

			// get directory from database
			$files = $mysql->get(db_audio::DATABASE, $props);
		}
			
		return $files;
	}


	static function cleanup($mysql, $watched)
	{
		// call default cleanup function
		parent::cleanup($mysql, $watched, get_class_const(get_class(), 'DATABASE'));
	}

}

?>
