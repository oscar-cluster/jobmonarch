var JobsDataStore;
var JobsColumnModel;
var JobListingEditorGrid;
var JobListingWindow;
var JobProxy;
var myfilters = { };
var myparams = { };
var ClusterImageArgs = { };

var filterfields = [ "jid", "queue", "name", "owner" ];

function makeArrayURL( somearr )
{
  filter_url = '';
  filter_sep = '';

  for( filtername in somearr )
  {
    filter_url = filter_url + filter_sep + filtername + '=' + somearr[filtername];
    filter_sep = '&';
  }

  return filter_url;
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

function ClusterImageSelectHost( somehost )
{

  if( !inMyArrayKeys( myfilters, 'host' ) )
  {
    myfilters['host'] = somehost;
  }
  else
  {
    delete myfilters['host'];
    delete myparams['host'];
  }

  reloadClusterImage();
  reloadJobStore();

  return false;
}

function reloadJobStore()
{
  // Respect any other parameters that may have been set outside filters
  //
  myparams = joinMyArray( myparams, myfilters );

  // Can't be sure if there are enough pages for new filter: reset to page 1
  //
  myparams = joinMyArray( myparams, { start: 0, limit: 30 } );

  JobsDataStore.reload( { params: myparams } );
}

function addListener(element, type, expression, bubbling)
{
  bubbling = bubbling || false;
  if(window.addEventListener)
  { // Standard
    element.addEventListener(type, expression, bubbling);
    return true;
  } 
  else if(window.attachEvent) 
  { // IE
    element.attachEvent('on' + type, expression);
    return true;
  } 
  else 
    return false;
}

var ImageLoader = function( id, url )
{
  this.url = url;
  this.image = document.getElementById( id );
  this.loadEvent = null;
};

ImageLoader.prototype = 
{
  load:function()
  {
    var url = this.url;
    var image = this.image;
    var loadEvent = this.loadEvent;
    addListener( this.image, 'load', function(e)
    {
      if( loadEvent != null )
      {
        loadEvent( url, image );
      }
    }, false);
    this.image.src = this.url;
  },
  getImage: function()
  {
    return this.image;
  }
};

function reloadClusterImage()
{
  ClusterImageArgs['view'] = 'big-clusterimage';

  filt_url = makeArrayURL( myfilters );
  imag_url = makeArrayURL( ClusterImageArgs );
  img_url = './image.php?' + filt_url + '&' + imag_url;

  var newClusterImage = new ImageLoader( 'clusterimage', img_url );
  newClusterImage.loadEvent = function( url, image ) 
    {
      ClusterImageWindow.getBottomToolbar().clearStatus( { useDefaults:true } );
    }

  ClusterImageWindow.getBottomToolbar().showBusy();
  newClusterImage.load();
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
      if( inMyArrayKeys( myfilters, fieldName ) )
      {
        Ext.fly(cell).removeClass( 'filterenabled' );
        Ext.fly(cell).addClass( 'filter' );

	// Remove this filter
	//
	delete myfilters[fieldName];
	delete myparams[fieldName];

        reloadJobStore();
	reloadClusterImage();
      }
      else
      {
        Ext.fly(cell).removeClass( 'filter' );
        Ext.fly(cell).addClass( 'filterenabled' );

	// Set filter for selected column to selected cell value
	//
        myfilters[fieldName] = data;

        reloadJobStore();
	reloadClusterImage();
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
      baseParams: { task: "LISTING" },
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
        {name: 'requested_memory', type: 'string', mapping: 'requested_memory'},
        {name: 'ppn', type: 'int', mapping: 'ppn'},
        {name: 'nodect', type: 'int', mapping: 'nodect'},
        {name: 'nodes', type: 'string', mapping: 'nodes'},
        {name: 'queued_timestamp', type: 'string', mapping: 'queued_timestamp'},
        {name: 'start_timestamp', type: 'string', mapping: 'start_timestamp'},
        {name: 'runningtime', type: 'string', mapping: 'runningtime'}
      ]),
      sortInfo: { field: 'jid', direction: "DESC" },
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
        width: 120,
        hidden: false
      },{
        header: 'Started',
	tooltip: 'At what time did this job enter the running status',
        readOnly: true,
        dataIndex: 'start_timestamp',
        width: 120,
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
      title: 'Nodes',
      closable: true,
      collapsible: true,
      animCollapse: true,
      width: 100,
      height: 100,
      y: 15,
      plain: true,
      shadow: true,
      resizable: false,
      shadowOffset: 10,
      layout: 'fit',
      bbar: new Ext.StatusBar({
            	defaultText: 'Ready.',
            	id: 'basic-statusbar',
            	defaultIconCls: ''
        })

    });

  GraphSummaryWindow = new Ext.Window({
      id: 'GraphSummaryWindow',
      title: 'Graph Summary',
      closable: true,
      collapsible: true,
      animCollapse: true,
      width: 300,
      height: 500,
      y: 15,
      plain: true,
      shadow: true,
      resizable: true,
      shadowOffset: 10,
      layout: 'table',
      layoutConfig: {
		columns: 2
	},
      defaults:{border: false},
      items: [{
	id: 'monarchlogo',
	cls: 'monarch',
	bodyStyle: 'background: transparent',
	html: '<A HREF="https://subtrac.sara.nl/oss/jobmonarch/" TARGET="_blank"><IMG SRC="./jobmonarch.gif" ALT="Job Monarch" BORDER="0"></A>'
        //colspan: 2
       },{
	id: 'summarycount'
       },{
	id: 'rjqjgraph'
       },{
	id: 'pie',
	colspan: 2
       }],
      bbar: new Ext.StatusBar({
            	defaultText: 'Ready.',
            	id: 'basic-statusbar',
            	defaultIconCls: ''
        })
    });

  JobListingWindow = new Ext.Window({
      id: 'JobListingWindow',
      title: 'Cluster Jobs Overview',
      closable:true,
      collapsible: true,
      animCollapse: true,
      maximizable: true,
      y: 375,
      width:860,
      height:500,
      plain:true,
      shadow: true,
      shadowOffset: 10,
      layout: 'fit',
      items: JobListingEditorGrid
    });

  JobListingEditorGrid.addListener( 'cellclick', jobCellClick );
}
