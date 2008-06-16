
Ext.FolderView = Ext.extend(Ext.DataView, {
						 
	multiSelect: true,
	emptyText: 'No files to display.',
	autoWidth: true,
	autoHeight: true,
	loadingText: 'Loading files...',

    // private
    initComponent : function(){
		this.loadTemplates();
		
        Ext.FolderView.superclass.initComponent.call(this);
		
		this.addEvents('reload', 'move');

    },
	
	loadTemplates : function(){
		this.addTemplate(new Ext.XTemplate(
			'<tpl for=".">',
				'<div class="thumb-wrap" id="file_{id}">',
					'<div class="thumb file_type_{type} file_ext_{ext}"><img src="{icon}" title=""></div>',
					'<span class="x-editable">{short}</span>',
				'</div>',
			'</tpl>'
		));
		if(!this.tpl)
			this.tpl = this.templates[0];
	},
	

    // private
    afterRender : function(){
		this.on({
			"reload": {
				fn: this.onReload,
				scope: this,
				buffer: 200
			}
		});

		Ext.FolderView.superclass.afterRender.call(this);

		this.on({
			"move": {
				fn: this.onMove,
				scope: this
			},
			"resize": {
				fn: this.onResize,
				scope: this,
				buffer: 100
			},
			"dblclick": {
				fn: this.dblClick,
				scope: this
			}
		});
 		
		if(!this.loading && this.loadingText)
		{
			var element = this.container.insertHtml('afterEnd', '<div class="ext-el-mask-msg x-mask-loading"><div>' + this.loadingText + '</div></div>', true);
			this.loading = element;
			this.loading.center(this.container.parent());
		}
		if(!this.empty && this.emptyText)
		{
			var element = this.container.insertHtml('afterEnd', '<div class="ext-el-mask-msg x-item-disabled"><div>' + this.emptyText + '</div></div>', true);
			this.empty = element;
			this.empty.center(this.container.parent());
		}
		
		if(!this.scroller)
			this.setScroller(this.ownerCt);
	},
	
	dblClick : function(view, index, node, e) {
		var record = this.store.getAt(index);
		if(record.get('ext') == 'FOLDER')
		{
			if(this.address)
			{
				this.address.setValue(record.get('path'));
				this.address.triggerBlur();
			}
		}
		else
		{
			window.location = record.get('link');
		}
	},

    // private
    updateIndexes : function(startIndex, endIndex){
        var ns = this.all.elements;
        startIndex = startIndex || 0;
        endIndex = endIndex || ((endIndex === 0) ? 0 : (ns.length - 1));
        for(var i = startIndex; i <= endIndex; i++){
            ns[i].viewIndex = i;
			// load selections from cache
			if(this.selectCache && this.selectCache.indexOf(this.store.getAt(i).id) != -1) this.select(ns[i], true);
        }
    },


    clearSelections : function(suppressEvent, skipUpdate, keepCache){
		if(!keepCache)
		{
			this.selectCache = [];
		}
        Ext.FolderView.superclass.clearSelections.call(this);
	},
	
    /**
     * Refreshes the view by reloading the data from the store and re-rendering the template.
     */
    refresh : function(){
		// get some variables to use with the get range
		var itemCount = this.getItemCount();
		var itemSize = this.getItemSize();
		if(this.all.getCount() == 0 && this.store.lastOptions && this.store.lastOptions.itemSize)
			itemSize = this.store.lastOptions.itemSize;
		if(this.all.getCount() == 0 && this.store.lastOptions && this.store.lastOptions.itemCount)
			itemCount = this.store.lastOptions.itemCount;
		var start = Math.floor(this.container.dom.scrollTop / itemSize.height) * itemCount.x;
		var limit = itemCount.x * itemCount.y;
		
		// get records by id
		var records = [];
		for(var i = start; i < start + limit; i++)
		{
			if(this.store.getById(i) != undefined)
				records[records.length] = this.store.getById(i);
		}
		
        this.clearSelections(false, true, true);
        this.el.update("");
        var html = [];
        //var records = this.store.getRange(start, start + limit - 1);
        if(records.length < 1){
            if(!this.deferEmptyText || this.hasSkippedEmptyText){
                this.el.update('');
				if(this.emptyText)
				{
					this.empty.center(this.container.parent());
					this.empty.setVisible(true);
				}
            }
            this.hasSkippedEmptyText = true;
            this.all.clear();
            return;
        }
		else
		{
			if(this.empty)
				this.empty.setVisible(false);
		}
        this.tpl.overwrite(this.el, this.collectData(records, 0));
        this.all.fill(Ext.query(this.itemSelector, this.el.dom));
        this.updateIndexes(0);
		
		
		// resize page to total size instead of just the items
		if(this.all.getCount() > 0)
		{
			var offset = this.container.dom.scrollTop % itemSize.height;
				
			var new_height = (Math.floor(this.store.getTotalCount() / itemCount.x + 0.5) || 1) * itemSize.height;
			this.el.dom.style.marginTop = (this.container.dom.scrollTop - offset) + 'px';
			this.el.dom.style.height = new_height - (this.container.dom.scrollTop - offset) + 'px';
		}
		
		
		// set up a tooltip that all the items can use
		if(!this.tooltip)
			this.tooltip = new Ext.ToolTip({
				html: 'Click the X to close me',
				title: 'My Tip Title'
			});
		
		// set the actions for all the items
		for(var i = 0; i < this.all.getCount(); i++)
		{
		
			var itemEl = this.all.item(i);
			itemEl.tooltip = this.tooltip;
			itemEl.title = records[i].get('name');
			itemEl.html = records[i].get('tip');
			itemEl.on({
				'mouseover': {
					fn: function(e) {
						e.preventDefault();
						this.tooltip.target = this;
						this.tooltip.setTitle(this.title);
						if(this.tooltip.body)
							this.tooltip.body.dom.innerHTML = this.html;
						else
							this.tooltip.html = this.html;
							
						this.tooltip.hide();
						// reset the elapsed time so it will show based on new delay
						this.tooltip.lastActive = this.tooltip.lastActive.add(Date.MILLI, -this.tooltip.quickShowInterval);
						this.tooltip.onTargetOver(e);

					},
					scope: itemEl
				},
				'mouseout': {
					fn: function(e) {
						e.preventDefault();
						this.tooltip.onTargetOut(e);
					},
					scope: itemEl
				},
				'mousemove': {
					fn: function(e) {
						e.preventDefault();
						this.tooltip.onMouseMove(e);
					},
					scope: itemEl
				}
			});

		}
		
    },

	// private
	onReload : function () {
			
		// reload items with new count
		var itemCount = this.getItemCount();
		var itemSize = this.getItemSize();
		
		var params = {
			start: Math.floor(this.container.dom.scrollTop / itemSize.height) * itemCount.x,
			limit: itemCount.x * itemCount.y,
			dir: this.dir || '/'
		}
			
		// select range of files to get based on empty spots in store
		var newStart = -1;
		var newEnd = params.start + params.limit;
		for(var i = params.start; i < params.start + params.limit; i++)
		{
			if(this.store.getById(i) == undefined)
			{
				if(newStart == -1) newStart = i;
				newEnd = i+1;
			}
		}

		// if dir changed reset buffer
		var add = true;
		if(!this.dir || !this.store.lastOptions || !this.store.lastOptions.params || !this.store.lastOptions.params.dir || this.store.lastOptions.params.dir != this.dir)
		{
			add = false;
			params.start = 0;
		}
		
		// make sure it has changed by at least a row
		// item count must also be the same for it to exit
		if(this.store.lastOptions && this.store.lastOptions.params && 
			this.store.lastOptions.params.start && 
			(Math.abs(this.store.lastOptions.params.start - params.start) < itemCount.x || Math.abs(params.start - this.store.lastOptions.params.start) < itemCount.x) && 
			this.store.lastOptions.params.limit && params.limit == this.store.lastOptions.params.limit &&
			add == true)
		{
			return;
		}
		else
		{
			// if the difference is zero then don't reload
			if(newStart == -1 && add == true)
			{
				this.refresh();
			}
			else
			{
				// if it is less then a page in difference then display loading in status
				if(this.store.lastOptions && this.store.lastOptions.params && 
					this.store.lastOptions.params.start && 
					(Math.abs(this.store.lastOptions.params.start - params.start) < params.limit || Math.abs(params.start - this.store.lastOptions.params.start) < params.limit))
				{
					this.ownerCt.ownerCt.bottomToolbar.setText(this.loadingText);
				}
				else
				{
					this.showLoading();
				}
				
				
					
				if(add == true)
				{
					params.start = newStart;
					params.limit = newEnd - newStart;
				}
				
				// finally load new items
				this.store.load({
					callback: this.finishStoreLoad,
					scope: this,
					add: add,
					itemSize: itemSize,
					itemCount: itemCount,
					params: params
				});
			}
		}
		
	},

	onResize : function() {
		this.fireEvent('reload');
	},

	onMove : function() {
		this.fireEvent('reload');
	},
	
	getItemSize : function() {
		if(this.all.getCount() > 0)
		{
			var width = this.all.item(0).getWidth() + this.all.item(0).getMargins('lr');
			var height = this.all.item(0).getHeight() + this.all.item(0).getMargins('tb');
			
			return {width: width, height: height};
		}
		else
		{
			return {width: 100, height: 97};
		}
	},
	
	getItemCount : function() {
		if(this.all.getCount() > 0)
		{
			var itemSize = this.getItemSize();
			var items_per_row = Math.floor(this.container.getWidth() / itemSize.width);
			var rows_per_page = Math.ceil(this.container.getHeight() / itemSize.height);
			
			return {x: items_per_row, y: rows_per_page};
		}
		else
		{
			return {x: 4, y: 5};
		}
	},
	
	
	showLoading : function() {
		if(this.loading)
		{
			this.loading.center(this.container.parent());
			this.loading.setVisible(true);
		}
	},
	
	finishStoreLoad : function(r, options, success){
		
		// make sure last read was a success if there is no items
		if(success)
		{
	
			if(this.loading)
			{
				this.loading.setVisible(false);
			}
	
			// set window title
			var address = this.address.getValue().split('/');
			this.ownerCt.ownerCt.setTitle(address[address.length-2]);
			
			// change the status bar text
			this.ownerCt.ownerCt.bottomToolbar.setText('Displaying ' + this.store.getTotalCount() + ' item' + ((this.store.getTotalCount() != 1)?'s.':'.'));
		}
	},
	
	// private
	setScroller : function(scroller){
		this.scroller = scroller;
		
		// set up some listeners
		this.scroller.view = this;
		this.scroller.on({
			'resize': {
				fn: function() { this.view.fireEvent("resize"); },
				scope: this.scroller
			}
		});
		
		this.container.on({
			'scroll': {
				fn: function() { this.fireEvent("move"); },
				scope: this
			}
		});
	},
	
	// private
	getSampleItem : function(){
		if(!this.sampleRecord)
			this.sampleRecord = {};
		// get the size of the template using record data
		var tpl_item = this.tpl.apply(this.sampleRecord);
		var element = this.el.insertHtml('afterEnd', tpl_item, true);
		element.setVisible(false);
		return element;
	},

    // private
    onBeforeLoad : function(){
        if(this.loadingText)
		{
			this.clearSelections(false, true, true);
			if(this.empty)
				this.empty.setVisible(false);
        }
		this.all.clear();
    },
	
    /**
     * Add a template that can be used in the view
     * @param {XTemplate} template The template to add
     */
	addTemplate : function(template){
		if(!this.templates)
			this.templates = Array();
		this.templates[this.templates.length] = template;
	},
	
    /**
     * Make a template the active template to be rendered
     * @param {XTemplate} template The template to activate
     */
	activateTemplate : function(template){
		this.tpl = template;
	}
	
	//
});

Ext.reg('folderview', Ext.FolderView);