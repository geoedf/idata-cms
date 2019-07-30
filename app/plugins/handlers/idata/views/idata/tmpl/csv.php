<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2019 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-idata
 * @author    Rob Campbell <rcampbel@purdue.edu>
 * @copyright Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

defined('_HZEXEC_') or die();   // No direct access
$this->css('idata');   // Add the idata stylesheet
$this->js('highcharts' . DS . 'v4' . DS . 'highcharts.js');
$this->js('highcharts' . DS . 'v4' . DS . 'modules' . DS . 'data.js');
$this->js('highcharts' . DS . 'v4' . DS . 'highcharts-more.js');

$this->css('jquery.ui.css','system'); // Resizable divs

// Chart viewer support
$this->css('xychart.css');
$this->js( 'xychart.js' );

?>

<div id="chart-viewer" class="width-container">
	<script type="text/javascript">
		$(document).ready(function () {
			xyChart.init(<?php echo '"'.basename($this->file->getAbsolutePath()).'","'.$this->ext.'"'; ?>);
		});
	</script>
	<div id="panel">
		<div id="inner">
			<div class="dataLbl copycat" onclick="openClose('info');">Info<span class="info-sign sign">- Hide -</span></div>
			<div id="info" class="sectionDiv info-area">Working...</div>
			<div id="basepros">
				<!-- Chart &amp; Data Options -->
				<div class="dataLbl copycat" onclick="openClose('basepros');">Options<span class="basepros-sign sign">- Hide -</span></div>
				<div class="sectionDiv basepros-area">
					<table><tr><td>
						<label class="labelSpan"><input id="area"      name="baseproradio" type="radio"    value=""        > Area    </label><br>
						<label class="labelSpan"><input id="column"    name="baseproradio" type="radio"    value=""        > Bar     </label><br>
						<label class="labelSpan"><input id="line"      name="baseproradio" type="radio"    value=""        > Line    </label><br>
						<label class="labelSpan"><input id="scatter"   name="baseproradio" type="radio"    value="" checked> Scatter </label><br>
						<label class="labelSpan"><input id="spline"    name="baseproradio" type="radio"    value=""        > Spline  </label><br>
					</td><td>
						<label class="labelSpan"><input id="inverted"  name="inverted"     type="checkbox" value=""        > Veritcal x-axis    </label><br>
						<label class="labelSpan"><input id="delimiter" name="delimiter"    type="checkbox" value=""        > Tab delimited      </label><br>
						<label class="labelSpan"><input id="decimal"   name="decimal"      type="checkbox" value=""        > Decimal comma      </label><br>
						<label class="labelSpan"><input id="noheader"  name="noheader"     type="checkbox" value=""        > No header row      </label><br>
						<label class="labelSpan"><input id="swrowcol"  name="swrowcol"     type="checkbox" value=""        > Records in columns </label><br>
					</td><tr></table>
					</td><tr></table>
				</div>
			</div>
			<div id="colpicker">
				<div class="dataLbl copycat" onclick="openClose('colpicker');">Columns<span class="colpicker-sign sign">- Hide -</span></div>
				<div class="sectionDiv colpicker-area" id="colpick">
					<label class="labelSpan"><input id="toggle" name="toggle" type="checkbox" checked="checked"> (All on/off) </label>
					<button id="redraw">Redraw Chart</button>
					<br>
					<div id="colpicklist">
					</div>
				</div>
			</div>
			<div class="dataLbl copycat">Data</div>
			<?php
				// TODO Use filesystem adapter?
				ini_set('auto_detect_line_endings', '1');
				echo '<pre id="csv">';
				$status = 'OK';
				$handle = fopen($this->file->getAbsolutePath(),'r');
				if ($handle)
				{
					$limited = true;
					for ($i=0; $i<$this->limit+1; $i++)
					{
						if (($line = fgets($handle)) == false)
						{
							$limited = false;
							break;
						}
						echo $line;
						if (0 == $i)
						{
							$first = $line;
						}
					}
					fclose($handle);
					if ($limited)
					{
						$status = 'LIMITED';
					}
				}
				else
				{
					$status = 'ERROR';
				}
				echo '</pre>';
				echo '<div id="lcount" style="display:none">'.$i.'</div>';
				echo '<div id="first"  style="display:none">'.$first.'</div>';
				echo '<div id="status" style="display:none">'.$status.'</div>';
			?>
		</div>
	</div>
	<div id="chart"></div>
	<br>
</div>
