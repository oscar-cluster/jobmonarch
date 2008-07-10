var JobsDataStore;
var JobsColumnModel;
var JobListingEditorGrid;
var JobListingWindow;

Ext.onReady(function(){

  Ext.QuickTips.init();

  JobProxy = new Ext.data.HttpProxy({
                url: 'jobstore.php?c=GINA Cluster',
                method: 'POST'
            });

  JobsDataStore = new Ext.data.Store({
      id: 'JobsDataStore',
      proxy: JobProxy,
      baseParams:{task: "LISTING"}, // this parameter is passed for any HTTP request
      reader: new Ext.data.JsonReader({
        root: 'results',
        totalProperty: 'total',
        id: 'id'
      },[
        {name: 'jid', type: 'int', mapping: 'jid'},
        {name: 'status', type: 'string', mapping: 'status'},
        {name: 'owner', type: 'string', mapping: 'owner'},
        {name: 'queue', type: 'string', mapping: 'queue'},
        {name: 'name', type: 'string', mapping: 'name'},
        {name: 'requested_time', type: 'string', mapping: 'requested_time'},
        //{name: 'requested_memory', type: 'string', mapping: 'requested_memory'},
        {name: 'ppn', type: 'int', mapping: 'ppn'},
        {name: 'cpu', type: 'int', mapping: 'cpu'},
        {name: 'nodes', type: 'string', mapping: 'nodes'},
        {name: 'queued_timestamp', type: 'string', mapping: 'queued_timestamp'},
        {name: 'start_timestamp', type: 'string', mapping: 'start_timestamp'}
      ]),
      sortInfo:{field: 'jid', direction: "ASC"}
    });
    
  JobsColumnModel = new Ext.grid.ColumnModel(
    [{
        header: '#',
        readOnly: true,
        dataIndex: 'jid',
        width: 50,
        hidden: false
      },{
        header: 'S',
        readOnly: true,
        dataIndex: 'status',
        width: 20,
        hidden: false
      },{
        header: 'User',
        readOnly: true,
        dataIndex: 'owner',
        width: 60,
        hidden: false
      },{
        header: 'Queue',
        readOnly: true,
        dataIndex: 'queue',
        width: 60,
        hidden: false
      },{
        header: 'Name',
        readOnly: true,
        dataIndex: 'name',
        width: 100,
        hidden: false
      },{
        header: 'Requested Time',
        readOnly: true,
        dataIndex: 'requested_time',
        width: 100,
        hidden: false
      },{
        header: 'Requested Memory',
        readOnly: true,
        dataIndex: 'requested_memory',
        width: 100,
        hidden: true
      },{
        header: 'PPN',
        readOnly: true,
        dataIndex: 'ppn',
        width: 25,
        hidden: false
      },{
        header: 'CPU',
        readOnly: true,
        dataIndex: 'ppn',
        width: 25,
        hidden: false
      },{
        header: 'Nodes',
        readOnly: true,
        dataIndex: 'nodes',
        width: 100,
        hidden: true
      },{
        header: 'Queued',
        readOnly: true,
        dataIndex: 'queued_timestamp',
        width: 140,
        hidden: false
      },{
        header: 'Started',
        readOnly: true,
        dataIndex: 'start_timestamp',
        width: 140,
        hidden: false
      }]
    );
    JobsColumnModel.defaultSortable= true;

  JobListingEditorGrid =  new Ext.grid.EditorGridPanel({
      id: 'JobListingEditorGrid',
      store: JobsDataStore,
      cm: JobsColumnModel,
      enableColLock:false,
      clicksToEdit:1,
      selModel: new Ext.grid.RowSelectionModel({singleSelect:false})
    });

function debug(){ 
JobListingEditorGrid.addListener({

	/**
	 * activate : ( Ext.Panel p )
	 * Fires after the Panel has been visually activated. Note that 
	 * Panels do not directly support being activated, but some Panel 
	 * subclasses do (like Ext.Window). Panels which are child 
	 * Components of a TabPanel fire the activate and deactivate events 
	 * under the control of the TabPanel.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 *     The Panel that has been activated.
	 */
	 'activate':{
		fn: function(panel){
			console.log('Grid listener fired (activate), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * add : ( Ext.Container this, Ext.Component component, Number index )
	 * Fires after any Ext.Component is added or inserted into the container.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Container
	 * component : Ext.Component
	 *     The component that was added
	 * index : Number
	 *     The index at which the component was added to the container's items collection
	 */
	,'add':{
		fn: function(container, component, index){
			console.log('Grid listener fired (add), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * afteredit : ( Object e )
	 * Fires after a cell is edited.
		 * grid - This grid
	 * record - The record being edited
	 * field - The field name being edited
	 * value - The value being set
	 * originalValue - The original value for the field, before the edit.
	 * row - The grid row index
	 * column - The grid column index
	 * 
	 * Listeners will be called with the following arguments:
	 * e : Object
	 * 		An edit event (see above for description)
	 */
	,'afteredit':{
		fn: function(event){
			console.log('Grid listener fired (afteredit), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * afterlayout : ( Ext.Container this, ContainerLayout layout )
	 * Fires when the components in this container are arranged by the 
	 * associated layout manager. Listeners will be called with the 
	 * following arguments:
	     * this : Ext.Container
     * layout : ContainerLayout
     * 		The ContainerLayout implementation for this container
	 */
	,'afterlayout':{
		fn: function(container, layout){
			console.log('Grid listener fired (afterlayout), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeadd : ( Ext.Container this, Ext.Component component, Number index )
	 * Fires before any Ext.Component is added or inserted into the 
	 * container. A handler can return false to cancel the add.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Container
	 * component : Ext.Component
	 * 		The component being added
	 * index : Number
	 * 		The index at which the component will be added to the
	 * 		container's items collection
	 */
	,'beforeadd':{
		fn: function(container, component, index){
			console.log('Grid listener fired (beforeadd), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeadd : ( Ext.Container this, Ext.Component component, Number index )
	 * Fires before any Ext.Component is added or inserted into the 
	 * container. A handler can return false to cancel the add.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Container
	 * component : Ext.Component
	 * 		The component being added
	 * index : Number
	 * 		The index at which the component will be added to the
	 * 		container's items collection
	 */
	,'beforeadd':{
		fn: function(container, component, index){
			console.log('Grid listener fired (beforeadd), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeclose : ( Ext.Panel p )
	 * Fires before the Panel is closed. Note that Panels do not 
	 * directly support being closed, but some Panel subclasses do 
	 * (like Ext.Window). This event only applies to such subclasses. A
	 * handler can return false to cancel the close.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * 		The Panel being closed.
	 */
	,'beforeclose':{
		fn: function(panel){
			console.log('Grid listener fired (beforeclose), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforecollapse : ( Ext.Panel p, Boolean animate )
	 * Fires before the Panel is collapsed. A handler can return 
	 * false to cancel the collapse.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * 		the Panel being collapsed.
	 * animate : Boolean
	 * 		True if the collapse is animated, else false.
	 */
	,'beforecollapse':{
		fn: function(panel, animate){
			console.log('Grid listener fired (beforecollapse), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforedestroy : ( Ext.Component this )
	 * Fires before the component is destroyed. Return false to stop 
	 * the destroy.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'beforedestroy':{
		fn: function(component){
			console.log('Grid listener fired (beforedestroy), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeedit : ( Object e )
	 * Fires before cell editing is triggered. The edit event object 
	 * has the following properties
	 * grid - This grid
	 * record - The record being edited
	 * field - The field name being edited
	 * value - The value for the field being edited.
	 * row - The grid row index
	 * column - The grid column index
	 * cancel - Set this to true to cancel the edit or return false 
	 * from your handler.
	 * 
	 * Listeners will be called with the following arguments:
	 * e : Object
	 * 		An edit event (see above for description)
	 */
	,'beforeedit':{
		fn: function(event){
			console.log('Grid listener fired (beforeedit), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeexpand : ( Ext.Panel p, Boolean animate 
	 * Fires before the Panel is expanded. A handler can return false to cancel the expand.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * The Panel being expanded.
	 * animate : Boolean
	 * True if the expand is animated, else false.
	 */
	,'beforeexpand':{
		fn: function(panel, animate){
			console.log('Grid listener fired (beforeexpand), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforehide : ( Ext.Component this )
	 * Fires before the component is hidden. Return false to stop the hide.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'beforehide':{
		fn: function(component){
			console.log('Grid listener fired (beforehide), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeremove : ( Ext.Container this, Ext.Component component )
	 * Fires before any Ext.Component is removed from the container. A handler 
	 * an return false to cancel the remove.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Container
	 * component : Ext.Component
	 * The component being removed
	 */
	,'beforeremove':{
		fn: function(container, component){
			console.log('Grid listener fired (beforeremove), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforerender : ( Ext.Component this )
	 * Fires before the component is rendered. Return false to stop the render.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'beforerender':{
		fn: function(component){
			console.log('04 - Grid listener fired (beforerender), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforeshow : ( Ext.Component this )
	 * Fires before the component is shown. Return false to stop the show.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'beforeshow':{
		fn: function(component){
			console.log('Grid listener fired (beforeshow), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforestaterestore : ( Ext.Component this, Object state )
	 * Fires before the state of the component is restored. Return false to stop the restore.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 * state : Object
	 * The hash of state values
	 */
	,'beforestaterestore':{
		fn: function(component, state){
			console.log('Grid listener fired (beforestaterestore), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * beforestatesave : ( Ext.Component this, Object state )
	 * Fires before the state of the component is saved to the configured 
	 * state provider. Return false to stop the save.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 * state : Object
	 * The hash of state values
	 */
	,'beforestatesave':{
		fn: function(component, state){
			console.log('Grid listener fired (beforestatesave), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * bodyresize : ( Ext.Panel p, Number width, Number height )
	 * Fires after the Panel has been resized.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * the Panel which has been resized.
	 * width : Number
	 * The Panel's new width.
	 * height : Number
	 * The Panel's new height.
	 */
	,'bodyresize':{
		fn: function(panel, width, height){
			console.log('08 - Grid listener fired (bodyresize), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * bodyscroll : ( Number scrollLeft, Number scrollTop )
	 * Fires when the body element is scrolled
	 * Listeners will be called with the following arguments:
	 * scrollLeft : Number
	 * scrollTop : Number
	 */
	,'bodyscroll':{
		fn: function(scrollLeft, scrollTop){
			console.log('Grid listener fired (bodyscroll), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * cellclick : ( Grid this, Number rowIndex, Number columnIndex, Ext.EventObject e )
	 * Fires when a cell is clicked. The data for the cell is drawn from the 
	 * Record for this row. To access the data in the listener function use the 
	 * following technique:
	 * function(grid, rowIndex, columnIndex, e) {
	 * 	var record = grid.getStore().getAt(rowIndex);  // Get the Record
	 *  var fieldName = grid.getColumnModel().getDataIndex(columnIndex); // Get field name
	 *  var data = record.get(fieldName);
	 * }
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'cellclick':{
		fn: function(grid, rowIndex, columnIndex, event){
			console.log('Grid listener fired (cellclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * cellcontextmenu : ( Grid this, Number rowIndex, Number cellIndex, Ext.EventObject e )
	 * Fires when a cell is right clicked
	 * Listeners will be called with the following arguments:
	 * rowIndex : Number
	 * cellIndex : Number
	 * e : Ext.EventObject
	 */
	,'cellcontextmenu':{
		fn: function(grid, rowIndex, cellIndex, event){
			console.log('Grid listener fired (cellcontextmenu), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * celldblclick : ( Grid this, Number rowIndex, Number columnIndex, Ext.EventObject e )
	 * Fires when a cell is double clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'celldblclick':{
		fn: function(grid, rowIndex, columnIndex, event){
			console.log('Grid listener fired (celldblclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * cellmousedown : ( Grid this, Number rowIndex, Number columnIndex, Ext.EventObject e )
	 * Fires before a cell is clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'cellmousedown':{
		fn: function(grid, rowIndex, columnIndex, event){
			console.log('Grid listener fired (cellmousedown), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * click : ( Ext.EventObject e )
	 * The raw click event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'click':{
		fn: function(event){
			console.log('Grid listener fired (click), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * close : ( Ext.Panel p )
	 * Fires after the Panel is closed. Note that Panels do not directly support being closed, but some Panel subclasses do (like Ext.Window).
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * The Panel that has been closed.
	 */
	,'close':{
		fn: function(panel){
			console.log('Grid listener fired (close), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * collapse : ( Ext.Panel p )
	 * Fires after the Panel has been collapsed.
	 * Listeners will be called with the following arguments:
	 *  p : Ext.Panel
	 * the Panel that has been collapsed.
	 */
	,'collapse':{
		fn: function(panel){
			console.log('Grid listener fired (collapse), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * columnmove : ( Number oldIndex, Number newIndex )
	 * Fires when the user moves a column
	 * Listeners will be called with the following arguments:
	 * oldIndex : Number
	 * newIndex : Number
	 */
	,'columnmove':{
		fn: function(oldIndex, newIndex){
			console.log('Grid listener fired (columnmove), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * columnresize : ( Number columnIndex, Number newSize )
	 * Fires when the user resizes a column
	 * Listeners will be called with the following arguments:
	 * columnIndex : Number
	 * newSize : Number
	 */
	,'columnresize':{
		fn: function(columnIndex, newSize){
			console.log('Grid listener fired (columnresize), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * contextmenu : ( Ext.EventObject e )
	 * The raw contextmenu event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject		
	 */
	,'contextmenu':{
		fn: function(event){
			console.log('Grid listener fired (contextmenu), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * dblclick : ( Ext.EventObject e )
	 * The raw dblclick event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'dblclick':{
		fn: function(event){
			console.log('Grid listener fired (dblclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * deactivate : ( Ext.Panel p )
	 * Fires after the Panel has been visually deactivated. Note that Panels do not directly support being deactivated, but some Panel subclasses do (like Ext.Window). Panels which are child Components of a TabPanel fire the activate and deactivate events under the control of the TabPanel.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * The Panel that has been deactivated.
	 */
	,'deactivate':{
		fn: function(panel){
			console.log('Grid listener fired (deactivate), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * destroy : ( Ext.Component this )
	 * Fires after the component is destroyed.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'destroy':{
		fn: function(component){
			console.log('Grid listener fired (destroy), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * disable : ( Ext.Component this )
	 * Fires after the component is disabled.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'disable':{
		fn: function(component){
			console.log('Grid listener fired (disable), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * enable : ( Ext.Component this )
	 * Fires after the component is enabled.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'enable':{
		fn: function(component){
			console.log('Grid listener fired (enable), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * expand : ( Ext.Panel p )
	 * Fires after the Panel has been expanded.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * The Panel that has been expanded.
	 */
	,'expand':{
		fn: function(panel){
			console.log('Grid listener fired (expand), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * headerclick : ( Grid this, Number columnIndex, Ext.EventObject e )
	 * Fires when a header is clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'headerclick':{
		fn: function(grid, columnIndex, event){
			console.log('Grid listener fired (headerclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * headercontextmenu : ( Grid this, Number columnIndex, Ext.EventObject e )
	 * Fires when a header is right clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'headercontextmenu':{
		fn: function(grid, columnIndex, event){
			console.log('Grid listener fired (headercontextmenu), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * headerdblclick : ( Grid this, Number columnIndex, Ext.EventObject e )
	 * Fires when a header cell is double clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'headerdblclick':{
		fn: function(grid, columnIndex, event){
			console.log('Grid listener fired (headerdblclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * headermousedown : ( Grid this, Number columnIndex, Ext.EventObject e )
	 * Fires before a header is clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * columnIndex : Number
	 * e : Ext.EventObject
	 */
	,'headermousedown':{
		fn: function(grid, columnIndex, event){
			console.log('Grid listener fired (headermousedown), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * hide : ( Ext.Component this )
	 * Fires after the component is hidden.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'hide':{
		fn: function(component){
			console.log('Grid listener fired (hide), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * keydown : ( Ext.EventObject e )
	 * The raw keydown event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'keydown':{
		fn: function(event){
			console.log('Grid listener fired (keydown), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * keypress : ( Ext.EventObject e )
	 * The raw keypress event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'keypress':{
		fn: function(event){
			console.log('Grid listener fired (keypress), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * mousedown : ( Ext.EventObject e )
	 * The raw mousedown event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'mousedown':{
		fn: function(event){
			//console.log('Grid listener fired (mousedown), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * mouseout : ( Ext.EventObject e )
	 * The raw mouseout event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'mouseout':{
		fn: function(event){
			//console.log('Grid listener fired (mouseout), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * mouseover : ( Ext.EventObject e )
	 * The raw mouseover event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'mouseover':{
		fn: function(event){
			console.log('Grid listener fired (mouseover), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * mouseup : ( Ext.EventObject e )
	 * The raw mouseup event for the entire grid.
	 * Listeners will be called with the following arguments:
	 * e : Ext.EventObject
	 */
	,'mouseup':{
		fn: function(event){
			//console.log('Grid listener fired (mouseup), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * move : ( Ext.Component this, Number x, Number y )
	 * Fires after the component is moved.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 * x : Number
	 * The new x position
	 * y : Number
	 * The new y position
	 */
	,'move':{
		fn: function(component, x, y){
			console.log('Grid listener fired (move), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * remove : ( Ext.Container this, Ext.Component component )
	 * Fires after any Ext.Component is removed from the container.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Container
	 * component : Ext.Component
	 * The component that was removed
	 */
	,'remove':{
		fn: function(container, component){
			console.log('Grid listener fired (remove), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * render : ( Ext.Component this )
	 * Fires after the component is rendered.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'render':{
		fn: function(component){
			console.log('06 - Grid listener fired (render), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * resize : ( Ext.Component this, Number adjWidth, Number adjHeight, Number rawWidth, Number rawHeight )
	 * Fires after the component is resized.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 * adjWidth : Number
	 * The box-adjusted width that was set
	 * adjHeight : Number
	 * The box-adjusted height that was set
	 * rawWidth : Number
	 * The width that was originally specified
	 * rawHeight : Number
	 * The height that was originally specified
	 */
	,'resize':{
		fn: function(component, adjWidth, adjHeight, rawWidth, rawHeight){
			console.log('09 - Grid listener fired (resize), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * rowclick : ( Grid this, Number rowIndex, Ext.EventObject e )
	 * Fires when a row is clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * e : Ext.EventObject
	 */
	,'rowclick':{
		fn: function(grid, rowIndex, event){
			console.log('Grid listener fired (rowclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * rowcontextmenu : ( Grid this, Number rowIndex, Ext.EventObject e )
	 * Fires when a row is right clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * e : Ext.EventObject
	 */
	,'rowcontextmenu':{
		fn: function(grid, rowIndex, event){
			console.log('Grid listener fired (rowcontextmenu), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * rowdblclick : ( Grid this, Number rowIndex, Ext.EventObject e )
	 * Fires when a row is double clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * e : Ext.EventObject	
	 */
	,'rowdblclick':{
		fn: function(grid, rowIndex, event){
			console.log('Grid listener fired (rowdblclick), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * rowmousedown : ( Grid this, Number rowIndex, Ext.EventObject e )
	 * Fires before a row is clicked
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * rowIndex : Number
	 * e : Ext.EventObject
	 */
	,'rowmousedown':{
		fn: function(grid, rowIndex, event){
			console.log('Grid listener fired (rowmousedown), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * show : ( Ext.Component this )
	 * Fires after the component is shown.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 */
	,'show':{
		fn: function(component){
			console.log('Grid listener fired (show), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * sortchange : ( Grid this, Object sortInfo )
	 * Fires when the grid's store sort changes
	 * Listeners will be called with the following arguments:
	 * this : Grid
	 * sortInfo : Object
	 * An object with the keys field and direction
	 */
	,'sortchange':{
		fn: function(grid, sortInfo){
			console.log('05 - Grid listener fired (sortchange), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * staterestore : ( Ext.Component this, Object state )
	 * Fires after the state of the component is restored.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 * state : Object
	 * The hash of state values
	 */
	,'staterestore':{
		fn: function(component, state){
			console.log('Grid listener fired (staterestore), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * statesave : ( Ext.Component this, Object state )
	 * Fires after the state of the component is saved to the configured state provider.
	 * Listeners will be called with the following arguments:
	 * this : Ext.Component
	 * state : Object
	 * The hash of state values
	 */
	,'statesave':{
		fn: function(component, state){
			console.log('Grid listener fired (statesave), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * titlechange : ( Ext.Panel p, String The )
	 * Fires after the Panel title has been set or changed.
	 * Listeners will be called with the following arguments:
	 * p : Ext.Panel
	 * the Panel which has had its title changed.
	 * The : String
	 * new title.
	 */
	,'titlechange':{
		fn: function(panel, title){
			console.log('07 - Grid listener fired (titlechange), arguments:',arguments);
		}
		,scope:this
	}

	/**
	 * validateedit : ( Object e )
	 * Fires after a cell is edited, but before the value is set in the record. Return false to cancel the change. The edit event object has the following properties
	 * grid - This grid
	 * record - The record being edited
	 * field - The field name being edited
	 * value - The value being set
	 * originalValue - The original value for the field, before the edit.
	 * row - The grid row index
	 * column - The grid column index
	 * cancel - Set this to true to cancel the edit or return false from your handler.
	 * Listeners will be called with the following arguments:
	 * e : Object
	 * An edit event (see above for descriptio
	 */
	,'validateedit':{
		fn: function(e){
			console.log('Grid listener fired (validateedit), arguments:',arguments);
		}
		,scope:this
	}
	
});//end grid.addListener

/**
 * Proxy Listeners
 * These listeners are not required but may be handy if you're
 * trying to debug your code or see how the process of loading
 * the store works.
 * I tried adding these as part of the constructor they would
 * not fire, but they do appear to work when I add them outside of
 * the constructor.
 */		
JobProxy.on({
	 'beforeload':{
		fn: function(store, options){
			console.log('02 - Proxy listener fired (beforeload), arguments:',arguments);
		}
		,scope:this
	}
	,'load':{
		fn: function(store, options){
			console.log('Proxy listener fired (load), arguments:',arguments);
		}
		,scope:this
	}
	,'loadexception':{
		fn: function(store, options){
			console.log('Proxy listener fired (loadexception), arguments:',arguments);
		}
		,scope:this
	}
});

/**
 * Ajax request listeners 
 */
Ext.Ajax.on({
	 //Fires before a network request is made to retrieve a data object:
	 'beforerequest':{
		fn: function(connection, options){
			console.log('03 - Ajax listener fired (beforerequest), arguments(connection, options):',arguments);
		}
		,scope:this
	}
	//Fires if the request was successfully completed:
	,'requestcomplete':{
		fn: function(connection, response, options){
			console.log('10 - Ajax listener fired (requestcomplete), arguments(connection, response, options):',arguments);
		}
		,scope:this
	}
	//Fires if an error HTTP status was returned from the server. See HTTP Status Code 
	//Definitions for details of HTTP status codes:
	,'requestexception':{
		fn: function(connection, response, options){
			console.log('Ajax listener fired (requestexception), arguments:(connection, response, options)',arguments);
		}
		,scope:this
	}
});

/**
 * Adding listeners outside the constructor
 */
JobsDataStore.on({
	'load':{
		fn: function(store, records, options){
			console.log('01 - Data Store listener fired (load), arguments:',arguments);
			console.log('         this:',this);
		}
		,scope:this
	}
	,'loadexception':{
		fn: function(httpProxy, dataObject, args, exception){
			console.log('** - Data Store listener fired (loadexception), arguments:',arguments);
		}
		,scope:this
	}

	//add remaining events for education:	         
	,'add':{
		fn: function(store, records, index){
			console.log('Data Store listener fired (add), arguments:',arguments);
		}
		,scope:this
	}
	,'beforeload':{
		fn: function(store, options){
			console.log('Data Store listener fired fired (beforeload), arguments:',arguments);
		}
		,scope:this
	}
	,'clear':{
		fn: function(store){
			console.log('Data Store listener fired fired (clear), arguments:',arguments);
		}
		,scope:this
	}
	,'datachanged':{
		fn: function(store){
			console.log('11 - Data Store listener fired fired (datachanged), arguments:',arguments);
			console.log('       If you set a breakpoint here the entire grid will be rendered without data');
			console.log('       ...about to "refresh" grid body');
		}
		,scope:this
	}
	,'remove':{
		fn: function(store, record, index){
			console.log('Data Store listener fired fired (remove), arguments:',arguments);
		}
		,scope:this
	}
	,'update':{
		fn: function(store, record, operation){
			console.log('Data Store listener fired fired (update), arguments:',arguments);
		}
		,scope:this
	}
});
}
    
  JobListingWindow = new Ext.Window({
      id: 'JobListingWindow',
      title: 'Cluster Jobs Overview',
      closable:true,
      width:700,
      height:350,
      plain:true,
      layout: 'fit',
      items: JobListingEditorGrid
    });

  //debug();  
  JobsDataStore.load();
  //JobListingEditorGrid.render();
  JobListingWindow.show();
  
});
