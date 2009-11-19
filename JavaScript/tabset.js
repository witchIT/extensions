// make passed div into a tabset
// - copied from MW 1.16's prefs.js but passing the div instead of getting by id
// - function names changed to not conflict with prefs ones
function tabsetOnload() {
	var tabsets = $('div.tabset');
	for (var i = 0; i < tabsets.length; i++) {
		tabsets[i].id = 'preferences';
		tabset(tabsets[i],i);
	}
}

function tabset( prefform, tabid ) {
	if (!prefform || !document.createElement) {
		return;
	}
	if (prefform.nodeName.toLowerCase() == 'a') {
		return; // Occasional IE problem
	}
	prefform.className = prefform.className + 'jsprefs';
	var sections = [];
	var children = prefform.childNodes;
	var seci = 0;
	for (var i = 0; i < children.length; i++) {
		if (children[i].nodeName.toLowerCase() == 'fieldset') {
			children[i].id = 'prefsection-' + seci;
			children[i].className = 'prefsection';
			if (is_opera) {
				children[i].className = 'prefsection operaprefsection';
			}
			var legends = children[i].getElementsByTagName('legend');
			sections[seci] = {};
			if (legends[0]) legends[0].className = 'mainLegend';
			if (legends[0] && legends[0].firstChild.nodeValue) {
				sections[seci].text = legends[0].firstChild.nodeValue;
			} else {
				sections[seci].text = '# ' + seci;
			}
			sections[seci].secid = children[i].id;
			seci++;
			if (sections.length != 1) {
				children[i].style.display = 'none';
			} else {
				var selectedid = children[i].id;
			}
		}
	}
	var toc = document.createElement('ul');
	toc.id = 'preftoc';
	toc.selectedid = selectedid;
	for (i = 0; i < sections.length; i++) {
		var li = document.createElement('li');
		if (i === 0) {
			li.className = 'selected';
		}
		var a = document.createElement('a');
		a.href = '#' + sections[i].secid;
		a.onmousedown = a.onclick = tabsetUncoverSection;
		a.appendChild(document.createTextNode(sections[i].text));
		a.secid = sections[i].secid;
		li.appendChild(a);
		toc.appendChild(li);
	}
	prefform.parentNode.insertBefore(toc, prefform.parentNode.childNodes[0]);
}

function tabsetUncoverSection() {
	var oldsecid = this.parentNode.parentNode.selectedid;
	var newsec = document.getElementById(this.secid);
	if (oldsecid != this.secid) {
		var ul = document.getElementById('preftoc');
		document.getElementById(oldsecid).style.display = 'none';
		newsec.style.display = 'block';
		ul.selectedid = this.secid;
		var lis = ul.getElementsByTagName('li');
		for (var i = 0; i< lis.length; i++) {
			lis[i].className = '';
		}
		this.parentNode.className = 'selected';
	}
	return false;
}

addOnloadHook(tabsetOnload);
