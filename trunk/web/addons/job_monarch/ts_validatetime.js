function validateTime( str_target, str_datetime ) {

        var time = document.cal.time.value.split( ':' );
        var invalid = 0;
	var dt_datetime = (str_datetime == null || str_datetime =="" ?  new Date() : str2dt(str_datetime));

        if( time.length <= 1 && invalid == 0 ) {

                document.cal.time.value = dt2tmstr( dt_datetime );
                alert( 'Invalid time specified (needs to be HH:MM:SS), reset to current time.' );
                invalid = 1;

        } else if( time.length == 2 ) {

                if( time[1] != '' ) {

                        document.cal.time.value = document.cal.time.value + ':00';

                } else if( invalid == 0 ) {

                        document.cal.time.value = dt2tmstr( dt_datetime );
                        alert( 'Invalid time specified (needs to be HH:MM:SS), reset to current time.' );
                        invalid = 1;
                }

        } else if( time.length == 3 ) {

                newtime = '';

                for( var i=0; i<time.length; i++) {

                        time_field = time[i];
			if( i == 0 )
				var sep = '';
			else
				var sep = ':';
				

                        if( time_field.length == 0 && invalid == 0 ) {

                                document.cal.time.value = dt2tmstr( dt_datetime );
                                alert( 'Invalid time specified (needs to be HH:MM:SS), reset to current time.' );
                                invalid = 1;

                        } else if( time_field.length == 1 ) {

                                newtime = newtime + sep + '0' + time_field;
                        } else {
                                newtime = newtime + sep + time_field;
                        }

                        for( var l=0; l<time_field.length; l++ ) {
                                var mydigit = time_field.charAt(l)

                                if(( mydigit < "0" || mydigit > "9" ) && invalid == 0 ) {

                                        document.cal.time.value = dt2tmstr( dt_datetime );
                                        alert( 'Invalid time specified (needs to be HH:MM:SS), reset to current time.' );
                                        invalid = 1;

                                }
                        }
                }

                if( invalid == 0 ) {

                        document.cal.time.value = newtime;
                }

        }

        if( invalid == 0 ) {

		target = str_target.split('.');
		frm = target[1];
		elm = target[2];
                var mf = window.opener.document.forms[frm].elements[elm].value.split( ' ' );

		if( mf[0] != '' )
			window.opener.document.forms[frm].elements[elm].value=mf[0]+' '+document.cal.time.value;
		else
			window.opener.document.forms[frm].elements[elm].value=dt2dtstr( new Date() ) +document.cal.time.value;
        }
}

// datetime parsing and formatting routimes. modify them if you wish other datetime format
function str2dt (str_datetime) {
        var re_date = /^(\d+)\-(\d+)\-(\d+)\s+(\d+)\:(\d+)\:(\d+)$/;
        if (!re_date.exec(str_datetime))
                return alert("Invalid Datetime format: "+ str_datetime);
        return (new Date (RegExp.$3, RegExp.$2-1, RegExp.$1, RegExp.$4, RegExp.$5, RegExp.$6));
}
function dt2dtstr (dt_datetime) {
        return (new String (
                        dt_datetime.getDate()+"-"+(dt_datetime.getMonth()+1)+"-"+dt_datetime.getFullYear()+" "));
}
function dt2tmstr (dt_datetime) {

        if( dt_datetime.getHours() < 10 )
                var hours = '0' + dt_datetime.getHours();
        else
                var hours = dt_datetime.getHours();

        if( dt_datetime.getMinutes() < 10 )
                var minutes = '0' + dt_datetime.getMinutes();
        else
                var minutes = dt_datetime.getMinutes();

        if( dt_datetime.getSeconds() < 10 )
                var seconds = '0' + dt_datetime.getSeconds();
        else
                var seconds = dt_datetime.getSeconds();

        return (new String (
                        hours+":"+minutes+":"+seconds));
}
