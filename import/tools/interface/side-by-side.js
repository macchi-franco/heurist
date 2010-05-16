function htmlEscape(val) {
	return ((val || "") + "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function formatDetails(bib, prefix, indent) {
	if (! prefix) prefix = "";
	if (! indent) indent = 0;

	var html = "";
	var bdr = top.HEURIST.bibDetailRequirements.valuesByReftypeID[bib.reftype];

	for (var bdt_id in bib.values) {
		var name = /* prefix + " " + */ bdr[bdt_id]? bdr[bdt_id][0]  :  top.HEURIST.bibDetailTypes.valuesByBibDetailTypeID[bdt_id][1];

		if (top.HEURIST.bibDetailTypes.valuesByBibDetailTypeID[bdt_id][2] != "resource") {
			html += "<tr><td style='padding-left: " + indent + "em;'><i>" + htmlEscape(name) + "</i></td><td>";
 			for (var i=0; i < bib.values[bdt_id].length; ++i)
				html += htmlEscape(bib.values[bdt_id][i]) + "<br>";
			html += "</td></tr>";
		}
		else {
 			for (var i=0; i < bib.values[bdt_id].length; ++i) {
				var innerBibID = parseInt(bib.values[bdt_id][i]);
				var innerBib = bibs[innerBibID];
				if (! innerBib) continue;
				var reftype = top.HEURIST.reftypes.names[innerBib.reftype];

				if (innerBib.reftype == 75) {
					// author editor is treated specially
					html += "<tr><td style='padding-left: " + indent + "em;'><i>" + prefix + " Author/Editor</i></td><td>" + htmlEscape(innerBib.title) + "</td></tr>";
				}
				else html += formatDetails(innerBib, reftype, indent+1);
			}
		}
	}

	return html;
}


function gotoNext() {
	// rearrange the alt bibids list
	var firstBibID = altBibIDs.shift();
	altBibIDs.push(firstBibID);
	fillInColumn(1, altBibIDs[0]);
}


function fillInColumn(colNum, bibID) {
	var bibCell = document.getElementById("bib" + colNum);
	var titleCell = document.getElementById("title" + colNum);
	var detailsCell = document.getElementById("details" + colNum);
	var notesCell = document.getElementById("notes" + colNum);

	var bib = bibs[bibID];

	if (! colNum) bibCell.innerHTML = "<b>Imported " + htmlEscape(top.HEURIST.reftypes.names[bib.reftype]).toLowerCase() + " record</b>";
	else {
		bibCell.innerHTML = "<b>Existing record #" + bibID + "</b>";
		if (altBibIDs.length > 0) {
			bibCell.innerHTML += " [<a href=# onclick='gotoNext(); return false;'>view next</a>]";
		}
	}

	titleCell.innerHTML = "<b>" + htmlEscape(bib.title) + "</b>";

	notesCell.innerHTML = htmlEscape(bib.notes).replace(/\n/g, "<br>");

	detailsCell.innerHTML = "<table cellpadding=3><tbody>" + formatDetails(bib) + "</tbody></table>";
}
