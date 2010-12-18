<?php


class Monolithic_WebDAV_Server extends HTTP_WebDAV_Server
{
	
	function Monolithic_WebDAV_Server()
	{
        $this->_SERVER = $_SERVER;
	}
	
    function ServeRequest($base = false)
    {
		// remove webdav portion of path, this will be our root
		$this->_SERVER['REQUEST_METHOD'] = 'PROPFIND';
		$this->_SERVER['SCRIPT_NAME'] = '/webdav/';

        // let the base class do all the work
        parent::ServeRequest();
    }
	
	function PROPFIND(&$options, &$files) 
	{
		$request['handler'] = validate($request, 'handler');
		$request['dir'] = validate(array('dir' => $options['path']), 'dir');
		
		$files["files"] = array();
		
		$files = get_files($request, $total_count);

		foreach($files as $i => $file)
		{
			$info = array();
			$info['path'] = $file['Filepath'];
			$info['props'] = array();
			$info['props'][] = $this->mkprop('displayname', $file['Filename']);
			$info['props'][] = $this->mkprop('creationdate',    strtotime($file['Filedate']));
			$info['props'][] = $this->mkprop('getlastmodified', strtotime($file['Filedate']));
			$info['props'][] = $this->mkprop('resourcetype', 'collection');
			$info['props'][] = $this->mkprop('getcontenttype', 'httpd/unix-directory');
		  
			$files['files'][] = $info;
		}
		
		// ok, all done
        return true;
	}
}


?>