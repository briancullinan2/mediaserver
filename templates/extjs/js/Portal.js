// JavaScript Document


Ext.app.PortalWindow = Ext.extend(Ext.app.Module, {
    id:'portal-win',
    init : function(){
        this.launcher = {
            text: 'Portal',
            iconCls:'portal',
            handler : this.createWindow,
            scope: this
        }
    },
	
	defaultRecord: [
		{name: 'name'},
		{name: 'icon'},
		{name: 'index'},
		{name: 'id'},
		{name: 'tip'},
		{name: 'short'},
		{name: 'link'},
		{name: 'path'},
		{name: 'ext'},
		{name: 'selected'}
	],
	
	displayColumns: function(options, success, response) {
		var columns = response.responseXML.documentElement.getElementsByTagName('columns')[0];
		var columns_arr = columns.firstChild.data.split(',');
		for(var i = 0; i < columns_arr.length; i++)
		{
			if(columns_arr[i] != '') this.defaultRecord[this.defaultRecord.length] = {name: 'info-' + columns_arr[i]};
		}
		
		var check_row = function(value, metadata, record, rowIndex, colIndex, store)
		{
			// reset all isBlank values
			if(rowIndex == 0 && colIndex == 0)
			{
				for(var i = 0; i < options.colModel.getColumnCount(); i++)
				{
					options.colModel.config[i].isBlank = true;
					options.colModel.config[i].longest = 0;
				}
				options.colModel.config[0].isBlank = false;
			}
			if(colIndex == 0)
			{
				metadata.css = 'detail file_ext_' + record.data.ext;
				return '';
			}
			// set the isBlank to false if there is any text what so ever.
			if(value && value.length != 0 && options.view.viewMode == 'Details')
			{
				options.colModel.config[colIndex].isBlank = false;
				if(value.length > options.colModel.config[colIndex].longest)
					options.colModel.config[colIndex].longest = value.length;
			}
				
			return value;
		}
		
		var tmp_col = [];
		for(var i = 0; i < this.defaultRecord.length; i++)
		{
			if(this.defaultRecord[i].name.substring(0, 5) == 'info-')
			{
				var isId = (this.defaultRecord[i].name == 'info-id');
				tmp_col[tmp_col.length] = {
					header: isId?'':this.defaultRecord[i].name.substring(5),
					sortable: true,
					dataIndex: this.defaultRecord[i].name,
					hidden: false,
					renderer: check_row,
					width: isId?24:100,
					fixed: isId,
					menuDisabled: isId,
					sortable: !isId
				}
			}
		}
		
		options.colModel.setConfig(tmp_col);
		options.ds.reader.recordType = Ext.data.Record.create(this.defaultRecord);
		options.ds.recordType = options.ds.reader.recordType;
		options.ds.fields = options.ds.recordType.prototype.fields
	},
	
    createWindow : function(){
        var desktop = this.app.getDesktop();
		
		// set up records		
		var File = Ext.data.Record.create(this.defaultRecord);
		
		var FileReader = new Ext.data.XmlReader({
			success: "success",
			totalRecords: "count", // The element which contains the total dataset size (optional)
			record: "file",           // The repeated element which contains row information
			id: "index"                 // The element within the row that provides an ID for the record (optional)
		}, File);
		
		
		var bufferedStore = new Ext.ux.grid.livegrid.Store({
			autoLoad : true,
			bufferSize : 300,
			reader : FileReader,
			url: plugins_path + 'select.php',
			baseParams: {
				dir: '/'
			},
			paramNames: {
				"start" : "start",
				"limit" : "limit",
				"sort" : "order_by",
				"dir" : "direction"
			}
		});
		
		var bufferedGridView = new Ext.ux.FolderView({
			nearLimit : 100,
			defaultRecord: this.defaultRecord,
			loadMask : {
				msg : 'Please wait...'
			}
		});
		
		var bufferedToolbar = new Ext.ux.grid.livegrid.Toolbar({
			view : bufferedGridView,
			displayInfo : true,
			cls: 'ux-toolbar'
		});
		
		var bufferedSelectionModel = new Ext.ux.grid.livegrid.RowSelectionModel();
		
		// set up colmodel
		var colModel = new Ext.grid.ColumnModel([]);
		// get some settings
		Ext.Ajax.request({
			url: plugins_path + 'display.php',
			callback: this.displayColumns,
			scope: this,
			colModel: colModel,
			ds: bufferedStore,
			view: bufferedGridView
		});
		
		
		// set up grid
		var grid = new Ext.ux.grid.livegrid.GridPanel({
			region: 'center',
			bodyStyle: 'border-bottom:0px;border-top:0px;',
			store : bufferedStore,
			enableDragDrop : false,
			colModel : colModel,
			selModel : bufferedSelectionModel,
			loadMask : {
				msg : 'Loading...'
			},
			view : bufferedGridView,
			rowContext: new Ext.menu.Menu({
				items: [{
					text: 'Send to Downloads',
					iconCls: 'ux-downloads-button',
					handler: function(e, t) {
						actions['downloads-button'](this.app);
					},
					scope: this
				}]
			})
		});
		
		var details = new Ext.Panel({
			frame:true,
			title: 'Details',
			cls: 'ux-task-panel',
			collapsible:true,
			titleCollapse: true,
			bodyStyle: 'margin-bottom:3px;'
		});

		grid.on({
			'rowdblclick': {
				fn: function(grid, rowIndex, e) {
					var r = grid.store.getAt(rowIndex);
					if(r.data.ext == 'FOLDER')
					{
						address.fireEvent('change', address.el, r.data.path, address.getValue());
					}
					else
					{
						if(r.data['info-Filemime'])
						{
							type = r.data['info-Filemime'].split('/')[0];
							switch(type)
							{
								case 'audio':
								case 'video':
								case 'image':
									module = this.app.getModule('player-win');
									player = module.createWindow(r);
								break;
								default:
									window.location = r.data.link;
							}
						}
						else
						{
							window.location = r.data.link;
						}
					}
				},
				scope: this
			},
			'rowclick': {
				fn: function(grid, rowIndex, e) {
					var r = grid.store.getAt(rowIndex);
					details.body.dom.innerHTML = '<b>' + r.data.name + '</b><br />' + r.data['info-Filetype'] + '<br /><br />' + r.data.tip;
				}
			},
			'rowcontextmenu': {
				fn: function(grid, rowIndex, e) {
					e.preventDefault();
					this.selModel.selectRow(rowIndex, this.selModel.isSelected(rowIndex));
					this.rowContext.showAt(e.getXY());
				}
			}
		});

		var address = new Ext.Address({
			store: new Ext.data.Store({
				url: plugins_path + 'select.php',
				reader: FileReader,
				baseParams: {
					dirs_only: true
				}
			}),
			value: '/',
			displayField: 'path',
			queryParam: 'dir',
			allQuery: '/'
		});
		
		// set up action panels
		var actions = {
			'downloads-button' : function(app){
				var selections = bufferedSelectionModel.getSelections();
				var selectedIds = '';
				for(var i = 0; i < selections.length; i++)
				{
					selectedIds += selections[i].data.id + ((i!=selections.length-1)?',':'');
				}
				Ext.Ajax.request({
					url: plugins_path + 'select.php',
					params: {
						on: selectedIds,
						select: true
					}
				});
				app.getModule('downloads-win').createWindow();
			}
		};

		var folderTasks = new Ext.Panel({
			frame:true,
			title: 'File and Folder Tasks',
			cls: 'ux-task-panel',
			collapsible:true,
			titleCollapse: true,
			html: '<ul>' +
				'<li>' +
				'<img src="' + Ext.BLANK_IMAGE_URL + '" class="ux-downloads-button"/>' +
				'<a id="downloads-button" href="#">Send selected items to Downloads.</a>' +
				'</li>' +
			'</ul>'
		});
		
		var otherPlaces = new Ext.Panel({
			frame:true,
			title: 'Other Places',
			cls: 'ux-task-panel',
			collapsible:true,
			titleCollapse: true
		});
		
		var tasksPanel = new Ext.Panel({
			title: 'Tasks',
			autoScroll: true,
			anchor: '100% 100%',
			border: false,
			baseCls:'x-plain',
			items: [folderTasks, otherPlaces, details],
			listeners: {
				'render': {
					fn: function(panel) {
						panel.body.on('mousedown', function(e, t){
							e.stopEvent();
							actions[t.id](this.app);
						}, 
						this, {delegate:'a'});
						panel.body.on('click', Ext.emptyFn, null, {delegate:'a', preventDefault:true});
					},
					scope: this
				}
			}
		});
		
		
		var dirField = new Ext.form.ComboBox({
			fieldLabel: 'Look in',
			name: 'dir',
			value: address.getValue()
		});
		
		var searchPanel = new Ext.form.FormPanel({
			title: 'Search',
			cls: 'ux-search-panel',
			autoScroll: true,
			border: false,
			baseCls:'x-plain',
			labelAlign: 'top',
			items: [{
				xtype: 'label',
				cls: 'ux-bold',
				text: 'Search by any or all of the criteria below.'
			},{
				xtype: 'textfield',
				fieldLabel: 'All or part of the file name',
				cls: 'ux-fill',
				name: 'includes'
			},dirField]
		});
		searchPanel.addButton('Search', function() {
				
			var searchParams = {};
			for(var i = 0; i < searchPanel.getForm().items.items.length; i++)
			{
				searchParams[searchPanel.getForm().items.items[i].name] = searchPanel.getForm().items.items[i].getValue();
			}
			
			if(address.getValue() != '/Search Results/')
				address.fireEvent('change', address.el, 'Search Results', address.getValue(), searchParams);
				
			// fix for making base params(always used) the current directory
			grid.store.baseParams.dir = searchParams.dir;
			grid.store.reload({params: searchParams});
		});
		
		var foldersPanel = new Ext.tree.TreePanel({
			title: 'Folders',
			autoScroll: true,
			//anchor: '100% 100%',
			address: address,
			root: new Ext.ux.XMLTreeNode({
				text:'Media Server',
				path: '/'
			}),
			loader: new Ext.data.Store({
				url: plugins_path + 'select.php',
				reader: FileReader,
				baseParams: {
					dirs_only: true,
					limit: 2000,
					short: ''
				}
			}),
			listeners: {
				'append': function(tree, parent, node, index ) {
					node.on({
						'click': function(node, e) {
							this.ownerTree.address.fireEvent('change', this.ownerTree.address.el, this.attributes.path, this.ownerTree.address.getValue());
						}
					});
				}
			}
		});
		
		var tabPanel = new Ext.TabPanel({
			region:'center',
			tabPosition: 'bottom',
			activeTab: 0,
			deferredRender: false,
			border: false,
			bodyStyle: 'background: none;',
			listeners: {
				'tabchange': {
					fn: function(tabpanel, tab)
					{
						if(searchPanel.rendered)
							dirField.setWidth(searchPanel.getSize().width - 6);
						//tabpanel.setTitle(tab.title);
					}
				}
			},
			items: [tasksPanel, searchPanel, foldersPanel]
		});
		tabPanel.on({
			'resize': function () {
				if(searchPanel.rendered)
					dirField.setWidth(searchPanel.getSize().width - 6);
			}
		});
		
		var leftPanel = new Ext.Panel({
			title: 'Folders',
			region:'west',
			cls: 'ux-leftpanel',
			bodyStyle: 'border-bottom:0px;border-top:0px;',
			split:true,
			collapsible: true,
			width:200,
			minWidth: 170,
			layout: 'border',
			items: [tabPanel]
		});
		
		var refreshbutton = new Ext.Toolbar.Button({
			cls:'x-btn-icon',
			iconCls: 'x-tbar-loading',
			tooltip: 'Refresh',
			handler: function() {
				this.view.reset(true);
			},
			scope: grid
		});
		
		// setup the botton to go up a directory
		var upbutton = new Ext.Toolbar.Button({
			cls:"x-btn-icon",
			iconCls: 'ux-up-button',
			tooltip: 'Up'
		});
		upbutton.on({
			'click': {
				fn: function() {
					var newDir = dir_sep;
					var oldDirs = this.getValue().split(/\/|\\/);
					
					for(var i = 1; i < oldDirs.length-2; i++)
					{
						newDir += oldDirs[i] + dir_sep;
					}
					
					this.fireEvent('change', this.el, newDir, this.getValue());
				},
				scope: address
			}
		});
		
		// set up the search and folders toggle buttons
		var searchbutton = new Ext.Toolbar.Button({
			cls:"x-btn-text-icon",
			iconCls: 'ux-search-button',
			text: 'Search',
			enableToggle: true
		});
		var foldersbutton = new Ext.Toolbar.Button({
			cls:"x-btn-text-icon",
			iconCls: 'ux-folders-button',
			text: 'Folders',
			enableToggle: true
		});
		
		// select the correct tab panel when the search button is pressed
		searchbutton.on({
			'toggle': {
				fn: function(item, pressed) {
					if(pressed == false && foldersbutton.pressed == false)
					{
						tabPanel.activate(0);
					}
					else
					{
						if(pressed == true)
						{
							foldersbutton.toggle(false);
							tabPanel.activate(1);
							dirField.setValue(address.getValue());
						}
					}
				}
			}
		});
		
		// select the correct tab panel when the folders button is pressed
		foldersbutton.on({
			'toggle': {
				fn: function(item, pressed) {
					if(pressed == false && searchbutton.pressed == false)
					{
						tabPanel.activate(0);
					}
					else
					{
						if(pressed == true)
						{
							searchbutton.toggle(false);
							tabPanel.activate(2);
						}
						else
						{
							searchbutton.toggle(false);
							foldersbutton.toggle(false);
						}
					}
				}
			}
		});
		
		// add the button selection based on which tab is selected
		tabPanel.on({
			'tabchange': function(panel, tab)
			{
				if(tab.title == 'Search')
				{
					searchbutton.toggle(true);
					foldersbutton.toggle(false);
				}
				else
				{
					if(tab.title == 'Folders')
					{
						searchbutton.toggle(false);
						foldersbutton.toggle(true);
					}
					else
					{
						searchbutton.toggle(false);
						foldersbutton.toggle(false);
					}
				}
			}
		});
		
		var backbutton = new Ext.Toolbar.SplitButton({
			text: 'Back',
			menu: [],
			cls:"x-btn-text-icon",
			iconCls: 'ux-back-button',
			disabled: true,
			handler: function () {
				this.menu.items.item(0).fireEvent('click', this.menu.items.item(0));
			}
		});
		var forwardbutton = new Ext.Toolbar.SplitButton({
			menu: [],
			cls:"x-btn-icon",
			iconCls: 'ux-forward-button',
			disabled: true,
			handler: function () {
				this.menu.items.item(0).fireEvent('click', this.menu.items.item(0));
			}
		});
		
		bufferedStore.on({
			'load' : {
				fn: function(store, records, options) {
					// check if there was an error
					if(store.totalLength == 0 && store.reader.xmlData && store.reader.xmlData.childNodes[0])
					{
						var error = store.reader.xmlData.childNodes[0].getElementsByTagName('error');
						if(error.length > 0)
						{
							Ext.MessageBox.show({
								title: 'Address Bar',
								msg: error[0].textContent,
								buttons: Ext.MessageBox.OK,
								fn: function () {
									// go back to previous directory
									address.fireEvent('change', this, backbutton.menu.items.item(0).params.dir, null);
									backbutton.menu.remove(backbutton.menu.items.item(0));
									if(backbutton.menu.items.getCount() > 0){ backbutton.enable(); }
									else{ backbutton.disable(); }
								},
								scope: address
							});
						}
					}
				},
				scope: this
			}
		});
		
		var viewChangeFn = function(item, checked) {
			if(checked)
				grid.view.changeView(item.text);
		}
		var viewbutton = new Ext.Toolbar.Button({
			cls:"x-btn-icon",
			iconCls: 'ux-view-button',
			tooltip: 'Change how the files are displayed.',
			menu: {
				items: [{
					text: 'Thumbnails',
					checked: true,
					group: 'view',
					checkHandler: viewChangeFn
				},{
					text: 'Tiles',
					checked: false,
					group: 'view',
					checkHandler: viewChangeFn
				},{
					text: 'Icons',
					checked: false,
					group: 'view',
					checkHandler: viewChangeFn
				},{
					text: 'Details',
					checked: false,
					group: 'view',
					checkHandler: viewChangeFn
				}]
			}
		});
		
		var filemenu = new Ext.menu.Menu({
			items: [
				{text: 'Close'}
			]
		});
		
		var filemenu = new Ext.menu.Menu({
			items: [
				{text: 'New'},
				'-',
				{text: 'Delete'},
				{text: 'Rename'},
				{text: 'Properties'},
				'-',
				{text: 'Close'}
			]
		});
		
		var editmenu = new Ext.menu.Menu({
			items: [
				{text: 'Undo'},
				'-',
				{text: 'Cut'},
				{text: 'Copy'},
				{text: 'Paste'},
				'-',
				{text: 'Select All'},
				{text: 'Invert Selection'}
			]
		});
		
		var viewmenu = new Ext.menu.Menu({
			items: [
				{text: 'Status Bar'},
				{text: 'Explorer Bar', menu: {
					items: [{text: 'Search'}, {text: 'Folders'}]
					}},
				'-',
				{text: 'Thumbnails'},
				{text: 'Tiles'},
				{text: 'Icons'},
				{text: 'Details'},
				'-',
				{text: 'Arrange By', menu: {
					items: [{text: 'Name'}, {text: 'Size'}, {text: 'Type'}, {text: 'Modified'}, '-', {text: 'Show In Groups'}]
					}},
				'-',
				{text: 'Columns'},
				{text: 'Go To', menu: {
					items: [{text: 'Back'}, {text: 'Forward'}, {text: 'Up One Level'}]
					}},
				{text: 'Refresh'}
			]
		});
		
		var toolsmenu = new Ext.menu.Menu({
			items: [
				{text: 'Folder Options'}
			]
		});
		
		var menubar = new Ext.Toolbar({
			items: [{text: 'File', menu: filemenu}, {text: 'Edit', menu: editmenu}, {text: 'View', menu: viewmenu}, {text: 'Tools', menu: toolsmenu}, '->']
		});
		
		address.backbutton = backbutton;
		address.forwardbutton = forwardbutton;
		address.search = searchbutton;
		address.grid = grid;
		
		var win = desktop.createWindow({
			title: 'Portal Window',
			width: 700,
			height: 450,
			iconCls: 'portal',
			shim: false,
			animCollapse: false,
			constrainHeader: true,
			cls: 'portal-window',
			tbar: menubar,
			bbar: bufferedToolbar,
			layout: 'border',
			border: false,
			layoutConfig: {
				animate: false
			},
			items: [grid, leftPanel]
		});
		
		var buttonsbar = new Ext.Toolbar({
			renderTo: win.tbar,
			items: [backbutton, forwardbutton, upbutton, refreshbutton, '-', searchbutton, foldersbutton, '-', viewbutton, '->']
		});
		
		var addressbar = new Ext.Toolbar({
			renderTo: win.tbar,
			items: ['Address:', address]
		});
		address.toolbar = addressbar;

        win.show();
    }
	
});
