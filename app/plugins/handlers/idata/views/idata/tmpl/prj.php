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
?>

<div id="prj" class="width-container">
	<br>
	<!-- TODO Use Language -->
	<h3>Projection: <span class="title-filename"><?php echo basename($this->file->getAbsolutePath()); ?></span></h3>
	<br>
	<tt>
	<?php
		// TODO Use filesystem adapter calls?
		$output = '';
		$latch  = false; // Already substituted equal sign for quotes on this line?
		$indent = 0;
		$handle = fopen($this->file->getAbsolutePath(),'r');
		if (!$handle) {
			return;
		}
		while (!feof($handle)) {
			$buf  = fread($handle,1);
			$orig = $buf;
			switch ($buf) {
				case '[':
					$indent += 1;
					$buf = ':';
					break;
				case ',':
					if ($prevQuote && !$latch) {
						$latch = true;
						$buf = ' = ';
					}
					else {
						$latch = false;
						$buf    = '<br>';
						for ($i=0; $i<$indent; $i++) {
							$buf .= '&nbsp;&nbsp;&nbsp;';
						}
					}
					break;
				case ']':
					$indent -= 1;
					$buf    = '&nbsp;';
					break;
				case ' ':
					$buf = '&nbsp;';
					break;
				case '"':
					$buf = '&nbsp;';
					break;
			}
			$output .= $buf;
			$prevQuote = false;
			if ($orig == '"') {
				$prevQuote = true;
			}
		}
		fclose($handle);

		// TODO Language
		$output = str_replace('PROJECTION'      ,'<strong>PROJECTION</strong>',$output);
		$output = str_replace('PARAMETER:&nbsp;',''                           ,$output);
		$output = str_replace('UNIT:&nbsp;'     ,''                           ,$output);
		echo $output;
	?>
	</tt>
	<br><br>
</div>
