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
 */

define('COLLECTIONS_ACTIVE_VIEW', 'library_flatlist_view');
define('COLLECTIONS_ALL_VIEW_ID', 1);

require_once dirname(__FILE__) . '/playerlib.php';


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
		case 'getactivecollection':
			echo json_encode(getCollection(getActiveCollectionId()));
			$handled = true;
			break;
		case 'getcollection':
			echo json_encode(getCollection($_POST['collection']));
			$handled = true;
			break;
	}

	return $handled;
}

function createCollection(string $name) : int {
	$name = trim($name ?? "");
	if (empty($name)) {
		debugLog("createView: Error: empty view name");
		return NULL;
	}

	debugLog("createView: Creating view '$name'");
	
	$dbh = cfgdb_connect();
	$stmt = $dbh->prepare('INSERT INTO cfg_view (name) VALUES(:name)');
	$stmt->execute([ 'name' => $name ]);
	
	$viewId = $dbh->lastInsertId();
	
	sysCmd('touch ' . LIBCACHE_BASE . '_view_' . $viewId . '.json');

	debugLog("createView: View created : $viewId, '$name'");

	return $viewId;
}

function saveCollection(array $view) : bool {
	$viewId = (int)$view["id"];
	debugLog("saveCollection(): " . $viewId);

	$currentView = getCollection($viewId);
	if (is_null($currentView)) {
		debugLog("saveCollection: view not found: ". $viewId);
		return false;
	}
	
	$name = trim((string)$view["name"] ?? "");
	if (empty($name)) {
		debugLog("saveCollection: Error: empty view name");
		return false;
	}
	
	if (!is_array($view["flatlist_filters"])) {
		debugLog("saveCollection: flatlist_filters not valid");
		return false;
	}
		
	// Update name
	$dbh = cfgdb_connect();
	$stmt = $dbh->prepare('UPDATE cfg_view SET name=:name WHERE id=:id');
	$stmt->execute([ 'name' => $name, 'id' => $viewId ]);
	
	// Create new filters
	// Remove deleted filters
	// Update existing filters
	// For now, just replace all filters....
	$stmt = $dbh->prepare('DELETE FROM cfg_view_filter WHERE view_id=:view_id');
	$stmt->execute([ 'view_id' => $viewId ]);
	
	foreach($view["flatlist_filters"] as $filter) {
		$stmt = $dbh->prepare('INSERT INTO cfg_view_filter (filter, str, view_id) VALUES (:filter, :str, :view_id)');
		$stmt->execute([ 'filter' => $filter["filter"], 'str' => $filter["str"], 'view_id' => $viewId ]);
	}
	
	return true;
}

function deleteCollection(int $viewId) : bool {
	debugLog('deleteCollection: Deleting collection ' . $viewId);
	if ($viewId == 1) {
		debugLog('deleteCollection: not allowed to delete the default view');
		return false;
	}
		
	$dbh = cfgdb_connect();
	$stmt = $dbh->prepare('DELETE FROM cfg_view WHERE cfg_view.id = :viewId');
	$stmt->execute([ 'viewId' => $viewId ]);
	
	return true;
}

function listCollections() : array {
	$views = array();
	$dbh = cfgdb_connect();
	$rows = sdbquery("SELECT cfg_view.id, cfg_view.name FROM cfg_view", $dbh);

	if (!is_array($rows)) {
		debugLog("listCollections: failed to get views");
		return array();
	}

	foreach($rows as $row) {
		$view = array();
		$view["id"] = $row["id"];
		$view["name"] = $row["name"];
		array_push($views, $view);
	}

	return $views;
}

function getCollection(int $viewId) : array {
	debugLog("getCollection: Get collection $viewId");
	$dbh = cfgdb_connect();

	$viewFilters = sdbquery(
		"SELECT cfg_view.name, cfg_view_filter.id as filter_id, cfg_view_filter.filter, cfg_view_filter.str
		FROM cfg_view 
		LEFT JOIN cfg_view_filter 
		ON cfg_view.id = cfg_view_filter.view_id 
		WHERE cfg_view.id = $viewId", 
		$dbh);

	if (!is_array($viewFilters)) {
		debugLog("getCollection: failed to get view for view $viewId");
		return NULL;
	}

	$view = array();
	$view["id"] = $viewId;
	$view["name"] = $viewFilters[0]["name"];
	$view["flatlist_filters"] = array();
	
	if (!is_null($viewFilters[0]["filter_id"])) {
		foreach($viewFilters as $index=>$filter) {
			$view["flatlist_filters"][$index] = array();
			$view["flatlist_filters"][$index]["id"] = $filter["filter_id"];
			$view["flatlist_filters"][$index]["filter"] = $filter["filter"];
			$view["flatlist_filters"][$index]["str"] = $filter["str"];
		}
	}
		
	return $view;
}

function getActiveCollectionId() : int {
	if (!empty($_SESSION[COLLECTIONS_ACTIVE_VIEW]))
		return (int)$_SESSION[COLLECTIONS_ACTIVE_VIEW];

	return COLLECTIONS_ALL_VIEW_ID;
}

function getActiveCollection() : array {
	return getCollection(getActiveCollectionId());
}

function activateCollection(int $collectionId) {
	debugLog("activateCollection: Activating collection $collectionId");

	if (is_null(getCollection($collectionId))) {
		debugLog("activateCollection: Collection not found: $collectionId");
		return "Error: not found";
	}

	playerSession('open');
	playerSession('write', COLLECTIONS_ACTIVE_VIEW, $collectionId);
	playerSession('unlock');

	// TODO: replace with better method:
	sendEngCmd('libupd_done');
	
	return "OK";
}


function collectionGetFilesAndMetadataFromMPD($sock, $dirs) : string {
	$collection = getActiveCollection();

	$resp = '';
	foreach ($dirs as $dir) {
		foreach($collection["flatlist_filters"] as $flatlist_filter)
			$resp .= getAndFilterFilesFromMpd($sock, $dir, $flatlist_filter["filter"], $flatlist_filter["str"]);
	}
	return $resp;
}

function collectionRebuild($collectionId) {
	if (empty($collectionId))
		return "Error: can't rebuild default collection yet";

	if (is_null(getCollection($collectionId))) {
		debugLog("collectionRebuild: Collection not found: $collectionId");
		return "Error: not found";
	}

	collectionClearLibCacheAll($collectionId);

	// TODO: replace with better method:
	sendEngCmd('libupd_done');

	return "OK";
}

function collectionClearLibCacheAll(int $viewId) : void {
	sysCmd('truncate ' . LIBCACHE_BASE . '_view_' . $viewId . '.json --size 0');
	//cfgdb_update('cfg_system', cfgdb_connect(), 'lib_pos','-1,-1,-1');
}
