/* 
This function runs after a filter on the table and sets the position numbers
to the positions in the filtered collection, rather than the outright ranking position,
with the outright ranking position preserved (or recovered !)
*/
function doPostFilter()
{
var table = document.getElementById("rankingTable");
if (table == null)
	return;

var pos = 0;
var lastpos = 0;
var lastrealranking = "";

for (var i = 0, row; row = table.rows[i]; i++) 
	{
	// Grab the ranking position of the row and whether its hidden		
	var cell = row.cells[0];
	var realranking = "";

	if (row.style.display != "none")
		{
		var matches = cell.innerHTML.match(/\([0-9]+\)/);
		if (matches != null)
			realranking = matches[0];
		else
			{
			matches = cell.innerHTML.match(/[0-9]+/);
			if (matches == null)
				continue;
			realranking = "(" + matches[0] + ")";
			}

		++pos;
		if (realranking == lastrealranking)
			cell.innerHTML = lastpos + " " + realranking;
		else
			{
			cell.innerHTML = pos + " " + realranking;
			lastpos = pos;
			lastrealranking = realranking;
			}
		}	
  }  
}

function cleanPosData()
{
var table = document.getElementById("rankingTable");
if (table == null)
	return;
for (var i = 2, row; row = table.rows[i]; i++) 
	{
	// Grab the ranking position of the row and whether its hidden		
	var cell = row.cells[0];
	var matches = cell.innerHTML.match(/\([0-9]+\)/);
	if (matches != null)
		{
		realranking = matches[0].replace("(","");
		realranking = realranking.replace(")","");
		}
	else
		{
		matches = cell.innerHTML.match(/[0-9]+/);
		if (matches == null)
			realranking = i-1;
		else
			realranking = matches[0];
		}
	cell.innerHTML = realranking;	
	}
}