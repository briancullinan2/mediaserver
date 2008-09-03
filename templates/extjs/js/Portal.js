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
	
    createWindow : function(){
        var desktop = this.app.getDesktop();
		
		var File = Ext.data.Record.create([
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
		]);
		
		var FileReader = new Ext.data.XmlReader({
			success: "success",
			totalRecords: "count", // The element which contains the total dataset size (optional)
			record: "file",           // The repeated element which contains row information
			id: "index"                 // The element within the row that provides an ID for the record (optional)
		}, File);
		
		
		var bufferedDataStore = new Ext.ux.grid.BufferedStore({
			autoLoad : true,
			bufferSize : 300,
			reader : FileReader,
			url: '/mediaserver/plugins/select.php',
			baseParams: {
				dir: '/home/share/Music/'
			},
			paramNames: {
				"start" : "start",
				"limit" : "limit",
				"sort" : "order_by",
				"dir" : "direction"
			}
		});
		
		var bufferedView = new Ext.ux.FolderView({
			nearLimit : 100,
			loadMask : {
				msg : 'Please wait...'
			}
		});
		
		var bufferedGridToolbar = new Ext.ux.BufferedGridToolbar({
			view : bufferedView,
			displayInfo : true
		});
		
		var bufferedSelectionModel = new Ext.ux.grid.BufferedRowSelectionModel();
		
		var actions = {
			'downloads-button' : function(app){
				var selections = bufferedSelectionModel.getSelections();
				var selectedIds = '';
				for(var i = 0; i < selections.length; i++)
				{
					selectedIds += selections[i].data.id + ((i!=selections.length-1)?',':'');
				}
				Ext.Ajax.request({
					url: '/mediaserver/plugins/select.php',
					params: {
						on: selectedIds,
						select: true
					}
				});
				app.getModule('downloads-win').createWindow();
			}
		};
		
		var colModel = new Ext.grid.ColumnModel([
		{header: "", align : 'right', sortable: false, dataIndex: 'tip', hidden: true, width: 24, fixed: true, menuDisabled: true,
			renderer: function(value, metadata, record, rowIndex, colIndex, store) {
				// add the extra values to the record
				var tipInfo = value.split("<br />");
				for(var i = 0; i < tipInfo.length; i++) {
					// extract name and value pairs
					var nameVal = String(tipInfo[i]).split(": ");
					if(nameVal[0] != '')
					{
						record.data[nameVal[0]] = tipInfo[i].substring(nameVal[0].length + 2);
						var cm = bufferedView.grid.getColumnModel();
						if(cm.findColumnIndex(nameVal[0]) == -1)
						{
							// add the field name to the store
							bufferedView.defaultRecord[bufferedView.defaultRecord.length] = {name: nameVal[0]};
							// add column to colmodel
							cm.config[cm.config.length] = {
								header: nameVal[0],
								align : (isNaN(record.data[nameVal[0]]))?'left':'right',
								sortable: true,
								dataIndex: nameVal[0],
								hidden: (this.viewMode != 'Details')
							};
							bufferedView.colModelChanged = true;
						}
					}
				}
			}
		},
		{header: "id", align : 'right', sortable: true, dataIndex: 'id', hidden: true}
		]);
		
		var grid = new Ext.grid.GridPanel({
			region: 'center',
			ds : bufferedDataStore,
			enableDragDrop : false,
			cm : colModel,
			sm : bufferedSelectionModel,
			loadMask : {
				msg : 'Loading...'
			},
			view : bufferedView,
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
						window.location = r.data.link;
					}
				}
			},
			'rowcontextmenu': {
				fn: function(grid, rowIndex, e) {
					this.selModel.selectRow(rowIndex, this.selModel.isSelected(rowIndex));
					this.rowContext.showAt(e.getXY());
				}
			}
		});

		var address = new Ext.Address({
			store: new Ext.data.Store({
				url: '/mediaserver/plugins/select.php',
				reader: FileReader,
				baseParams: {
					dirs_only: true
				}
			}),
			value: '/home/share/Music/',
			displayField: 'path',
			queryParam: 'dir',
			allQuery: '/'
		});
		
		// set up action panels
		var folderTasks = new Ext.Panel({
			frame:true,
			title: 'File and Folder Tasks',
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
			collapsible:true,
			titleCollapse: true
		});
		
		var details = new Ext.Panel({
			frame:true,
			title: 'Details',
			collapsible:true,
			titleCollapse: true
		});
		
		var tasksPanel = new Ext.Panel({
			autoScroll: true,
			anchor: '100% 100%',
			border: false,
			baseCls:'x-plain',
			items: [folderTasks, otherPlaces, details]
		});
		
		
		var dirField = new Ext.form.ComboBox({
			fieldLabel: 'Look in',
			name: 'dir',
			width: 183,
			value: address.getValue()
		});
		
		var searchPanel = new Ext.form.FormPanel({
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
			autoScroll: true,
			anchor: '100% 100%',
			address: address,
			root: new Ext.ux.XMLTreeNode({
				text:'Media Server',
				path: '/'
			}),
			loader: new Ext.data.Store({
				url: '/mediaserver/plugins/select.php',
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
		
		var leftPanel = new Ext.Panel({
			region:'west',
			split:true,
			collapsible: true,
			collapseMode: 'mini',
			animCollapse: false,
			width:200,
			minWidth: 150,
			border: false,
			baseCls:'x-plain',
			margins:'3 3 0 3',
			layout: 'anchor',
			items: [tasksPanel, searchPanel, foldersPanel]
		});
		leftPanel.on({
			'resize': function () {
				if(searchPanel.rendered)
					dirField.setWidth(searchPanel.getSize().width);
			}
		});
		
		searchPanel.hide();
		foldersPanel.hide();
		
		var win = desktop.createWindow({
			title: 'Portal Window',
			width: 700,
			height: 450,
			iconCls: 'portal',
			shim: false,
			animCollapse: false,
			constrainHeader: true,
			cls: 'portal-window',
			tbar: [],
			bbar: bufferedGridToolbar,
			layout: 'border',
			border: false,
			layoutConfig: {
				animate: false
			},
			items: [grid, leftPanel]
		});

		grid.view.el.on('contextmenu', function (e, t) { e.preventDefault(); }, this);
		tasksPanel.body.on('mousedown', function(e, t){
				e.stopEvent();
				actions[t.id](this.app);
			}, 
		this, {delegate:'a'});
		tasksPanel.body.on('click', Ext.emptyFn, null, {delegate:'a', preventDefault:true});
		
		var refreshbutton = new Ext.Toolbar.Button({
			cls:"x-btn-icon",
			iconCls: 'ux-reload-button',
			tooltip: 'Refresh',
			handler: function() {
				this.view.reset(true)
			},
			scope: grid
		});
		
		var upbutton = new Ext.Toolbar.Button({
			cls:"x-btn-icon",
			iconCls: 'ux-up-button',
			tooltip: 'Up'
		});
		upbutton.on({
			'click': {
				fn: function() {
					var newDir = '';
					var oldDirs = this.getValue().split('/');
					
					for(var i = oldDirs.length-1; i >= 0; i--)
					{
						if(oldDirs[i] != '')
						{
							oldDirs[i] = '';
							break;
						}
					}
					
					newDir = oldDirs.join('/');
					this.fireEvent('change', this.el, newDir, this.getValue());
				},
				scope: address
			}
		});
		
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
		searchbutton.on({
			'toggle': {
				fn: function(item, pressed) {
					if(pressed == false && foldersbutton.pressed == false)
					{
						tasksPanel.show();
						searchPanel.hide();
						foldersPanel.hide();
					}
					else
					{
						if(pressed == true)
						{
							foldersbutton.toggle(false);
							tasksPanel.hide();
							searchPanel.show();
							foldersPanel.hide();
						}
					}
				}
			}
		});
		foldersbutton.on({
			'toggle': {
				fn: function(item, pressed) {
					if(pressed == false && searchbutton.pressed == false)
					{
						tasksPanel.show();
						searchPanel.hide();
						foldersPanel.hide();
					}
					else
					{
						if(pressed == true)
						{
							searchbutton.toggle(false);
							tasksPanel.hide();
							searchPanel.hide();
							foldersPanel.show();
						}
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
		
		bufferedDataStore.on({
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
					text: 'List',
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
		
		
		address.backbutton = backbutton;
		address.forwardbutton = forwardbutton;
		address.toolbar = win.getTopToolbar();
		address.search = searchbutton;
		address.grid = grid;
		
		var topToolbar = win.getTopToolbar();
		topToolbar.add(
			backbutton, forwardbutton,
			upbutton, refreshbutton,
			'-',
			searchbutton, foldersbutton,
			'-',
			viewbutton,
			'->',
			'|',
			'Address:',
			address
		);

        win.show();
    }
	
});
