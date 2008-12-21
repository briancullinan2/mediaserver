// JavaScript Document

Ext.app.DownloadsWindow = Ext.extend(Ext.app.Module, {
    id:'downloads-win',
    init : function(){
        this.launcher = {
            text: 'Downloads',
            iconCls:'downloads',
            handler : this.createWindow,
            scope: this
        }
    },
	
    createWindow : function(){
        var desktop = this.app.getDesktop();
		
		var windows = desktop.getManager().getBy(function(win) {
			if(win.title == 'Downloads Window')
				return true;
			else
				return false;
		});
		if(windows.length > 0)
		{
			windows[0].show();
			return;
		}
		
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
		
		var windowToolbar = new Ext.Toolbar();
		
		var createGridButton = function(record, id) {
			var newButton = new Ext.Toolbar.SplitButton({
				iconCls: 'ux-download-button',
				renderTo: id,
				cls: 'x-btn-icon',
				scope : this,
				menu: new Ext.menu.Menu({
					record: record,	
					items: [{
						iconCls: 'ux-singlefile',
						text: 'Single File',
						handler: function(item) {
							window.location = this.parentMenu.record.data.link
						},
						listeners: {
							'render': function() {
								this.setDisabled(this.parentMenu.record.data.ext == 'FOLDER');
							}
						}
					},{
						iconCls: 'ux-zipfile',
						text: 'Zip File',
						handler: function(item) {
							var path = plugins_path + 'zip/' + this.parentMenu.record.data.id + '/' + this.parentMenu.record.data.id + '.zip';
							window.location = path;
						}
					},{
						iconCls: 'ux-torrentfile',
						text: 'Torrent File',
						handler: function(item) {
							var path = plugins_path + 'bt/' + this.parentMenu.record.data.id + '/' + this.parentMenu.record.data.id + '.torrent';
							window.location = path;
						}
					}]
				})
			});
			windowToolbar.initMenuTracking(newButton);
		}
		
		var colModel = new Ext.grid.ColumnModel([
		{
			align : 'right',
			sortable: false,
			resizable: false,
			hideable: false,
			menuDisabled: true,
			width: 38,
			renderer: function(value, metadata, record, rowIndex, colIndex, store) {
				
				var contentId = Ext.id();
				createGridButton.defer( 1, this, [record, contentId]);
				metadata.css += 'x-toolbar';
				return '<div id="' + contentId + '"></div>'; 
				
			}
		},
		{header: "id", align : 'right', sortable: true, dataIndex: 'id'},
		{header: "Filename", align : 'left', sortable: true, dataIndex: 'name'},
		{header: "Extension", align : 'left', sortable: true, dataIndex: 'ext'}
		]);
		
		var grid = new Ext.grid.GridPanel({
			region: 'center',
			ds : new Ext.data.Store({
				url: site_path + 'plugins/list.php',
				reader: FileReader,
				baseParams: {
					type: 'select',
					selected_only: true
				}
			}),
			enableDragDrop : false,
			cm : colModel,
			loadMask : {
				msg : 'Loading...'
			}
		});

		var win = desktop.createWindow({
			tbar: windowToolbar,
			title: 'Downloads Window',
			width: 400,
			height: 350,
			iconCls: 'downloads',
			shim: false,
			animCollapse: false,
			constrainHeader: true,
			cls: 'downloads-window',
			layout: 'border',
			border: false,
			items: [grid],
			listeners: {
				'activate': function() {
					this.items.get(0).store.load();
				}
			}
		});
		
		windowToolbar.add([{
			text: 'Remove',
			cls:"x-btn-text-icon",
			iconCls: 'ux-remove-button',
			handler: function() {
				var selections = this.getSelections();
				var selectedIds = '';
				for(var i = 0; i < selections.length; i++)
				{
					selectedIds += selections[i].data.id + ((i!=selections.length-1)?',':'');
				}
				Ext.Ajax.request({
					url: site_path + 'plugins/select.php',
					params: {
						off: selectedIds,
						select: true
					},
					success: function() {this.store.load();},
					scope: this
				});
			},
			scope: grid
		},{
			text: 'Clear All',
			cls:"x-btn-text-icon",
			iconCls: 'ux-clear-button',
			handler: function() {
				var selections = this.store.data.items;
				var selectedIds = '';
				for(var i = 0; i < selections.length; i++)
				{
					selectedIds += selections[i].data.id + ((i!=selections.length-1)?',':'');
				}
				Ext.Ajax.request({
					url: site_path + 'plugins/select.php',
					params: {
						off: selectedIds,
						select: true
					},
					success: function() {this.store.load();},
					scope: this
				});
			},
			scope: grid
		}]);
		windowToolbar.add('-');
		windowToolbar.add({
			xtype: 'tbsplit',
			text: 'All Files',
			cls:"x-btn-text-icon",
			iconCls: 'ux-download-button',
			menu: new Ext.menu.Menu({
				items: [{
					iconCls: 'ux-zipfile',
					text: 'Zip File',
					handler: function(item) {
						var selections = this.store.data.items;
						var selectedIds = '';
						for(var i = 0; i < selections.length; i++)
						{
							selectedIds += selections[i].data.id + ((i!=selections.length-1)?',':'');
						}
						var path = plugins_path + 'zip/' + selectedIds + '/files.zip';
						window.location = path;
					},
					scope: grid
				},{
					iconCls: 'ux-torrentfile',
					text: 'Torrent File',
					handler: function(item) {
						var selections = this.store.data.items;
						var selectedIds = '';
						for(var i = 0; i < selections.length; i++)
						{
							selectedIds += selections[i].data.id + ((i!=selections.length-1)?',':'');
						}
						var path = plugins_path + 'bt/' + selectedIds + '/files.torrent';
						window.location = path;
					},
					scope: grid
				}]
			})
		});
		
		
		win.show();
	}
	
});