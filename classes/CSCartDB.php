<?php
namespace csc2wc;

/*
 * CSCart2WooCommerce
 * CSCartDB
 *
 * Description - Database query handlers for the CS Cart DB Tables
 *
 * Author:      John Arnold <john@jdacsolutions.com>
 * Link:           https://jdacsolutions.com
 *
 * Created:             Apr 30, 2018 9:27:14 PM
 * Last Updated:    Date 
 * Copyright            Copyright 2018 JDAC Computing Solutions All Rights Reserved
 */

/**
 * Description of CSCartDB
 *
 * @author John Arnold <john@jdacsolutions.com>
 */
class CSCartDB {
    
    private $csCartDB;
    
    public function __construct($dbHandle) {
        $this->csCartDB = $dbHandle;
    }
    
    /**
     *  Get all CS Cart Product Categories and their taxonomies 
     * @return array
     */
    function getCSCartCats() {
        $catRows = array();
        $query = "select a.category_id as cat_id, a.parent_id as parent_id, b.category as cat_name, b.meta_description as cat_desc from cscart_categories a, cscart_category_descriptions b " .
            "where a.category_id = b.category_id order by a.category_id ASC";
        $res = mysqli_query($this->csCartDB, $query);
        if ($res) {
           while ($catRow = mysqli_fetch_assoc($res)) {
               array_push($catRows, $catRow);
           }
        }
        return $catRows;
    }
    
    /**
     *  Get a product category name
     * @param type $catId
     * @return string
     */
    public function getCatName($catId = 0) {
        if (!$catId == 0) {
            $query = "select category_id, category from cscart_category_descriptions where category_id = " . $catId;
            $res = mysqli_query($this->csCartDB, $query);
            if ($res) {
                $resultRow = mysqli_fetch_assoc($res);
                return $resultRow['category'];
            }
        }
        return '';
    }
    
    /**
     *  Get all product ids in the CS Cart product inventory
     * @return array
     */
    public function getCSCartProdIds() {
        if (!$this->csCartDB) {
            $this->csCartDB = db_connect('CSCART');
        }
        $query = "select product_id from cscart_products order by product_id ASC";
        $idArray = array();
        $res = mysqlI_query($this->csCartDB, $query);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                array_push($idArray, $row['product_id']);
            }
        } 
        return $idArray;
    }
    
    /**
     *  Get all product related information for the product id from CS Cart
     * @param type $prodID
     * @return array
     */
    public function getCSCartProd($prodID) {
        // Don't do anything for a 0 product ID
        if (!$prodID == 0) {
            // Get all the product information from cd CSCart Tables
            $query = "select b.product_id as `ID`, 'simple' as `Type`, null as `SKU`, a.product as `Name`, 1 as `Published`, 0 as `Featured`, 'visible' as `visibility`,
	a.short_description as `Short-Desc`, replace(replace(a.full_description, char(10), ''), char(13), '') as `Description`, a.meta_description as `Meta-Desc`, null as `Date-Sale-Price-Starts`, null as `Date-Sale-Price-Ends`,
	'taxable' as `Tax-Status`, null as `Tax-Class`, 1 as `In-Stock`, null as `Stock`, 0 as `Backorder`, 0 as `Sold-Individually`, 
	b.weight as `Weight`, b.length as `Length`, b.width as `Width`, b.height as `Height`, 1 as `Allow-Customer-Review`, null as `Purchase-Note`,
	null as `Sale-Price`, b.list_price as `Regular-Price`, d.id_path as `Categories`, null as `Tags`, null as `Shipping-Class`,
	null as `Images`, null as `Download-Limit`, null as `Download-Expiry-Days`, null as `Parent`, null as `Grouped-Products`,
	null as `Upsells`, null as `Cross-Sell`, null as `External-URL`, null as `Button-Text`, 0 as `Position`, null as `Meta: _customize_changeset_uuid`,
	null as `Meta: _wpas_done_all` " .
                     " from cscart_product_descriptions a, cscart_products b, cscart_products_categories c, cscart_categories d " .
                    " where (a.product_id =  b.product_id) and (a.product_id = c.product_id) and (a.product_id = " . $prodID . ") and (d.category_id = c.category_id)";
            $res = mysqli_query($this->csCartDB, $query);
            if ($res) {
                return mysqli_fetch_assoc($res);
            } else {
                echo "Coult not retrieve row\n";
                return array();
            }
        }
        
    }
    
    /**
     *  Get the product image information from CS Cart including image_id, image filename, and product id that is referencing
     *  this image
     * @param int $product_id
     * @returns array
     */
    public function getProductImageData($product_id) {
        $query = "select a.object_id as product_id, a.object_type, a.image_id, b.image_path, b.image_x, b.image_y from cscart_images_links a, cscart_images b
                            where a.object_type = 'product' and (a.detailed_id = b.image_id) and (a.object_id = " . $product_id . ")";
        $res = mysqli_query($this->csCartDB, $query);
        if ($res) {
            return mysqli_fetch_assoc($res);
        } else {
            return array();
        }

    }
    
}
