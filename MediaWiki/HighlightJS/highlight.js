$(document).ready(function() {
	window.hljsGo = function() {
		$('pre code.todo').each(function(i, block) {
			$(block).removeClass('todo');
			hljs.highlightBlock(block);
		});
	};
	window.hljsGo();
});
