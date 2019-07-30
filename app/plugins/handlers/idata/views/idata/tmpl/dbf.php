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

defined('_HZEXEC_') or die(); // No direct access
$this->css('idata'); // Add the idata stylesheet
?>

<div id="dbf" class="width-container">
	<br>
	<!-- TODO Use Language -->
	<h3>
		Attributes:
		<span class="title-filename"><?php echo basename($this->file->getAbsolutePath()); ?></span>
		<div style="float:right;">
			<strong><span id="status"></status></strong>
		</div>
	</h3>
	<br>
	<table>
	<?php
		$field_names = array();

		// Following code modified from 1st comment at http://php.net/manual/en/book.dbase.php
		// TODO Use filesystem adapter calls
		// TODO Limit size?
		$handle = fopen($this->file->getAbsolutePath(),'r');
		if (!$handle) {
			return;
		}
		$fields = array();
		$buf    = fread($handle,32);
		$header = unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
		$goon         = true;
		$unpackString ='';
		echo '<tr>';

		// Fields descriptions
		while ($goon && !feof($handle)) {
			$buf = fread($handle,32);
			if (substr($buf,0,1) == chr(13)) {
				$goon = false;
			}
			else {
				$field = unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
				array_push($field_names, $field['fieldname']);
				echo '<th>'.$field['fieldname'].'</th>';
				$unpackString .= "A$field[fieldlen]$field[fieldname]/";
				array_push($fields, $field);
			}
		}
		echo '</tr>';

		// Go back to start of 1st rec (after field defs)
		fseek($handle, $header['FirstRecord']+1);

		$status = '';

		// Data records
		for ($i=1; $i<=$header['RecordCount']; $i++) {

			if ($i >= ($this->limit * 3))
			{
				$status = 'NOTE: Due to file size, not all records are shown.';
				break;
			}

			$buf    = fread($handle,$header['RecordLength']);
			$record = unpack($unpackString,$buf);
			echo '<tr>';
			foreach ($field_names as $fn) {
				echo '<td>';
				if (is_numeric($record[$fn])) {
					echo sprintf('%.4f',$record[$fn]);
				}
				else {
					echo $record[$fn];
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		fclose($handle);
	?>
	</table>
	<br>
</div>
<script>(function() {document.getElementById("status").innerHTML = "<?php echo $status; ?>";})();</script>
