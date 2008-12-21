Ext.ux.FolderView = function(config) {
    this.addEvents({
        'beforebuffer' : true,
        'buffer' : true,
        'cursormove' : true
    });   
    this.horizontalScrollOffset = 16; 
    
    this.loadMask = false;
    
    Ext.apply(this, config);

    this.templates = {};

	this.templates.master = new Ext.Template(
        '<div class="x-grid3" hidefocus="true"><div style="z-index:2000;background:none;position:relative;height:321px; float:right; width: 18px;overflow: scroll;"><div style="background:none;width:1px;overflow:hidden;font-size:1px;height:0px;"></div></div>',
            '<div class="x-grid3-viewport" style="float:left">',
                '<div class="x-grid3-header ux-grid3-header"><div class="x-grid3-header-inner"><div class="x-grid3-header-offset">{header}</div></div><div class="x-clear"></div></div>',
                '<div class="x-grid3-scroller" style="overflow-y:hidden !important;"><div class="x-grid3-body" style="position:relative;">{body}</div><a href="#" class="x-grid3-focus" tabIndex="-1"></a></div>',
            "</div>",
            '<div class="x-grid3-resize-marker">&#160;</div>',
            '<div class="x-grid3-resize-proxy">&#160;</div>',
        "</div>"
    );    
	
	this.templates = Ext.apply(this.templates, this.views[this.viewMode]);
    
    Ext.ux.grid.BufferedGridView.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.FolderView, Ext.ux.grid.BufferedGridView, {
	tooltip : new Ext.ToolTip({
		html: 'Click the X to close me',
		title: 'My Tip Title'
	}),

	viewMode : 'Thumbnails',
	
	views: {
		'Thumbnails' : {
			row : new Ext.Template(
				'<div class="x-grid3-row {alt} thumb-wrap" style="margin:4px;">',
				'<div class="thumb file_type_{type} file_ext_{ext}"><img src="{icon}" title=""></div>',
				'<span class="x-editable">{short}</span>',
				'</div>'
			)
		},
		'Tiles' : {
		},
		'Icons' : {
		},
		'List' : {
		},
		'Details' : {
			row : new Ext.Template(
				'<div class="x-grid3-row {alt}" style="{tstyle}"><table class="x-grid3-row-table" border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
				'<tbody><tr>{cells}</tr>',
				(this.enableRowBody ? '<tr class="x-grid3-row-body-tr" style="{bodyStyle}"><td colspan="{cols}" class="x-grid3-body-cell" tabIndex="0" hidefocus="on"><div class="x-grid3-row-body">{body}</div></td></tr>' : ''),
				'</tbody></table></div>'
			)
		}
	},
	    
    removeRow : function(row){
        Ext.removeNode(this.getRow(row));
        //this.focusRow(row);
    },
    
    removeRows : function(firstRow, lastRow){
        var bd = this.mainBody.dom;
        for(var rowIndex = firstRow; rowIndex <= lastRow; rowIndex++){
            Ext.removeNode(bd.childNodes[firstRow]);
        }
        //this.focusRow(firstRow);
    },

    renderUI : function()
    {
        Ext.ux.FolderView.superclass.renderUI.call(this);
		
		this.mainBody.on("mousemove", function(e){this.tooltip.onMouseMove(e);}, this);
		this.mainBody.unselectable();
	},
	
    onRowOver : function(e, t)
    {
        var row;
        if((row = this.findRowIndex(t)) !== false){
            var viewIndex = row-this.rowIndex;
            this.addRowClass(viewIndex, "x-grid3-row-over");
			
			this.tooltip.target = this.fly(this.getRow(viewIndex));
			var row = this.ds.getById(row);
			this.tooltip.setTitle(row.data.name);
			if(this.tooltip.body)
				this.tooltip.body.dom.innerHTML = row.data.tip;
			else
				this.tooltip.html = row.data.tip;
				
			this.tooltip.hide();
			// reset the elapsed time so it will show based on new delay
			this.tooltip.lastActive = this.tooltip.lastActive.add(Date.MILLI, -this.tooltip.quickShowInterval);
			this.tooltip.onTargetOver(e);

        }
    },
	
    onRowOut : function(e, t)
    {
        var row;
        if((row = this.findRowIndex(t)) !== false && row !== this.findRowIndex(e.getRelatedTarget())){
            var viewIndex = row-this.rowIndex;
            this.removeRowClass(viewIndex, "x-grid3-row-over");
			this.tooltip.onTargetOut(e);
        }
    },    
	
	refresh : function(headersToo) {
		Ext.ux.FolderView.superclass.refresh.call(this, headersToo);

		// check the view mode and display or hide columns
		var cm = this.grid.getColumnModel();
		for(var i = 0; i < cm.getColumnCount(); i++)
		{
			cm.setHidden(i, (this.viewMode != 'Details'));
		}
	},
	
	changeView : function(viewMode) {
		this.viewMode = viewMode;
		this.rowHeight = -1;
		this.grid.store.removeAll();
		this.templates = Ext.apply(this.templates, this.views[viewMode]);
		this.grid.store.reload();
	},
	
    onLiveScroll : function()
    {
        var scrollTop     = this.liveScroller.dom.scrollTop; 
        
		//------------------------------------------------------------------------------- increment by the number of items in a row
        var cursor = Math.floor((scrollTop)/this.rowHeight) * this.itemCount.x;
        this.rowIndex = cursor;
        // the lastRowIndex will be set when refreshing the view has finished
        if (cursor == this.lastRowIndex) {
            return;
        }
        
        this.updateLiveRows(cursor);
        this.lastScrollPos = this.liveScroller.dom.scrollTop; 
    },    
	
    // protected
    adjustBufferInset : function()
    {
        var g = this.grid, ds = g.store;
        
        var c  = g.getGridEl();

		
        var scrollbar = (this.cm.getTotalWidth()+this.scrollOffset > c.getSize().width && this.viewMode == 'Details');
        //var scrollbar = false;
        
        // adjust the height of the scrollbar
        this.liveScroller.dom.style.height = this.liveScroller.dom.parentNode.offsetHeight + 
                                             (Ext.isGecko 
                                             ? ((ds.totalLength > 0 && scrollbar)
                                                ? - this.horizontalScrollOffset
                                                : 0)
                                             : (((ds.totalLength > 0 && scrollbar)
                                                ? 0 : this.horizontalScrollOffset)))+"px";                
        if (this.rowHeight == -1) {
            return;
        }
               
        if (ds.totalLength <= this.visibleRows) {
            this.liveScrollerInset.style.height = "0px";
            return;
        } 
        
		//------------------------------------------------------------------------------- total items vertically and horizontally 
        var height = this.rowHeight * Math.ceil(ds.totalLength / this.itemCount.x);
        
        height += (c.getSize().height-(this.itemCount.y * this.rowHeight));
        
        if (scrollbar) {
            height -= this.horizontalScrollOffset;
        }
        
        this.liveScrollerInset.style.height = (height)+"px";
    },
		   
    // protected
    adjustVisibleRows : function()
    {
		if (this.rowHeight == -1) {
			var row = this.getRows()[0];
			if (row) {
				this.rowHeight = row.offsetHeight + Ext.get(row).getMargins('tb'); 
				this.rowWidth = row.offsetWidth + Ext.get(row).getMargins('lr');
			} else {
				return;
			}
		} 
        
        
        var g = this.grid, ds = g.store;
        
        var c    = g.getGridEl();
        var cm   = this.cm;
        var size = c.getSize(true);
        var vh   = size.height;    
        
        var vw = size.width-this.scrollOffset;        
        // horizontal scrollbar shown?
        if (cm.getTotalWidth() > vw && this.viewMode == 'Details') {
            // yes!
            vh -= this.horizontalScrollOffset;
        }
		if(this.viewMode == 'Details') this.rowWidth = vw;
        
        vh -= this.mainHd.getHeight();
        
		//------------------------------------------------------------------------------- items on a page vertically and horizontally 
		this.itemCount = {x: Math.floor(vw/this.rowWidth), y: Math.floor(vh/this.rowHeight)};
        var visibleRows = Math.max(1, this.itemCount.x * this.itemCount.y);
        
        var totalLength = ds.getTotalCount();
        
        if (totalLength < this.visibleRows || this.visibleRows == visibleRows) {
            return;
        }
        
        this.visibleRows = visibleRows;
        
        if (this.rowIndex + visibleRows > totalLength) {
            this.rowIndex     = Math.max(0, ds.totalLength-this.visibleRows);
            this.lastRowIndex = this.rowIndex;
            this.updateLiveRows(this.rowIndex, true);
        } else {
            this.updateLiveRows(this.rowIndex, true);
        } 
    },
	
	renderBody: function() {
        var markup = this.renderRows(0, this.visibleRows-1);
		
		if( this.viewMode == 'Details' && this.colModelChanged)
		{
			// check if columns have changed
			this.colModelChanged = false;
			var cm = this.grid.getColumnModel();
			cm.setConfig(cm.config);
			this.ds.reader.recordType = Ext.data.Record.create(this.defaultRecord);
			this.ds.recordType = this.ds.reader.recordType;
			this.ds.fields = this.ds.recordType.prototype.fields
			markup = this.renderRows(0, this.visibleRows-1);
		}
		
        return this.templates.body.apply({rows: markup});

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
	
    // private
    doRender : function(cs, rs, ds, startRow, colCount, stripe){

		var ts = this.templates, ct = ts.cell, rt = ts.row, last = colCount-1;
        var tstyle = 'width:'+this.getTotalWidth()+';';
        // buffers
        var buf = [], cb, c, p = {}, r;
        for(var j = 0, len = rs.length; j < len; j++){
			var rp = {tstyle: tstyle};
            r = rs[j]; cb = [];
			
            var rowIndex = (j+startRow);
            for(var i = 0; i < colCount; i++){
                c = cs[i];
                p.id = c.id;
                p.css = i == 0 ? 'x-grid3-cell-first ' : (i == last ? 'x-grid3-cell-last ' : '');
                p.attr = p.cellAttr = "";
                p.value = c.renderer(r.data[c.name], p, r, rowIndex, i, ds);
                p.style = c.style;
                if(p.value == undefined || p.value === "") p.value = "&#160;";
                if(r.dirty && typeof r.modified[c.name] !== 'undefined'){
                    p.css += ' x-grid3-dirty-cell';
                }
                cb[cb.length] = ct.apply(p);
            }
            var alt = [];
            if(stripe && ((rowIndex+1) % 2 == 0)){
                alt[0] = "x-grid3-row-alt";
            }
            if(r.dirty){
                alt[1] = " x-grid3-dirty-row";
            }
            rp.cols = colCount;
            if(this.getRowClass){
                alt[2] = this.getRowClass(r, rowIndex, rp, ds);
            }
            rp.alt = alt.join(" ");
            rp.cells = cb.join("");
			//------------------------------------------------------------------------------- add the record data to rp so that a row can use the data as well as the cells
			rp = Ext.applyIf(rp, r.data);
            buf[buf.length] =  rt.apply(rp);
        }
		
        return buf.join("");
    }

	
});

Ext.reg('folderview', Ext.ux.FolderView);