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

require_once dirname(__FILE__) . '/inc/collections.php';
define('COLLECTIONS_ACTION_ADD', 'add');
define('COLLECTIONS_ACTION_REMOVE', 'remove');
define('COLLECTIONS_ACTION_REBUILD', 'rebuild');
define('COLLECTIONS_ACTION_EDIT', 'edit');
define('COLLECTIONS_ACTION_ACTIVATE', 'activate');

playerSession('open');
$tpl = "";
$_collections = "";

if ($_GET['cmd'] == COLLECTIONS_ACTION_ACTIVATE) {
	activateCollection($_GET['id']);
	unset($_GET['cmd']);
}

if ($_GET['cmd'] == COLLECTIONS_ACTION_ADD) {
	createCollection("New Collection");
	unset($_GET['cmd']);
}

if ($_GET['cmd'] == COLLECTIONS_ACTION_REMOVE) {
	deleteCollection($_GET['id']);
	unset($_GET['cmd']);
}

if ($_GET['cmd'] == COLLECTIONS_ACTION_REBUILD) {
	collectionRebuild($_GET['id']);
	unset($_GET['cmd']);
}

if ($_GET['cmd'] == COLLECTIONS_ACTION_EDIT) {
	unset($_GET['cmd']);
}



// COLLECTIONS CONFIG FORM
if (!isset($_GET['cmd'])) {
	$tpl = "collections-config.html";

	// display list of collections if any
	$collections = listCollections();
	$activeCollection = getActiveCollection();
	$_collections .= "<p>Active collection: " . (empty($activeCollection['id']) ? "-" : $activeCollection['title']) . "</p>";
	foreach ($collections as $collection) {
		$icon = ($collection['id'] == $activeCollection['id']) ? "<i class='fas fa-check green sx'></i>" : "<i class='fas fa-times red sx'></i>";
		$_collections .= "<p>";
		
		// default collection can't be edited
		if (empty($collection['id'])) 
			$_collections .= "<span class='btn btn-large' style='width:240px;background-color:#333;text-align:left;'> " . $icon . " Default</span>";
		else
			$_collections .= "<a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_EDIT . "&id=" . $collection['id'] . "\" class='btn btn-large' style='width:240px;background-color:#333;text-align:left;'> " . $icon . " " . $collection['title'] . "</a>";
	
		$_collections .= " <a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_ACTIVATE . "&id=" . $collection['id'] ."\"><button class=\"btn btn-medium btn-primary\">Activate</button></a>";
		
		
		// default collection can't be rebuild or removed
		if (!empty($collection['id'])) {
			$_collections .= " <a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_REBUILD . "&id=" . $collection['id'] ."\"><button class=\"btn btn-medium btn-primary\">Rebuild</button></a>";
			$_collections .= " <a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_REMOVE . "&id=" . $collection['id'] ."\"><button class=\"btn btn-medium btn-primary\">Delete</button></a>";
		}
		
		$_collections .= "</br><ul>";
		foreach($collection["flatlist_filters"] as $flatlist_filter)
			$_collections .= "<li style='font-size:12px;'>Filter: '" . $flatlist_filter["filter"] . "' => '" . $flatlist_filter["str"] . "'</li>";
			$_collections .= "</ul>";

		$_collections .= "</p>";
	}

	if (empty($collections))
		$_collections .= '<p class="btn btn-large" style="width: 240px; background-color: #333;">None configured</p><p></p>';
} elseif ($_GET['cmd'] == 'edit') {

}
playerSession('unlock');

$section = basename(__FILE__, '.php');
storeBackLink($section, $tpl);

include('header.php');
eval("echoTemplate(\"".getTemplate("templates/$tpl")."\");");
include('footer.php');
