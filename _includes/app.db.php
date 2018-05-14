<?php

/**
 *  Connect to the Application DB specified by the prefix and return a DB Connection Handle.  The DB Prefix is
 *  found in the .env file for the application.
 */
function db_connect($dbPrefix) {
    if (!getenv( $dbPrefix . '_SERVER')) {
        echo ('One or more environment variables failed assertions: DATABASE_DSN is missing');
    } elseif (!$dbConn = mysqli_connect(getenv($dbPrefix . '_SERVER'), getenv($dbPrefix . '_USERNAME'), getenv($dbPrefix . '_PASSWORD'), getenv($dbPrefix . '_DATABASE'))) {
        echo ("Could not connect to the database.  Please try again later.");
        return false;
    } else {
        return $dbConn;
    } 
}

function getCSCartCats($dbHandle) {
    $catRows = array();
    $query = "select a.category_id as cat_id, a.parent_id as parent_id, b.category as cat_name, b.meta_description as cat_desc from cscart_categories a, cscart_category_descriptions b " .
	"where a.category_id = b.category_id order by a.category_id ASC";
    $res = mysqli_query($dbHandle, $query);
    if ($res) {
       while ($catRow = mysqli_fetch_assoc($res)) {
           array_push($catRows, $catRow);
       }
    }
    return $catRows;
}





