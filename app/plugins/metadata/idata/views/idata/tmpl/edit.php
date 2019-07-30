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

// NOTE This file was adapted from HUBzero core view annotate.php

// No direct access
defined('_HZEXEC_') or die();

// TODO Why don't following two lines work?
$this->css('idata');
$this->js('idata');
// TODO Remove temp code when lines above work
echo '<link rel="stylesheet" href="/app/plugins/metadata/idata/assets/css/idata.css" type="text/css" />';
echo '<script src="/app/plugins/metadata/idata/assets/js/idata.js" type="text/javascript"></script>';

Lang::load('plg_metadata_idata', PATH_APP . DS . 'plugins' . DS . 'metadata' . DS . 'idata');

// Directory path breadcrumbs
$bc    = \Components\Projects\Helpers\Html::buildFileBrowserCrumbs($this->subdir, $this->url, $parent, false);
$bcEnd = $this->item->isDir() ? '<span class="folder">' . $this->item->getName() . '</span>' : '<span class="file">' . $this->item->getName() . '</span>';
$lang  = $this->item->isDir() ? Lang::txt('PLG_METADATA_IDATA_FOLDER') : Lang::txt('PLG_METADATA_IDATA_FILE');

$dublinCore= [
      'description',
      'title',
      'subject',
      'contributor',
      'publisher',
      'date',
      'identifier',
      'format',
      'type',
      'source',
      'creator',
      'language'
   ];

$geoFields = [
      'northlimit',
      'southlimit',
      'eastlimit',
      'westlimit',
      'latmin',
      'latmax',
      'lonmin',
      'lonmax',
      'xsize',
      'ysize',
      'coverage'
   ];

?>

<div id="abox-content">
	<h3>
		<?php echo Lang::txt('PLG_METADATA_IDATA_TITLE') . ' ' . $lang . ' ' . $bc . ' ' . $bcEnd; ?>
	</h3>
	<?php if ($this->getError()) : ?>
		<p class="witherror"><?php $this->getError(); ?></p>
	<?php else : ?>

		<div id="scroll-area">

		<form id="hubForm-ajax" method="post" action="<?php echo Route::url($this->url); ?>">
			<fieldset>
				<input type="hidden" name="subdir" value="<?php echo $this->subdir; ?>" />
				<input type="hidden" name="action" value="annotateit" />
				<input type="hidden" name="item" value="<?php echo $this->item->getName(); ?>" />

            
			   <?php  $i = 0; //a counter that is unique to each key-value pair ?>
				<ul id="metadata-entries">
<? /*
 */ ?>
					<!-- Dublin Core: display values and remove from metadata array -->
					<?php echo '<h4 onclick="openClose('."'core','"
						. Lang::txt('PLG_METADATA_IDATA_SHOW') .  "','"
						. Lang::txt('PLG_METADATA_IDATA_HIDE') . "')" . '">'
						. Lang::txt('PLG_METADATA_IDATA_LABEL_CORE')
						. '<span class="core-sign show-hide-label">'
							. Lang::txt('PLG_METADATA_IDATA_HIDE')
						. '</span></h4>';
					?>
					<div class="core-area">
                  <?php foreach ($dublinCore as $element) : ?> 
							<li>
								<div class="entry key-value-pair" data-idx="<?php echo $i; ?>">
									<div class="entry-label">
                              <input type="hidden" name="pre[<?php echo $i; ?>]" value="" >
										<input type="text" name="key[<?php echo $i; ?>]" maxlength="250" value="<?php echo $element; ?>" readonly />
                              <input type="hidden" name="post[<?php echo $i; ?>]" value="" >
									</div>
									<div class="separator">:</div>
									<div class="entry-value">
										<?php
											$value = '';
											if (isset($this->metadata['static'][$element]))
											{
                                    $value = $this->metadata['static'][$element];
											}
										?>
										<input type="text"   name="value[<?php echo $i; ?>]" value="<?php echo $value; ?>" />
									</div>
								</div>
							</li>
							<?php $i++; ?>
						<?php endforeach; ?>
					</div>
					<!-- Geographic: display all fields in the geoFields list whether or not they have corresponding values-->
					<?php echo '<h4 onclick="openClose('."'geo','"
						. Lang::txt('PLG_METADATA_IDATA_SHOW') . "','"
						. Lang::txt('PLG_METADATA_IDATA_HIDE') . "')". '">Geographic Coverage<span class="geo-sign show-hide-label">'
						. Lang::txt('PLG_METADATA_IDATA_HIDE')
						. '</span></h4>';
					?>
					<div class="geo-area">
						<?php foreach ($geoFields as $element) : ?>
							<?php
								if (isset($this->metadata['geo'][$element]))
								{
                           $pre = $this->metadata['geo'][$element]['pre'];
                           $value = $this->metadata['geo'][$element]['value'];
                           $post = $this->metadata['geo'][$element]['post'];
								}
							?>
							<li>
								<div class="entry key-value-pair" data-idx="<?php echo $i; ?>">
									<div class="entry-label">
										<input type="hidden" name="pre[<?php echo $i; ?>]" value="<?php echo $pre ?>" />
										<input type="text" name="key[<?php echo $i; ?>]" maxlength="250" value="<?php echo $element; ?>" readonly />
										<input type="hidden" name="post[<?php echo $i; ?>]" value="<?php echo $post; ?>" />
									</div>
									<div class="separator">:</div>
									<div class="entry-value">
										<input type="text"   name="value[<?php echo $i; ?>]" value="<?php echo $value; ?>"  readonly />
									</div>
								</div>
							</li>
							<?php $i++; ?>
						<?php endforeach; ?>
               </div>
					<!-- Subdata Layers:-->
					<?php echo '<h4 onclick="openClose('."'geo','"
						. Lang::txt('PLG_METADATA_IDATA_SHOW') . "','"
						. Lang::txt('PLG_METADATA_IDATA_HIDE') . "')". '">Subdata Layers<span class="geo-sign show-hide-label">'
						. Lang::txt('PLG_METADATA_IDATA_HIDE')
						. '</span></h4>';
					?>
               <div class="sub-area">
                  <?php $size = sizeof($this->metadata['sub']); ?>
                  <?php for ($index = 0; $index < $size; $index++) : 
                     $pre = $this->metadata['sub'][$index]['pre'];
                     $post = $this->metadata['sub'][$index]['post'];
                  ?>
                     <?php foreach($this->metadata['sub'][$index]['pairs'] as $key => $value): ?>
                        <li>
                           <span><?php echo "Layer $index" ?></span>
                           <div class="entry key-value-pair" data-idx="<?php echo $i; ?>">
                              <div class="entry-label">
                                 <input type="hidden" name="pre[<?php echo $i; ?>]" value="<?php echo $pre; ?>" />
                                 <input type="hidden" name="index[<?php echo $i; ?>]" value="<?php echo $index; ?>" />
                                 <input type="text" name="key[<?php echo $i; ?>]" maxlength="250" value="<?php echo $key; ?>" readonly />
                                 <input type="hidden" name="post[<?php echo $i; ?>]" value="<?php echo $post; ?>" />
                              </div>
                              <div class="separator">:</div>
                              <div class="entry-value">
                                 <input type="text"   name="value[<?php echo $i; ?>]" value="<?php echo $value; ?>"  readonly />
                              </div>
                           </div>
                        </li>
                        <?php $i++; ?>
                     <?php endforeach; ?>
                  <?php endfor; ?>
					</div>
					<!-- User Defined (display remaining fields in metadata array) -->
					<?php echo '<h4 onclick="openClose('."'external','"
						. Lang::txt('PLG_METADATA_IDATA_SHOW') . "','"
						. Lang::txt('PLG_METADATA_IDATA_HIDE') . "')".'">User-Defined Metadata<span class="external-sign show-hide-label">'
						. Lang::txt('PLG_METADATA_IDATA_HIDE') . '</span></h4>';
					?>
					<div class="usr-area">
                  <?php if (isset($this->metadata['usr'])): ?>
                     <?php foreach($this->metadata['usr'] as $key => $value): ?>
                         <li>
                           <div class="entry key-value-pair" data-idx="<?php echo $i; ?>">
                              <div class="entry-label">
                                 <input type="hidden" name="pre[<?php echo $i; ?>]" value="usr" />
                                 <input type="text" name="key[<?php echo $i; ?>]" maxlength="250" value="<?php echo $key; ?>" />
                                 <input type="hidden" name="post[<?php echo $i; ?>]" value="t">
                              </div>
                              <div class="separator">:</div>
                              <div class="entry-value">
                                 <input type="text"   name="value[<?php echo $i; ?>]" value="<?php echo $value['value']; ?>"  />
                              </div>
                           </div>
                        </li>
                        <?php $i++; ?>
                     <?php endforeach; ?>
                  <?php endif; ?>
						<!-- New entries -->
						<li>
							<div class="entry key-value-pair" data-idx="<?php echo $i; ?>">
								<div class="entry-label">
                           <input type="hidden" name="pre[<?php echo $i; ?>]" value="usr" />
									<input type="text" name="key[<?php echo $i; ?>]" maxlength="250" value="" />
                           <input type="hidden" name="post[<?php echo $i; ?>]" value="t" />
								</div>
								<div class="separator">:</div>
								<div class="entry-value">
									<input type="text"   name="value[<?php echo $i; ?>]" value="" />
									<input type="hidden" name="units[<?php echo $i; ?>]" value="external" />
								</div>
							</div>
						</li>
					</div>
				</ul>

				<div class="btn icon-add add-new-annotation">
					<?php echo Lang::txt('PLG_METADATA_IDATA_ADD'); ?>
				</div>
				<div class="buttons">
					<input type="submit" class="btn" value="<?php echo Lang::txt('PLG_METADATA_IDATA_SAVE'); ?>" />
					<input type="reset" class="btn btn-cancel" id="cancel-action" value="<?php echo Lang::txt('PLG_METADATA_IDATA_CANCEL'); ?>" />
				</div>
			</fieldset>
		</form>

		</div>

	<?php endif; ?>
</div>
