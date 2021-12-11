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

playerSession('open');
$tpl = "";
$_collections = "";

if ($_GET['cmd'] == 'activate') {
	activateCollection($_GET['id']);
	unset($_GET['cmd']);
}

if ($_GET['cmd'] == 'add') {
	createCollection("New Collection");
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
		$_collections .= "<p><a href=\"collections-config.php?cmd=edit&id=" . $collection['id'] . "\" class='btn btn-large' style='width:240px;background-color:#333;text-align:left;'> " . $icon . " [" . $collection['id'] . "] " . $collection['title'] . "</a>";
		$_collections .= "<a href=\"collections-config.php?cmd=activate&id=" . $collection['id'] ."\"><button class=\"btn btn-medium btn-primary\">Activate</button></a>";
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
