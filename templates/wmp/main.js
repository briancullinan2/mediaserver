Ext.onReady(function(){
	
	var defaultRecord = [
		{name: 'name'},
		{name: 'index'},
		{name: 'id'},
		{name: 'short'},
		{name: 'class'},
		{name: 'icon'},
		{name: 'link'},
		{name: 'path'},
		{name: 'cat'},
		{name: 'selected'}
	];
				 
	// get some settings
	Ext.Ajax.request({
		url: modules_path + 'display.php',
		callback: displayColumns,
		defaultRecord: defaultRecord
	});
	
	// render functions
    function renderFilename(value, p, record){
        return String.format(
                '<img src="' + Ext.BLANK_IMAGE_URL + '" class="thumb file_ext_{1} file_type_{2}" />{0}',
                value, record.data['info-Filetype'], record.data['info-Filemime'].replace('/', ' file_type_'));
    }
	
	function displayColumns(options, success, response) {
		var columns = response.responseXML.documentElement.getElementsByTagName('columns')[0];
		var columns_arr = columns.firstChild.data.split(',');
		for(var i = 0; i < columns_arr.length; i++)
		{
			if(columns_arr[i] != '') options.defaultRecord[options.defaultRecord.length] = {name: 'info-' + columns_arr[i]};
		}
		
		setup();
	}
	
	function renderAlbum(value, metaData, record, rowIndex, colIndex, store)
	{
		metaData.css = "albumicon";
		if(record.data.class == "album")
		{
			metaData.attr = 'style="background-image:url(' + record.data.icon + ');background-repeat:no-repeat;background-position:0px 0px;"';
			return value;
		}
		else if(record.data.class == "artist")
		{
			metaData.attr = 'style="background-image:url(' + record.data.icon + ');background-repeat:no-repeat;background-position:0px -20px;"';
			return record.data["info-Artist"];
		}
		else if(record.data.class == "genre")
		{
			metaData.attr = 'style="background-image:url(' + record.data.icon + ');background-repeat:no-repeat;background-position:0px -40px;"';
			return record.data["info-Genre"];
		}
		else if(record.data.class == "year")
		{
			metaData.attr = 'style="background-image:url(' + record.data.icon + ');background-repeat:no-repeat;background-position:0px -60px;"';
			return record.data["info-Year"];
		}
		else if(record.data.class == "last")
		{
			metaData.attr = 'style="background-image:url(' + record.data.icon + ');background-repeat:no-repeat;background-position:0px -80px;"';
			return "";
		}
		else if(record.data.class == "none")
		{
			metaData.attr = "";
			metaData.css = "";
			return "";
		}
	}
	
	function setup()
	{
		var File = Ext.data.Record.create(defaultRecord);
		
		var FileReader = new Ext.data.XmlReader({
			success: "success",
			totalRecords: "count", // The element which contains the total dataset size (optional)
			record: "file",           // The repeated element which contains row information
			id: "index"                 // The element within the row that provides an ID for the record (optional)
		}, File);
	
		var store = new Ext.data.Store({
			url: modules_path + 'select.php',
			reader: FileReader,
			baseParams: {
				limit: 50
			}
		});
		
		// set up the columns
		var columns = [];
		for(var i = 0; i < File.prototype.fields.items.length; i++)
		{
			if(File.prototype.fields.items[i].name.substring(0, 5) == "info-")
			{
				columns[columns.length] = {
					header: File.prototype.fields.items[i].name.substring(5, File.prototype.fields.items[i].name.length),
					dataIndex: File.prototype.fields.items[i].name,
					sortable:true
				};
				
				if(File.prototype.fields.items[i].name == "info-Album")
				{
					columns[columns.length-1].renderer = renderAlbum;
				}
			}
		}
		
		var view = new Ext.ux.grid.livegrid.GridView({
			nearLimit : 100,
			loadMask  : {
				msg :  'Buffering. Please wait...'
			}
		});
		
		var grid = new Ext.ux.grid.livegrid.GridPanel({
			region         : 'center',
			enableDragDrop : false,
			cm             : new Ext.grid.ColumnModel(columns),
			loadMask       : {
				msg          : 'Loading...'
			},
			store          : new Ext.ux.grid.livegrid.Store({
				url          : modules_path + 'select.php',
				bufferSize   : 300,
				reader       : FileReader
			}),
			selModel       : new Ext.ux.grid.livegrid.RowSelectionModel(),
			view           : view,
			bbar           : new Ext.ux.grid.livegrid.Toolbar({
				view          : view,
				displayInfo   : true
			})
		});
	
		// tabs for the center
		var tabs = new Ext.TabPanel({
			region    : 'east',
			width : 250,
			activeTab : 0,
			defaults  : {
				autoScroll : true
			},
			items     : [{
				title    : 'Play'
			 },{
				title    : 'Manage'
			 }]
		});
	
		var rootNode = new Ext.tree.TreeNode({
			text:'Media Server'
		})
		
		var filesNode = new Ext.ux.XMLTreeNode({
			text: 'Files',
			path: '/'
		});
		
		var musicNode = new Ext.tree.TreeNode({
			text: 'Music',
			path: '/',
			listeners: {
				"click": {
					fn: function(node, e) {
						// clear the table
						
						// hide all the columns
						for(var i = 0; i < this.colModel.getColumnCount(); i++)
						{
							var index = this.colModel.getColumnAt(i).dataIndex;
							if(index == "info-Album" || index == "info-Artist" || index == "info-Track" || index == "info-Title" || index == "info-Length" || index == "info-Year" || index == "info-Genre" || index == "info-Filepath")
							{
								this.colModel.setHidden(i, false);
							}
							else
							{
								this.colModel.setHidden(i, true);
							}
						}
						
						// change the items displayed
						this.store.reload({
							params: {
								cat: "db_audio",
								order_by: "Album,Artist",
								short: ''
							}
						});
					},
					scope: grid
				}
			}
		});
		
		musicNode.appendChild(new Ext.tree.TreeNode({
			text: 'Artist',
			path: '/'
		}));
		
		musicNode.appendChild(new Ext.tree.TreeNode({
			text: 'Album',
			path: '/'
		}));
		
		musicNode.appendChild(new Ext.tree.TreeNode({
			text: 'Genre',
			path: '/'
		}));
		
		var videosNode = new Ext.tree.TreeNode({
			text: 'Videos',
			path: '/'
		});
		
		var picturesNode = new Ext.tree.TreeNode({
			text: 'Pictures',
			path: '/'
		});
		
		rootNode.appendChild(filesNode);
		rootNode.appendChild(musicNode);
		rootNode.appendChild(videosNode);
		rootNode.appendChild(picturesNode);
	
		// Panel for the west
		var nav = new Ext.tree.TreePanel({
			title: 'Library',
			region    : 'west',
			autoScroll: true,
			width : 250,
			root: rootNode,
			loader: new Ext.data.Store({
				url: modules_path + 'select.php',
				reader: FileReader,
				baseParams: {
					dirs_only: true,
					limit: 50,
					short: ''
				}
			}),
			listeners: {
				'append': function(tree, parent, node, index ) {
					node.on({
						'click': function(node, e) {
							//this.ownerTree.address.fireEvent('change', this.ownerTree.address.el, this.attributes.path, this.ownerTree.address.getValue());
						}
					});
				}
			}
		});
	
		var win = new Ext.Window({
			title      : 'Media Player',
			closable   : false,
			draggable  : false,
			resizeable : false,
			maximized  : true,
			width      : 800,
			height     : 600,
			x          : 0,
			y          : 0,
			//border   : false,
			plain      : true,
			layout     : 'border',
			items      : [nav, grid, tabs]
		});
	
		win.show();
		
	}
});