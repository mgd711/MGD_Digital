function setupFilterHighlightControls(div, timeline, bandIndices, theme) {
	var table = document.createElement("table");
	var tr = table.insertRow(0);

	var td = tr.insertCell(0);
	td.innerHTML = "Filter:";

	td = tr.insertCell(1);
	td.innerHTML = "Highlight:";

	var handler = function(elmt, evt, target) {
		onKeyPress(timeline, bandIndices, table);
	};

	tr = table.insertRow(1);
	tr.style.verticalAlign = "top";

	td = tr.insertCell(0);

	var input = document.createElement("input");
	input.type = "text";
	Timeline.DOM.registerEvent(input, "keypress", handler);
	td.appendChild(input);

	for (var i = 0; i < theme.event.highlightColors.length; i++) {
		td = tr.insertCell(i + 1);

		input = document.createElement("input");
		input.type = "text";
		Timeline.DOM.registerEvent(input, "keypress", handler);
		td.appendChild(input);

		var divColor = document.createElement("div");
		divColor.style.height = ".5em";
		divColor.style.background = theme.event.highlightColors[i];
		td.appendChild(divColor);
	}

	td = tr.insertCell(tr.cells.length);
	var button = document.createElement("button");
	button.innerHTML = "Clear All";
	Timeline.DOM.registerEvent(button, "click", function() {
		clearAll(timeline, bandIndices, table);
	});
	td.appendChild(button);

	div.appendChild(table);
}

function setupFilterControls(div, timeline, bandIndices, theme) {
	var table = document.createElement("table");
	var tr = table.insertRow(0);

	var td = tr.insertCell(0);
	td.innerHTML = "Filter:";

	var handler = function(elmt, evt, target) {
		onKeyPress(timeline, bandIndices, table);
	};

	tr = table.insertRow(1);
	tr.style.verticalAlign = "top";

	td = tr.insertCell(0);

	var input = document.createElement("input");
	input.type = "text";
	Timeline.DOM.registerEvent(input, "keypress", handler);
	td.appendChild(input);
	td = tr.insertCell(tr.cells.length);
	var button = document.createElement("button");
	button.innerHTML = "Clear Filter";
	Timeline.DOM.registerEvent(button, "click", function() {
		clearAll(timeline, bandIndices, table);
	});
	td.appendChild(button);
	div.appendChild(table);
}

function setupHighlightControls(div, timeline, bandIndices, theme) {
	var table = document.createElement("table");
	var tr = table.insertRow(0);

	var td = tr.insertCell(0);
	td = tr.insertCell(0);
	td.innerHTML = "Highlight:";

	var handler = function(elmt, evt, target) {
		onKeyPress(timeline, bandIndices, table);
	};

	tr = table.insertRow(1);
	tr.style.verticalAlign = "top";

	td = tr.insertCell(0);

	for (var i = 0; i < theme.event.highlightColors.length; i++) {
		td = tr.insertCell(i + 1);

		input = document.createElement("input");
		input.type = "text";
		Timeline.DOM.registerEvent(input, "keypress", handler);
		td.appendChild(input);

		var divColor = document.createElement("div");
		divColor.style.height = "0.5em";
		divColor.style.background = theme.event.highlightColors[i];
		td.appendChild(divColor);
	}

	td = tr.insertCell(tr.cells.length);
	var button = document.createElement("button");
	button.innerHTML = "Clear All";
	Timeline.DOM.registerEvent(button, "click", function() {
		clearAll(timeline, bandIndices, table);
	});
	td.appendChild(button);
	div.appendChild(table);
}

var timerID = null;
function onKeyPress(timeline, bandIndices, table) {
	if (timerID != null) {
		window.clearTimeout(timerID);
	}
	timerID = window.setTimeout(function() {
		performFiltering(timeline, bandIndices, table);
		}, 300);
	}

	function cleanString(s) {
		return s.replace(/^\s+/, '').replace(/\s+$/, '');
	}

	function performFiltering(timeline, bandIndices, table) {
		timerID = null;

		var tr = table.rows[1];
		var text = cleanString(tr.cells[0].firstChild.value);

		var filterMatcher = null;
		if (text.length > 0) {
			var regex = new RegExp(text, "i");
			filterMatcher = function(evt) {
				return regex.test(evt.getText()) || regex.test(evt.getDescription());
			};
		}

		var regexes = [];
		var hasHighlights = false;
		for (var x = 1; x < tr.cells.length - 1; x++) {
			var input = tr.cells[x].firstChild;
			var text2 = cleanString(input.value);
			if (text2.length > 0) {
				hasHighlights = true;
				regexes.push(new RegExp(text2, "i"));
			} else {
				regexes.push(null);
			}
		}

		var highlightMatcher = hasHighlights ? function(evt) {
			var text = evt.getText();
			var description = evt.getDescription();
			for (var x = 0; x < regexes.length; x++) {
				var regex = regexes[x];
				if (regex != null && (regex.test(text) || regex.test(description))) {
					return x;
				}
			}
			return -1;
			} : null;

			for (var i = 0; i < bandIndices.length; i++) {
				var bandIndex = bandIndices[i];
				timeline.getBand(bandIndex).getEventPainter().setFilterMatcher(filterMatcher);
				timeline.getBand(bandIndex).getEventPainter().setHighlightMatcher(highlightMatcher);
			}
			timeline.paint();
		}

		function clearAll(timeline, bandIndices, table) {
			var tr = table.rows[1];
			for (var x = 0; x < tr.cells.length - 1; x++) {
				tr.cells[x].firstChild.value = "";
			}

			for (var i = 0; i < bandIndices.length; i++) {
				var bandIndex = bandIndices[i];
				timeline.getBand(bandIndex).getEventPainter().setFilterMatcher(null);
				timeline.getBand(bandIndex).getEventPainter().setHighlightMatcher(null);
			}
			timeline.paint();
		}

		var tl;
		function onLoad() {

			var eventSource = new Timeline.DefaultEventSource();
			var theme = Timeline.ClassicTheme.create();
			theme.event.label.width = 300;
			theme.event.bubble.width = 200;
			theme.event.bubble.height = 100;
			theme.ether.backgroundColors = ["#D1CECA","#E7DFD6","#E8E8F4","#D0D0E8"];
			  var bandInfos = [
				Timeline.createBandInfo({
					timeZone:       -8,
					trackHeight:    1.25,
					trackGap:       0.2,
					eventSource:    eventSource,
					width:          "85%", 
					intervalUnit:   Timeline.DateTime.DAY,
					theme:			theme,
					intervalPixels: 100
				}),
				Timeline.createBandInfo({
					timeZone:       -8,
					showEventText:  false,
					trackHeight:    0.6,
					trackGap:       0.1,
					eventSource:    eventSource,
					width:          "15%", 
					intervalUnit:   Timeline.DateTime.YEAR, 
					theme:			theme,
					intervalPixels: 365
				})
			  ];
				bandInfos[1].syncWith = 0;
				bandInfos[1].highlight = true;
				tl = Timeline.create(document.getElementById("my-timeline"), bandInfos,0);
				tl.loadXML(urlToLoad, function(xml, url) { eventSource.loadXML(xml, url); }); // urlToLoad is a global, naughty, I know....
				//tl.loadXML("http://loghound.com/test/rapidblog6/page6/files/blogRSS.php?output=timeline", function(xml, url) { eventSource.loadXML(xml, url); });
				setupFilterControls(document.getElementById("filter_controls"), tl, [0,1,2], theme);
				//setupFilterHighlightControls(document.getElementById("controls"), tl, [0,1,2], theme);
				// setupHighlightControls(document.getElementById("hightlight_controls"), tl, [0,1,2], theme);
			}
			var resizeTimerID = null;
			function onResize() {
				if (resizeTimerID == null) {
					resizeTimerID = window.setTimeout(function() {
						resizeTimerID = null;
						tl.layout();
						}, 500);
					}
				}

				window.onload=onLoad;
				//window.onresize=onResize;