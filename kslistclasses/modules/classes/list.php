<?php
/**
 * KpPwnReviewForm review module.
 *
 * @copyright Copyright (C) 2015 Kaliop Poland. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version 1.0.0
 * @package kpmultiupload
 */
$http         = eZHTTPTool::instance();
$tpl          = eZTemplate::factory();
$module       = $Params['Module'];
$classId      = $Params['id'];
$currentPage  = $Params['page'];
$sortBy       = $Params['sort'];
$orderBy      = $Params['order'];
$error        = false;
$ezIni        = eZINI::instance('kslistclasses.ini');
$limit        = kslistclassesTableBuilder::DEFAULT_NO_OF_ENTRIES_PER_PAGE;
$currentPages = kslistclassesTableBuilder::DEFAULT_NO_OF_PAGINATION_PAGES;

$headers;
$offset;
$lastPage;

$sortingMethods = array(
    "relevance",
    "modified",
    "published",
    "author",
    "class_name",
    "class_id",
    "name",
    "path",
    "section_id",
);

$orderByDirection = array(
    "asc",
    "desc",
);

try {
    if (!is_numeric($classId)) {
        throw new Exception("To use this view you must provide a valid Class ID.");
    }

    $eZContentClassObject = eZContentClass::fetch($classId);
    if (!$eZContentClassObject) {
        throw new Exception("Class ID you provided does not exists in system.");
    }

    if ($currentPage && !is_numeric($currentPage)) {
        throw new Exception("Page must be a number grater or equal 0.");
    }

    if (!$currentPage) {
        $currentPage = 1;
    }

    if ($ezIni->hasVariable("Defaults", "FetchLimit")) {
        $limit = $ezIni->variable("Defaults", "FetchLimit");
    }

    if (!in_array(strtolower($orderBy), $orderByDirection)) {
        $orderBy = "asc";
    }

    if (!in_array(strtolower($sortBy), $sortingMethods)) {
        $sortBy = "path";
    }

    $db           = eZDB::instance();
    $query        = <<<EOT
        SELECT count(*) as count
        FROM  ezcontentobject ezc
        LEFT JOIN ezcontentclass ezcc ON ezc.contentclass_id = ezcc.id
        LEFT JOIN ezcontentobject_tree ezt ON ezc.id = ezt.contentobject_id
        WHERE ezc.contentclass_id = "{$classId}" and ezt.contentobject_id IS NOT NULL;
EOT;
    $result       = $db->arrayQuery($query);
    $objectsCount = $result[0]["count"];
    $lastPage     = ceil($objectsCount / $limit);

    if ($currentPage > $lastPage) {
        $request = filter_input(INPUT_SERVER, "REQUEST_URI", FILTER_SANITIZE_STRING);
        $path    = preg_replace("/page\/(\d+)/", "page/{$lastPage}", $request);
        eZHTTPTool::redirect($path);
        eZExecution::cleanExit();
    }
} catch (Exception $ex) {
    $error = $ex->getMessage();
}

$tableBuilder = new kslistclassesTableBuilder($classId, $currentPage, $sortBy, $orderBy, $objectsCount, $limit);

$headers = $tableBuilder->getTableHeaders();

$pagination  = $tableBuilder->getPaginationArray();
$definition  = eZContentClassName::definition();
$classObject = eZPersistentObject::fetchObject($definition, null, array(
        'contentclass_id' => $classId,
        )
);
$className   = $classObject->Name;
$offset = $currentPage * $limit - $limit;

$tpl->setVariable('error', $error);
$tpl->setVariable('class_id', $classId);
$tpl->setVariable('offset', $offset);
$tpl->setVariable('sort_by', $sortBy);
$tpl->setVariable('order_by', $orderBy);
$tpl->setVariable('objects_count', $objectsCount);
$tpl->setVariable('class_name', $className);
$tpl->setVariable('limit', $limit);
$tpl->setVariable('headers', $headers);
$tpl->setVariable('pagination', $pagination);

$Result            = array();
$Result['content'] = $tpl->fetch('design:classes/list.tpl');

$Result['path'] = array(
    array(
        'text' => 'Classes / List',
        'url'  => '',
    ),
);
