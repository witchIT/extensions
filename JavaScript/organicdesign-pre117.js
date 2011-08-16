/*
 * Various patches and JS additions needed by wikis in the OD wikia
 */

// Cookie set/get functions from W3C
function setCookie(c_name,value,exdays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name) {
var i,x,y,ARRcookies=document.cookie.split(";");
for (i=0;i<ARRcookies.length;i++) {
		x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		x=x.replace(/^\s+|\s+$/g,"");
		if (x==c_name) return unescape(y);
	}
}

// OD functions to run after page load
odOnloadHook = function() {

	// Make vanadium validation not work for RecordAdmin searches
	$('#ra-find').attr('onClick','Vanadium={}');

	$('#pt-userpage').css('text-decoration','underline');

	// Improve RA record name inputs
	// - normal record-id is always hidden (css)
	// - if a record-name row exists, then it should be visible and mandatory only if record-id also exists
	if ($('#record-name')) {
		if ($('#ra-record').val()) {
			$('#record-name input').removeClass(':required');
			$('#record-name').hide();
		} else {
			$('#record-name input').addClass(':required').val($('#ra-title').val());
			var submit = $('form.recordadmin').attr('onSubmit');
			if( submit ) submit = submit + ';'; else submit = '';
			$('form.recordadmin').attr('onSubmit', submit + '$("#ra-title").val($("#record-name input").val() );');
		}
	}

};

addOnloadHook( odOnloadHook );
