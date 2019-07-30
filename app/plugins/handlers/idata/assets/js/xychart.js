/**
 * @package     hubzero-irods
 * @file        app/plugins/handlers/irods/assets/js/xychart.js
 * @copyright   Copyright 2016 HUBzero Foundation, LLC.
 * @license     http://opensource.org/licenses/MIT MIT
 *
 * GABBS chart viewer - data limited
 *
 */

var xyChart =
{
	// Chart types
	AREA          : 'area',       // Constants sent to Highcharts. Also correspond to ID of html element
	COLUMN        : 'column',     //  "
	LINE          : 'line',       //  "
	SCATTER       : 'scatter',    //  "
	SPLINE        : 'spline',     //  "

	// Chart options
	INVERTED      : 'inverted',   // ID of html element
	SWITCHROWCCOL : 'swrowcol',   //  "
	NOHEADER      : 'noheader',   //  "
	DELIMITER     : 'delimiter',  //  "
	DECIMAL       : 'decimal',    //  "
	STARTCOL      : 'startcol',   //  "
	ENDCOL        : 'endcol',     //  "
	//COLSELECT     : 'colselect',  //  Class for START/ENDCOL
	COLPICK       : 'colpick',    // ID of div to hold col checkboxes

	// Properites
	panelAdjusting: false,
	filename      : '',
	ext           : '',
	chartObj      : [],
	delimiter     : ",",
	avail         : [],           // List of all possible headers
	headers       : [],           // Headers to use for axis titles
	//papa          : null,         // Data parsed by papaparse library

	init: function(pFilename,pExt)
	{
		this.filename             = pFilename;
		this.ext                  = pExt;
		this.data                 = document.getElementById('csv').innerHTML;
		this.chartType            = this.SCATTER;
		this.inverted             = false;
		this.switchRowsAndColumns = false;
		this.firstRowAsNames      = false;
		//this.papa                 = Papa.parse(document.getElementById('csv').innerHTML);

		// Expand display to fill window
		// From http://stackoverflow.com/questions/2872967/how-to-grow-an-iframe-depending-on-the-size-of-its-contents
		var useHeight = parent.document.documentElement.clientHeight; //IE 6+ in 'standards compliant mode'
		var useWidth  = parent.document.documentElement.clientWidth;
		if (typeof (parent.window.innerWidth) == 'number') {
			useHeight = parent.window.innerHeight; //Non-IE
			useWidth  = parent.window.innerWidth;
		}
		else if (parent.document.body && (parent.document.body.clientWidth || parent.document.body.clientHeight)) {
			useHeight = parent.document.body.clientHeight; //IE 4 compatible
			useWidth = parent.document.body.clientWidth;
		}
		$('#chart-viewer').height(useHeight - 120);
		//$('#chart-viewer').width( useWidth  - 110);
		$.fancybox.update() // NOTE Assumes we're inside a fancybox

		var that = this;

		// Make panel resizeable, redraw chart when it changes
		$("#panel").resizable({handles: 'e'});
		$('#panel').resize(function() {that.panelAdjustment();});

		// When window resizes, redraw chart
		// NOTE: This triggers the inital draw of chart.
		$(window).resize(function()   {that.panelAdjustment();});

		// Set tab ckbox based on file extentsion
		if (this.ext.toUpperCase() == 'TSV') {
			$('#'+this.DELIMITER).prop("checked", true);
		}

		// Set initial values for delimiter, headers, and data column menus
		this.getDelimiter();
		this.setColumnNames();

		// React to chart type selection
		$('#'+this.AREA   ).click(function() {that.redrawChart(that.AREA   );});
		$('#'+this.COLUMN ).click(function() {that.redrawChart(that.COLUMN );});
		$('#'+this.LINE   ).click(function() {that.redrawChart(that.LINE   );});
		$('#'+this.SCATTER).click(function() {that.redrawChart(that.SCATTER);});
		$('#'+this.SPLINE ).click(function() {that.redrawChart(that.SPLINE );});

		// React to checkboxes

		$('#'+this.INVERTED).click(function() {that.redrawChart();});
		$('#'+this.DECIMAL ).click(function() {that.redrawChart();});
		//$('.colpickckbox'  ).click(function() {that.redrawChart();});

		$('#'+this.SWITCHROWCCOL).click(function() {
			//$('.'+this.COLSELECT).prop("disabled",$('#'+this.SWITCHROWCCOL).prop("checked")); // Enable/disabel data col selection
			that.redrawChart();
		});

		$('#'+this.NOHEADER).click(function() {
			that.setColumnNames();
			that.redrawChart();
		});

		$('#'+this.DELIMITER).click(function() {
			that.getDelimiter();
			that.setColumnNames();
			that.redrawChart();
		});

		openClose('colpicker');
	},

	getDelimiter: function()
	{
		// Field delimiter
		if ($('#'+this.DELIMITER).prop("checked")) {
			this.delimiter  = '\t';
		}
		else {
			this.delimiter  = ',';
		}
	},

	setColumnNames: function()
	{
		// Set up manual column selection controls

		var that   = this;
		var first  = $('#first').text();

		this.avail = first.split(this.delimiter);

		$('#colpicklist').empty();

		// Toggle and redraw buttons
		$('#toggle').click(function(event) { that.toggle(); });

		$('#redraw').button();
		$('#redraw').click(function(event) { that.redrawChart(); });

		// Checkboxes for alls columns
		for (var use='',i=0; i<this.avail.length; i++) {
			if ($('#'+this.NOHEADER).prop("checked")) {
				use = 'Column '+(i+1);
			}
			else {
				use = this.avail[i];
			}
			$('#colpicklist').append('<label class="labelSpan"><input class="colpickckbox" id="'+i+'" name="'+use+'" type="checkbox" checked="checked"> '+use+' </label><br>');
		}
	},

	setHeaders: function()
	{
		var that     = this;
		this.headers = [];

		$.each($('.colpickckbox'), function(i,val) {
			if (val.checked) {
				if ($('#'+that.NOHEADER).prop("checked")) {
					that.headers.push('Column '+(i+1));
				}
				else {
					that.headers.push(that.avail[i]);
				}
			}
		});
	},

	toggle: function()
	{
		// Check/uncheck all column ckboxes
		$('.colpickckbox').attr('checked', ($('#toggle').attr('checked') == 'checked'));
	},

	adjustData: function(columns)
	{
		var orig = columns.slice();
		var ckbs = $('.colpickckbox');
		var limit = Math.min(orig.length,ckbs.length);


		// Empty out array and put back those user wants to keep.
		columns.splice(0,columns.length);

		for (var i=0; i<limit; i++) {
			if (ckbs[i].checked) {
				columns.push(orig[i]);
			}
		}
	},

	redrawChart: function(newChartType)
	{
		var chartError   = false;
		var decimal      = '.'; // Default value
		var xAxisTitle   = '';
		var yAxisTitle   = '';
		var startDataCol = 0;    // Default value
		var endDataCol   = 9999; // Default value

		this.setHeaders();

		if (newChartType) {
			this.chartType = newChartType;
		}

		// Decimal point
		if (!$('#'+this.DECIMAL).prop("checked")) {
			decimal = ',';
		}

		// Find data column numbers
		var startText = $('#'+this.STARTCOL).val();
		var endText   = $('#'+this.ENDCOL   ).val();
		if ($.isNumeric(startText) && $.isNumeric(endText)) {
			startDataCol = parseInt(startText,10);
			endDataCol   = parseInt(endText  ,10);
		}
		else {
			startDataCol = 0;
			endDataCol   = this.headers.length-1;
		}

		// Axes titles
		for (var i=startDataCol; (i<=endDataCol); i++) {
			if (i == startDataCol) {
				xAxisTitle = this.headers[i];
			}
			else if (i == endDataCol) {
				yAxisTitle += this.headers[i];
			}
			else { // Middle
				yAxisTitle += this.headers[i]+', ';
			}
		}
		if ($('#'+this.SWITCHROWCCOL).prop("checked")) {
			yAxisTitle = '(data)';
		}

		// Create chart
		try {
			// Create chart in given div
			$('#chart').highcharts({
				chart    :	{	type                 : this.chartType
								,inverted            : $('#'+this.INVERTED).prop("checked")			}
				,title   :	{	text                 : this.filename								}
				,subtitle:	{	text                 : '"' + yAxisTitle +' by ' + xAxisTitle + '"'  }
				//,rows    :  this.papa
				,data    :	{	csv                  : this.data
								,switchRowsAndColumns: $('#'+this.SWITCHROWCCOL).prop("checked")
								,firstRowAsNames     : !$('#'+this.NOHEADER).prop("checked")
								,decimalPoint        : decimal
								,itemDelimiter       : this.delimiter
								//,startColumn         : startDataCol
								//,endColumn           : endDataCol
								,parsed              : this.adjustData								}
				,xAxis   :	[	{title  : {text      : '<b>'+ xAxisTitle+'</b>'						} } ]
				,yAxis   :	[	{title  : {text      : '<b>'+ yAxisTitle+'</b>'						} } ]
				,legend  :	 	{enabled             : false										}
				,credits :	 	{enabled             : false										}
			});
		}
		catch (e) {
			chartError = true;
		}

		// Set info

		var status = $('#status').text();

		if (status == 'ERROR' || chartError) {
			this.setInfo('<b>ERROR: Unable to display chart.<br>Adjust options and select data columns.</b>');

			if (isClosed('colpicker')) {
				openClose('colpicker');
			}
		}
		else {
			// Retain handle on chart object
			this.chartObj = $('#chart').highcharts();

			this.setInfo('');

			// Limited?
			if (status == 'LIMITED') {
				this.addInfo('<br><b>WARNING: File exceeds size limit.<br>PARTIAL DATA SHOWN.</b><br><br>');
			}

			// Line count
			this.addInfo('Line count');

			if (!$('#'+this.NOHEADER).prop("checked")) {
				this.addInfo(', including header');
			}

			this.addInfo(' = '+$('#lcount').text());


			// Instructions
			this.addInfo('<br><i>Hover over chart elements for more info.</i>');
		}

		// SAVE: example of using chart obj: curSeries = this.chartObj.series[i];
	},

	panelAdjustment: function()
	{
		// Adjust width of chart
		$('#chart').width( $("#chart-viewer").width()  - $("#panel").width() - 12);

		// Redraw chart
		if (!this.panelAdjusting) {
			var that = this;
			this.panelAdjusting = true;
			setTimeout( function() {
					that.panelAdjusting = false;
					that.redrawChart();
				}
				,1000
			);
		}
	},

	// Clear and set info window
	setInfo: function(html)
	{
		$('#info').html(html);
	},

	// Append to info window
	addInfo: function(html)
	{
		$('#info').append(html);
	},
};

// Expand or collapse a panel section

var HIDE = '- Hide -';
var SHOW = '- Show -';

function isClosed(section)
{
	return ($('.'+section+'-sign').html() == this.SHOW);
}

function openClose(section)
{
	$('.'+section+'-area').slideToggle(100);

	if (this.isClosed(section)) {
		$('.'+section+'-sign').html(this.HIDE);
	}
	else {
		$('.'+section+'-sign').html(this.SHOW);
	}
}
