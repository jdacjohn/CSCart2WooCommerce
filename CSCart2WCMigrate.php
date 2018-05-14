<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require( 'vendor/autoload.php');
require('_includes/app.db.php');
include('classes/CSCartDB.php');
include('classes/WooCommerceDB.php');
include('classes/CSCartWCCats.php');
include('classes/CSCartWCProds.php');
include('classes/WPImageMeta.php');

$dotenv = new Dotenv\Dotenv('./');
$dotenv->load();

if (!$csDBConn = db_connect('CSCART')) {
    echo "Could not connect to CSCart DB\n";
    die();
} else {
    echo "Successfully connected to the CSCart DB\n";    
    if (!$wcDBConn = db_connect('WC')) {
        echo "Could not connect to WooCommerce DB\n";
        die();
    } else {
        echo "Successfully connected to the WooCommerce DB\n";
        // Do all your stuff here then close both DB connections when finished.
        // Set up the DB interface objects for the migration
        $csCartDB = new csc2wc\CSCartDB($csDBConn);
        $wcDB = new csc2wc\WooCommerceDB($wcDBConn);
        // Instantiate the category migrator
        $catMover = new csc2wc\CSCartWCCats($csCartDB, $wcDB, 'Hercules');
        //$catMover->migrateCategories();
        
        $prodMover = new csc2wc\CSCartWCProds($csCartDB, $wcDB);
        $prodMover->migrateProducts('Hercules');
        //$prodMover->migrateProduct(10002, 'Hercules');
        
        mysqli_close($csDBConn);
        mysqli_close($wcDBConn);
    }
    
}



