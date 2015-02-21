<?php
/**
 * kslistclasses module definition.
 *
 * @copyright Copyright (C) 2015 Kamil Szymański. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version 0.1.0
 * @package kpmultiupload
 */
$Module = array(
    'name' => 'kslistclasses',
);

$ViewList         = array();
$ViewList['list'] = array(
    'script'           => 'list.php',
    'unordered_params' => array(
        'id'     => 'id',
        'page' => 'page',
        'sort'   => 'sort',
        'order'  => 'order',
    ),
    'functions'        => array(
        'admin',
    ),
);

$FunctionList          = array();
$FunctionList['admin'] = array();
