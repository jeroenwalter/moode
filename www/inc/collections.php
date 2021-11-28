<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * tsunamp player ui (C) 2013 Andrea Coiutti & Simone De Gregori
 * http://www.tsunamp.com
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 *
 * This Program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This is based on the @chris-rudmin 2019-08-08 rewrite of the GenLibrary()
 * function to support the new Library renderer /var/www/js/scripts-library.js
 * Refer to https://github.com/moode-player/moode/pull/16 for more info.
 *
 */

define('COLLECTIONS_DIR', '/var/local/www/collections/');
define('COLLECTIONS_LIBCACHE_BASE', 'libcache');
define('COLLECTIONS_PARAMETERS', 'parameters.json');
define('COLLECTIONS_ACTIVE_COLLECTION_ID', 'library_collection_name');

require_once dirname(__FILE__) . '/playerlib.php';

// TODO: Why not store the collection parameters in SQL?

function setupCollections() {
	workerLog('worker: Setup collections');

	mkdir(COLLECTIONS_DIR, 0777, false);
	
	$dbh = cfgdb_connect();
	sdbquery("INSERT INTO cfg_system (param, value) SELECT '" . COLLECTIONS_ACTIVE_COLLECTION_NAME . "', '' WHERE NOT EXISTS(SELECT 1 FROM cfg_system WHERE param = '" . COLLECTIONS_ACTIVE_COLLECTION_NAME ."');", $dbh);
}

function handleCollectionCommand() {
	$handled = false;
	switch ($_GET['cmd']) {
		case 'createcollection':
			echo json_encode(createCollection($_POST['collection']));
			$handled = true;
			break;
		case 'deletecollection':
			echo json_encode(deleteCollection($_POST['collection']));
			$handled = true;
			break;
		case 'activatecollection':
			echo json_encode(activateCollection($_POST['collection']));
			$handled = true;
			break;
		case 'listcollections':
			echo json_encode(listCollections());
			$handled = true;
			break;
		case 'getcollection':
			echo json_encode(getCollection($_POST['collection']));
			$handled = true;
			break;
	}

	return $handled;
}

function getCollectionLibcacheBase() {
	$activeCollectionId = getActiveCollectionId();
	return is_null($activeCollection) 
		? LIBCACHE_BASE
		: COLLECTIONS_DIR . $activeCollectionId . "/" . COLLECTIONS_LIBCACHE_BASE;
}

function createCollection($title) {
	if (is_null($title) || empty(trim($title))) {
		return 'Error: empty collection name';
	}

	workerLog('Creating collection ' . $title);
	
	// For now use a unique id for the folder name instead of creating a valid filename from $name
	$collectionId = uniqid('collection-', false);
	$collectionDir = COLLECTIONS_DIR . $collectionId . '/';

	mkdir($collectionDir, 0777, false);

	$collection = array();
	$collection["id"] = $collectionId;
	$collection["title"] = $title;
	$json = json_encode($collection);

	if (false === file_put_contents($collectionDir . COLLECTIONS_PARAMETERS, $json)) {
		workerLog('createCollection(): error: file create failed: ' . $collectionDir . COLLECTIONS_PARAMETERS);
		return 'error: file create failed: ' . $collectionDir . COLLECTIONS_PARAMETERS;
	}

	$libcache_base = $collectionDir . COLLECTIONS_LIBCACHE_BASE;
	sysCmd('touch ' . $libcache_base . '_all.json');
	sysCmd('touch ' . $libcache_base . '_folder.json');
	sysCmd('touch ' . $libcache_base . '_format.json');
	sysCmd('touch ' . $libcache_base . '_hdonly.json');
	sysCmd('touch ' . $libcache_base . '_lossless.json');
	sysCmd('touch ' . $libcache_base . '_lossy.json');
	sysCmd('touch ' . $libcache_base . '_tag.json');

	return 'OK';
}

function deleteCollection($collectionId) {
	workerLog('Deleting collection ' . $collectionId);

	if (is_null(getCollection($collectionId))) {
		return "Collection not found";
	}

	if (getActiveCollectionId() == $collectionId) {
		activateCollection('');
	}

	sysCmd('rm -rf ' . COLLECTIONS_DIR . $collectionId);

	return "OK";
}

function listCollections() {

	$retval = array();
	$iterator = new DirectoryIterator(COLLECTIONS_DIR);
	foreach ($iterator as $fileinfo) {
    	if (!$fileinfo->isDot() && $fileinfo->isDir()) {
			$collection = getCollection($fileinfo->getFilename());
			if (!is_null($collection))
				array_push($retval, $collection);
		}
    }

	return $retval;
}

function listCollections_org() {
	$files = scandir(COLLECTIONS_DIR);

	$retval = array();
	array_push($retval, COLLECTIONS_DIR);
	foreach ($files as $file) {
		if (!is_dot($file) && is_dir($file)) 
			array_push($retval, $file);
	}

	return $retval;
}

function getCollection($collectionId) {
	$collectionFile = COLLECTIONS_DIR . $collectionId . '/' . COLLECTIONS_PARAMETERS;
	if (file_exists($collectionFile))
		return json_decode(file_get_contents($collectionFile));	
		
	return NULL;
}

function getActiveCollectionId() {
	
	if (!empty($_SESSION[COLLECTIONS_ACTIVE_COLLECTION_ID]))
		return $_SESSION[COLLECTIONS_ACTIVE_COLLECTION_ID];

	return NULL;
}


function activateCollection($name){
	workerLog('Activating collection ' . $name);

	if (is_null(getCollection($name))) {
		return "Collection not found";
	}

	loadLibrary();
	return "OK";
}


function getCollectionParameters($name) {
	if (is_null(getCollection($name))) {
		return "Collection not found";
	}
}

function setCollectionParameters($params) {
	if (is_null(getCollection($name))) {
		return "Collection not found";
	}
}
