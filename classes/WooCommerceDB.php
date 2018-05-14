<?php
namespace csc2wc;
/*
 * CSCart2WooCommerce
 * WooCommerceDB
 *
 * Description - Enter a description of the file and its purpose.
 *
 * Author:      John Arnold <john@jdacsolutions.com>
 * Link:           https://jdacsolutions.com
 *
 * Created:             Apr 30, 2018 9:28:19 PM
 * Last Updated:    Date 
 * Copyright            Copyright 2018 JDAC Computing Solutions All Rights Reserved
 */


/**
 * Description of WooCommerceDB
 *
 * @author John Arnold <john@jdacsolutions.com>
 */
class WooCommerceDB {

    private $wcDB;
    private $wpPostMetaDataArr = array(
        '_wpas_done_all' => '1',
        '_sku' => '',
        '_regular_price' => '',
        '_sale_price' => '',
        '_sale_price_dates_from' => '',
        '_sale_price_dates_to' => '',
        'total_sales' => '0',
        '_tax_status' => '',
        '_tax_class' => '',
        '_manage_stock' =>  'no',
        '_backorders' => '',
        '_sold_individually' => '',
        '_weight' => '',
        '_length' => '',
        '_width' => '',
        '_height' => '',
        '_upsell_ids' => 'a:0:{}',
        '_crosssell_ids' => 'a:0:{}',
        '_purchase_note' => '',
        '_default_attributes' => 'a:0:{}',
        '_virtual' => 'no',
        '_downloadable' => 'no',
        '_product_image_gallery' => '',
        '_download_limit' => '0', 
        '_download_expiry' => '0',
        '_stock' => '',
        '_stock_status' => 'instock',
        '_wc_average_rating' => '0',
        '_wc_rating_count' => 'a:0:{}',
        '_wc_review_count' => '0',
        '_downloadable_files' => 'a:0"{}',
        '_product_attributes' => 'a:0:{}',
        '_product_version' => '3.3.5',
        '_price' => '0.0',
        '_custom_changeset_uuid' => '',
        '_wpas_done_all' => '',
        '_edit_lock' => ''
    );
    
    public function __construct($dbHandle) {
        $this->wcDB = $dbHandle;
    }
    
    /**
     *  Insert a new product category into the WooCommerce wp_term and related tables.
     * @param type $name
     * @param type $slug
     * @param type $parent
     * @param type $description
     * @return type
     */
    public function insertProductCategory($name = '', $slug = '', $parent = 0, $description = '') {
        $newId = 0;
        if (!$name == '') {
            // insert the prod cat into the WP_TERMS
            $insert = "insert into " . getenv('WP_TABLE_PREFIX') ."_terms (`name`, `slug`) values('" . $name . "', '" . $slug . "')";
            $res = mysqli_query($this->wcDB, $insert);
            if ($res) {
                // Save the new id, we're going to need it.
                $newId = mysqli_insert_id($this->wcDB);
                // now insert the taxonomy table
                $insert2 = "insert into " . getenv('WP_TABLE_PREFIX') ."_term_taxonomy (`term_id`, `taxonomy`, `description`, `parent`) values(" . $newId . ", 'product_cat', '" . mysqli_real_escape_string($this->wcDB, $description) . "', " . $parent . ")";
                $res2 = mysqli_query($this->wcDB, $insert2);
                if (!$res2) {
                    echo "Error occurred inserting new taxomony.\n";
                    echo "Offending Statement = " . $insert2 . "\n";
                    die();
                }
                $insert3 = "insert into " . getenv('WP_TABLE_PREFIX') ."_termmeta (`term_id`, `meta_key`, `meta_value`) values(" . $newId . ", 'product_count_product_cat', 0)";
                mysqli_query($this->wcDB, $insert3);
                $insert3 = "insert into " . getenv('WP_TABLE_PREFIX') ."_termmeta (`term_id`, `meta_key`, `meta_value`) values(" . $newId . ", 'order', 0)";
                mysqli_query($this->wcDB, $insert3);
                $insert3 = "insert into " . getenv('WP_TABLE_PREFIX') ."_termmeta (`term_id`, `meta_key`, `meta_value`) values(" . $newId . ", 'display_type', '')";
                mysqli_query($this->wcDB, $insert3);
                $insert3 = "insert into " . getenv('WP_TABLE_PREFIX') ."_termmeta (`term_id`, `meta_key`, `meta_value`) values(" . $newId . ", 'thumbnail_id', 0)";
                mysqli_query($this->wcDB, $insert3);
            }
        }
        return $newId;
    }
    
    /**
     *  Pretty self-explanatory
     * @param type $termId
     * @param type $parentId
     */
    public function updateCategoryParent($termId = 0, $parentId = 0) {
        if (!$termId == 0) {
            // First we need to get the parent name because we're going to have to create a new slug
            // If we have more than one child with this name.
            $parentCat = $this->getCategory($parentId);
            $childCat = $this->getCategory($termId);
            if ($this->getCategoryCount($childCat['name']) > 1) {
                // update the slug on the child cat with the 'parent-child' notation
                $newSlug = str_replace(array(".", "/", " "), "-", strtolower($parentCat['name'])) . "-" . str_replace(array(".", "/", " "), "-", strtolower($childCat['name']));
                $fixit = "update " . getenv('WP_TABLE_PREFIX') ."_terms set slug = '" . $newSlug . "' where term_id = " . $childCat['term_id'];
                $res = mysqli_query($this->wcDB,$fixit);
                if (!$res) {
                    echo "Could not update slug";
                }
            }
            $update = "update " . getenv('WP_TABLE_PREFIX') ."_term_taxonomy set parent = " . $parentId . " where term_id = " . $termId;
            mysqli_query($this->wcDB, $update);
        }
    }
    
    private function getCategory($catId) {
        $query = "select term_id, name, slug from " . getenv('WP_TABLE_PREFIX') ."_terms where term_id = " . $catId;
        $res = mysqli_query($this->wcDB, $query);
        if ($res) {
            return mysqli_fetch_assoc($res);
        }        
    }
    
    private function getCategoryCount($catName) {
        $query = "select * from " . getenv('WP_TABLE_PREFIX') ."_terms where name = '" . $catName . "'";
        $res = mysqli_query($this->wcDB, $query);
        return mysqli_num_rows($res);
    }
    
    /**
     *  Return the category matching the name parameter that does NOT have a parent.
     * @param type $catName
     * @return type
     */
    public function getTopCat($catName) {
        $query = "select a.name as cat_name, a.term_id as term_id, b.term_taxonomy_id as taxonomy_id from " . getenv('WP_TABLE_PREFIX') ."_terms a, " . getenv('WP_TABLE_PREFIX') ."_term_taxonomy b where a.name = '" . $catName . "' and (a.term_id = b.term_id) and (b.parent = 0)";
        $res = mysqli_query($this->wcDB, $query);
        if ($res) {
            $catRow = mysqli_fetch_assoc($res);
            $newCat = array( 'catId' => $catRow['term_id'], 'catName' => $catName, 'taxonomyId' => $catRow['taxonomy_id']);
            return $newCat;
        } else {
            return array();
        }
    }
    
    /**
     *  Pretty self explanatory
     * @param type $catId
     * @return array
     */
    public function getChildCategories($catId) {
        $query = "select a.name as cat_name, b.term_id as term_id, b.term_taxonomy_id as taxonomy_id from " . getenv('WP_TABLE_PREFIX') ."_terms a, " . getenv('WP_TABLE_PREFIX') ."_term_taxonomy b where b.parent = " . $catId . " and a.term_id = b.term_id";
        $res = mysqli_query($this->wcDB, $query);
        $cats = array();
        if ($res) {
            while ($catRow = mysqli_fetch_assoc($res)) {
                $cat = array('catId' => $catRow['term_id'], 'catName' => $catRow['cat_name'], 'taxonomyId' => $catRow['taxonomy_id']);
                array_push($cats, $cat);
            }
        } 
        return $cats;
    }
    
    /**
     *  This function 'walks' the old CSCart category id_path and matches it to the new WC categories in the wp_terms table,
     *  and then inserts a new relationship between the post (the product) and the category.
     * @param type $catsArray
     * @param type $postId
     * @param type $newTopNode
     * @return type
     */
    public function fillLeafCategoryIds($catsArray, $postId, $newTopNode = '') {
        // Get id and name of new top category node if this migration placed all of the migrated categories / sub-categories under
        // a new master category.
        $leafNode = 0;
        if (!$newTopNode == '') {
            $topCat = $this->getTopCat($newTopNode);
            array_unshift($catsArray, $topCat);
        } else {
            $topCat = $this->getTopCat($catsArray[0]['catName']);
            $catsArray[0]['catId'] = $topCat['catId'];
            $catsArray[0]['taxonomyId'] = $topCat['taxonomyId'];
        }
        foreach ($catsArray as $value) {
            echo "Cat ID = " . $value['catId'] . "  Cat NAME = " . $value['catName'] . "\n";
        }
        // Create the first taxonomy relationships - one for product type and one for the top category in the array
        $this->insertTaxonomyRelationship($postId, 2,0);
        $this->insertTaxonomyRelationship($postId, $catsArray[0]['taxonomyId'], 0);
        $termOrder = 0;
        for ($i = 0; $i < count($catsArray) - 1; $i++) {
            // Get the id of this level category and find all it's children categories.
            // from those, find the id of  of the next level category.
            $currentId = $catsArray[$i]['catId'];
            $termOrder++;
            foreach ($this->getChildCategories($currentId) as $newCat) {
                echo "WC Child Cat = " . $newCat['catName'] . "  ID = " . $newCat['catId'] . "  :  Current Cat Name = " . $catsArray[$i+1]['catName'] . "\n";
                if ($newCat['catName'] == $catsArray[$i+1]['catName']) {
                    $leafNode = $newCat['catId'];
                    $catsArray[$i + 1]['catId'] = $leafNode;
                    $this->insertTaxonomyRelationship($postId, $newCat['taxonomyId'], $termOrder);
                }
            }
        }
        return $leafNode;    
    }
        
   /**
    *  This ties products to categories
    * @param type $postId
    * @param type $taxonomyId
    * @param type $termOrder 
    */
    private function insertTaxonomyRelationship($postId, $taxonomyId, $termOrder) {
       $stmt = "insert into " . getenv('WP_TABLE_PREFIX') ."_term_relationships (`object_id`, `term_taxonomy_id`, `term_order`) values(" . $postId . ", " . $taxonomyId . ", " . $termOrder . ")";
       $res = mysqli_query($this->wcDB, $stmt);
       if (!$res) {
           echo "Could not insert term relationship.\n";
       }
   }
    
    public function insertProduct($prodArr) {
     
        $insertId = $this->insertWP_POST($prodArr);
        if ($insertId > 0) {
            $this->insertWP_POSTMETA($prodArr, $insertId);
        }
        return $insertId;        
    }
    
    public function insertImage($imageArr, $postName) {
     
        $insertId = $this->insertWP_IMAGE_POST($imageArr, $postName);
        if ($insertId > 0) {
           $this->insertWP_IMAGE_POSTMETA($imageArr, $insertId);
        }
        return $insertId;        
    }
    
    public function tieImageToPost($imagePostId, $productPostId) {
        $insert = "insert into " . getenv('WP_TABLE_PREFIX') ."_postmeta (`post_id`, `meta_key`, `meta_value`) values(". $productPostId . ", '_thumbnail_id', '" . $imagePostId . "') ";
        $res = mysqli_query($this->wcDB, $insert);
        if ($res) {
            return mysqli_insert_id($this->wcDB);
        } else {
            return 0;
        }
    }
    
    private function insertWP_IMAGE_POST($prodImageArr, $postName) {
        // 1st step of a product migration from CSCart.  Insert the post.
        // Do nothing for emplty array
        If (!count($prodImageArr) == 0) {
            $fileExt = explode(".", $prodImageArr['image_path'])[1];
            $mimeType = ($fileExt == 'jpg') ? 'image/jpeg' : ('image/' . $fileExt);
            $insert = "insert into " . getenv('WP_TABLE_PREFIX') ."_posts (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, " .
                "`post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, " . 
                "`post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) " .
                
                "values(1, now(), now(), '', '" . explode(".", $prodImageArr['image_path'])[0] . "', '', 'inherit', 'open', 'closed', '', '" . $postName . "', '', '', now(), now(), '', 0, '" . getenv('IMAGE_MIG_FOLDER') . '/' . $prodImageArr['image_path'] .
                    "', 0, 'attachment', '" . $mimeType . "', 0)";
            
            $res = mysqli_query($this->wcDB, $insert);
            if ($res) {
                $newId = mysqli_insert_id($this->wcDB);
                return $newId;
            } else {
                echo "Image Post could not be inserted.\n\n";
                echo $insert . "\n\n";
                return 0;
            }
        }   
    }
    
    private function insertWP_POST($prodArr) {
        // 1st step of a product migration from CSCart.  Insert the post.
        // Do nothing for emplty array
        If (!count($prodArr) == 0) {
            $slug = str_replace(array(".", "/", " "), "-", strtolower($prodArr['Name']));
            $insert = "insert into " . getenv('WP_TABLE_PREFIX') ."_posts (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, " .
                "`post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, " . 
                "`post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) " .
                "values(1, now(), now(), '" . mysqli_real_escape_string($this->wcDB, $prodArr['Description']) . "', '" . $prodArr['Name'] . "', '" . $prodArr['Short-Desc'] . "', " .
                " 'publish', 'open', 'closed', '', '" . $slug . "', '', '', now(), now(), '', 0, '" . (getenv('DOMAIN') . '/product/' . $slug) . "', 0, 'product', '', 0)";
            
            $res = mysqli_query($this->wcDB, $insert);
            if ($res) {
                $newId = mysqli_insert_id($this->wcDB);
                return $newId;
            } else {
                echo "product could not be inserted.\n\n";
                echo $insert . "\n\n";
                return 0;
            }
        }   
    }
    
    /**
     *  Insert new post meta for a newly inserted product
     * @param type $prodArr
     * @param type $prodID
     */
    private function insertWP_POSTMETA($prodArr, $prodID) {
        $this->loadPostMetaData($prodArr);
        foreach ($this->wpPostMetaDataArr as $key => $value) {
            $insert = "insert into " . getenv('WP_TABLE_PREFIX') ."_postmeta (`post_id`, `meta_key`, `meta_value`) values(" . $prodID . ", '" . $key . "', '" . $value . "')";
            $res = mysqli_query($this->wcDB, $insert);
            if (!$res) {
                echo "Coult not insert post meta data into wp_postmeta for key value " . $key . "\n";
            }
        }
        $this->reinitPostMetaData();
    }
    
    /**
     *  Insert new post meta for a newly inserted product
     * @param type $prodArr
     * @param type $prodID
     */
    private function insertWP_IMAGE_POSTMETA($imageArr, $postID) {
        // Insert the '_wp_attached_file' meta data to the wp_postmeta table
        $insertFileAttach = "insert into " . getenv('WP_TABLE_PREFIX') ."_postmeta (`post_id`, `meta_key`, `meta_value`) " .
            "values(" . $postID . ", '_wp_attached_file', '" . "ccart_migrate/" . $imageArr['image_path'] . "')";
        //echo "Insert statement:  " . $insertFileAttach . "\n";
        $res = mysqli_query($this->wcDB, $insertFileAttach);
        
        // insert the very whacky attachment meta data
        //$imageAttachMeta = $this->buildAttachMetaData($imageArr);
        //$insertMeta = "insert into wp_postmeta (`post_id`, `meta_key`, `meta_value`) " .
        //    "values(" . $postID . ", '_wp_attachment_metadata', '" . $imageAttachMeta . "') ";
        //$res = mysqli_query($this->wcDB, $insertMeta);
        
        $insertStarter = "insert into " . getenv('WP_TABLE_PREFIX') ."_postmeta (`post_id`, `meta_key`, `meta_value`) values(" . $postID . ", '_starter_content_theme', 'storefront') ";
        $res = mysqli_query($this->wcDB, $insertStarter);
    }
    
    private function buildAttachMetaData($imgArr) {
        $wpImageMeta = new WPImageMeta('ccart_migrate', $imgArr);
        return $wpImageMeta->getMetaDataString();
    }
    
    
    private function loadPostMetaData($prodArr) {
        $this->wpPostMetaDataArr['_regular_price'] = $prodArr['Regular-Price'];
        $this->wpPostMetaDataArr['_tax_status'] = $prodArr['Tax-Status'];
        $this->wpPostMetaDataArr['_tax_class'] = $prodArr['Tax-Class'];
        $this->wpPostMetaDataArr['_backorders'] = $prodArr['Backorder'];
        $this->wpPostMetaDataArr['_sold_individually'] = $prodArr['Sold-Individually'];
        $this->wpPostMetaDataArr['_weight'] = $prodArr['Weight'];
        $this->wpPostMetaDataArr['_length'] = $prodArr['Length'];
        $this->wpPostMetaDataArr['_width'] = $prodArr['Width'];
        $this->wpPostMetaDataArr['_height'] = $prodArr['Height'];
        $this->wpPostMetaDataArr['_purchase_note'] = $prodArr['Purchase-Note'];
         $this->wpPostMetaDataArr['_price'] = $prodArr['Regular-Price'];

    }

    private function reinitPostMetaData() {
        $this->wpPostMetaDataArr['_regular_price'] = '';
        $this->wpPostMetaDataArr['_tax_status'] = '';
        $this->wpPostMetaDataArr['_tax_class'] = '';
        $this->wpPostMetaDataArr['_backorders'] = '';
        $this->wpPostMetaDataArr['_sold_individually'] = '';
        $this->wpPostMetaDataArr['_weight'] = '';
        $this->wpPostMetaDataArr['_length'] = '';
        $this->wpPostMetaDataArr['_width'] = '';
        $this->wpPostMetaDataArr['_height'] = '';
        $this->wpPostMetaDataArr['_purchase_note'] = '';
         $this->wpPostMetaDataArr['_price'] = '';

    }
    
    /**
     *  Attempt to locate a wp_post entry of type image where post_name matches the parameter
     * @param type $postName
     */
    public function findImagePostWithName($postName = '') {
        if (!$postName == '') {
            $query = "select ID from " . getenv('WP_TABLE_PREFIX') ."_posts where post_name = '" . $postName . "'";
            $res = mysqli_query($this->wcDB, $query);
            if ($res) {
                return mysqli_fetch_assoc($res)['ID'];
            }
        }
        return 0;
    }
    
    /**
     *  Insert a post meta entry on '_thumbnail' for the product post using the imagePost ID
     * @param int $productPost
     * @param int $imagePost
     */
    public function associateProductPostToImagePost($productPost, $imagePost) {
        
    }
    
}
    

