var PresidentsDataStore;
var PresidentsColumnModel;
var PresidentListingEditorGrid;
var PresidentListingWindow;

Ext.onReady(function(){

  Ext.QuickTips.init();

  PresidentsDataStore = new Ext.data.Store({
      id: 'PresidentsDataStore',
      proxy: new Ext.data.HttpProxy({
                url: 'database.php', 
                method: 'POST'
            }),
            baseParams:{task: "LISTING"}, // this parameter is passed for any HTTP request
      reader: new Ext.data.JsonReader({
        root: 'results',
        totalProperty: 'total',
        id: 'id'
      },[ 
        {name: 'IDpresident', type: 'int', mapping: 'IDpresident'},
        {name: 'FirstName', type: 'string', mapping: 'firstname'},
        {name: 'LastName', type: 'string', mapping: 'lastname'},
        {name: 'IDparty', type: 'int', mapping: 'IDparty'},
        {name: 'PartyName', type: 'string', mapping: 'name'},
        {name: 'TookOffice', type: 'date', mapping: 'tookoffice'},
        {name: 'LeftOffice', type: 'date', mapping: 'leftoffice'},
        {name: 'Income', type: 'float', mapping: 'income'}
      ]),
      sortInfo:{field: 'IDpresident', direction: "ASC"}
    });
    
  PresidentsColumnModel = new Ext.grid.ColumnModel(
    [{
        header: '#',
        readOnly: true,
        dataIndex: 'IDpresident',
        width: 50,
        hidden: false
      },{
        header: 'First Name',
        dataIndex: 'FirstName',
        width: 60,
        editor: new Ext.form.TextField({
            allowBlank: false,
            maxLength: 20,
            maskRe: /([a-zA-Z0-9\s]+)$/
          })
      },{
        header: 'Last Name',
        dataIndex: 'LastName',
        width: 80,
        editor: new Ext.form.TextField({
          allowBlank: false,
          maxLength: 20,
          maskRe: /([a-zA-Z0-9\s]+)$/
          })
      },{
        header: 'ID party',
        readOnly: true,
        dataIndex: 'IDparty',
        width: 50,
        hidden: true
      },{
        header: 'Party',
        dataIndex: 'PartyName',
        width: 150,
        readOnly: true
      },{
				header: 'Took Office',
				dataIndex: 'TookOffice',
				width: 80,
				renderer: Ext.util.Format.dateRenderer('m/d/Y'),
				editor: new Ext.form.DateField({
					format: 'm/d/Y'
				}),
				hidden: false
		},{
				header: 'Left Office',
				dataIndex: 'LeftOffice',
				width: 80,
				renderer: Ext.util.Format.dateRenderer('m/d/Y'),
				editor: new Ext.form.DateField({
					format: 'm/d/Y'
				}),
				hidden: false
		},{
        header: "Income",
        dataIndex: 'Income',
        width: 150,
        renderer: function(v){ return '$ ' + v; },
        editor: new Ext.form.NumberField({
          allowBlank: false,
          allowDecimals: true,
          allowNegative: false,
          blankText: '0',
          maxLength: 11
          })
      }]
    );
    PresidentsColumnModel.defaultSortable= true;
    
  PresidentListingEditorGrid =  new Ext.grid.EditorGridPanel({
      id: 'PresidentListingEditorGrid',
      store: PresidentsDataStore,
      cm: PresidentsColumnModel,
      enableColLock:false,
      clicksToEdit:1,
      selModel: new Ext.grid.RowSelectionModel({singleSelect:false})
    });
    
  PresidentListingWindow = new Ext.Window({
      id: 'PresidentListingWindow',
      title: 'The Presidents of the USA',
      closable:true,
      width:700,
      height:350,
      plain:true,
      layout: 'fit',
      items: PresidentListingEditorGrid
    });
  
  PresidentsDataStore.load();
  PresidentListingWindow.show();
  
});