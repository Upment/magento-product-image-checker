<?php
/**
 *
 * Magento Product Image Checker
 * https://upment.com
 *
 * @link      https://github.com/Upment/magento-product-image-checker
 *
 */


define ('MAGE_ROOT', '../..');    // Magento root location (without the trailing /)

/**
 * Check the status code of the given URL
 *
 * @param string $url
 *
 * @return string|boolean
 */
function checkURL($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    if (curl_exec($curl)) {
        $cinfo=curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $cinfo;
    }
    curl_close($curl);
    return false;
}

/*
 * Set headers and PHP options for possible long execution time
 * and PHP flushing.
 */
header('Content-Type: text/HTML; charset=utf-8');
header('Content-Encoding: none; ');
session_start();
ob_end_flush();
ob_start();
ini_set('max_execution_time',3600);
ini_set('implicit_flush', 1);
set_time_limit(0);
ob_implicit_flush(1);

// Initialize Mage app
require MAGE_ROOT.'/app/Mage.php';
Mage::app('admin');

// Write output to browser and get an array of product IDs
echo '<h1>Starting check</h1>';
ob_flush();
flush();
$ids=Mage::getModel('catalog/product')->getCollection()->getAllIds();
echo '<span id="num">0</span>/'.count($ids).' complete<br><br>'.PHP_EOL;
ob_flush();
flush();
$i=0;

// Loop through products
foreach ($ids as $id) {
  $product = Mage::getModel('catalog/product')->load($id);    // get product info
  $images = $product->getMediaGalleryImages();    // get the list of images
  $ag=true;
  $pn=$product->getName();
  $sku=$product->getSku();
  foreach ($product->getMediaGalleryImages() as $image) {
    $url=$image->getUrl();
    $code=checkURL($url);
    $line=$code.': '.$url.'<br>';
    $pro='<span style="color:#f00">(disabled product)</span>';
    $prurl=str_replace(basename(__FILE__)."/", "", $product->getProductUrl());
    if ($product->isVisibleInCatalog() && $product->isVisibleInSiteVisibility()) $pro='<span style="color:#080">(visible)</span>';
    if ($code != '200') {
      if ($ag) echo "<h2><a href=\"$prurl\">$pn</a>, SKU: $sku $pro</h2>";
      $ag=false;
      echo $line.PHP_EOL;
    }
  }
  $i++;
  if ((($i % 20) == 0)||($i == count($ids))) {
    echo "<script>document.getElementById('num').innerHTML='$i';</script>".str_pad('',4096).PHP_EOL;
  }
  ob_flush();
  flush();
}
echo '<h1>Done</h1>';
?>
