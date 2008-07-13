var JobsDataStore;
var JobsColumnModel;
var JobListingEditorGrid;
var JobListingWindow;
var JobProxy;
var myfilters = { };
var myparams = { };

var filterfields = [ "jid", "queue", "name", "owner" ];

function ClusterImageSelectHost( somehost )
{
 // reload clusterimage with somehost as arg
}

function isset( somevar )
{
  try
  {
    if( eval( somevar ) ) { }
  }
  catch( err )
  {
    return false;
  }
  return true;
}

function inMyArray( arr, someval )
{
  for( arval in arr )
  {
    if( arval == someval )
    {
      return true;
    }
  }
  return false;
}

function inMyArrayValues( arr, someval )
{
  for( arkey in arr )
  {
    if( arr[arkey] == someval )
    {
      return true;
    }
  }
  return false;
}

function inMyArrayKeys( arr, someval )
{
  for( arkey in arr )
  {
    if( arkey == someval )
    {
      return true;
    }
  }
  return false;
}

function joinMyArray( arr1, arr2 )
{
  for( arkey in arr2 )
  {
    arr1[arkey] = arr2[arkey];
  }

  return arr1;
}

function initJobGrid() {

  Ext.QuickTips.init();

  function jobCellClick(grid, rowIndex, columnIndex, e)
  {
    var record = grid.getStore().getAt(rowIndex);  // Get the Record
    var fieldName = grid.getColumnModel().getDataIndex(columnIndex);
    var data = record.get(fieldName);
    var view = grid.getView();
    var cell = view.getCell( rowIndex, columnIndex );

    if( fieldName == 'owner' || fieldName == 'jid' || fieldName == 'status' || fieldName == 'queue' )
    {
      if( !isset( myfilters[fieldName] ) )
      {
        Ext.fly(cell).removeClass( 'filterenabled' );
        Ext.fly(cell).addClass( 'filter' );

	// Remove this filter
	//
	delete myfilters[fieldName];
	delete myparams[fieldName];

	// Respect any other parameters that may have been set outside filters
	//
        myparams = joinMyArray( myparams, myfilters );

	// Can't be sure if there are enough pages for new filter: reset to page 1
	//
        myparams = joinMyArray( myparams, { start: 0, limit: 30 } );

        grid.getStore().reload( { params: myparams } );
      }
      else
      {
        Ext.fly(cell).removeClass( 'filter' );
        Ext.fly(cell).addClass( 'filterenabled' );

	// Set filter for selected column to selected cell value
	//
        myfilters[fieldName] = data;

        myparams = joinMyArray( myparams, myfilters );
        myparams = joinMyArray( myparams, { start: 0, limit: 30 } );

        grid.getStore().reload( { params: myparams } );
      }
    }
  }

  function jobCellRender( value, metadata, record, rowindex, colindex, store )
  {
    var fieldName = JobsColumnModel.getColumnById( colindex ).dataIndex;

    if( fieldName == 'owner' || fieldName == 'jid' || fieldName == 'status' || fieldName == 'queue' )
    {
      if( myfilters[fieldName] != null )
      {
        metadata.css = 'filterenabled';
      }
      else
      {
        metadata.css = 'filter';
      }
    }
    return value;
  }

  JobProxy = new Ext.data.HttpProxy({
                url: 'jobstore.php',
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
        {name: 'nodect', type: 'int', mapping: 'nodect'},
        {name: 'nodes', type: 'string', mapping: 'nodes'},
        {name: 'queued_timestamp', type: 'string', mapping: 'queued_timestamp'},
        {name: 'start_timestamp', type: 'string', mapping: 'start_timestamp'},
        {name: 'runningtime', type: 'string', mapping: 'runningtime'}
      ]),
      sortInfo: {field: 'jid', direction: "ASC"},
      remoteSort: true
    });
    
  JobsColumnModel = new Ext.grid.ColumnModel(
    [{
        header: '#',
	tooltip: 'Job id',
        readOnly: true,
        dataIndex: 'jid',
        width: 50,
        hidden: false,
	renderer: jobCellRender
      },{
        header: 'S',
	tooltip: 'Job status',
        readOnly: true,
        dataIndex: 'status',
        width: 20,
        hidden: false,
	renderer: jobCellRender
      },{
        header: 'User',
	tooltip: 'Owner of job',
        readOnly: true,
        dataIndex: 'owner',
        width: 60,
        hidden: false,
	renderer: jobCellRender
      },{
        header: 'Queue',
	tooltip: 'In which queue does this job reside',
        readOnly: true,
        dataIndex: 'queue',
        width: 60,
        hidden: false,
	renderer: jobCellRender
      },{
        header: 'Name',
	tooltip: 'Name of job',
        readOnly: true,
        dataIndex: 'name',
        width: 100,
        hidden: false
      },{
        header: 'Requested Time',
	tooltip: 'Amount of requested time (wallclock)',
        readOnly: true,
        dataIndex: 'requested_time',
        width: 100,
        hidden: false
      },{
        header: 'Requested Memory',
	tooltip: 'Amount of requested memory',
        readOnly: true,
        dataIndex: 'requested_memory',
        width: 100,
        hidden: true
      },{
        header: 'P',
	tooltip: 'Number of processors per node (PPN)',
        readOnly: true,
        dataIndex: 'ppn',
        width: 25,
        hidden: false
      },{
        header: 'N',
	tooltip: 'Number of nodes (hosts)',
        readOnly: true,
        dataIndex: 'nodect',
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
	tooltip: 'At what time did this job enter the queue',
        readOnly: true,
        dataIndex: 'queued_timestamp',
        width: 140,
        hidden: false
      },{
        header: 'Started',
	tooltip: 'At what time did this job enter the running status',
        readOnly: true,
        dataIndex: 'start_timestamp',
        width: 140,
        hidden: false
      },{
        header: 'Runningtime',
	tooltip: 'How long has this job been in the running status',
        readOnly: true,
        dataIndex: 'runningtime',
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
      loadMask: true,
      selModel: new Ext.grid.RowSelectionModel({singleSelect:false}),
      stripeRows: true,
      bbar: new Ext.PagingToolbar({
                pageSize: 30,
                store: JobsDataStore,
                displayInfo: true,
	    	displayMsg: 'Displaying jobs {0} - {1} out of {2} jobs total found.',
    		emptyMsg: 'No jobs found to display'
            }),
      tbar: [ new Ext.app.SearchField({
		                store: JobsDataStore,
				params: {start: 0, limit: 30},
		                width: 200
		    })
      ]
    });

  ClusterImageWindow = new Ext.Window({
      id: 'ClusterImageWindow',
      title: 'Cluster Nodes Overview',
      closable:true,
      collapsible: true,
      animCollapse: true,
      width:100,
      height:100,
      y: 50,
      plain:true,
      shadow: true,
      resizable: false,
      shadowOffset: 10,
      layout: 'fit'
    });

  JobListingWindow = new Ext.Window({
      id: 'JobListingWindow',
      title: 'Cluster Jobs Overview',
      closable:true,
      collapsible: true,
      animCollapse: true,
      maximizable: true,
      y: 400,
      width:900,
      height:500,
      plain:true,
      shadow: true,
      shadowOffset: 10,
      layout: 'fit',
      items: JobListingEditorGrid
    });

  JobListingEditorGrid.addListener( 'cellclick', jobCellClick );
}
