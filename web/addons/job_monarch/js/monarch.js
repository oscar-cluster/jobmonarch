var JobsDataStore;
var JobsColumnModel;
var JobListingEditorGrid;
var JobListingWindow;
var JobProxy;
var SearchField;
var filterButton;
var globalWindowCount = 0;

// Extra header to prevent browser caching
//
Ext.Ajax.defaultHeaders =
{
	'If-Modified-Since':	'Sat, 1 Jan 2005 00:00:00 GMT'
};

// associative filter array
//
var myfilters = { };

// any other datastore params
//
var myparams = { };

// (default) paging size
//
var mylimit = 15;

var ClusterImageArgs = { };

var filterfields = [ "jid", "queue", "name", "owner" ];

var graphWindowBehaviour = 'tabbed-new-window';
var previousGraphWindow;

var filterMenu = new Ext.menu.Menu(
{
	id:	'filterMenu',
	items:	[ new Ext.menu.Item({ text: 'Clear all', handler: clearFilters }) ]
});

var filterButton = new Ext.Button(
{
	id:		'filtermenuknop',
	text:		'Filters',
	toolip:		'Click to change filter options',
	disabled:	true,
	menu:		filterMenu,
	listeners:
	{
		'click':
		{
			scope:	this,
			fn:	function( myButton, event )
				{	// immediatly show menu when button is clicked
					myButton.menu.show( myButton.getEl() );
				}
		}
	}
});

function checkGraphWindowOption( item, checked )
{
	graphWindowBehaviour	= item.id;
}

var graphMenu = new Ext.menu.Menu(
{
	id:	'graphMenu',
	items:
	[{
		id:		'new-window',
		text:		'Each job in new window',
		checked:	false,
		group:		'graphwindow',
		handler:	checkGraphWindowOption
	},{
		id:		'tabbed-new-window',
		text:		'Each job in a seperate tab, in new window',
		checked:	true,
		group:		'graphwindow',
		handler:	checkGraphWindowOption
	},{
		id:		'tabbed-prev-window',
		text:		'Each job in a seperate tab, in last opened window',
		checked:	false,
		group:		'graphwindow',
		handler:	checkGraphWindowOption
	}]
});

var showGraphsButton = new Ext.Toolbar.SplitButton(
{
	id:		'showgraphbutton',
	text:		'Show graphs',
	disabled:	true,
	menu:		graphMenu,
	listeners:
	{
		'click':
		{
			scope:	this,
			fn:	ShowGraphs
		}
	}
});

Ext.namespace('Ext.ux');

Ext.ux.PageSizePlugin = function()
{
	Ext.ux.PageSizePlugin.superclass.constructor.call(this,
	{
		store:	new Ext.data.SimpleStore(
		{
			fields:	['text', 'value'],
			data:	[['10', 10], ['15', 15], ['20', 20], ['30', 30], ['50', 50], ['100', 100], ['250', 250], ['500', 500], ['all', 'max' ]]
		}),
		mode:		'local',
		displayField:	'text',
		valueField:	'value',
		editable:	false,
		allowBlank:	false,
		triggerAction:	'all',
		width:		50
	});
};

Ext.extend(Ext.ux.PageSizePlugin, Ext.form.ComboBox,
{
	init:			function(paging)
				{
					paging.on('render', this.onInitView, this);
				},
    
	onInitView:		function(paging)
				{
					paging.add('-',
					this,
					'jobs per page');
					this.setValue(paging.pageSize);
					this.on('select', this.onPageSizeChanged, paging);
				},

	onPageSizeChanged:	function(combo)
				{
					if ( combo.getValue() == 'max' )
					{
						mylimit = JobsDataStore.getTotalCount();
					}
					else
					{
						mylimit = parseInt(combo.getValue());
					}
					this.pageSize = mylimit;
					this.doLoad(0);
				}
});

Ext.namespace( 'Ext' );

function clearFilters()
{
	if( inMyArrayKeys( myfilters, 'query' ) )
	{
		SearchField.getEl().dom.value = '';
		delete SearchField.store.baseParams['query'];
		delete myfilters['query'];
		delete myparams['query'];
	}
	if( inMyArrayKeys( myfilters, 'host' ) )
	{
		delete myfilters['host'];
		delete myparams['host'];
	}
	if( inMyArrayKeys( myfilters, 'jid' ) )
	{
		delete myfilters['jid'];
		delete myparams['jid'];
	}
	if( inMyArrayKeys( myfilters, 'queue' ) )
	{
		delete myfilters['queue'];
		delete myparams['queue'];
	}
	if( inMyArrayKeys( myfilters, 'owner' ) )
	{
		delete myfilters['owner'];
		delete myparams['owner'];
	}
	if( inMyArrayKeys( myfilters, 'status' ) )
	{
		delete myfilters['status'];
		delete myparams['status'];
	}
	reloadJobStore();
}

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

function ArraySize( arr )
{
	count = 0;

	for( arkey in arr )
	{
		count = count + 1;
	}

	return count;
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
		if( myfilters['host'] == somehost )
		{
			delete myfilters['host'];
			delete myparams['host'];
		}
		else
		{
			myfilters['host'] = somehost;
		}
	}

	reloadClusterImage();
	reloadJobStore();

	// returning false causes a image reload
	//
	return false;
}

function reloadJobStore()
{
	// Respect any other parameters that may have been set outside filters
	//
	myparams = joinMyArray( myparams, myfilters );

	// Can't be sure if there are enough pages for new filter: reset to page 1
	//
	myparams = joinMyArray( myparams, { start: 0, limit: mylimit } );

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
	{
		return false;
	}
}

function makeFilterString()
{
	var filter_str = '';

	for( arkey in myfilters )
	{
		filter_str = filter_str + ' > ' + myfilters[arkey];
	}

	return filter_str;
}

var ImageLoader = function( id, url )
{
	this.url = url;
	this.image = document.getElementById( id );
	this.loadEvent = null;
};

ImageLoader.prototype = 
{
	load:		function()
			{
				var url		= this.url;
				var image	= this.image;
				var loadEvent	= this.loadEvent;
				addListener( this.image, 'load',
					function(e)
					{
						if( loadEvent != null )
						{
							loadEvent( url, image );
						}
					}, false);
				this.image.src = this.url;
			},
	getImage:	function()
			{
				return this.image;
			}
};

function achorJobListing()
{
	JobListingWindow.anchorTo( "ClusterImageWindow", "tr-br", [ 0, 10 ] );
}

function setClusterImagePosition()
{
	ci_x = (window.innerWidth - ClusterImageWindow.getSize()['width'] - 20); 
	ClusterImageWindow.setPosition( ci_x, 10 );
}

function deselectFilterMenu( menuItem, event )
{
	filterValue = menuItem.text;

	if( filterValue == SearchField.getEl().dom.value && inMyArrayKeys( myfilters, 'query' ) )
	{
		SearchField.getEl().dom.value = '';
		delete SearchField.store.baseParams['query'];
	}

	for( arkey in myfilters )
	{
		if( myfilters[arkey] == filterValue )
		{
			delete myfilters[arkey];
			delete myparams[arkey];
		}
	}
	reloadJobStore();
}

function makeFilterMenu()
{
	var filterMenu = new Ext.menu.Menu(
	{
		id:	'filterMenu',
		items:	[ new Ext.menu.Item({ text: 'Clear all', handler: clearFilters }) ]
	});

	if( ArraySize( myfilters ) > 0 )
	{
		filterMenu.addSeparator();
	}

	for( arkey in myfilters )
	{
		filterMenu.add( new Ext.menu.CheckItem({ text: myfilters[arkey], handler: deselectFilterMenu, checked: true }) );
	}

	if( filterButton )
	{
		filterButton.menu = filterMenu;

		if( ArraySize( myfilters ) > 0 )
		{
			filterButton.enable();
		}
		else
		{
			filterButton.disable();
		}
	}
}

function reloadClusterImage()
{
	ClusterImageArgs['view']	= 'big-clusterimage';

	filt_url			= makeArrayURL( myfilters );
	imag_url			= makeArrayURL( ClusterImageArgs );
	img_url				= './image.php?' + filt_url + '&' + imag_url;

	var newClusterImage		= new ImageLoader( 'clusterimage', img_url );
	newClusterImage.loadEvent	= function( url, image ) 
	{
		ClusterImageWindow.getBottomToolbar().clearStatus( { useDefaults:true } );
		setTimeout( "resizeClusterImage()", 250 );
		setTimeout( "setClusterImagePosition()", 500 );
		//setTimeout( "achorJobListing()", 1000 );
	};

	ClusterImageWindow.getBottomToolbar().showBusy();

	filter_str = 'Nodes' + makeFilterString();
	ClusterImageWindow.setTitle( filter_str );

	newClusterImage.load();
}

function resizeClusterImage()
{
	var ci_height	= document.getElementById( "clusterimage" ).height + ClusterImageWindow.getFrameHeight();
	var ci_width	= document.getElementById( "clusterimage" ).width + ClusterImageWindow.getFrameWidth();

	ClusterImageWindow.setSize( ci_width, ci_height );
}

Ext.apply(Ext.form.VTypes,
{
	num:		function(val, field)
			{
				if (val) 
				{
					var strValidChars = "0123456789";
					var blnResult = true;

					if (val.length == 0) return false;

					//  test strString consists of valid characters listed above
					for (i = 0; i < val.length && blnResult == true; i++)
					{
						strChar = val.charAt(i);
						if (strValidChars.indexOf(strChar) == -1)
						{
							blnResult = false;
						}
					}
					return blnResult;

				}
			},
	numText:	'Must be numeric'
});

function jobBeforeRowSelect( mySelectionModel, rowIndex, keepExisting, myRecord )
{
	if( myRecord.get('status') == 'Q' )
	{	// return false: dont select row if queued
		return false;
	}

	return true;
}

function jobRowSelect( mySelectionModel, rowIndex, myRecord ) 
{
	if( mySelectionModel.hasSelection() )
	{
		showGraphsButton.enable();

		return 0;
	}

	showGraphsButton.disable();
}

function jobCellClick(grid, rowIndex, columnIndex, e)
{
	var record		= grid.getStore().getAt(rowIndex);  // Get the Record
	var fieldName		= grid.getColumnModel().getDataIndex(columnIndex);
	var data		= record.get(fieldName);
	var view		= grid.getView();
	var cell		= view.getCell( rowIndex, columnIndex );
	var filter_title	= false;
	var fil_dis		= 'filter';
	var fil_ena		= 'filterenabled';
	var filterName		= fieldName;

	if( fieldName == 'owner' || fieldName == 'jid' || fieldName == 'status' || fieldName == 'queue' || fieldName == 'nodes')
	{
		if( fieldName == 'nodes' )
		{
			filterName	= 'host';
			fil_dis		= 'nodesfilter';
			fil_ena		= 'nodesfilterenabled';
		}
		if( inMyArrayKeys( myfilters, filterName ) )
		{
			Ext.fly(cell).removeClass( fil_ena );
			Ext.fly(cell).addClass( fil_dis );

			// Remove this filter
			//
			delete myfilters[filterName];
			delete myparams[filterName];

			reloadJobStore();
			//reloadClusterImage();
		}
		else
		{
			Ext.fly(cell).removeClass( fil_dis );
			Ext.fly(cell).addClass( fil_ena );

			if( fieldName == 'nodes' )
			{ // Get the first node (master mom) as node filter
				new_data = data.split( ',' )[0];
				data = new_data;
			}

			// Set filter for selected column to selected cell value
			//
			myfilters[filterName] = data;

			reloadJobStore();
			//reloadClusterImage();
		}
		JobListingWindow.setTitle( filter_str );

		filter_title	= true;
		filter_str	= myparams.c + ' Jobs Overview' + makeFilterString();
	}
}

function jobCellRender( value, metadata, record, rowindex, colindex, store )
{
	var fieldName	= JobsColumnModel.getColumnById( colindex ).dataIndex;
	var fil_dis	= 'filter';
	var fil_ena	= 'filterenabled';
	var filterName	= fieldName;

	if( fieldName == 'owner' || fieldName == 'jid' || fieldName == 'status' || fieldName == 'queue' || fieldName == 'nodes' )
	{
		if( fieldName == 'nodes' )
		{
			fil_dis		= 'nodesfilter';
			fil_ena		= 'nodesfilterenabled';
			filterName	= 'host';
		}
		if( myfilters[filterName] != null )
		{
			metadata.css	= fil_ena;
		}
		else
		{
			metadata.css	= fil_dis;
		}
	}
	return value;
}

var JobProxy = new Ext.data.HttpProxy(
{
	url:		'jobstore.php',
	method:		'POST'
});

JobsDataStore = new Ext.data.Store(
{
	id:		'JobsDataStore',
	proxy:		JobProxy,
	baseParams:	{ task: "GETJOBS" },
	reader:
		new Ext.data.JsonReader(
		{
			root:		'results',
			totalProperty:	'total',
			id:		'id'
		},
		[
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
	sortInfo: 
	{ 
		field:		'jid', 
		direction:	"DESC" 
	},
	remoteSort: true,
	listeners:
	{ 
		'beforeload':
		{
			scope: this,
			fn:

			function( myStore, myOptions )
			{
				// Add a (bogus) timestamp, to create a unique url and prevent browser caching
				//
				myStore.proxy.url	= 'jobstore.php?timestamp=' + new Date().getTime();

				if( SearchField )
				{
					search_value = SearchField.getEl().dom.value;
					if( search_value == '' )
					{
						delete SearchField.store.baseParams['query'];
						delete myfilters['query'];
						delete myparams['query'];
					}
					else
					{
						myfilters['query']	= search_value;
					}

					makeFilterMenu();
					reloadClusterImage();

					filter_str = myparams.c + ' Jobs Overview' + makeFilterString();
					JobListingWindow.setTitle( filter_str );
				}
			}
		},
		'load':
		{
			scope: this,
			fn:

			function( store, records, options )
			{
				if( records.length == 1 ) // TODO: if job state is running
				{
					jobid		= records[0].get('jid');

					myPanel		= createGraphPanel();
					nodeDatastore	= createNodesDataStore( myparams.c, jobid );
					graphView	= createGraphView( nodeDatastore, jobid );

					//graphView.autoShow = true;

					newtab = myPanel.add( graphView );
					myPanel.setActiveTab( newtab );
					myPanel.doLayout();

					//nodeDatastore.removeAll();

					Ext.getCmp('preview-pane').removeAll();
					Ext.getCmp('preview-pane').add( myPanel );
					Ext.getCmp('preview-pane').doLayout();
				}
				else
				{
					Ext.getCmp('preview-pane').removeAll();
					Ext.getCmp('preview-pane').doLayout();
				}
			}
		}
	}
});
   
var CheckJobs =

	new Ext.grid.CheckboxSelectionModel(
	{
		listeners:
		{
			'beforerowselect':
			{
				scope:	this,
				fn:	jobBeforeRowSelect
			},
			'rowselect':
			{
				scope:	this,
				fn:	jobRowSelect
			},
			'rowdeselect':
			{
				scope:	this,
				fn:	jobRowSelect
			}
		}
	});

JobsColumnModel = new Ext.grid.ColumnModel(
[
	CheckJobs,
	{
		header:		'#',
		tooltip:	'Job id',
		readOnly:	true,
		dataIndex:	'jid',
		width:		50,
		hidden:		false,
		renderer:	jobCellRender
	},{
		header:		'S',
		tooltip:	'Job status',
		readOnly:	true,
		dataIndex:	'status',
		width:		20,
		hidden:		false,
		renderer:	jobCellRender
	},{
		header:		'User',
		tooltip:	'Owner of job',
		readOnly:	true,
		dataIndex:	'owner',
		width:		60,
		hidden:		false,
		renderer:	jobCellRender
	},{
		header:		'Queue',
		tooltip:	'In which queue does this job reside',
		readOnly:	true,
		dataIndex:	'queue',
		width:		60,
		hidden:		false,
		renderer:	jobCellRender
	},{
		header:		'Name',
		tooltip:	'Name of job',
		readOnly:	true,
		dataIndex:	'name',
		width:		100,
		hidden:		false
	},{
		header:		'Requested Time',
		tooltip:	'Amount of requested time (wallclock)',
		readOnly:	true,
		dataIndex:	'requested_time',
		width:		100,
		hidden:		false
	},{
		header:		'Requested Memory',
		tooltip:	'Amount of requested memory',
		readOnly:	true,
		dataIndex:	'requested_memory',
		width:		100,
		hidden:		true
	},{
		header:		'P',
		tooltip:	'Number of processors per node (PPN)',
		readOnly:	true,
		dataIndex:	'ppn',
		width:		25,
		hidden:		false
	},{
		header:		'N',
		tooltip:	'Number of nodes (hosts)',
		readOnly:	true,
		dataIndex:	'nodect',
		width:		25,
		hidden:		false
	},{
		header:		'Nodes',
		readOnly:	true,
		dataIndex:	'nodes',
		width:		100,
		hidden:		false,
		renderer:	jobCellRender
	},{
		header:		'Queued',
		tooltip:	'At what time did this job enter the queue',
		readOnly:	true,
		dataIndex:	'queued_timestamp',
		width:		120,
		hidden:		false
	},{
		header:		'Started',
		tooltip:	'At what time did this job enter the running status',
		readOnly:	true,
		dataIndex:	'start_timestamp',
		width:		120,
		hidden:		false
	},{
		header:		'Runningtime',
		tooltip:	'How long has this job been in the running status',
		readOnly:	true,
		dataIndex:	'runningtime',
		width:		140,
		hidden:		false
	}
]);

JobsColumnModel.defaultSortable	= true;

var win;

MetricsDataStore = new Ext.data.Store(
{
	id:		'MetricsDataStore',
	proxy:		new Ext.data.HttpProxy(
	{
		url:		'jobstore.php',
		method:		'POST'
	}),
	autoLoad:	true,
	baseParams:	{ task: "GETMETRICS" },
	reader:
		new Ext.data.JsonReader(
		{
			root:		'names',
			totalProperty:	'total',
			id:		'id'
		},
		[{
			name:		'ID'
		},{
			name:		'name'
		}
		]),
	listeners:
	{ 
		'beforeload':
		{
			scope: this,
			fn:

			function( myStore, myOptions )
			{
				// Add a (bogus) timestamp, to create a unique url and prevent browser caching
				//
				myStore.proxy.url	= 'jobstore.php?timestamp=' + new Date().getTime();
			}
		}
	}
});

SearchField	= new Ext.app.SearchField(
		{
			store:	JobsDataStore,
			params:	{start: 0, limit: mylimit},
			width:	200
		});

function createNodesDataStore( cluster, jid )
{
	nodesDataStore =

		new Ext.data.Store(
		{
			//id:		'NodesDataStore',
			proxy:		new Ext.data.HttpProxy(
			{
				url:		'jobstore.php',
				method:		'POST'
			}),
			autoLoad:	true,
			baseParams:
			{
				'task':			"GETNODES",
				'c':			cluster,
				'jid':			jid
			},
			reader: new Ext.data.JsonReader(
			{
				root:		'results',
				totalProperty:	'total',
				id:		'id'
			},[
				{name: 'jid', type: 'string', mapping: 'jid'},
				{name: 'ga', type: 'string', mapping: 'ga'}
			]),
			listeners:
			{ 
				'beforeload':
				{
					scope: this,
					fn:

					function( myStore, myOptions )
					{
						// Add a (bogus) timestamp, to create a unique url and prevent browser caching
						//
						myStore.proxy.url	= 'jobstore.php?timestamp=' + new Date().getTime();
					}
				}
			}

		});

	return nodesDataStore;
}

function createGraphView( store, jid )
{
	var graphView =
	
		new Ext.DataView(
		{
			//id:		jid,
			id:		'jobPanel',
			itemSelector:	'thumb',
			title:		jid,
			style:		'overflow:auto, heigth: auto',
			multiSelect:	true,
			//autoHeight:	true,
			autoShow:	true,
			//autoScroll:	true,
			//loadMask:	true,
			store:		store,
			//layout:		'fit',
			closable:	true,
			tpl:
			
				new Ext.XTemplate(
					'<tpl for=".">',
					'<div class="rrd-float"><a href="./graph.php?z=large&{ga}" border="0" rel="lightbox[{jid}.{[globalWindowCount]}]"><img src="./graph.php?z=small&{ga}" border="0"></a></div>',
					'</tpl>')
		});

	return graphView;
}

function createGraphPanel( view )
{
	var scrollerMenu = new Ext.ux.TabScrollerMenu(
	{
		maxText  : 15,
		pageSize : 5
	});


	var graphPanel = 

		new Ext.TabPanel(
		{
			id:		'tabPanel',
			xtype:		'tabpanel',
			//region:		'center',
			//bodyStyle:	'background: transparent',
			autoShow:	true,
			//autoHeight:	true,
			//autoWidth:	true,
			enableTabScroll:true,
			resizeTabs:	true,
			border:		false,
			bodyStyle:	'overflow:auto; background: transparent; heigth: auto',
			minTabWidth:	60,
			plugins:	[ scrollerMenu ],

			// RB TODO: range combobox; hour, day, week, etc

			tbar:
			[
				'Metric name: ',
				new Ext.form.ComboBox(
				{
					fieldLabel:	'Metric',
					id:		'myComboBox',
					store:		MetricsDataStore,
					valueField:	'name',
					displayField:	'name',
					typeAhead:	true,
					mode:		'remote',
					triggerAction:	'all',
					emptyText:	'load_one',
					selectOnFocus:	true,
					xtype:		'combo',
					width:		100,
					//myview:		view,
					listeners:
					{
						select: 
								
						function(combo, record, index)
						{
							var metric	= record.data.name;

							parentPanel	= this.findParentByType( 'tabpanel' );
							my_dataview	= parentPanel.getActiveTab();

							my_dataview.getStore().baseParams.metricname	= metric;
							my_dataview.getStore().reload();
						}
					}
				})
			]
		});

	return graphPanel;
}

function createGraphWindow( panel, Button )
{
	graphWindow =

		new Ext.Window(
		{
			animateTarget:	Button,
			width:		500,
			height:		300,
			closeAction:	'hide',
			collapsible:	true,
			animCollapse:	true,
			maximizable:	true,
			layout:		'fit',
			//autoScroll:	true,
			//defaults:	{autoScroll:true},
			title:		'Node graph details',
			//tbar:		panel,
			items:		panel,
		
			listeners:
			{
				resize:

				function(  myWindow, width, height )
				{
					var myPanel	= myWindow.items.get( 'tabPanel' );
					var myView	= myPanel.getActiveTab();

					myPanel.doLayout();
					myWindow.doLayout();
				}
			}
		});

	return graphWindow;
}

function ShowGraphs( Button, Event ) 
{
	var row_records		= CheckJobs.getSelections();
	var graphJids		= Array();
	var windowCount		= 0;
	var tabCount		= 0;

	for( var i=0; i<row_records.length; i++ )
	{
		rsel		= row_records[i];
		jid		= rsel.get('jid');

		if( graphJids[windowCount] == undefined )
		{
			graphJids[windowCount]	= Array();
		}

		graphJids[windowCount][tabCount]	= jid;

		if( (i+1) < row_records.length )
		{
			if( graphWindowBehaviour == 'new-window' )
			{
				windowCount++;
			}
			else
			{
				tabCount++;
			}
		}
	}

	for( var w=0; w<=windowCount; w++ )
	{
		if( ( graphWindowBehaviour == 'tabbed-prev-window' ) && ( previousGraphWindow != null ) && ( previousGraphPanel != null ) )
		{
			myWindow	= previousGraphWindow;
			myPanel		= previousGraphPanel;
		}
		else
		{
			myPanel		= createGraphPanel();
			myWindow	= createGraphWindow( myPanel, Button );

			myWindow.add( myPanel );

			globalWindowCount++;
		}

		for( var t=0; t<=tabCount; t++ )
		{
			nodeDatastore	= createNodesDataStore( myparams.c, graphJids[w][t] );
			graphView	= createGraphView( nodeDatastore, graphJids[w][t] );

			nodeDatastore.removeAll();

			lastView	= myPanel.add( graphView );

			myPanel.doLayout();
		}

		myPanel.setActiveTab( lastView );

		myWindow.show( Button );
		myWindow.doLayout();

		previousGraphWindow	= myWindow;
		previousGraphPanel	= myPanel;
	}
}

var JobListingEditorGrid =

	new Ext.grid.EditorGridPanel(
	{
		id:		'JobListingEditorGrid',
		region:		'center',
		store:		JobsDataStore,
		cm:		JobsColumnModel,
		enableColLock:	false,
		clicksToEdit:	1,
		loadMask:	true,
		selModel:	new Ext.grid.RowSelectionModel( { singleSelect:	false } ),
		stripeRows:	true,
		sm:		CheckJobs,
		listeners:
		{
			'cellclick':
			{
				scope:	this,
				fn:	jobCellClick
			}
		},
		bbar:
	
		new Ext.PagingToolbar(
		{
			pageSize:	15,
			store:		JobsDataStore,
			displayInfo:	true,
			displayMsg:	'Displaying jobs {0} - {1} out of {2} jobs total.',
			emptyMsg:	'No jobs found to display',
			plugins:	[ new Ext.ux.ProgressBarPager(), new Ext.ux.PageSizePlugin() ]
		}),

		tbar: 
		[ 
			SearchField,
			'-',
			showGraphsButton,
			'-',
			filterButton 
		],

		view:		new Ext.ux.grid.BufferView(
		{
			// custom row height
			//rowHeight: 34,
			// render rows as they come into viewable area.
			scrollDelay: false
		})

	});

var ClusterImageWindow =

	new Ext.Window(
	{
		id:		'ClusterImageWindow',
		title:		'Nodes',
		closable:	true,
		collapsible:	true,
		animCollapse:	true,
		width:		1,
		height:		1,
		y:		15,
		plain:		true,
		shadow:		true,
		resizable:	false,
		shadowOffset:	10,
		layout:		'fit',
		bbar: 
		
			new Ext.ux.StatusBar(
			{
				defaultText:	'Ready.',
				id:		'basic-statusbar',
				defaultIconCls:	''
			})
	});

var GraphSummaryWindow =

	new Ext.Window(
	{
		id:		'GraphSummaryWindow',
		title:		'Graph Summary',
		closable:	true,
		collapsible:	true,
		animCollapse:	true,
		width:		500,
		height:		400,
		x:		10,
		y:		10,
		plain:		true,
		shadow:		true,
		resizable:	true,
		shadowOffset:	10,
		layout:		'table',
		layoutConfig: 
		{
			columns: 2
		},
		defaults:	{ border: false },
		items: 
		[
			{
				id:		'monarchlogo',
				cls:		'monarch',
				bodyStyle:	'background: transparent'
			},{
				id:		'summarycount'
			},{
				id:		'rjqjgraph'
			},{
				id:		'pie',
				colspan:	2
			}
		],
		bbar:
		
			new Ext.ux.StatusBar(
			{
				defaultText:	'Ready.',
				id:		'basic-statusbar',
				defaultIconCls:	''
			})
	});

var JobListingWindow =

	new Ext.Window(
	{
		id:		'JobListingWindow',
		title:		'Cluster Jobs Overview',
		closable:	true,
		collapsible:	true,
		animCollapse:	true,
		maximizable:	true,
		y:		375,
		width:		860,
		height:		645,
		plain:		true,
		shadow:		true,
		shadowOffset:	10,
		layout:		'border',
		items:		
		[		JobListingEditorGrid,
				{
					region:		'south', 
					layout:		'fit',
					id:		'preview-pane',
					height:		200,
					collapsible:	true,
					border:		true,
					title:		'Preview',
					split:		true,
					bodyStyle:	'overflow:auto; background: transparent; heigth: auto'
				}
		]
	});
