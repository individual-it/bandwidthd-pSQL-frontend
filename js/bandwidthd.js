$(document).ready(function() {
	$.tinysort.defaults.attr='class';						
});

var aAsc = [];
function sortTable(nr) {
	console.log('sortTable',nr);
	aAsc[nr] = aAsc[nr]=='asc'?'desc':'asc';
	$('#xtable>tbody>tr').tsort('td:eq('+nr+')',{order:aAsc[nr]});
}