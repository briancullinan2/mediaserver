// JavaScript Document


Ext.app.PortalWindow = Ext.extend(Ext.app.Module, {
    id:'portal-win',
    init : function(){
        this.launcher = {
            text: 'Portal',
            iconCls:'accordion',
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

		var view = new Ext.FolderView({
			store: new Ext.data.Store({
				url: '/mediaserver/plugins/select.php',
				reader: FileReader
			}),
			sampleRecord: {
				name: 'Music',
				icon: '/mediaserver/templates/extjs/images/filetypes/folder_96x96.png',
				tip: 'id: 20934<br />Filename: Music<br />Filemime: <br />Filesize: 0<br />Filedate: 2008-05-28 11:29:53<br />Filetype: FOLDER<br />Filepath: /home/share/Music/<br />',
				short: 'Music'
			},
			dir: '/home/share/Music/',
			itemSelector:'div.thumb-wrap',
			overClass:'x-view-over',
			cls: 'ux-data-view',
			address: address
		});
		
		var backbutton = new Ext.Toolbar.SplitButton({text: 'Back', menu: [], cls:"x-btn-text-icon", iconCls: 'ux-back-button'});
		var forwardbutton = new Ext.Toolbar.SplitButton({menu: [], cls:"x-btn-icon", iconCls: 'ux-forward-button', backbutton: backbutton});
		backbutton.forwardbutton = forwardbutton;
		
		address.folderview = view;
		address.backbutton = backbutton;
		address.forwardbutton = forwardbutton;
		
		var folderTasks = new Ext.Panel({
			frame:true,
			title: 'File and Folder Tasks',
			collapsible:true,
			titleCollapse: true
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
			items: [folderTasks, otherPlaces, details]
		});
			
		var main = new Ext.Panel({
			region: 'center',
			autoScroll: true,
			items: view,
			listeners: {
			}
		});
		
		//var toolbar = new Ext.Toolbar();
		
		var win = desktop.createWindow({
			title: 'Portal Window',
			width: 700,
			height: 450,
			iconCls: 'accordion',
			shim: false,
			animCollapse: false,
			constrainHeader: true,
			cls: 'portal-window',
			listeners: {
				'activate' : {
					fn: function(){ 
					},
					scope: this
				},
				'close' : {
					fn: function(){
					},
					scope: this
				}
			},
			tbar: [],
			bbar: new Ext.StatusBar({
			}),
			layout: 'border',
			border: false,
			layoutConfig: {
				animate: false
			},
			items: [main, tasksPanel],
			
			closeMSG : function() { alert('closed'); }
		});
		
		var topToolbar = win.getTopToolbar()
		topToolbar.add(
			backbutton, forwardbutton,
			{xtype: 'tbbutton', cls:"x-btn-icon", iconCls: 'ux-up-button', tooltip: 'Up'},
			{xtype: 'tbbutton', cls:"x-btn-icon", iconCls: 'ux-reload-button', tooltip: 'Refresh'},
			'->',
			'|',
			'Address:',
			address
		);

        win.show();

    }
});
