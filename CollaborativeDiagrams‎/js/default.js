

function cd_init() {
	console.log('Init');
	$('#cd-make-box').bind("click", cd_make_rect );
}

function cd_make_rect() {
	var s = new Shape();
	console.log(s);
	$("#cd-workspace").append(s.to_html());	
}

function Shape( p ) {
	
	this.to_string = function() { return 'a shape'; }
	this.to_html = function() { return '<div>Shape</div>'; }
	return this;
}