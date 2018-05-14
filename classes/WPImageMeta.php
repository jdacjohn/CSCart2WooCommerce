<?php

/*
 * CSCart2WooCommerce
 * WPImageMeta
 *
 * Description - Enter a description of the file and its purpose.
 *
 * Author:      John Arnold <john@jdacsolutions.com>
 * Link:           https://jdacsolutions.com
 *
 * Created:             May 2, 2018 5:38:14 PM
 * Last Updated:    Date 
 * Copyright            Copyright 2018 JDAC Computing Solutions All Rights Reserved
 */

namespace csc2wc;

/**
 * Description of WPImageMeta
 *
 * @author John Arnold <john@jdacsolutions.com>
 */
class WPImageMeta {
    public $startPath = '';
    public $imageData = '';
    private $fileroot = '';
    private $filext = '';
    private $mimeType = '';
    
    public function __construct($startPath, $imgData) {
        $this->startPath = $startPath;
        $this->imageData = $imgData;
        $temp = explode(".", $imgData['image_path']);
        $this->fileroot = strtolower($temp[0]);
        $this->filext = strtolower($temp[1]);
        switch ($this->filext) {
            case 'png':
                $this->mimeType = "image/png";
                break;
            case 'jpeg':
                $this->mimeType = "image/jpeg";
                break;
            case 'jpg':
                $this->mimeType = "image/jpeg";
                break;
            case 'gif':
                $this->mimeType = "image/gif";
                break;
            case 'bmp':
                $this->mimeType = "imiage/bmp";
                break;
            case 'webp':
                $this->mimeType = "image/webp";
                break;
            default:
                $this->mimeType = "image/jpeg";
        }
        
        }
    
    public function getMetaDataString() {
        $metaString = 
            $this->getStart() .
            //$this->getImgSizeMeta("thumbnail",150) .
            //$this->getImgSizeMeta("medium", 300) .
            //$this->getImgSizeMeta("medium_large", 768) .
            //$this->getImgSizeMeta("large", 1024) .
            //$this->getImgSizeMeta("woocommerce_thumbnail_preview", 324) .
            //$this->getImgSizeMeta("woocommerce_thumbnail", 324) .
            //$this->getImgSizeMeta("woocommerce_single", 416) .
            //$this->getImgSizeMeta("woocommerce_gallery_thumbnail", 100) .
            //$this->getImgSizeMeta("shop_catalog", 324) .
            //$this->getImgSizeMeta("shop_single", 416) .
            //$this->getImgSizeMeta("shop_thumbnail", 100) .
            '}s:10:"image-meta";' .
            $this->getImageMeta() .
            '}';
        echo "Meta String be: " . $metaString . "\n";
        return $metaString;        
    }
    
    private function getStart() {
        $str = 'a:5:{s:5:"width";i:' . $this->imageData['image_x'] . ';s:6:"height";i:' . $this->imageData['image_y'] .';s:4:"file";s:';
        $fileNameLength = strlen($this->startPath . "/" . $this->imageData['image_path']);
        $str .= $fileNameLength . ':"' . $this->startPath . "/" . $this->imageData['image_path'] . '";s:5:"sizes";a:0:{';
        return $str;
        
    }

    private function getImgSizeMeta($size, $dim) {
        $sizeLen = strLen($size);
        
        $str = 's:' . $sizeLen . ':"' . $size . '";a:4:{s:4:"file";s:';
        $thFileName = $this->fileroot . "-" . $dim . "x" . $dim . "." . $this->filext;
        $fnlen = strlen($thFileName);
        $mimeLen = strlen($this->mimeType);
        $str .= $fnlen . ':"' . $thFileName . '";s:5:"width";i:' . $dim . ';s:6:"height";i:' . $dim . ';s:9:"mime-type";s:' . $mimeLen . ':"' . $this->mimeType . '";}';
        return $str;
    }
    
    private function getImageMeta() {
        return 'a:13:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";	s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}';
            
    }
}
