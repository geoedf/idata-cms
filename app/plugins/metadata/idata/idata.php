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

// No direct access
defined('_HZEXEC_') or die();


/**
 * Plugin class for iData metadata handling
 */
class plgMetadataIdata extends \Hubzero\Plugin\Plugin
{
 
	/**
	* Responds to events for rendering the metadata edit interface
	*
	* @return  void
	**/
	public function onMetadataEdit()
	{
		$view = new \Hubzero\Plugin\View([
			'folder'  => 'metadata',
			'element' => 'idata',
			'name'    => 'idata',
			'layout'  => 'edit'
		]);

		$hidden = array();

		//group is no longer used, superseded by owner and owner_type
		//we include group here since it may be present in older files
		// TODO Remove group?
		$hidden[] = 'group';
		$hidden[] = 'owner';
		$hidden[] = 'owner_type';
		$view->hideFields = $hidden;

		return $view;
	}

	/**
	* Responds to events for saving file metadata
	*
	* @param   Hubzero\Filesystem\File  $file      The file to which the metadata pertains
	* @param   array                    $metadata  The metadata itself
	* @return  void
	**/
	public function onMetadataSave(Hubzero\Filesystem\File $file, $metadata)
	{
      $metadata['id'] = $file->getAbsolutePath();     
      error_log('(old)>>>');
      error_log(print_r($metadata,true));
      error_log('<<<(old)');
		// NOTE Only those key/value pairs that have NON-EMPTY values
		//      are being passed to us in $metadata!

	   plgMetadataIdata::indexMetadataToSolrImmediately($metadata);

   }
	/**
	* Responds to events for getting the latest metadata annotation set
	*
	* @param   Hubzero\Filesystem\File  $file        The file to which the metadata pertains
	* @param   int                      $maxEntries  The maximum number of entries to return
	* @return  array
	**/
	public function onMetadataGet(Hubzero\Filesystem\File $file, $maxEntries = 1)
	{

// 		$prodsf = Prods::getProdsFile($file);
// 		$prodsm = $prodsf->getMeta();
		$ameta = array(); // TODO Fix

		// Add code to get metadata from Solr
		// throw error if null?
		$metadata = plgMetadataIdata::getMetadataFromSolr($file->getAbsolutePath());

		// Append key with ":<units>"
		foreach ($ameta as $eachmeta)
		{
			$metadata[$eachmeta->name] = ['value' => $eachmeta->value, 'units' => $eachmeta->units];
		}
		return $metadata;

	}

	/**
	* Write metadata for file
	*
	* @param   string  object     file
	* @param   array   metadata   key/value pairs
	* @return  void
	**/
	private static function bulkMeta($path, $metadata)
	{
	// TODO Figure out if $path needs to include base...

		//indexMetadataToSolr($metadata);

//error_log('metadata plugin, bulkMeta(): '.print_r($metadata,true));

// 		// Build the iData function call
// 		$kvpairs = new RODSKeyValPair($metadata);
// 		$body    = "updateBulkMeta{msiSetBulkMeta(*KeyVal,*path,*status);}";
// 		$inp     = array();
// 		$inp['*KeyVal'] = $kvpairs;
// 		$inp['*path']   = $prodsf->path_str;
// 		$out     = array("*status");
//
// 		// Get access
// 		$config  = Plugin::params('filesystem', 'idata');
// 		$account = new RODSAccount(
// 			$config->get('host')
// 			, intval($config->get('port'))
// 			, $config->get('user')
// 			, $config->get('pass')
// 			, $config->get('zone')
// 			, $config->get('resc')
// 		);
//
// 		// Perform the iData funtion call
// 		$rule = new ProdsRule($account,$body,$inp,$out);
// 		$res = $rule->execute();
// 		return($res["*status"]);
	}

	/**
	* Derive and record metadata for new file (called by file adapter)
	*
	* @param   string  $path        Full path to new file
	* @return  void
	**/
	public static function captureInitialMetadata($path)
	{
		// TODO Verify path is correct (Should/shouldn't incl. base?)

		$config = Plugin::params('metadata', 'idata');

		$metadata = array();
		$metadata['contributor'] = User::get('username');
		$metadata['publisher']   = $config->get('publisher');

		plgMetadataIdata::bulkMeta($path,$metadata);
	}

	/**
   * Parse fieldnames. Separate fields into their sections as they are shown in the metadata editor:
   * dub, geo, usr, and sub. Title is defined as multivalued in Solr, which returns an array. This arrays is replaced
	* by the first array entry.
	*
	* @param array metadata from Solr
	* @return array metadata sorted by field type
	**/
	public static function parseFieldnames($metadata)
   {
      $new = array('static' => array(), 'geo' => array(), 'usr' => array(), 'sub' => array());

       
		foreach($metadata as $fieldname => $value) {
         $tokens = explode("_", $fieldname);
         if (sizeof($tokens) == 1) {
            // Not all static fields are dublin core, but all dublin core fields are static 
            $new['static'][$tokens[0]] = $value;
         } else if ($tokens[0] === 'coverage') {
            // coverage is static but it field in solr, but it's displayed as a geofield, so it needs an empty prefix and postfix 
				$new['geo']['coverage'] = array('pre' => '','value' => $value, 'post' => ''); 
         } else if ($tokens[0] === 'geo') {
            // example geofield: 'geo_northlimit_f'
            $new['geo'][$tokens[1]] = array('pre' => $tokens[0], 'value' => $value, 'post' => $tokens[2]);
         } else if ($tokens[0] === 'usr') {
            // User-defined fields could contain underscores, so 
            // we remove the pre and post fix and implode the remaining inner tokens to get the fieldname
            // example userfield: 'usr_may_contain_an_underscore_txt'
            $pre = array_shift($tokens);
            $post = array_pop($tokens);
            $field =  implode('_', $tokens);
            $new['usr'][$field] = array('pre' => $pre, 'value' => $value, 'post' => $post);
         } else if (substr($tokens[0], 0, 3) === 'sub') {
            // example subfield: 'sub0_title_txt'
            $num = (int)substr($tokens[0], 3);
            if(!array_key_exists($num, $new['sub'])) {
               $new['sub'][$num] = array('pre' => $tokens[0], 'pairs' => array(), 'post' => $tokens[2]);
				}
            $new['sub'][$num]['pairs'][$tokens[1]] = $value; 
         }
      }
      return $new;
	}



	/**
	* Get metadata from Solr
	*
	* @param  string $path	Full path to file
	* @return array  metadata or Null
	**/
	public static function getMetadataFromSolr($path)
	{
		$config = Component::params('com_search');
		$searchQuerier = new \Hubzero\Search\Adapters\SolrQueryAdapter($config);
		$query="id:\"$path\"";
		$searchQuerier->query($query);
		$res = $searchQuerier->getResults();
		if ($searchQuerier->getNumFound() == 0)
		{
			return array();
		} else if ($searchQuerier->getNumFound() > 1) {
			// right now it's possible to get back AAA.txt and AAA.txt.gz by looking up "AAA.txt"
			throw new Exception("This shouldn't happen because id's are unique");
		}
		// 'title' is defined as multivalued in solr, so it is returned as an array
		// we change it to a single value for convenience
		if (array_key_exists('title', $res[0])) {
			$title = $res[0]['title'][0];
			$res[0]['title'] = $title;
		}
		// get rid of multivalued fields and fields with bad underscores that we don't need
		$fields = array('title_auto', '_version_', 'timestamp', 'score', 'access_level');
		foreach ($fields as $field) {
			if (array_key_exists($field, $res[0])) {
				unset($res[0][$field]);
			}
		}
		$metadata = plgMetadataIdata::parseFieldnames($res[0]);
      return $metadata;
   }

	/**
	* Index metadata to Solr (not buffered, immediate commit)
	* Can use if you don't want to sleep(3); to guarentee update when testing
	* @param  array metadata
	* @return void
	**/
	public static function indexMetadataToSolrImmediately($metadata)
	{
		$config = Component::params('com_search');
		$searchIndexer= new \Hubzero\Search\Adapters\SolrIndexAdapter($config);
		$update = $searchIndexer->connection->createUpdate();
		$newDoc = $update->createDocument();
	   foreach ($metadata as $field => $value)
	   {
			$newDoc->$field = $value;
		}
		$update->addDocument($newDoc);
		$update->addCommit();
		$searchIndexer->connection->update($update);
	}

	/**
	* Index metadata to Solr
	*
	* @param  array metadata
	* @return void
	**/
	public static function indexMetadataToSolr($metadata)
	{
		$config = Component::params('com_search');
		$searchIndexer= new \Hubzero\Search\Adapters\SolrIndexAdpter($config);
		$searchIndexer->updateDocument($metadata);
	}




}
