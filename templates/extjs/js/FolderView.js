
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
		
		this.addEvents('reload', 'move', 'storefinished');

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
        Ext.FolderView.superclass.afterRender.call(this);

		this.on({
			"reload": {
				fn: this.onReload,
				scope: this,
				buffer: 200
			},
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
			},
			"storefinished": {
				fn: this.finishStoreLoad,
				scope: this,
				buffer: 100
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
        this.clearSelections(false, true, true);
        this.el.update("");
        var html = [];
        var records = this.store.getRange();
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
		
		if(!this.tooltip)
			this.tooltip = new Ext.ToolTip({
				html: 'Click the X to close me',
				title: 'My Tip Title'
			});
		
		// set the actions for all the items
		for(var i = 0; i < this.all.getCount(); i++)
		{
			var itemEl = Ext.Element.get(this.all.elements[i]);
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
		if(!this.sampleItem)
			this.sampleItem = this.getSampleItem();
			
		var scrollerEl = Ext.Element.get(this.el.dom.parentNode);
		var sampleSize = this.sampleItem.getSize();
		
		// scroll to top if dir changes
		if(this.dir && this.store.lastOptions && (this.store.lastOptions.params.dir == undefined || this.store.lastOptions.params.dir != this.dir))
			scrollerEl.dom.scrollTop = 0;
		
		var items_per_row = 1;
		if(!this.store.lastOptions)
			items_per_row = Math.floor(scrollerEl.getWidth() / (sampleSize.width + this.sampleItem.getMargins('lr')));
		else
			items_per_row = Math.floor(this.el.getWidth() / (sampleSize.width + this.sampleItem.getMargins('lr')));
			
		var rows_per_page = Math.ceil(scrollerEl.getHeight() / (sampleSize.height + this.sampleItem.getMargins('tb')));
			
		var itemIndex = Math.floor(scrollerEl.getScroll().top / (sampleSize.height + this.sampleItem.getMargins('tb')) / rows_per_page) * items_per_row * rows_per_page - (items_per_row * rows_per_page);
		if(itemIndex < 0) itemIndex = 0;
		var itemCount = rows_per_page * items_per_row ;

		// only do this part if a field as changed
		if(!this.store.lastOptions || !this.store.lastOptions.params ||
		   this.store.lastOptions.params.limit == undefined || this.store.lastOptions.params.limit != itemCount ||
		   this.store.lastOptions.params.start == undefined || this.store.lastOptions.params.start != itemIndex ||
		   (this.dir && (this.store.lastOptions.params.dir == undefined || this.store.lastOptions.params.dir != this.dir)))
		{
			
			// check if it is already displaying all the items
			if(this.dir && this.store.lastOptions && this.store.lastOptions.params.dir && this.store.lastOptions.params.dir == this.dir)
			{
				// reload not needed because all the items fit on one page
				if(this.store.totalLength && itemCount > this.store.getTotalCount())
				{
					// don't reload it just exit
					this.loading.setVisible(false);
					return;
				}
				
				// same dir as last time, so save selections for new load
				this.selectCache = this.selectCache || []
				var s = this.selected.elements;
				for(var i = 0, len = s.length; i < len; i++){
					this.selectCache.push(this.store.getAt(s[i].viewIndex).id);
				}
					
			}
			
			var params = {
				start: itemIndex,
				limit: itemCount * 3
			};
			if(this.dir)
				params.dir = this.dir;
				
			var add = false;
			var insert = false;
			// if it is only changing by 1 page then add a single pages items
			if(this.store.lastOptions && this.store.lastOptions.params)
			{
				if(itemIndex == this.store.lastOptions.params.start + itemCount)
				{
					params.start = itemIndex + itemCount * 2;
					params.limit = itemCount;
					add = true;
				}
				else
				{
					if(itemIndex == this.store.lastOptions.params.start - itemCount)
					{
						//params.start = itemIndex - itemCount;
						params.limit = itemCount;
						add = true;
						insert = true;
						//if(params.start < 0) return;
					}
					else
					{
						this.showLoading();
					}
				}
			}
			else
			{
				this.showLoading();
			}
			
			// finally load new items
			this.store.load({
				callback: function(r, options, success) { this.fireEvent("storefinished", r, options, success); },
				items_per_row: items_per_row,
				sampleSize: this.sampleItem.getSize(),
				scope: this,
				add: add,
				insert: insert,
				params: params
			});
		}
		else
		{
			this.loading.setVisible(false);
		}
	},

	onResize : function() {
		
		if(!this.sampleItem)
			this.sampleItem = this.getSampleItem();
	
		var previousIndex = 0;
		if(this.store.lastOptions && this.store.lastOptions.params && this.store.lastOptions.params.start != undefined)
			var previousIndex = this.store.lastOptions.params.start;
			
		var scrollerEl = Ext.Element.get(this.el.dom.parentNode);
		var sampleSize = this.sampleItem.getSize();
		
		var items_per_row = Math.floor(this.el.getWidth() / (sampleSize.width + this.sampleItem.getMargins('lr')));
		
		var new_height = (Math.floor(this.store.getTotalCount() / items_per_row) || 1) * (sampleSize.height + this.sampleItem.getMargins('tb'));
		
		var new_scroll_pos = Math.floor(previousIndex / items_per_row) * (sampleSize.height + this.sampleItem.getMargins('tb'));
		if(new_scroll_pos > new_height - scrollerEl.getHeight() + (sampleSize.height + this.sampleItem.getMargins('tb')))
			new_scroll_pos = new_height - scrollerEl.getHeight() + (sampleSize.height + this.sampleItem.getMargins('tb'));

		scrollerEl.dom.scrollTop = new_scroll_pos;
		
		this.showLoading();

		this.onReload();
	},

	onMove : function() {
		if(!this.sampleItem)
			this.sampleItem = this.getSampleItem();
		
		var sampleSize = this.sampleItem.getSize();
		var scrollerEl = Ext.Element.get(this.el.dom.parentNode);
		
		var items_per_row = Math.floor(this.el.getWidth() / (sampleSize.width + this.sampleItem.getMargins('lr')));
		var rows_per_page = Math.ceil(scrollerEl.getHeight() / (sampleSize.height + this.sampleItem.getMargins('tb')));

		var itemIndex = Math.floor(scrollerEl.getScroll().top / (sampleSize.height + this.sampleItem.getMargins('tb')) / rows_per_page) * items_per_row * rows_per_page - (items_per_row * rows_per_page);
		if(itemIndex < 0) itemIndex = 0;
		//var itemCount = rows_per_page * items_per_row * 3;
		
		if(!this.store.lastOptions || !this.store.lastOptions.params ||
		   this.store.lastOptions.params.start == undefined || this.store.lastOptions.params.start != itemIndex)
		{
			// don't show loading unless it is more then a page difference
			if(this.store.lastOptions && this.store.lastOptions.params &&  Math.abs(this.store.lastOptions.params.start - itemIndex) <= items_per_row * rows_per_page)
			{
				this.window.bottomToolbar.setText(this.loadingText);
				
				this.fireEvent("reload");
			}
			else
			{
				this.showLoading();
				
				this.fireEvent("reload");
			}
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
			// if an add was done, remove the first items
			if((options.add || options.insert) && options.params && options.params.limit)
			{
				this.store.lastOptions = this.store.lastOptions || {};
				this.store.lastOptions.params = this.store.lastOptions.params || {};
				if(options.add && !options.insert)
				{
					for(var i = 0; i < options.params.limit; i++)
					{
						this.store.remove(this.store.getAt(0));
					}
					// change last params value so it doesn't try to load again
					this.store.lastOptions.params.start = options.params.start - options.params.limit * 2;
				}
				else
				{
					// remove the ones added
					for(var i = 0; i < r.length; i++)
					{
						this.store.remove(r[i]);
						this.store.insert(i, r[i]);
					}
					// remove off the end
					for(var i = 0; i < r.length; i++)
					{
						this.store.remove(this.store.getAt(this.store.getCount()-1));
					}
					//this.store.lastOptions.params.start = options.params.start + options.params.limit;
				}
				this.store.lastOptions.params.limit = options.params.limit * 3;
				
			}
			
			// make sure there was no error
			if(r.length == 0)
			{
				var error_element = Ext.Element.get(this.store.reader.xmlData.documentElement).child('error');
				if(error_element)
				{
					Ext.Msg.show({
						title: 'Error',
						msg: error_element.dom.textContent,
						buttons: Ext.MessageBox.ERROR
					});
					
					// go to previous directory
					this.address.fireEvent('change', this.address, this.address.startValue, this.address.getValue())
					return;
				}
			}
			
			if(!this.sampleItem)
				this.sampleItem = this.getSampleItem();
				
			var scrollerEl = Ext.Element.get(this.el.dom.parentNode);
			var items_per_row = 1;
			var sampleSize = this.sampleItem.getSize();
			if(this.sampleItem.getWidth() == 0 && options.sampleSize != undefined)
			{
				sampleSize = options.sampleSize;
			}
			if(this.el.getWidth() == 0 && options.items_per_row != undefined)
			{
				items_per_row = options.items_per_row || 1;
			}
			else
			{
				items_per_row = Math.floor(this.el.getWidth() / (sampleSize.width + this.sampleItem.getMargins('lr'))) || 1;
			}
			var rows_per_page = Math.ceil(scrollerEl.getHeight() / (sampleSize.height + this.sampleItem.getMargins('tb')));
			if(items_per_row < this.store.getTotalCount()) rows_per_page = rows_per_page || 1;
			//var rows_per_page = Math.ceil(scrollerEl.getHeight() / (sampleSize.height + this.sampleItem.getMargins('tb'))) || 1;
			
			var itemIndex = Math.floor(scrollerEl.getScroll().top / (sampleSize.height + this.sampleItem.getMargins('tb')) / rows_per_page) * items_per_row * rows_per_page - (items_per_row * rows_per_page);
			
			// set height based on total count
			var offset = (scrollerEl.getScroll().top / (rows_per_page || 1)) % 
				(sampleSize.height + this.sampleItem.getMargins('tb')) * rows_per_page + 
				((itemIndex<0)?0:(rows_per_page * (sampleSize.height + this.sampleItem.getMargins('tb'))));
				
			var new_height = Math.floor(this.store.getTotalCount() / items_per_row + 0.5) * (sampleSize.height + this.sampleItem.getMargins('tb'));
			this.el.dom.style.marginTop = (scrollerEl.getScroll().top - offset) + 'px';
			this.el.dom.style.height = new_height - (scrollerEl.getScroll().top - offset) + 'px';
			
	
			// set window title
			var address = this.address.getValue().split('/');
			this.ownerCt.ownerCt.setTitle(address[address.length-2]);
	
			if(this.loading)
			{
				this.loading.setVisible(false);
			}
			// change the status bar text
			this.window.bottomToolbar.setText('Displaying ' + this.store.getTotalCount() + ' item' + ((this.store.getTotalCount() != 1)?'s.':'.'));
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
        if(this.loadingText){
			this.clearSelections(false, true, true);
			if(this.empty)
				this.empty.setVisible(false);
			this.all.clear();
        }
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