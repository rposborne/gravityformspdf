<?php
// Handy trick to let us run the default print view and give us the processed string... it's all we want really. 
function GetRequire($sFilename) {
  ob_start();
  require($sFilename);
  $sResult = ob_get_contents();
  ob_end_clean();
  return $sResult;
  return ob_get_clean();
}
/*
  TODO Break this function out so we have a function to call to output a PDF as a string. or as a dompdf object
  TODO Add the ability to email said PDF as part of the Gravity forms Mailer. 
*/

$form_id = $_GET["fid"];
$lead_id = $_GET["lid"];
$filename ="form-$form_id-entry-$lead_id.pdf";
$entry = GetRequire(dirname(__FILE__)."/../gravityforms/print-entry.php");

//Parse the default print view from Gravity forms so we can play with it.

$DOM = new DOMDocument;
$DOM->loadHTML($entry);

//Make Stylesheets/Images Absolute
$stylesheets = $DOM->getElementsByTagName('link');
foreach($stylesheets as $stylesheet){
  $href = $stylesheet->getAttribute('href');
  if(strpos($src, site_url()) !== 0){
    $stylesheet->setAttribute('href', site_url()."$href");
  }
}
$imgs = $DOM->getElementsByTagName('img');
foreach($imgs as $img){
  $src = $img->getAttribute('src');
  if(strpos($src, site_url()) !== 0){
    $img->setAttribute('src', site_url()."$src");
  }
}

//Remove Ugly Header
$xpath = new DOMXPath($DOM);
$nlist = $xpath->query("//div[@id='print_preview_hdr']");
$node = $nlist->item(0);
$node->parentNode->removeChild($node);
$entry = $DOM->saveHTML();

//Load the DOMPDF Engine to render the PDF
require_once("dompdf/dompdf_config.inc.php");
$dompdf = new DOMPDF();
$dompdf -> load_html($entry);
$dompdf -> set_base_path(site_url());
$dompdf -> render();

if ($_GET["download"] == false) {
  $dompdf -> stream($filename, array("Attachment" => false));
  exit(0);
}else{
  $dompdf -> stream($filename, array("Attachment" => true));
  exit(0);
}
?>