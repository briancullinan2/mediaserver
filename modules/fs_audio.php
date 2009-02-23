<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class fs_audio extends fs_file
{
	const NAME = 'Audio on Filesystem';

	static function columns()
	{
		return array('id', 'Track', 'Title', 'Artist', 'Album', 'Genre', 'Year', 'Length', 'Bitrate', 'Comments', 'Filepath');
	}
	
	static function handles($file)
	{
				
		// get file extension
		$ext = getExt(basename($file));
		$type = getExtType($ext);
		
		if( $type == 'audio' )
		{
			return true;
		}
	
		return false;

	}
	
	static function getInfo($file)
	{
		$info = $GLOBALS['getID3']->analyze($file);
		getid3_lib::CopyTagsToComments($info);

		$fileinfo = array();
		$fileinfo['id'] = bin2hex($file);
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
		
		return $fileinfo;
	}
	
	static function get($database, $request, &$count, &$error)
	{
		$files = array();
		
		if(!USE_DATABASE)
		{
			// do validation! for the fields we use
			$database->validate($request, $props, $module);

			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				foreach($request['selected'] as $i => $id)
				{
					$file = pack('H*', $id);
					if(is_file($file))
					{
						if(fs_audio::handles($file))
						{
							$info = fs_audio::getInfo($file);
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
					if(fs_audio::handles($request['file']))
					{
						return array(0 => fs_audio::getInfo($request['file']));
					}
					else{ $error = 'Invalid ' . fs_audio::NAME . ' file!'; }
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
						if(!fs_audio::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					$tmp_files = array_values($tmp_files);
					$count = count($tmp_files);
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						$info = fs_audio::getInfo($request['dir'] . $tmp_files[$i]);
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
