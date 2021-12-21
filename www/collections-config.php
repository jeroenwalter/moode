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
define('COLLECTIONS_ACTION_REBUILD', 'rebuild');
define('COLLECTIONS_ACTION_EDIT', 'edit');
define('COLLECTIONS_ACTION_ACTIVATE', 'activate');

playerSession('open');
$tpl = "";

$_collectionsHtml = "";

$_editCollectionName = "";
$_editCollectionId = "";

// collection-config.html POSTS

// remove collection
if (isset($_POST['delete']) && $_POST['delete'] == 1) {
	deleteCollection($_POST['collection-id']);
	unset($_GET['cmd']);
}

// save source
if (isset($_POST['save']) && $_POST['save'] == 1) {
	// validate
	$collectionId = $_POST['collection-id'];
	$validationError = "";

	if (isset($_POST['collection-name'])) {
		$collectionName = trim($_POST['collection-name']);
		if (empty($collectionName)) {
			$validationError = "Collection name can't be empty";
		}
	}

	

	if (empty($validationError)) {

		if (empty($collectionId)) {
			// add
			$collectionId = createCollection($collectionName);
		} 
			
		// edit existing or newly created
		$collection = getCollection($collectionId);
		if (!is_null($collection)) {
			$collection['title'] = $collectionName;
			$collection["flatlist_filters"] = array();
		
			if (isset($_POST['collection-filter']) && is_array($_POST['collection-filter'])) {
				foreach($_POST['collection-filter'] as $index=>$filter) {
					if (!empty($filter) && !empty($_POST['collection-filter-str'][$index])) {
						$newFilter = array();
						$newFilter["filter"] = $filter;
						$newFilter["str"] = $_POST['collection-filter-str'][$index];
						$collection["flatlist_filters"][] = $newFilter;
					}
				}
			}

			saveCollection($collection);
		}
	}
	
	unset($_GET['cmd']);
}


if ($_GET['cmd'] == COLLECTIONS_ACTION_ACTIVATE) {
	activateCollection($_GET['id']);
	unset($_GET['cmd']);
}

if ($_GET['cmd'] == COLLECTIONS_ACTION_REBUILD) {
	collectionRebuild($_GET['id']);
	unset($_GET['cmd']);
}

// Show all collections
if (!isset($_GET['cmd'])) {
	
	$tpl = "collections-config.html";

	// display list of collections if any
	$collections = listCollections();
	$activeCollection = getActiveCollection();
	$_collectionsHtml .= "<p>Active collection: " . (empty($activeCollection['id']) ? "-" : $activeCollection['title']) . "</p>";
	foreach ($collections as $collection) {
		$icon = ($collection['id'] == $activeCollection['id']) ? "<i class='fas fa-check green sx'></i>" : "<i class='fas sx'></i>";
		$_collectionsHtml .= "<p>";
		
		// default collection can't be edited
		if (empty($collection['id'])) 
			$_collectionsHtml .= "<span class='btn btn-large' style='width:240px;background-color:#333;text-align:left;'> " . $icon . " " . $collection['title'] . "</span>";
		else
			$_collectionsHtml .= "<a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_EDIT . "&id=" . $collection['id'] . "\" class='btn btn-large' style='width:240px;background-color:#333;text-align:left;'> " . $icon . " " . $collection['title'] . "</a>";
	
		$_collectionsHtml .= " <a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_ACTIVATE . "&id=" . $collection['id'] ."\"><button class=\"btn btn-medium btn-primary\">Activate</button></a>";
		
		
		// default collection can't be rebuild or removed
		if (!empty($collection['id'])) {
			$_collectionsHtml .= " <a href=\"collections-config.php?cmd=" . COLLECTIONS_ACTION_REBUILD . "&id=" . $collection['id'] ."\"><button class=\"btn btn-medium btn-primary\">Rebuild</button></a>";
		}
		
		$_collectionsHtml .= "</p>";
	}

	if (empty($collections))
		$_collectionsHtml .= '<p class="btn btn-large" style="width: 240px; background-color: #333;">None configured</p><p></p>';

} 

// Add/Edit collection form
if ($_GET['cmd'] == COLLECTIONS_ACTION_EDIT || $_GET['cmd'] == COLLECTIONS_ACTION_ADD) {
	
	$tpl = 'collection-config.html';
	$_collectionFiltersHtml = "";
	$filter_index = 0;

	// edit
	if ($_GET['cmd'] == COLLECTIONS_ACTION_EDIT && isset($_GET['id']) && !empty($_GET['id'])) {

		$_editCollectionId = $_GET['id'];
		$_editCollection = getCollection($_editCollectionId);
		if (!is_null($_editCollection)) {
			$_editCollectionName = $_editCollection['title'];

			foreach($_editCollection["flatlist_filters"] as $flatlist_filter) {
				$_collectionFiltersHtml .= GetExistingFilterControls($filter_index, $flatlist_filter["filter"], $flatlist_filter["str"], true);
				$filter_index++;
			}
		} else {
			// error, collection not found
			$tpl = 'collections-config.html';
		}
	}
	// create
	elseif ($_GET['cmd'] == COLLECTIONS_ACTION_ADD) {
		$_hide_remove = 'hide';
		$_editCollectionId = "";
		$_editCollectionName = "New Collection";
	}

	$_collectionFiltersHtml .= GetAddFilterControls();
}

function GetFilterOption($optionText, $optionValue, $selectedValue)
{
	return '<option value="' . $optionValue. '" '. ($optionValue == $selectedValue ? 'selected' : '' ) .'>'. $optionText .'</option>';
}

function GetFilterOptions($selectedValue)
{
	$options = "";
	$options .= GetFilterOption("-- Raw MPD Search --", "tags", $selectedValue);
	$options .= GetFilterOption("-- Any --", "any", $selectedValue);
	$options .= GetFilterOption("Album", "album", $selectedValue);
	$options .= GetFilterOption("Album Artist", "albumartist", $selectedValue);
	$options .= GetFilterOption("Artist", "artist", $selectedValue);
	$options .= GetFilterOption("Folder/File name", "folder", $selectedValue);
	$options .= GetFilterOption("Genre", "genre", $selectedValue);
	$options .= GetFilterOption("Title", "title", $selectedValue);
	return $options;
}

function GetExistingFilterControls($filterIndex, $selectedFilter, $filterContent) {
	$controls = '<div class="control-group" id="collection-filter-controls-' . $filterIndex . '">';
	$controls .= '<select id="collection-filter-'.$filterIndex.'" name="collection-filter['.$filterIndex.']" class="input-large">';
	$controls .= GetFilterOptions($selectedFilter);
	$controls .= '</select>';
	$controls .= '<input class="input-large" type="text" name="collection-filter-str['.$filterIndex.']" value="'. $filterContent .'">';
	$controls .= '<a class="collection-remove-filter" data-collection-filter="'.$filterIndex.'" href="#notarget"><button class="btn btn-small btn-primary">Remove</button></a>';
	$controls .= '</div>';
	return $controls;
}

function GetAddFilterControls() {
	$controls = '<div class="control-group" id="collection-filter-controls-add">';
	$controls .= '<a class="collection-add-filter" href="#notarget"><button class="btn btn-small btn-primary">Add</button></a>';
	$controls .= '</div>';
	return $controls;
}

playerSession('unlock');

$section = basename(__FILE__, '.php');
storeBackLink($section, $tpl);

include('header.php');
eval("echoTemplate(\"".getTemplate("templates/$tpl")."\");");
include('footer.php');
