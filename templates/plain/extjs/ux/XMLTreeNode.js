
/**
 * @class Ext.tree.AsyncTreeNode
 * @extends Ext.tree.TreeNode
 * @cfg {TreeLoader} loader A TreeLoader to be used by this node (defaults to the loader defined on the tree)
 * @constructor
 * @param {Object/String} attributes The attributes/config for the node or just a string with the text for the node 
 */
Ext.ux.XMLTreeNode = function(config){
    this.loaded = false;
    this.loading = false;
    Ext.ux.XMLTreeNode.superclass.constructor.apply(this, arguments);
    /**
    * @event beforeload
    * Fires before this node is loaded, return false to cancel
    * @param {Node} this This node
    */
    this.addEvents('beforeload', 'load');
    /**
    * @event load
    * Fires when this node is loaded
    * @param {Node} this This node
    */
    /**
     * The loader used by this node (defaults to using the tree's defined loader)
     * @type TreeLoader
     * @property loader
     */
};
Ext.extend(Ext.ux.XMLTreeNode, Ext.tree.TreeNode, {
    expand : function(deep, anim, callback){
        if(this.loading){ // if an async load is already running, waiting til it's done
            var timer;
            var f = function(){
                if(!this.loading){ // done loading
                    clearInterval(timer);
                    this.expand(deep, anim, callback);
                }
            }.createDelegate(this);
            timer = setInterval(f, 200);
            return;
        }
        if(!this.loaded){
            if(this.fireEvent("beforeload", this) === false){
                return;
            }
            this.loading = true;
            this.ui.beforeLoad(this);
            var loader = this.loader || this.attributes.loader || this.getOwnerTree().getLoader();
            if(loader){
                //loader.load(this, this.loadComplete.createDelegate(this, [deep, anim, callback]));
                loader.load({
					callback: this.loadComplete.createDelegate(this, [deep, anim, callback]),
					params: {
						dir: this.attributes.path,
						start: (this.attributes.start)?this.attributes.start:0
					}
				});
                return;
            }
        }
        Ext.ux.XMLTreeNode.superclass.expand.call(this, deep, anim, callback);
    },
    
    /**
     * Returns true if this node is currently loading
     * @return {Boolean}
     */
    isLoading : function(){
        return this.loading;  
    },
    
    loadComplete : function(deep, anim, callback){
		var loader = this.loader || this.attributes.loader || this.getOwnerTree().getLoader();
        this.loading = false;
        this.loaded = true;
        this.ui.afterLoad(this);
        this.fireEvent("load", this);
		
		var appendee = this;
		if(this.attributes.start)
		{
			appendee = this.parentNode;
		}
		
		// get the child nodes from the loader
		for(var i = 0; i < loader.data.items.length; i++)
		{
			appendee.appendChild(new Ext.ux.XMLTreeNode({
				text: loader.data.items[i].data.name,
				path: loader.data.items[i].data.path
			}));
		}
		
		// if the total length is over 50, add a more setting
		if(loader.totalLength > 50 && ((this.attributes.start && loader.totalLength - this.attributes.start > 50) || !this.attributes.start))
		{
			appendee.appendChild(new Ext.ux.XMLTreeNode({
				text: "More",
				path: this.attributes.path,
				start: (this.attributes.start)?this.attributes.start+50:50
			}));
		}
		
		// don't expand if the totalLength is wrong
		if(loader.totalLength != -1)
		{
			this.expand(deep, anim, callback);
		}
		else
		{
			this.loaded = false;
		}
		
		// clear loader data
		loader.data.items = [];
		loader.totalLength = -1;
		
		// remove this node
		if(this.attributes.start)
		{
			this.parentNode.removeChild(this);
		}
    },
    
    /**
     * Returns true if this node has been loaded
     * @return {Boolean}
     */
    isLoaded : function(){
        return this.loaded;
    },
    
    hasChildNodes : function(){
        if(!this.isLeaf() && !this.loaded){
            return true;
        }else{
            return Ext.ux.XMLTreeNode.superclass.hasChildNodes.call(this);
        }
    },

    /**
     * Trigger a reload for this node
     * @param {Function} callback
     */
    reload : function(callback){
        this.collapse(false, false);
        while(this.firstChild){
            this.removeChild(this.firstChild);
        }
        this.childrenRendered = false;
        this.loaded = false;
        if(this.isHiddenRoot()){
            this.expanded = false;
        }
        this.expand(false, false, callback);
    }
});