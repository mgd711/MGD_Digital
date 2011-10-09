
	

	//Please feel free to use parts of my code to build your own applications
	//based on the Google Spreadsheets API.  But please include a reference to this
	//site in your code.  Also, please let me know; I'm very interested in seeing
	//what creative things others do with the API!
	
	var _valuesFeed = null;
	var _colHeaders = new Array();
	var _dataRows = new Array();
	var _urlArguments = new Object();
	var defaultKey = "pSoOiPq9Ls--TtFBHV3QPdg";//"pSoOiPq9Ls--ZdLwh0ZRARA";
	var defaultSheetID = "default"; //this can be changed by sending a sheet=xxxxx url argument.
	var dataContainer="dataContainer";
	var dataTable='dataTable';
	
	var DROP_DOWN_MAX_LENGTH = 15;
	var DEBUG_FEED = false; //if you have Firebug installed and DEBUG_FEED is true, some useful debug
							//info will be printed to the console.
	
	var _hasConsole;
	function hasConsole() {
		//returns true if the console exists, false otherwise.
		if(_hasConsole != undefined) return _hasConsole;
		
		try {
			console.info("Found firebug console.");
			//if trying to access console didn't fail, then it exists.
			_hasConsole = true;
		}
		catch (er) {
			//nope, doesn't exist
			_hasConsole = false;
		}
		return _hasConsole;
	}

	function doInspect(arg) {
		//used to automatically inspect javascript Objects if Firebug is installed and enabled.
		//does nothing if Firebug does not exist.
		if(!hasConsole())return; //don't do anything if firebug isn't enabled
		console.dir(arg);
	}
	
	function userAdjustColumnWidth(){
		var result = prompt("What value should the column select boxes be truncated to?  A smaller value will make it more likely that all columns can display without requiring horizontal scrolling.", DROP_DOWN_MAX_LENGTH);
		result = parseInt(result);
		if(isNaN(result)) return;
		DROP_DOWN_MAX_LENGTH = result;
		refreshDropDownWidths();
	}
	
	function trimDropDownValue(str) {
		//returns a version of string that is no longer than DROP_DOWN_MAX_LENGTH (+3) characters long.
		if (str.length < DROP_DOWN_MAX_LENGTH) return str;
		return str.substr(0, DROP_DOWN_MAX_LENGTH) + "...";
	}
	
	function selectChangedFunction() {
		//helps us avoid a memory leak.
		selectChanged(this);
	}
	
	function dispose() {
		//cleans up DOM references in _colHeaders to avoid memory leaks.
		var i;
		for(i=0;i<_colHeaders.length;i++) {
		
			_colHeaders[i].ele.parentObj = null;
		}
	}
	
	function getColumnHeaders(root) {
		//called by the json callback to load the column headers of the table.
		//without column headers, we don't even know how to access the actual data in the feed!
		//the _colHeaders array contains objects that hold alot of useful information about each column.
		if(DEBUG_FEED) doInspect(root);
	
		var entry = root.feed.entry;
		var tempName, realName;

		var newRow = document.createElement("tr");
		newRow.className = "header-row";
		var newTD = null;
		var newDropDown = null;

		
		//append the new row to the tbody (which we explicitly create for IE)
		document.getElementById(dataTable).firstChild.appendChild(newRow);
		for(var i = 0; i<entry.length; i++) {
			tempName = entry[i].gs$cell.$t;
			//the 'realName' is the name by which we will actually extract the data from the other feed.
			realName = tempName.toLowerCase().replace(/['!@#*"()=_,\s]/g, "");
			//HACKISH: It's more complex than its worth to get a '/' in a RegExp, so we just
			//split it into an array using '/' as a delimiter and then join that array into
			//a string--accomplishing the same thing.
			realName = realName.split("/").join("");
			newTD = document.createElement("td");
			newDropDown = document.createElement("select");
			newDropDown[0] = new Option("Show All", "#all#");
			newDropDown[1] = new Option("Custom filter...", "#custom#");
			newDropDown.onchange =  selectChangedFunction;
			
			if (!ignoreColumn(realName)) { // Only add element if not in the ignore list	
				newTD.className="column"+i;		
				newTD.appendChild(document.createTextNode(tempName));
				newTD.appendChild(document.createElement("br"));
				newTD.appendChild(newDropDown);
				newRow.appendChild(newTD);
			}
			_colHeaders[i] = {index: i, prettyName: tempName, name: realName, ele:newTD, dropDown: newDropDown, values : [], filterFunc : null, filterFuncDisp:""};
			newDropDown.parentObj = _colHeaders[i];
		}
		//if the valuesFeed has somehow loaded before us, it's now our responsibility to load it up.
		if(_valuesFeed) constructTable(_valuesFeed);
	}
	
	function constructTable(root) {
		//constructTable is called by the json callback after the columns have loaded.
		//it is constructTable that adds rows for every data entry.
		
		if(_colHeaders.length == 0) {
			//we haven't seen the column headers yet (which we need to have constructed before we process us)
			//so, save the response and wait for the column headers feed to load.
			_valuesFeed = root;	
			return;
		}
		
		if(DEBUG_FEED) doInspect(root);
		
		//we'll look up the dataTable.tBody element now, to avoid looking it up for each row we add.
		var container = document.getElementById(dataTable).firstChild; 
		var entry = root.feed.entry;
		var i,j, newData,newRow,newTD, tempName; 
		var length = entry.length;
		for(i=0;i<length;i++) {
			
			newRow = document.createElement("tr");
			//to speed access, we create an object that holds information about the row like
			//whether it is hidden, the data contained in each column, and a reference to the 
			//DOM element.
			_dataRows[i] = {data: [], ele:newRow, hidden:false};
			for(j = 0;j<_colHeaders.length;j++) {

					
				tempName = "gsx$" + _colHeaders[j].name;
				newData = entry[i][tempName] ? entry[i][tempName].$t : "";
				newTD = document.createElement("td");
				if (!ignoreColumn(_colHeaders[j].name)) {
				newTD.className='column'+j;
				if (htmlColumn(_colHeaders[j].name)) // Tricky -- HTML columns are set to inner HTML but non html are appended to get proper behavior
					newTD.innerHTML=newData; // uncomment this and comment out next line to get html goodness (perhaps just leave it on by default?)  Loghound.com
				else
					newTD.appendChild(document.createTextNode(newData));
				}
				//we'll be doing case-free comparisons.  We'll save time by just performing
				//the lowercase transformation on the data once.
				//this does not create memory leaks because none of the DOM elements points
				//back to this parent object.
				_dataRows[i].data.push({ele:newTD, val: newData, compareVal: newData.toLowerCase()});
				if (!ignoreColumn(_colHeaders[j].name)) 
					newRow.appendChild(newTD);
				//valueSeen keeps track of the unique values in each column, and how many times
				//non-unique values show up.  This data will be used a little bit later when 
				//we built out the dropDowns.
				valueSeen(j,newData);
			}
			container.appendChild(newRow);
		}
		//fill up the dropDown with all of the unique values we saw
		populateDropDowns();
		//apply the alternating yellow fill
		reAlternate();
		//show the table we just constructed, and hide the loading message... we're done!
		doneLoading();
	}
	
	function valueSeen(colIndex, value) {
		//keeps track of all of the unique values seen for a given row, and the counts of 
		//non-unique values.  This data will be used by populateDropDowns.
		var newValue = value.toLowerCase();
		if(_colHeaders[colIndex].values[newValue]) {
			//if we've already got an entry, just increment the count.
			_colHeaders[colIndex].values[newValue].count++;
			return;
		}
		//this is the first time we've seen this value.  create a new count.
		//prettyValue is used to actually construct the drop-downs; prettyValue retains all
		// of the capitalization, wheras the newValue is what the value comparisons are done on.
		_colHeaders[colIndex].values[newValue] = {prettyValue: value, count: 1};
	}
	
	function populateDropDowns() {
		//populates the drop down selectors based on the valuesSeen results.
		var optObject = null;
		for(var i = 0; i <_colHeaders.length; i++) {
			if (ignoreColumn(_colHeaders[i].name))
					continue;
			for(val in _colHeaders[i].values) {
				//we trim the display value using trimDropDownValue. 
				//this ensures that the selectBoxes don't get too wide
				//(unforunately, this is the best solution to making sure the select boxes don't
				//get too big).
				optObject = new Option(trimDropDownValue(_colHeaders[i].values[val].prettyValue), val);
				optObject.prettyValue = _colHeaders[i].values[val].prettyValue;
				//it would be easier if the array of options were a normal array, with a push method...
				//but it isn't, so we have to do it this way.
				_colHeaders[i].dropDown[_colHeaders[i].dropDown.length] = optObject;
			}
		}
	}
	
	function clearDropDowns() {
		//this isn't currently used by any other function.
		//it resets all of the dropdowns.
		var i;
		for(i=0;i<_colHeaders.length;i++){
		if (ignoreColumn(_colHeaders[i].name))
					continue;
			_colHeaders[i].dropDown.innerHTML = "<option value='#all#'>Select All</option><option value='#custom#'>Custom Filter...</option>";
		}
	}
	
	function refreshDropDownWidths() {
		//uses the new MAX_DROP_DOWN_WIDTH value to refresh the text.
		//this is only called by userAdjustDropDownWidth
		var i,j;
		for(i =0; i < _colHeaders.length; i++) {
			if (ignoreColumn(_colHeaders[i].name))
					continue;
			//we start at 2 to skip over the 'Show all' and 'Custom Filter...' options.
			for(j = 2; j < _colHeaders[i].dropDown.childNodes.length;j++) {
				_colHeaders[i].dropDown[j].text = trimDropDownValue(_colHeaders[i].dropDown[j].prettyValue);
			}
		}
	}
	
	function getFilterFunc(colID) {
		//asks the user for a filter func and stores it in the given colID's filterFunc
		//uses FunctorFactory to build the functor object
		var inputVar = window.prompt("Enter a custom function to compare the value to, using 'value' (without quotes) to represent the value being considered.  Click the Help link at the top of the page for help and examples." , _colHeaders[colID].filterFuncDisp);
		if(!inputVar) return; //they didn't give us an expression to use.
	
		var factory = new FunctorFactory();
		//we ask for thre tree separetely because we want to print the tree later in this function
		//and save the output.
		var tree = factory.treeFromString(inputVar);
		var func = factory.functorFromTree(tree); 
		
		if(!func) {
			//there must have been some kind of error in their syntax.  Let them try again.
			return getFilterFunc(colID);
		}
		
		//show the text of their filter in the drop down, and change the styling so it's clear 
		//that it's a filter and not a normal value. (unforunately, we're limited from just changing
		//something subtle like font-weight, because most browsers don't support a large number of
		//css styles for options). 
		_colHeaders[colID].dropDown.options[1].text = trimDropDownValue(inputVar);
		_colHeaders[colID].dropDown.options[1].style.backgroundColor = '#d9ffd9';
		//store the filterFunc for later.
		_colHeaders[colID].filterFunc = func;
		//filterFuncDisp helps us fill in the prompt box next time it's called.
		//instead of putting in exactly what they gave us, we return what the computer understood.
		//this should help diagnose problems in their syntax.
		_colHeaders[colID].filterFuncDisp = factory.printTree(tree);
		//the func is returned as a formality--the important part is that it's stored in the _colHeaders.
		return func;
	}
	
	function selectChanged(obj) {
		//the obj given is the dropDown object that was changed.
	
		//if they just selected 'Custom Filter...' then we need to allow them to 
		//specify the filter and store the new func in the _colHeaders
		//remember that every dropDown has a parentObj pointer to the _colHeader that it
		//is associated with.
		if(obj.selectedIndex == 1) {
			// they're switching into custom filter mode for this column.
			getFilterFunc(obj.parentObj.index);
			obj.className = 'special';
		} else {
			//reset the text of the select box to 'Custom Filter...'
			//and set the styling back, too.
			obj.options[1].text = 'Custom Filter...';	
			obj.options[1].style.backgroundColor = '';
			obj.className = '';
		}
		
		//it might seem like we only need to add the filtering for this one column that 
		//was just changed.  But in reality, this would require a very complicated operation
		//that looked at all /hidden/ rows, too (which are now skipped), so it's actually faster
		//to reset and reapply all filters every time any dropDown is changed.
		
		resetFiltering();
		for(var i = 0; i< _colHeaders.length; i++) {
			if (ignoreColumn(_colHeaders[i].name))
					continue;
			if(_colHeaders[i].dropDown.selectedIndex == 0) continue; // 'Show All'
			if(_colHeaders[i].dropDown.selectedIndex == 1) {
				//only filter on the function if it actually exists--otherwise, treat it like 'Show All'
				if(_colHeaders[i].filterFunc) filterBasedOn(i, _colHeaders[i].filterFunc); 
				continue;
			}
			//one of the other options was picked.  just filter on the value they picked.
			filterBasedOn(i,_colHeaders[i].dropDown.options[_colHeaders[i].dropDown.selectedIndex].value);
		}
		//we most likely need to realternate after filtering.
		reAlternate();
		
	}
	
	function reAlternate() {
		//applies an alternating fill to the data rows.
		var even = true;
		//avoid accessing an unnecessary property every loop.
		var length = _dataRows.length;
		//we'll make a local pointer to _dataRows so we don't have to 
		//ramble up the scope chain every time through the loop.
		var dataRows = _dataRows;
		for(var i = 0; i < length; i++) {
			if(dataRows[i].hidden) continue;
			row="row"+i;
			dataRows[i].ele.className = even ? "even "+row : "odd "+row;
			even = !even;
		}
	}
	
	function defaultSpreadsheet(isDefault){
		//are we loading the default spreadsheet?  if so, we'll show a message so users know why 
		//they didn't get the spreadsheet they might have been expecting.
		document.getElementById("default-message").style.display = isDefault ? '' : 'none';
	}
	
	function hideRow(rowID) {
		//marks a row as hidden and hides it with css.
		//setting an easy property speeds up filtering, which skips hidden rows.
		_dataRows[rowID].hidden = true;
		_dataRows[rowID].ele.style.display = "none";
	}
	
	function showRow(rowID) {
		_dataRows[rowID].hidden = false;
		_dataRows[rowID].ele.style.display = "";
	}
	
	function resetFiltering() {
		//simply shows all rows
		//avoid accessing a property every loop:
		var length = _dataRows.length;
		for(var i=0;i < length;i++) {
			showRow(i);
		}
	}
	
	function filterBasedOn(colID,val) {
		//hides any (visible!) rows that do NOT have val in their colID.
		//val can be either a simple value, or a function that returns TRUE if the argument should be 
		//left visible, or FALSE if it should be hidden.
		if(typeof val != "function") {
			//we've been giving a non-functor comparator.
			//build a trivial one that returns true only if the value == val.
			var inputVal = val;
			inputVal = inputVal.toLowerCase();
			val = function(arg) {return arg == inputVal;};
		}
		var length = _dataRows.length;
		//avoid having to walk all the way up the scope chain each loop
		var dataRows = _dataRows;
		for(var i=0;i < length;i++) {
			if(dataRows[i].hidden) continue;
			if(!val(dataRows[i].data[colID].compareVal)) hideRow(i);
		}
	}
	
	function loadSheet(key){
		//loads up the sheet with the associated key.
		//if we aren't provided a key, we'll use the defaultSpreadsheet, and turn on
		//the flag alerting the user that we're using the default spreadsheet.
		if(!key) {
			key = defaultKey;
			defaultSpreadsheet(true);
		} else {
			defaultSpreadsheet(false);
		}
		
		//reset the application--this allows us to load multiple sheets sequentially wihtout reloading.
		reset();
		//turn on the 'Loading...' flag.  This will be turned off by constructTable.
		startLoading();
		
		//We can't use XmlHttpRequest objects because of cross-site-scripting security.
		//unfortunately, this means that we have to resort to creating script objects and inserting
		//them into the DOM. Fortunately, the feed API will allow us to return the data as a JSON
		//object wrapped in a callback function.
		
		//The larger problem is how to detect when a specified document is not published.  When the 
		//document is not published, we will get back a script that reads, in its entirety,
		//'You do not have view access to the spreadsheet. Make sure you are properly authenticated.'
		//This is not valid javascript, clearly.
		//We can't use XmlHttpRequests, as pointed out earlier.
		//We can't register an onload function to help us, because if the response is the invalid 
		//script we get if the document isn't published, Firefox (and maybe others) just won't 
		//fire the onload event for the script object.
		//We can't step into the DOM and read the contents of the script because that isn't available
		//in Firefox (and maybe others) for dynamically-generated script objects. 
		//So, we're left with the remaining, less-than-ideal solution, which is just to leave the 
		//loading flag up indefinitely if the document isn't published.
		//Using a timeout is 'cheating'.  Also, loading for large spreadsheets or over slow connections
		//can take a long time, so relying just on timeouts to figure out if the sheet is unpublished
		//is not ideal.
		
		//About the feed URLs: we use the undocumented 'default' sheet ID.  Most sheets 
		//have a sheet called 'od6' as their first sheet.  However, if that sheet is later 
		//deleted and replaced by a /new/ sheet, that sheet will not have the same id.
		//Using default will return the first sheet no matter what.
		
		var container = document.getElementById("scriptContainer");
		var tempNode = document.createElement("script");
		tempNode.src = "http://spreadsheets.google.com/feeds/cells/" + key + "/" + defaultSheetID + "/public/values?max-row=1&alt=json-in-script&callback=getColumnHeaders";
		container.appendChild(tempNode);
		tempNode = document.createElement("script");
		tempNode.src = 'http://spreadsheets.google.com/feeds/list/' + key + '/' + defaultSheetID + '/public/values?alt=json-in-script&callback=constructTable';
		container.appendChild(tempNode);
	}
	
	function reset() {
		//resets the application back to how it was when the whole application loaded the first time.
		//this allows us to load multiple sheets in sequence without refreshing the page.
		document.getElementById(dataContainer).innerHTML = "<table id='"+dataTable+"' class='gtable'><tbody><!--all data will go here --></tbody></table>";
		_valuesFeed = null;
		_colHeaders = new Array();
		_dataRows = new Array();
	}
	
	function startLoading() {
		//merely shows the loading flag and hides the data area.
		document.getElementById("loading-message").style.display = "";
		document.getElementById(dataContainer).style.display = "none";
	}
	
	function doneLoading() {
		//merely hides the loading flag and shows the data area.  
		document.getElementById("loading-message").style.display = "none";
		document.getElementById(dataContainer).style.display = "";
	}
	
	function showOpenSheet() {
		//This is the UI to present to the user who clicks the 'Load Different Sheet' link.
		var result = window.prompt("Enter the URL containing the key, or just the key. Remember that the document must be published to be usable by this application.");
		if(!result) return;
		result = extractKey(result);
		if(!result) {alert("No valid key could be extracted"); return;}
		loadSheet(result);
	}
	
	function extractKey(input) {
		//given any kind of input, tries its hardest to extract a valid trix ID
		//these IDs generally come after the 'key=' paramater.  
		var result = input;
		input = input.split("?");
		if(input.length > 1) {
			//we put in the LAST item for the cases like what the bookmarklet gives us,
			//where it simply escapes the URL of the sheet and gives us the whole big thing
			//as an argument. (which is unescaped before being given to us).
			//example: http://THIS-APP/index.html?url=http://SPREADSHEETS/ccc?key=xxxxxxx;
			//in this example, we want the stuff after the last ?.
			input = input[input.length - 1];
		} else {
			//there were no ?'s.
			input = input[0];
		}
		if(input) {
			//there are some url parameters. let's search for key.
			input = input.split("&");
			for(var i = 0; i < input.length; i++){
				input[i]= input[i].split("=");
				if(input[i][0] == "key"){
					//found it!
					result = input[i][1];
					break;
				}
			}
		}
		
		//the variable input now contains our best guess at the key.
		
		//valid keys must be 23 characters long.  If it's  not 23 characters, then 
		//it's definitely not right, and we'll just return nothing.
		if(result.length == 23) return result;
		return "";
	}
	
	function extractArguments(input){
		//constructs an associative array of the different url arguments.
		var result = new Array();
		input = input.split("?");
		if(input.length > 1){
			input = input[1];
		} else {
			return result;
		}
		input = input.split("&");
		for(var i=0;i<input.length;i++){
			input[i] = input[i].split("=");
			if(input[i].length < 2) continue;
			input[i][0] = input[i][0].toLowerCase();
			result[input[i][0]] = unescape(input[i][1]);
		}
		return result;
	}
	
	function startUp() {
		//we're going to look for a key to start with.
		//we unescape the input because the bookmarklet just appends the whole escaped
		//URL of the spreadsheet to the end. We've got to unescape it for extractKey to be
		//able to make sense of it.
		_urlArguments = extractArguments(window.location.href);
		var key ='';
		if(_urlArguments['key']) {
			key = _urlArguments['key'];
		} else if(_urlArguments['url']) {
			key = extractKey(_urlArguments['url']);
		}
		if(_urlArguments['sheet']) defaultSheetID = _urlArguments['sheet'];
		loadSheet(key);
	}
	
	/*************************************************
	 * The remainder of the code is devoted to parsing the expressions input by a user and
	 * creating a functor object out of them.  The two remaining objects (FunctorFactory and Tokenizer)
	 * take up a bulk of the code but perform only a small task, not directly related 
	 * to most of the functionality of the application.
	 *************************************************/
	
	function FunctorFactory() {
		/* FunctorFactory works by:
		 * Using a tokenizer to extract all tokens in the given string
		 * Converting the infix notation to prefix notation
		 * Building a parse tree out of the prefix notation array
		 * Converting the parse tree into nested functor objects
		 * 
		 * NOTES:
		 * The natural-language features are implemented mainly on the tokenizer
		 * level (the Tokenizer() object), for example 'is not' is reported as a
		 * != operator by the tokenizer. 
		 */
		var _precedence = new Array();
		_precedence["||"] = 1;
		_precedence["&&"] = 2;
		_precedence["=="] = 3;
		_precedence["!="] = 3;
		_precedence["in-func"] = 3;
		_precedence["<="] = 4;
		_precedence["<"] = 4;
		_precedence["=>"] = 4;
		_precedence[">"] = 4;
		_precedence["!"] = 5;
		
		this.allowWildCards = true; //if true, then wildcards are allowed in string comparisons.
									//searching for wildcards gives worse performance.
		
		var _tokenizer = new Tokenizer("");
		
		//this function lives here to avoid a longer scope chain.
		var _wildcardCompare = function(str, pattern){
			//returns true if str matches pattern, where pattern may contain the following special characters:
			// * - matches 0 or more characters
			// ? - matches exactly one character
			
			if(!str || !pattern) return false;
			
			//implemented recursively for * calls.
			var a = 0; //counter in str
			var b = 0; //counter in pattern
			var nextChar = "";
			var peekChar = "";
			var allowSpecials = true;//'*' and '?' will only be interpreted specially if allowSpecials is true.
			while(a < str.length && b < pattern.length) {
				
				nextChar = pattern.charAt(b);
				allowSpecials = true;
				
				if(nextChar == "\\") {
					//it might be an escaped char!
					peekChar = pattern.charAt(b+1);
					if(peekChar && (peekChar == "?" || peekChar == '*')) {
						//it is an escaped special character!
						allowSpecials = false;
						nextChar = peekChar;
						b++;
					}
				}
				if(allowSpecials && nextChar == '*') {
					//first, we need to consume all of the *'s that are immediatley after this.
					//the reason is that any run of *'s (e.g. ****) has the same meaning as a single
					//*.  Also, a run of *** would confuse this algorithm otherwise.
					peekChar = pattern.charAt(b + 1);
					while(peekChar && peekChar == '*' ) {
						b++;
						peekChar = pattern.charAt(b+1);
					}
					
					//this is the special case.
					//we'll be delegating to recursive calls for the rest of the time.
					
					var nextCharInString = pattern.charAt(b + 1);
					var lastBreakPoint = str.indexOf(nextCharInString,b);
					//if there's nothing more to match (if the * is at the end of the string)
					//then it matches automatically!
					if(!nextCharInString) return true; 
					pattern = pattern.substr(b + 1);
					//if lastBreakPoint is -1, then we didn't find it, should return false.
					while(lastBreakPoint >= 0 && lastBreakPoint < str.length) {
						 
						if(_wildcardCompare(str.substr(lastBreakPoint), pattern)) {
							return true;
						}
						lastBreakPoint++; //next time, we don't want to return this match.
						lastBreakPoint = str.indexOf(nextCharInString, lastBreakPoint);
					}
					//if we get to here, none of the breaks worked. return false.
					return false;
					
				} else if(allowSpecials && nextChar == '?') {
					//skip the comparison--everything matches!
					a++;
					b++;
				} else {
					//normal character
					if(nextChar != str.charAt(a)) return false;
					a++;
					b++;
				}
			}
			//it's possible that all that's left in pattern is a '*' (or a run of '*''s)
			//and that we've matched everything up until now.
			
			//first, consume the whole run of '*'s
			nextChar = pattern.charAt(b);
			peekChar = pattern.charAt(b + 1);
			while(peekChar && nextChar == '*' && peekChar == "*") {
				b++;
				nextChar = peekChar;
				peekChar = pattern.charAt(b+1);
			}
			
			if(pattern.charAt(b) == "*" && b == pattern.length - 1) return true;
			if(a != str.length || b != pattern.length) return false;
			return true;
		}
		
		//this function lives here to avoid a longer scope chain.
		var _compare = function(a,b){
			//the compare function is a wrapper that decides to use
			//wildcardCompare or just return the simple result a==b.
			
			//NOTE: if doing a wildcardCompare, b is ALWAYS the pattern to match.
			//this must be ensured by the calling function.
			if(typeof a == 'string' && typeof b =='string') {
				return _wildcardCompare(a,b);
			}
			return a == b;
		}
		
		this.functorFromString = function(str){
			var tree = this.treeFromString(str);
			if(!tree) return null;
			return this.functorFromTree(tree);
		}
		
		this.treeFromString = function(str){
			try {
				var tokens = this.getAllTokens(str);
				if(!tokens) {
					return null;
				}
				//correct syntax that is too simple:
				tokens = this.inFixMissingArgumentCheck(tokens);
				
				var preFix = this.inFixToPreFix(tokens);
				
				//now we have the statement in prefix form.
				var result = this.getParseTree(preFix);
				if(preFix.length > 0) {
					//there were too many tokens!
					alert("The expression had more items than was expected based on the number of operators and functions. Extra items were ignored. Quick tip: try the same expression, but with every string contained in quotes.")
				}
				return result;
			} catch(er){
				alert("There was an error parsing your statement.  Please check your syntax. \n" + er);
				return null;
			}
		}
		
		this.inFixMissingArgumentCheck = function(list){
			//takes a list of infix tokens, and returns a list of infix tokens
			//(likely the same list).  Specifically, if there are no tokens of type argument,
			//we need to (some-what intelligently) modify it so it does include a value statement.
			
			//this whole fuction is essentially just one big tweak.
			
			//note that because arrays are passed by reference, returning a reference to the list
			//is just a formality.
			
			var length = list.length;
			if(length < 1) return list;
			for(var i=0; i<length;i++){
				if(list[i].type == 'argument') return list;
			}
			//if we get to here, then there is no argument token in the list.
			//depending on what we see at the front of the list, we need to mend it in various ways.
			
			//NOTE: if there isn't an argument token, then this statement must be one of a 
			//very limited number of simple types of expressions, so making changes like this
			//without examining the structure of the rest of the tree is safe.
			if(list[0].val == "!") {
				//the statement is of the type:
				//'not 3 or 4 or 5', and shold be interpreted as 'value is not 3 or 4 or 5'
				//note that 'is not' is different from 'not', so the front token is bogus.
				list.shift();
				list.unshift({type:"operator", val:"!=", negative:true});
				list.unshift({type:'argument',val:null});
				return list;
			}
			if(String(list[0].val).toLowerCase() == 'doesnotcontain' || String(list[0].val).toLowerCase() == 'contains') {
				//just insert 'value' in the first position.
				list.unshift({type:'argument', val:null});
				return list;
			}
			if(String(list[0].val).toLowerCase() == 'length') {
				var tempToken = list.shift();
				list.unshift({type:'argument', val:null});
				list.unshift(tempToken);
				return list;
			}
			if(list[0].type == 'boolean' || list[0].type == 'operator') {
				//just inser tvalue in front
				list.unshift({type:'argument', val:null});
				return list;
			}
			//if we get to here, we need to append a 'value is' to the front.
			list.unshift({type:"operator", val:"=="});
			list.unshift({type:'argument',val:null});
			return list;
				
		}
		
		this.evalFunctorFromTree = function(tree){
			//the magical eval function in javascript can interpret the statement for us.
			//this type of functor "cheats" and returns a functor that just runs the 
			//eval statement. Turns out, it's not much faster than creating our own, 'real' functor
			//also, to properly support functions, we'd have to explicitly add them within the
			//closure anyway, which takes time (and is not currently performed by this function).
			//we built the expression tree to parse the statement anyway; we might as well
			//build our own functor.
			
			//WARNING: expressions involving the use of functions will not work currently with 
			//functors created by this method.
			
			//therefore, this function is not used by default.
		
			//returns an eval functor from a tree (first, it prints the tree).
			var statement = this.printTree(tree);
			return function(arg) {
				var value = arg;
				return eval(statement);
			};
		}
		
		this.functorFromTree = function(tree){
			//recursively builds a functor object that returns true when the expression
			//represented by the tree evaluates to true, and false otherwise.
			if(!tree) return function(arg) {return false};
			var funcA, funcB;
			var aHasArg = false;
			var bHasArg = false;
			if(tree.type == "number" || tree.type == "string") {
				return function(arg) {return tree.val};
			} else if(tree.type == "argument") {
				return function(arg) {return arg};
			} else if(tree.type == "function") {
				var funcs = new Array();
				for(var i = 0;i<tree.numArgs;i++){
					funcs[i] = this.functorFromTree(tree[i]);	
				}
				var result = this.functorFromFunction(tree.val, funcs);
				if(!result){
					return function(arg) {return false};
				}
				return result;
			} else if(tree.type == "operator") {
				funcA = this.functorFromTree(tree[0]);
				funcB = this.functorFromTree(tree[1]);
				switch(tree.val){
					case "==":
						if(this.allowWildCards) {
							aHasArg = this.treeContainsArgument(tree[0]);
							bHasArg = this.treeContainsArgument(tree[1]);
							if(aHasArg && bHasArg) {
								//the argument can never be a pattern.
								return function(arg) {return funcA(arg) == funcB(arg); };
							} else if(bHasArg) {
								//b can't be the pattern, becuase it has the argument.
								return function(arg) {return _compare( funcB(arg), funcA(arg))};
							} else {
								return function(arg) {return _compare(funcA(arg), funcB(arg))};
							}
						}
						return function(arg) {return funcA(arg) == funcB(arg); };
						break;
					case "!=":
						if(this.allowWildCards) {
							aHasArg = this.treeContainsArgument(tree[0]);
							bHasArg = this.treeContainsArgument(tree[1]);
							if(aHasArg && bHasArg) {
								//the argument can never be a pattern.
								return function(arg) {return funcA(arg) != funcB(arg); };
							} else if(bHasArg) {
								//b can't be the pattern, becuase it has the argument.
								return function(arg) {return ! _compare( funcB(arg), funcA(arg))};
							} else {
								return function(arg) {return ! _compare(funcA(arg), funcB(arg))};
							}
						}
						return function(arg) {return funcA(arg) != funcB(arg);};
						break;
					case "<":
						return function(arg) {return funcA(arg) < funcB(arg);};
						break;
					case ">":
						return function(arg) {return funcA(arg) > funcB(arg);};
						break;
					case "<=":
						return function(arg) {return funcA(arg) <= funcB(arg);};
						break;
					case ">=":
						return function(arg) {return funcA(arg) >= funcB(arg);};
						break;
					default:
						alert("Unknown operation type in expression: "+ tree.val);
						return function(arg) {return false};
				}
			} else if(tree.type == "boolean") {
				funcA = this.functorFromTree(tree[0]);
				
				switch(tree.val){
					case "&&":
						funcB = this.functorFromTree(tree[1]);
						return function(arg) {return funcA(arg) && funcB(arg); };
						break;
					case "||":
						funcB = this.functorFromTree(tree[1]);	
						return function(arg) {return funcA(arg) || funcB(arg);};
						break;
					case "!":
						return function(arg) {return !funcA(arg);};
						break;
					default:
						alert("Unknown boolean type in expression: "+ tree.val);
						return function(arg) {return false};
				}
			}
			alert("Unknown node type in tree: " + tree.type);
			return function(arg){return false};
			
		}
		
		this.functorFromFunction = function(functionType, funcs){
			//returns a functor of the given functionType, applied to funcs,
			//where funcs are the necessary number of functor objects.
			//funcs is an array of function objects as argumentts.
			//only supports a limited number of functionTypes!
			functionType = functionType.toLowerCase();
			switch(functionType) {
				case "parseint":
					return function(arg) {return parseInt(funcs[0](arg))};
					break;
				case "length":
					return function(arg) {return funcs[0](arg).length;};
					break;
				case "date":
					return function(arg) {return new Date(funcs[0](arg));};
					break;
				case "contains":
					//in order to support wildcards in contains, contains is implemented as a simple
					//wildcardCompare, with needle being couched in "*" on either side, e.g. if 
					//needle is 'dog', contains will be implemented as a wildcardCompare based on
					//the pattern '*dog*'. (The legacy way used indexOf).
					if(this.allowWildCards){ 
						return function(arg) {return _wildcardCompare(String(funcs[0](arg)), "*" + String(funcs[1](arg)) + "*")};
					} else {
						return function(arg) {return String(funcs[0](arg)).indexOf(funcs[1](arg)) != -1;};
					}
					break;
				case 'doesnotcontain':
					//exactly the same as the contains function, just reversed.
					if(this.allowWildCards) {
						return function(arg) {return ! _wildcardCompare(String(funcs[0](arg)), "*" + String(funcs[1](arg)) + "*")};
					} else {
						return function(arg) {return String(funcs[0](arg)).indexOf(funcs[1](arg)) == -1;};
					}
					break;
				case "price":
					return function(arg) {return parseFloat(funcs[0](arg).split("$").join(""));};
					break;
				case "is_between":
					return function(arg) {return (funcs[1](arg) <= funcs[0](arg) && funcs[0](arg) <= funcs[2](arg));};
					break;
				default:
					return function(arg) {return false;};
			}
		}
		
		this.treeContainsArgument = function(tree, force){
			//performs a DFS looking for a type:argument token in the tree.
			//it stores its results as it rises up the tree, so repeated
			//calls should return quickly.
			
			//if force is true, it recomputes.
			
			if(!force && tree.hasArgument != undefined) return tree.hasArgument;
			if(tree.type == 'argument') return true;
			var result = false;
			var i = 0;
			//as soon as we get a single true, we can stop. 
			while(!result && tree[i]){
				result = this.treeContainsArgument(tree[i], force);
				i++;
			}
			tree.hasArgument = result;
			return result;
		}
		
		this.verifyArgumentExists = function(tree, defaultType) {
			//this function takes a subtree and returns a subtree just like it
			//except that it ensures that an argument node exists somewhere within it.
			
			//it uses a token of defaultType to join the expression if it has to perform surgery.
			//if defaultType is null, it will use an equality operator.
			
			if(!tree || this.treeContainsArgument(tree)) return tree; //it's already there, or it's null.
			
			if(tree.type =='operator' && ! tree[1]) {
				//we're going to swap tree[0] to tree[1] and 
				//stick a value in tree[0].
				tree[1] = tree[0];
				tree[0] = {type:'argument', val:null};
				//hasArgument must now be false (because we called treeContainsArgument above, but that is now incorrect!
				//this is the only place where we change a subtree after first computing hasArgument.
				tree.hasArgument = true;
				return tree;
			}
			
			if(!defaultType) {
				//construct the default one.
				defaultType = {type:'operator', val:'=='};
				defaultType[0] = {type:'argument', val:null};
			} else {
				defaultType = this.cloneTree(defaultType);
			}
			
			//it's not there.  we've got to add it.
			defaultType[1] = tree;
			
			return defaultType;
		}
		
		this.cloneTree = function(tree) {
			//clones all tokens in the given tree, returns a copy.
			//uses _tokenizer.cloneToken to actually clone the body of each token.
			var result = _tokenizer.cloneToken(tree);
			var i = 0;
			while(tree[i]) {
				result[i] = this.cloneTree(tree[i]);
				i++;
			}
			return result;
		}
		
		this.getParseTree = function(prefixTokens, _defaultType){
			//builds a parse tree out of tokens
			//the tokens should be arranged in prefix notation.
			//the returned tree's nodes will actually be the tokens provided, repurposed.
			
			//_defaultType is optional. (and used as the default to pass when calling verifyArgumentExists.
			//... which is useful for long or-expresions (like 'value is 3 or 4 or 5')
			
			//clobbers the array passed to it.
			var mainToken = prefixTokens.shift();
			if(!mainToken) {
				return null;
			}
			if(mainToken.type == "number" || mainToken.type == "string" || mainToken.type == "argument") {
				return mainToken;
			} else if(mainToken.type == "boolean" && mainToken.val == "!") {
				mainToken[0] = this.getParseTree(prefixTokens);
				if(!mainToken[0]) {
					throw "Syntax error. Object '" + mainToken.val + "' expected one argument, got none.";
					return mainToken;
				}
				return mainToken;
			} else if(mainToken.type == "function"){
				var funcs = new Array();
				for(var i=0;i<mainToken.numArgs;i++){
					mainToken[i] = this.getParseTree(prefixTokens);
					if(!mainToken[i]) {
						throw "Syntax error. Object '" + mainToken.val + "' expected " + mainToken.numArgs + " argument(s), got " + i;
						return mainToken;
					}
				}
			} else if(mainToken.type == 'boolean') {
				//boolean values should have an argument in both their left and right sides.
				mainToken[0]= this.verifyArgumentExists(this.getParseTree(prefixTokens), _defaultType);
				if(!mainToken[0]) {
					throw "Syntax error. Object '" + mainToken.val + "' expected two arguments, got none.";
					return mainToken;
				}
				//in verifyArgument, we want to default to the operator on the LHS.
				mainToken[1] = this.verifyArgumentExists(this.getParseTree(prefixTokens, mainToken[0]), mainToken[0]);
				if(!mainToken[1]) {
					throw "Syntax error. Object '" + mainToken.val + "' expected two arguments, got only one.";
					return mainToken;
				}
			} else {
				//it needs a left and a right.
				mainToken[0]= this.getParseTree(prefixTokens);
				if(!mainToken[0]) {
					throw "Syntax error. Object '" + mainToken.val + "' expected two arguments, got none.";
					return mainToken;
				}
				mainToken[1] = this.getParseTree(prefixTokens);
				//instead of throwing an error if we don't have a second argument, 
				//we'll try to mend it ourselves first.  if we STILL don't have a 
				//second argument, then we'll throw an error.
				mainToken = this.verifyArgumentExists(mainToken);
				
				if(!mainToken[1]) {
					throw "Syntax error. Object '" + mainToken.val + "' expected two arguments, got only one.";
					return mainToken;
				}
				
			}
			//manual tweak: if someone said something like value is not blue or red, then 
			//the statement really should be 'value != blue *AND* value != red', not or.
			//this also takes into account or-string expressions, where the string is like 'value is not 3 or 4 or 5'
			if(mainToken.val == "||" && mainToken[0].negative && (mainToken[1].negative || (mainToken[1].val == '&&' && mainToken[1][0].negative && mainToken[1][1].negative))){
				mainToken.val = "&&";
			}
			return mainToken;
		}
		
		this.getAllTokens = function(str){
			//returns a list of all tokens, taken from the str argument.
			//uses tokenizer to extract the tokens.
			
			_tokenizer.setString(str);
			var result = new Array();
			var nextToken = null;
			var EOF = false;
			while(!EOF){
				nextToken = _tokenizer.getNextToken();
				if(nextToken.type == "EOF") {
					EOF = true;
				} else if(nextToken.type == "error"){
					throw "Improperly formatted input string: " + nextToken.val;
					return null;
				} else {
					result.push(nextToken);
				}
			}
			return result;
		}
		
		this.printTree = function(tree){
			//returns a tree of  the given tree in infix notation.
			//useful for debugging operator binding--the infix that is output is how
			//the application "sees" the expression.
			if(!tree) return "SYNTAX-ERROR";
			if(tree.type == "number") {
				return tree.val;
			}
			if(tree.type == "argument") {
				return "value";
			}
			if(tree.type == "string") {
				return "\"" + tree.val + "\"";
			}
			if(tree.type == "function"){
				var result = new Array();
				//using tree.length won't work because that would return all properties
				//of the objects, and we only want number of argument children.
				//luckily, tree.numArgs has the info we need.
				for(var i=0;i<tree.numArgs;i++) {
					result.push(this.printTree(tree[i]));
				}
				return tree.val + "(" + result.join(" , ") + ")";
			}
			if(tree.type == "operator" || tree.type == "boolean"){
				if(tree.val == "!") {
					//one argument only.
					return "!" + this.printTree(tree[0]);
				} 
				//otherwise, two arguments.
				return "(" + this.printTree(tree[0]) + " " + tree.val + " " + this.printTree(tree[1]) + ")";
			}
			return "";
		}
		
		this.inFixToPreFix = function(arr){
			//given an array of tokens, returns an array of tokens
			//representing the same expression, but in prefix notation.
			//clobbers the argument array.
			
			//this conversion uses a bit of black magic, and might not perform as intended in some
			//instances.
			arr.reverse(); //operates in place.
			var result = new Array();
			var stack = new Array();
			var doAgain = false;
			for (var i = 0; i<arr.length; i++){
				if(arr[i].type == "argument" || arr[i].type == "number" || arr[i].type == "string") {
					result.push(arr[i]);
				} else if(arr[i].type == "right-paren") {
					stack.push(arr[i]);
				} else if(arr[i].type == "operator" || arr[i].type == "boolean" || arr[i].type == "function"){
					do {
						doAgain = false;
						if(stack.length == 0) {
							stack.push(arr[i]);
						} else if(stack[stack.length -1].type == "right-paren") {
							stack.push(arr[i]);
						
						//functions are treated as operators that have a higher priority than everything else.
						} else if((stack[stack.length - 1].type == "operator" || stack[stack.length - 1].type == "boolean") && (_precedence[stack[stack.length - 1].val] < _precedence[arr[i].val]) || 
								   (arr[i].type=="function" && !arr[i].inFix) ||
								   (arr[i].type == "function" && arr[i].inFix && _precedence[stack[stack.length - 1].val] < _precedence["in-func"])){
							stack.push(arr[i]);
						} else {
							result.push(stack.pop());
							doAgain = true;
						}
					} while(doAgain);
				} else if(arr[i].type == "left-paren"){
					var keepPopping = true;
					var tempItem = null;
					while(keepPopping && stack.length > 0){
						tempItem = stack.pop();
						if(tempItem.type == "right-paren"){
							keepPopping = false;
							break;
						}
						result.push(tempItem);
					}
				}
			}
			while(stack.length > 0){
				result.push(stack.pop());
			}
			return result.reverse();
		}
		//end of FunctorFactory
	}
	
	function Tokenizer(input){
			
		/*
		 * The point of the tokenizer is to return, piece by piece, what the 
		 * given string *means*, syntactically.  For example, 'is not' *means*
		 * '!=', so the tokenizer returns that.  Any 'fluff' in the syntax is
		 * not returned--only things that would otherwise change the meaning of 
		 * the expression.
		 */
	
		var _input = input;
		var _currentLoc = 0;
		var _stack = new Array();
		var _stringLiterals = new Array();
		
		this.lowerCaseStrings = true; //this flag can be set to false to return string tokens exactly as written.
		this.allowIllegalStrings = true; //if true, then strings not enclosed in quotes will be allowed.  Note that this can cause confusing syntax errors.
		
		this.addStringLiteral = function(argStr, argToken){
			_stringLiterals.push({str:argStr.toLowerCase(), token:argToken, firstChar:argStr.substr(0,1).toLowerCase(), len: argStr.length});
		}
		
		//the natural-language functionality is almost all accomplished by stringLiterals.
		//tweaks are later peformed on syntax in the FunctorFactory.
		
		//stringLiterals are un-quoted strings that are valid syntactically.  addStringLiteral accepts
		//both the string and what kind of token it represents.
		//stringLiterals should be added in order from more to less specific, to prevent mis-matches.
		//for example, 'value' should be insertted before 'val', otherwise the val literal 
		//would be matched before value, and the remaining 'ue' would be misinterpreted.
		//As an additional check against that occurance, string literals must have non-letter, non-number room
		//on either side: 'isle' would not match 'is' because there is no space after the term.
		this.addStringLiteral("and", {type:"boolean", val: "&&"});
		this.addStringLiteral("or", {type:"boolean", val: "||"});
		this.addStringLiteral("not", {type:"boolean", val: "!"});
		this.addStringLiteral("true", {type:"number", val: true});
		this.addStringLiteral("false", {type:"number", val: false});
		this.addStringLiteral("value", {type:"argument", val: null});
		this.addStringLiteral("val", {type:"argument", val: null});
		this.addStringLiteral("is between", {type:"function", val:"is_between", numArgs:3, inFix: true});
		this.addStringLiteral("is_between", {type:"function", val:"is_between", numArgs:3, inFix: true});
		this.addStringLiteral("is greater than or equal to", {type:"operator", val:">="});
		this.addStringLiteral("is greater than", {type:"operator", val:">"});
		this.addStringLiteral("is less than or equal to", {type:"operator", val:"<="});
		this.addStringLiteral("is less than", {type:"operator", val:"<"});
		this.addStringLiteral("greater than or equal to", {type:"operator", val:">="});
		this.addStringLiteral("greater than", {type:"operator", val:">"});
		this.addStringLiteral("less than or equal to", {type:"operator", val:"<="});
		this.addStringLiteral("less than", {type:"operator", val:"<"});
		this.addStringLiteral("is not", {type:"operator", val:"!=", negative:true});
		this.addStringLiteral("is", {type:"operator", val:"=="});
		this.addStringLiteral("parseInt", {type:"function", val:"parseint", numArgs:1});
		this.addStringLiteral("count", {type:"function", val:"length", numArgs:1});
		this.addStringLiteral("length", {type:"function", val:"length", numArgs:1});
		this.addStringLiteral("price", {type:"function", val:"price", numArgs:1});
		this.addStringLiteral("date", {type:"function", val:"date", numArgs:1});
		this.addStringLiteral("contains", {type:"function", val:"contains", numArgs:2, inFix:true});
		this.addStringLiteral("does not contain", {type:'function', val:'doesNotContain', numArgs:2, inFix:true, negative:true});
		this.addStringLiteral("doesnotcontain", {type:'function', val:'doesNotContain', numArgs:2, inFix:true, negative:true});
		
		this.setString = function(argInput) {
			//tokenizers can be created to operate on one string, or can be changed to
			//operate on a different string.
			_input = argInput;
			_currentLoc =0;
			_stack = new Array();
		}
		
		this.cloneToken = function(token) {
			//returns a simple clone of the given object, cloning only the properties we care about.
			//this is important because token objects are used by FunctorFactory to build up the
			//expression tree, so it's important to hand out only unique tokens.
			var result = new Object();
			result.val = token.val;
			result.type = token.type;
			result.numArgs = token.numArgs;
			result.inFix = token.inFix;
			result.negative = token.negative // if true, then 'or' expressions with this operation on both sides will be converted to 'and'
			return result;
		}
		
		this.getNextToken = function() {
			//getNextToken is the general purpose, outward-facing method.
			//it will return the next token in the string, or a token of type 'EOF'
			//if all of the input is exhausted.
			//this function figures out what type the next token is, and then delegates
			//to a function specifically designed to extract that kind of token.
			
			//it's possible to push a seen token back on the stack.  So, if we've got anything
			//on the stack, return that instead of figuring out what the next token is. 
			if(_stack.length > 0) return _stack.pop();
			this.eatWhiteSpace();
			while(this.lookAhead("of ".length).toLowerCase() == "of ") {
				//skip over 'of' strings as if they weren't there.
				//('of' strings are not string literals because they do not *mean* anything)
				_currentLoc = _currentLoc + "of ".length;
				this.eatWhiteSpace();
			}
			if(!this.checkPosition()) return {type:"EOF", val:null};
			var currentChar = _input.charAt(_currentLoc);
			if(currentChar == "#") {
				//# is a stand-in for 'value'
				_currentLoc++;
				return {type:"argument", val:null};
			} 
			if(currentChar == "(") {
				_currentLoc++;
				return {type:"left-paren", val:"("};
			}
			if(currentChar == ")") {
				_currentLoc++;
				return {type:"right-paren", val: ")"};
			}
			if(currentChar == "\"" || currentChar == "\'") return this.getString();
			if("0123456789+-".indexOf(currentChar) != -1) return this.getNumber();
			if("<!=>".indexOf(currentChar) != -1) return this.getOperator();
			if("&|!".indexOf(currentChar) != -1) return this.getBoolean();
			var stringLiteralResult = this.getStringLiteral();
			//if the next token isn't any of the stringLiterals, then it must be illegal.
			if(!stringLiteralResult) {
				//what's next is not a string literal.  Should we interpret it as a string,
				//even though it's not in quotes?
				if(this.allowIllegalStrings){
					return this.getIllegalString();
				}
				return {type:"error", val:"A string not enclosed in quotes was found."};
			}
			return stringLiteralResult;
		}
		
		this.lookAhead = function(length){
			//returns the string that starts at the currentLoc and extends length characters.
			return _input.substr(_currentLoc, length);
		}
		
		this.getStringLiteral = function() {
			//this function tries to return any of the valid string-literal tokens.
			//if it fails, it will return null.
			//stringLiterals should be registered with the tokenizer by calling 
			//addStringLiteral().
			var currentChar = _input.charAt(_currentLoc).toLowerCase();
			for(var i = 0;i < _stringLiterals.length; i++){
				if(currentChar != _stringLiterals[i].firstChar) continue;
				//the first char matches--do a look-ahead.
				if(this.lookAhead(_stringLiterals[i].len).toLowerCase() == _stringLiterals[i].str) {
					//we may have found it!  now we just need to make sure that there is whitespace after
					//the string literal.
					if(!this.isNonString(_input.substr(_currentLoc + _stringLiterals[i].len, 1))) continue;
					_currentLoc = _currentLoc + _stringLiterals[i].len;
					return this.cloneToken(_stringLiterals[i].token);
				}
			}
			return null;
			
		}
		
		this.getString = function() {
			//gets the string starting at _currentLoc
			//the string either starts with a ' or a ".  We figure out which one, then
			//read in until we see the next one.
			//NOTE: does not recognize escaped quotes as valid parts of the string.
			var buffer = "";
			//we look for terminate char because the string could open with ' or ".
			var terminateChar = _input.charAt(_currentLoc);
			if(terminateChar != '"' && terminateChar != "'") return {type:"error", val: "Un-opened string found."};
			_currentLoc++; //we're at the double quote (or single quote) right now
			while(this.checkPosition() && _input.charAt(_currentLoc) != terminateChar){
				buffer += _input.charAt(_currentLoc);
				_currentLoc++;
			}
			if(_input.charAt(_currentLoc) != terminateChar) {
				//if we dropped off the end up input, then we found an unterminated string.
				return {type:"error", val: "Unterimnated string found."};
			}
			_currentLoc++;
			if(this.lowerCaseStrings){
				buffer = buffer.toLowerCase();
			}
			return {type:"string", val: buffer};
		}
		
		this.getIllegalString = function() {
			//returns a string object that consists of everything up to (but not including)
			//the first whitespace.
			var buffer = "";
			var currentChar = _input.charAt(_currentLoc);
			var targetString = " \n\t";
			while(this.checkPosition() && targetString.indexOf(currentChar) == -1) {
				buffer += currentChar;
				_currentLoc++;
				currentChar = _input.charAt(_currentLoc);
			}
			if(this.lowerCaseStrings){
				buffer = buffer.toLowerCase();
			}
			return {type:"string", val: buffer};
		}
		
		this.getNumber = function(){
			var buffer = "";
			while(this.checkPosition() && "0123456789.+-".indexOf(_input.charAt(_currentLoc)) != -1){
				buffer += _input.charAt(_currentLoc);
				_currentLoc++;
			}
			return {type:"number", val: parseFloat(buffer)};
		}
		
		this.getOperator = function() {
			var buffer = "";
			var isNegative  = false;
			while(this.checkPosition() && "<!=>".indexOf(_input.charAt(_currentLoc)) != -1){
				buffer += _input.charAt(_currentLoc);
				_currentLoc++;
			}
			//using '=' when they mean '==' is a common error, and '=' has no meaning in this context,
			//so we can replace it for them automatically.
			if(buffer == "=") buffer = "==";
			if(buffer == "!") {
				//this is actually a boolean operator!
				return {type:"boolean", val: buffer};
			}
			if(buffer == '!=') isNegative = true;
			return {type:"operator", val: buffer, negative:isNegative};
		}
		
		this.getBoolean = function() {
			var buffer = "";
			while(this.checkPosition() && "&|!".indexOf(_input.charAt(_currentLoc)) != -1) {
				buffer += _input.charAt(_currentLoc);
				_currentLoc++;
			}
			//replace common values with what they meant.
			if(buffer == "&") buffer = "&&";
			if(buffer == "|") buffer = "||";
			return {type:"boolean", val: buffer};
		}
		
		this.checkPosition = function() {
			//returns true if we haven't gone past the end of the string, false otherwise. 
			return !(_currentLoc >= _input.length);
		}
		
		this.isNonString = function(str) {
			//returns true if given string is null or BEGINS WITH anything that could be interpreted
			//as the start of a token.
			if(!str) return true;
			str = str.charAt(0);
			if(" \n\t,;=<>!&|()".indexOf(str) == -1) return false;
			return true;
		}
		
		this.eatWhiteSpace = function() {
			//automatically consumes all white space until the next non-white space is 
			//seen. Note that commas are treated as white-space, because outside of strings,
			//they don't *mean* anything in this expression syntax--they are just helpful 
			//to visually separate arguments in functions. 
		
			if(!this.checkPosition()) return;
			var currentChar = _input.charAt(_currentLoc);
			var targetString = " \n\t,;"; // look for newlines, tabs, spaces, commas, and semicolons.
			while(this.checkPosition() && targetString.indexOf(currentChar) != -1){
				_currentLoc++;
				currentChar = _input.charAt(_currentLoc);
			}
		}
		
		this.pushToken = function(token){
			//it's possible to push seen tokens back on the stack.
			//every call to getNextToken() will first return tokens in 
			//the stack, only fetching a fresh token when the stack is exhausted.
			_stack.push(token);
		}
		//end of Tokenizer
	}
	
	//-->
	function ignoreColumn(str) {
		//returns TRUE if the column should be ignored
		if (_ignoreColumns[str])
			return true;
		return false;
	}

	function htmlColumn(str) {
		if (_htmlColumns[str])
			return true;
		return false;
	
	}