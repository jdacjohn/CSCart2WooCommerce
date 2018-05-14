<?php

/*
 * CSCart2WooCommerce
 * CSCartWCCats
 *
 * Description - A utility class to pull CS cart Categories from a CS Cart DB instance and insert them into a WP WooCommerce
 *  instance.
 *
 * Author:      John Arnold <john@jdacsolutions.com>
 * Link:           https://jdacsolutions.com
 *
 * Created:             Apr 30, 2018 
 * Last Updated:    Date 
 * Copyright            Copyright 2018 JDAC Computing Solutions All Rights Reserved
 */

namespace csc2wc;
use csc2wc\CSCartDB;
use csc2wc\WooCommerceDB;

/**
 * Description of CSCartWCCats
 *
 * @author John Arnold <john@jdacsolutions.com>
 */
class CSCartWCCats {

    private $cscDB;
    private $wcDB;
    private $catIDMap = array();
    private $masterCat = '';
    
    
    public function __construct($csCartDB, $wcCartDB, $newMasterCat = '') {
        $this->cscDB = $csCartDB;
        $this->wcDB = $wcCartDB;
        $this->masterCat = $newMasterCat;
    }
    
    /**
     *  Migrate all old CSCart product categories and their taxonomies to the WooCommerce / WP wp_term structures.
     */
    public function migrateCategories() {
        // Get all of the categories from the CSCart database cscart_categories and cscart_category_descriptions tables.  We 
        // don't really need a lot of information from these tables, just the category id, the parent id, category name, and description.     
        
        // Before we do anything, check to see if we're creating a new master parent cat.  If we are, insert it into the wp_terms table and store
        // the id maps as 0 => newID
        // insertProductCategory() args are:  $name, $slug, $parent, $description
        If (!$this->masterCat == '') {
            $insertId = $this->wcDB->insertProductCategory($this->masterCat, strtolower(str_replace(' ', '-', $this->masterCat)), 0, '');
            $this->mapId($insertId, 0);
        }
        $catRows = $this->cscDB->getCSCartCats();
        $noCats = 0;
        // Now move the CSCart Categories one at a time to the WooCommerce instance WP tables
        foreach ($catRows as $catRow) {
            // 1. Insert a new row to the target db table wp_terms with values (null (auto inc), name, slug, 0);  Have the function to insert
            // return the new insert id to be used in the next step.
            $noCats++;
            $snip = str_replace("\t", '', $catRow['cat_desc']);; // remove CRs
            $snip = str_replace("\n", '', $catRow['cat_desc']); // remove new lines
            $insertId = $this->wcDB->insertProductCategory($catRow['cat_name'] , strtolower(str_replace(' ', '-', $catRow['cat_name'] )), $catRow['parent_id'], $snip);
            $this->mapId($insertId, $catRow['cat_id']);
            echo "Cat ID:  " . $catRow['cat_id'] . "  Parent:  " . $catRow['parent_id'] . "  Name:  " . $catRow['cat_name'] . "  DESC:  " . $snip . "\n";            
        }   
        
        echo "Total Number of Categories Transferred = " . $noCats . "\n\n";
        
        foreach ($this->catIDMap as $key => $value) {
            echo "CatMap Entry: " . $key . " => [newID] => " . $value['newID']  . " [oldID] => " . $value['oldID'] . "\n";
        }
        // Update the parent ids with the new cat ids stored in the catIDMap inst var.
        echo "Remapping categorie taxonomies...\n\n";
        foreach ($catRows as $catRow) {
            // Find the new id for the prod cat that was inserted above
            $termId = $this->findNewId($catRow['cat_id']);
            // Get the new parent id
            $newParentId = $this->findNewId($catRow['parent_id']);
            $this->wcDB->updateCategoryParent($termId, $newParentId);
        }
  }
    
    private function mapId($newId, $oldId) {
        $mapArr = array( 'newID' => $newId, 'oldID' => $oldId);
        array_push($this->catIDMap, $mapArr);
    }
    
    private function findNewId($oldId) {
        $newId = 0;
        foreach ($this->catIDMap as $key => $value) {
            if ($value['oldID'] == $oldId) {
                $newId = $value['newID'];
                break;
            }
        }
        return $newId;
    }
    
}
