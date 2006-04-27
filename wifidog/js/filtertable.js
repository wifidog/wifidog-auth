/*
 * Filterable Library for filtering tables
 *
 * @author Copyright (C) 2005 Lokkju, lokkju@lokkju.com
 * @version 1.0
 * @modified 04/26/2006
 * 
 * Other licenses are available, please contact the above address for more details.
 *
 * This program is free software; you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License as published by the Free 
 * Software Foundation; either version 2 of the License, or (at your option) 
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT 
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for 
 * more details.
 * 
 * You should have received a copy of the GNU General Public License along 
 * with this program; if not, write to the Free Software Foundation, Inc., 59 
 * Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
 
addEvent(window, "load", filterables_init);

var FILTER_COLUMN_INDEX;

function filterables_init() {
    // Find all tables with class filterable and make them filterable
    if (!document.getElementsByTagName) return;
    tbls = document.getElementsByTagName("table");
    for (ti=0;ti<tbls.length;ti++) {
        thisTbl = tbls[ti];
        if (((' '+thisTbl.className+' ').indexOf("filterable") != -1) && (thisTbl.id)) {
			var inputFilter = document.getElementById(thisTbl.id+'_filter');
			if (inputFilter && inputFilter.tagName == 'INPUT') {
            		tf_makeFilterable(thisTbl,inputFilter);
			} else {
				alert('you have not defined a input box for filtering, for table id \'' + thisTbl.id + '\'.  Please add an input box to the page, with an id of \'' + thisTbl.id + '_filter\'.');
			}
        }
    }
}

function tf_makeFilterable(table,filter) {
	if (table.rows && table.rows.length > 0) {
		var firstRow = table.rows[0];
	}
	if (!firstRow) return;
	// We have a first row: assume it's the header, and make its have filters
	filter.tableid = table.id;
	addEvent(filter,'keypress',tf_filterTable);
}

function tf_getInnerText(el) {
	if (typeof el == "string") { return el; }
	if (typeof el == "undefined") { return el; }
	if (el.innerText) return el.innerText;
	var str = "";	
	var cs = el.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += tf_getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
	}
	return str;
}

function tf_filterTable(e) {
	var targ;
	if (!e) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
	targ = targ.parentNode;
	// tf_filter_col(newRows,strFilter,col);
	setTimeout("tf_filterTableTO('"+targ.id+"');", 10);
}

function tf_filterTableTO(targ) {
	targ = document.getElementById(targ);
	// Make the filter a regex
	table = document.getElementById(targ.tableid);
	strFilter = targ.value;
	strFilter = '^.*' + strFilter + '.*$';
	if (table.rows.length <= 1) return;
	var newRows = new Array();
	var j = 0;
	for (i=0;i<table.rows.length;i++) { 
		if ((' '+table.rows[i].className+' ').indexOf("nofilter") == -1) {
			newRows[j] = table.rows[i];
			j++;
		}
	}
	tf_filter_all(newRows,strFilter);
}

function tf_filter_all(rows,fltr) {
	var re = new RegExp(fltr,"igm");
	var x,y,z = 0;
	for (x=0;x<rows.length;x++) {
		var hide = true;
		for (y=0;y<rows[x].cells.length;y++) {
			var strCell = "";
			strCell = tf_getInnerText(rows[x].cells[y])
			if (strCell.match(re)) {
				hide = false;
				break;
			}
		}
		if (hide) {
			rows[x].style.display = 'none';
		} else {
			rows[x].style.display = '';
		}
	}
}

function getParent(el, pTag) {
	if (el == null) {
		return null;
	} else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTag.toLowerCase()) {
		return el;
	} else {
		return getParent(el.parentNode, pTag);
	}
}

function addEvent(el, ev, fn, useCapture) {
  if (el.addEventListener){
		el.addEventListener(ev, fn, useCapture);
		return true;
	} else if (el.attachEvent){
		var e = el.attachEvent("on"+ev, fn);
		return e;
	} else {
		alert("Event handler could not be added");
	}
} 
