<?php

/*
 * CSCart2WooCommerce
 * CSCartWCProds
 *
 * Description - Mrgrates products from the  CS Cart to the WooCommerce DB tables in WordPress.
 *
 * Author:      John Arnold <john@jdacsolutions.com>
 * Link:           https://jdacsolutions.com
 *
 * Created:             May 1, 2018 3:00:37 PM
 * Last Updated:    Date 
 * Copyright            Copyright 2018 JDAC Computing Solutions All Rights Reserved
 */

namespace csc2wc;

/**
 * Description of CSCartWCProds
 *
 * @author John Arnold <john@jdacsolutions.com>
 */
class CSCartWCProds {
    private $cscDB;
    private $wcDB;
    
    public function __construct($csCartDB, $wcCartDB) {
        $this->cscDB = $csCartDB;
        $this->wcDB = $wcCartDB;
    }
    
    public function migrateProducts($wpTopCatNode = '') {
        // Dont do anything on a 0 product ID
        if (!$this->cscDB) {
            $this->cscDB = db_connect('CSCART');
        }
            $prodIds = $this->cscDB->getCSCartProdIds();
            $totalProdsMigrated = 0;
            foreach ($prodIds as $key => $value) {
                //if ($value < 10010) {
                    echo "Old Prod ID: " . $value . "\n";
                    $this->migrateProduct($value, $wpTopCatNode);                    
                //}
                $totalProdsMigrated++;
            }
            return $totalProdsMigrated;
        }
    
    

    public function migrateProduct($prodID = 0, $wpTopCatNode = '') {
        // Dont do anything on a 0 product ID
        if (!$prodID == 0) {
             if (!$this->cscDB) {
                $this->cscDB = db_connect('CSCART');
            }
            if (!$this->wcDB) {
                $this->wcDB = db_connect('WC');
            }
            $csCartProdRow = $this->cscDB->getCSCartProd($prodID);
            foreach ($csCartProdRow as $key => $value) {
                echo $key . " => " . $value . "\n";
            }
            $newPostId = $this->wcDB->insertProduct($csCartProdRow);
            if ($newPostId > 0) {
                // Product Post and Post Meta were inserted correctly.  Now we need to straighten out the
                // Category issue.
                $prodCatTree = $this->crawlCatTree($csCartProdRow['Categories']);
                $leafNode = $this->wcDB->fillLeafCategoryIds($prodCatTree,$newPostId, $wpTopCatNode);
                echo "Product Category is: " . $leafNode . "\n";
                // Now get the product image information from CS Cart and build the new post (if necessary) for the
                // image and tie the newly inserted product to the image.
                $prodImage = $this->cscDB->getProductImageData($csCartProdRow['ID']);
                // Need to split the filename to the format we know WP uses for post-names of type 'attachment' that are images
                $tempPostName = implode(explode(".", $prodImage['image_path'], -1)) . "-image";
                echo "Temp Post Name = " . $tempPostName . "\n";
                $postImageId = $this->wcDB->findImagePostWithName($tempPostName);
                echo "Returned postImageId from findImagePostWithName = " . $postImageId . "\n";
                if (!$postImageId > 0) {
                    // The image has NOT been created as an attachment type post in the WP tables so we need to create it and then
                    // Tie the product to it.
                    print_r($prodImage);
                    print_r($tempPostName);
                    $newImagePostId = $this->wcDB->insertImage($prodImage, $tempPostName);
                    echo "\nNew Image Post ID = " . $newImagePostId;
                    // Now tie the new image post to the product post
                    $newMetaId = $this->wcDB->tieImageToPost($newImagePostId, $newPostId);
                    echo "\nStopping for Lunch\n";
                } else {
                    $newMetaId = $this->wcDB->tieImageToPost($postImageId, $newPostId);
                    echo "\nStopping for Lunch\n";
                }
                // Tie the product to the image
                
            } else {
                echo "Count not insert new product\n";
            }
            
        }
    }
    
    private function crawlCatTree($catTreeStr = '') {
        if (!$catTreeStr == '') {
            $catTree = explode("/", $catTreeStr);
            $prodCatTree = array();
            for ($i = 0; $i < count($catTree); $i++) {
                $catName = $this->cscDB->getCatName($catTree[$i]);
                $catLevel = array( 'catId' => $catTree[$i], 'catName' => $catName);
                array_push($prodCatTree, $catLevel);
                echo "Cat[" . $i . "] = " . $catTree[$i] . " = " . $catName .  "\n";
            }
            return $prodCatTree;
        } else {
            return array();
        }
            
    }
    
}
