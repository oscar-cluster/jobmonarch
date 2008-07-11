var JobsDataStore;
var JobsColumnModel;
var JobListingEditorGrid;
var JobListingWindow;
var JobProxy;

//Ext.onReady( initJobGrid() );

function initJobGrid() {

  Ext.QuickTips.init();

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
        hidden: false
      },{
        header: 'S',
	tooltip: 'Job status',
        readOnly: true,
        dataIndex: 'status',
        width: 20,
        hidden: false
      },{
        header: 'User',
	tooltip: 'Owner of job',
        readOnly: true,
        dataIndex: 'owner',
        width: 60,
        hidden: false
      },{
        header: 'Queue',
	tooltip: 'In which queue does this job reside',
        readOnly: true,
        dataIndex: 'queue',
        width: 60,
        hidden: false
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
      selModel: new Ext.grid.RowSelectionModel({singleSelect:false}),
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

  JobListingWindow = new Ext.Window({
      id: 'JobListingWindow',
      title: 'Cluster Jobs Overview',
      closable:true,
      collapsible: true,
      animCollapse: true,
      maximizable: true,
      minimizable: true,
      width:900,
      height:500,
      plain:true,
      shadow: true,
      shadowOffset: 10,
      layout: 'fit',
      items: JobListingEditorGrid
    });
}
