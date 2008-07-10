/*
 *
 * This file is part of Jobmonarch
 *
 * Copyright (C) 2006  Ramon Bastiaans
 *
 * Jobmonarch is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Jobmonarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * SVN $Id: libtoga.js 231 2006-03-10 16:36:38Z bastiaans $
 */

function setSort( sortbyval ) {

	if( sortbyval != document.toga_form.sortby.value ) {

		document.toga_form.sortby.value = sortbyval;
		document.toga_form.sortorder.value = "asc";

	} else {

		if( document.toga_form.sortorder.value == "desc" )
			document.toga_form.sortorder.value = "asc";
		else if( document.toga_form.sortorder.value == "asc" )
			document.toga_form.sortorder.value = "desc";
	}

	document.forms['toga_form'].submit();
}

function setFilter( filtername, filterval ) {

	//document.toga_form.id.value = '';
	//document.toga_form.queue.value = '';
	//document.toga_form.state.value = '';
	//document.toga_form.user.value = '';
	var myfilterorder = document.toga_form.elements['filterorder'].value;

	if( document.toga_form.elements[filtername] ) {
		document.toga_form.elements[filtername].value = filterval;
		if( myfilterorder != '')
			myfilterorder = myfilterorder + "," + filtername;
		else
			myfilterorder = filtername;

	}
	document.toga_form.elements['filterorder'].value = myfilterorder;

	//setTimeout( "document.forms['toga_form'].submit();", 1000 );

	document.forms['toga_form'].submit();
}

//function removeFilters( filters ) {

	//var myfilter_fields = filters.split( " " );
	//for( var i=0; i<myfilter_fields.length; i++ ) {
		//removeFilter( myfilter_fields[i] );
		//setTimeout( "removeFilter( "+myfilter_fields[i]+" );", 50 );
	//}
	// delay 100 ms before submit or fields might not be set
	//document.forms['toga_form'].submit();", 100 );

	//setTimeout( "document.forms['toga_form'].submit();", 1000 );
//}

//function removeFilter( filtername ) {

//      var filterorder_fields = document.toga_form.elements['filterorder'].value.split( "," );
//      var myfilterorder = '';

//      for( var i=0; i<filterorder_fields.length; i++ ) {
//              if( filterorder_fields[i] != filtername ) {
//                      if( myfilterorder != '') {
//                              myfilterorder = myfilterorder + "," + filterorder_fields[i];
//                              //alert('myfilterorder = '+myfilterorder);
//                      } else {
//                              myfilterorder = filterorder_fields[i];
//                              //alert('emyfilterorder = '+myfilterorder);
//                      }
//              }
//      }
//      document.toga_form.elements[filtername].value = '';
//      document.toga_form.elements['filterorder'].value = myfilterorder;
//}
