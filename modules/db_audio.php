<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_audio extends db_file
{
	const DATABASE = 'audio';
	
	const NAME = 'Audio from Database';

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

	static function handle($mysql, $file)
	{
		if(db_audio::handles($file))
		{
			// check to see if it is in the database
			$db_audio = $mysql->get('audio',
				array(
					'SELECT' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
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
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
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
	
	static function getInfo($file)
	{
		$info = $GLOBALS['getID3']->analyze($file);
		getid3_lib::CopyTagsToComments($info);

		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
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

	static function add($mysql, $file, $audio_id = NULL)
	{
		// pull information from $info
		$fileinfo = db_audio::getInfo($file);
	
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
		
		flush();
		
	}
	
	
	static function get($mysql, $request, &$count, &$error)
	{
		$files = array();
		
		if(USE_DATABASE)
		{
			// do validation! for the fields we use
			if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
				$request['start'] = 0;
			if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
				$request['limit'] = 15;
			if( !isset($request['order_by']) || !in_array($request['order_by'], db_audio::columns()) )
				$request['order_by'] = 'Title';
			if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
				$request['direction'] = 'ASC';
			if( isset($request['id']) )
				$request['item'] = $request['id'];
			getIDsFromRequest($request, $request['selected']);

			$props = array();
			
			$props['OTHER'] = ' ORDER BY ' . $request['order_by'] . ' ' . $request['direction'] . ' LIMIT ' . $request['start'] . ',' . $request['limit'];
			
			// select an array of ids!
			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = '';
				foreach($request['selected'] as $i => $id)
				{
					if(is_numeric($id)) {
						$props['WHERE'] .= ' id=' . $id . ' OR';
					} else {
						$props['WHERE'] .= ' Filepath="' . addslashes(pack('H*', $id)) . '" OR';
					}
				}
				$props['WHERE'] = substr($props['WHERE'], 0, strlen($props['WHERE'])-2);
				unset($props['OTHER']);
				unset($request);
			}
			
			// add where includes
			if(isset($request['includes']) && $request['includes'] != '')
			{
				$props['WHERE'] = '';
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true) $request['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['includes']);
				$regexp = addslashes(addslashes($request['includes']));
				
				$columns = db_audio::columns();
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
				} else { $error = 'File does not exist!11'; }
			}
		
			if($error == '')
			{
				$props['SELECT'] = db_audio::columns();
	
				// get directory from database
				$files = $mysql->get(db_audio::DATABASE, $props);
				
				// this is how we get the count of all the items
				unset($props['OTHER']);
				$props['SELECT'] = 'count(*)';
				
				$result = $mysql->get(db_audio::DATABASE, $props);
				
				$count = intval($result[0]['count(*)']);
			}
			else
			{
				$count = 0;
				$files = array();
			}
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
