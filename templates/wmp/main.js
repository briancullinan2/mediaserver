Ext.onReady(function(){
	
	var defaultRecord = [
		{name: 'name'},
		{name: 'icon'},
		{name: 'index'},
		{name: 'id'},
		{name: 'tip'},
		{name: 'short'},
		{name: 'link'},
		{name: 'path'},
		{name: 'ext'},
		{name: 'cat'},
		{name: 'selected'}
	];
				 
	// get some settings
	Ext.Ajax.request({
		url: plugins_path + 'display.php',
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
			url: plugins_path + 'select.php',
			reader: FileReader,
			baseParams: {
				limit: 50,
				dir: '/'
			}
		});
	
		var grid = new Ext.grid.GridPanel({
			region    : 'center',
			autoExpandColumn: 'filename',
			store: store,
	
			columns: [{
				id: 'filename',
				header: "Filename",
				dataIndex: 'info-Filename',
				renderer: renderFilename,
				width: 420,
				sortable:true
			}],
			
			bbar: new Ext.PagingToolbar({
				store: store,
				pageSize:500,
				displayInfo:true
			}),
		
			view: new Ext.ux.BufferView({
				// custom row height
				//rowHeight: 34,
				// render rows as they come into viewable area.
				scrollDelay: false
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
	
		// Panel for the west
		var nav = new Ext.tree.TreePanel({
			title: 'Library',
			region    : 'west',
			autoScroll: true,
			width : 250,
			root: new Ext.ux.XMLTreeNode({
				text:'Media Server',
				path: '/'
			}),
			loader: new Ext.data.Store({
				url: plugins_path + 'select.php',
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