Ext.ux.FolderView = function(config) {
    this.addEvents({
        'beforebuffer' : true,
        'buffer' : true,
        'cursormove' : true
    });   
    this.horizontalScrollOffset = 17; 
    
    this.loadMask = false;
    
    Ext.apply(this, config);

    this.templates = {};

    this.templates.master = new Ext.Template(
        '<div class="x-grid3" hidefocus="true"><div class="ext-ux-livegrid-liveScroller"><div></div></div>',
            '<div class="x-grid3-viewport"">',
                '<div class="x-grid3-header"><div class="x-grid3-header-inner"><div class="x-grid3-header-offset">{header}</div></div><div class="x-clear"></div></div>',
                '<div class="x-grid3-scroller" style="overflow-y:hidden !important;"><div class="x-grid3-body">{body}</div><a href="#" class="x-grid3-focus" tabIndex="-1"></a></div>',
            "</div>",
            '<div class="x-grid3-resize-marker">&#160;</div>',
            '<div class="x-grid3-resize-proxy">&#160;</div>',
        "</div>"
    );
	
	this.templates = Ext.apply(this.templates, this.views[this.viewMode]);
    
    this._gridViewSuperclass = Ext.ux.grid.livegrid.GridView.superclass;

    this._gridViewSuperclass.constructor.call(this);
};

Ext.extend(Ext.ux.FolderView, Ext.ux.grid.livegrid.GridView, {
	tooltip : new Ext.ToolTip({
		html: 'Click the X to close me',
		title: 'My Tip Title'
	}),

	viewMode : 'Thumbnails',
	
	views: {
		'Thumbnails' : {
			row : new Ext.Template(
				'<div class="x-grid3-row {alt} thumb-wrap" style="margin:4px;">',
				'<div class="thumb file_ext_{ext}"><div></div></div>',
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
	
    onRowOver : function(e, t)
    {
		Ext.ux.FolderView.superclass.onRowOver.call(this, e, t);
        var row;
        if((row = this.findRowIndex(t)) !== false){
            var viewIndex = row-this.rowIndex;
			
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
		Ext.ux.FolderView.superclass.onRowOut.call(this, e, t);
		var row;
        if((row = this.findRowIndex(t)) !== false && row !== this.findRowIndex(e.getRelatedTarget())){
			this.tooltip.onTargetOut(e);
        }
    },    
	
	refresh : function(headersToo) {
		Ext.ux.FolderView.superclass.refresh.call(this, headersToo);

		// check the view mode and display or hide columns
		var cm = this.grid.getColumnModel();
		for(var i = 0; i < cm.getColumnCount(); i++)
		{
			if(this.viewMode == 'Details' && cm.config[i].isBlank === false)
			{
				cm.setHidden(i, false);
				if(i > 0) cm.setColumnWidth(i, cm.config[i].longest * 6 + 20);
			} else {
				cm.setHidden(i, true);
			}
		}
	},
	
	changeView : function(viewMode) {
		this.viewMode = viewMode;
		this.viewModeChanged = true;
		this.grid.store.removeAll();
		this.templates = Ext.apply(this.templates, this.views[viewMode]);
		this.grid.store.reload();
	},
	
    onLiveScroll : function()
    {
        var scrollTop = this.liveScroller.dom.scrollTop;

		//------------------------------------------------------------------------------- increment by the number of items in a row
        var cursor = Math.floor((scrollTop)/this.rowHeight) * this.itemCount.x;
        //var cursor = Math.floor((scrollTop)/this.rowHeight);

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
        var liveScrollerDom = this.liveScroller.dom;
        var g = this.grid, ds = g.store;
        var c  = g.getGridEl();
        var elWidth = c.getSize().width;

        // hidden rows is the number of rows which cannot be
        // displayed and for which a scrollbar needs to be
        // rendered. This does also take clipped rows into account
        var hiddenRows = (ds.totalLength == this.visibleRows-this.rowClipped)
                       ? 0
                       : Math.max(0, ds.totalLength-(this.visibleRows-this.rowClipped));

        if (hiddenRows == 0) {
            this.scroller.setWidth(elWidth);
            liveScrollerDom.style.display = 'none';
            return;
        } else {
            this.scroller.setWidth(elWidth-this.scrollOffset);
            liveScrollerDom.style.display = '';
        }

        var scrollbar = this.cm.getTotalWidth()+this.scrollOffset > elWidth;

        // adjust the height of the scrollbar
        var contHeight = liveScrollerDom.parentNode.offsetHeight +
                         ((ds.totalLength > 0 && scrollbar)
                         ? - this.horizontalScrollOffset
                         : 0)
                         - this.hdHeight;

        liveScrollerDom.style.height = Math.max(contHeight, this.horizontalScrollOffset*2)+"px";

        if (this.rowHeight == -1) {
            return;
        }

		//------------------------------------------------------------------------------- total items vertically and horizontally 
        this.liveScrollerInset.style.height = (hiddenRows == 0 ? 0 : contHeight+(Math.ceil(hiddenRows / this.itemCount.x)*this.rowHeight))+"px";
    },
		   
    // protected
    adjustVisibleRows : function()
    {
        if (this.rowHeight == -1) {
            if (this.getRows()[0]) {
                this.rowHeight = this.getRows()[0].offsetHeight + Ext.get(this.getRows()[0]).getMargins('tb');
                this.rowWidth = this.getRows()[0].offsetWidth + Ext.get(this.getRows()[0]).getMargins('lr');

				if (this.rowHeight <= 0) {
                    this.rowHeight = -1;
                    return;
                }

            } else {
                return;
            }
        }


        var g = this.grid, ds = g.store;

        var c     = g.getGridEl();
        var cm    = this.cm;
        var size  = c.getSize();
        var width = size.width;
        var vh    = size.height;

        var vw = width-this.scrollOffset;
		//------------------------------------------------------------------------------- Horizontal scrollbar only used in detailed mode
        // horizontal scrollbar shown?
        if (cm.getTotalWidth() > vw && this.viewMode == 'Details') {
            // yes!
            vh -= this.horizontalScrollOffset;
        }
		//------------------------------------------------------------------------------- Row width is the entire row!
		if(this.viewMode == 'Details') this.rowWidth = vw;

        vh -= this.mainHd.getHeight();

        var totalLength = ds.totalLength || 0;

		//------------------------------------------------------------------------------- items on a page vertically and horizontally 
		this.itemCount = {
			x: Math.floor(vw / this.rowWidth),
			y: Math.floor(vh / this.rowHeight)
		};
        var visibleRows = Math.max(1, this.itemCount.x * this.itemCount.y);

        this.rowClipped = 0;
        // only compute the clipped row if the total length of records
        // exceeds the number of visible rows displayable
        if (totalLength > visibleRows && this.rowHeight / 3 < (vh - (this.itemCount.y * this.rowHeight))) {
            visibleRows = Math.min(visibleRows+this.itemCount.x, totalLength);
            this.rowClipped = this.itemCount.x;
			this.itemCount.y += 1
        }

        // if visibleRows   didn't change, simply void and return.
        if (this.visibleRows == visibleRows) {
            return;
        }

        this.visibleRows = visibleRows;

        // skip recalculating the row index if we are currently buffering.
        if (this.isBuffering) {
            return;
        }

        // when re-rendering, do not take the clipped row into account
        if (this.rowIndex + (visibleRows-this.rowClipped) > totalLength) {
            this.rowIndex     = Math.max(0, totalLength-(visibleRows-this.rowClipped));
            this.lastRowIndex = this.rowIndex;
        }

        this.updateLiveRows(this.rowIndex, true);
    },
	
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
		
		//------------------------------------------------------------------------------- Row height must be recalculated after the render
		if(this.viewModeChanged == true)
		{
			this.viewModeChanged = false;
			this.rowHeight = -1;
		}
		
        return buf.join("");
    }

	
});

Ext.reg('folderview', Ext.ux.FolderView);
