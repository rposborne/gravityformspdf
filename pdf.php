<?php
/*
Plugin Name: Gravity Forms PDF Add On
Plugin URI: http://blog.burningpony.com
Description: Adds PDF rendering to the Gravity Forms Admin Page.
Version: 0.0.2
Author: Russell Osborne
Author URI: http://blog.burningpony.com

------------------------------------------------------------------------
Copyright 2012 Burningpony Corp

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/


add_action('gform_entries_first_column_actions', 'pdf_link', 10, 4);
add_action("gform_entry_info", "detail_pdf_link", 10, 2);
add_action('wp',   'process_exterior_pages');
add_action("gform_advanced_settings", "render_pdfs_on_submission", 10, 2);
//Link for Entry Detail View (Provide both View Link and Download)
function detail_pdf_link($form_id, $lead) {
  $lead_id = $lead['id'];
  echo "PDF:  ";
  echo "<a href=\"javascript:;\" onclick=\"var notes_qs = jQuery('#gform_print_notes').is(':checked') ? '&notes=1' : ''; var url='".site_url()."/?gf_pdf=print-entry&fid=".$form_id."&lid=".$lead_id."' + notes_qs; window.open (url,'printwindow');\" class=\"button\"> View</a>";
  echo " <a href=\"javascript:;\" onclick=\"var notes_qs = jQuery('#gform_print_notes').is(':checked') ? '&notes=1' : ''; var url='".site_url()."/?gf_pdf=print-entry&download=1&fid=".$form_id."&lid=".$lead_id."' + notes_qs; window.open (url,'printwindow');\" class=\"button\"> Download</a>";
}

// Made this first... figured i would leave it in.  View link on the Entry list view. 
function pdf_link($form_id, $field_id, $value, $lead) {
  $lead_id = $lead['id'];
  echo "| <a href=\"javascript:;\" onclick=\"var notes_qs = '&notes=1'; var url='".site_url()."/?gf_pdf=print-entry&fid=".$form_id."&lid=".$lead_id."' + notes_qs; window.open (url,'printwindow');\"> View PDF</a>";
}

//Handle Incoming route.   Look for GF_PDF namespace 
function process_exterior_pages(){
  if(rgempty("gf_pdf", $_GET))
    return;

  //ensure users are logged in
  if(!is_user_logged_in())
    auth_redirect();

  switch(rgget("gf_pdf")){
    case "print-entry" :
    require_once("render_to_pdf.php");
    break;
  }
  exit();
}

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
function process_print_view(){
  $form_id = $_GET["fid"];
  $lead_id = $_GET["lid"];
  $filename ="form-$form_id-entry-$lead_id.pdf";
  $entry = GetRequire(dirname(__FILE__)."/print-view.php");

  //Parse the default print view from Gravity forms so we can play with it.
  $entry= absolutify_html($entry);

  //Load the DOMPDF Engine to render the PDF
  require_once("dompdf/dompdf_config.inc.php");
  $dompdf = new DOMPDF();
  $dompdf -> load_html($entry);
  $dompdf -> set_base_path(site_url());
  $dompdf -> render();
  return $dompdf;
}

function absolutify_html($entry){

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
  // $xpath = new DOMXPath($DOM);
  // $nlist = $xpath->query("//div[@id='print_preview_hdr']");
  // $node = $nlist->item(0);
  // $node->parentNode->removeChild($node);
  $entry = $DOM->saveHTML();
  return $entry;
}
function render_pdfs_on_submission($postion, $form_id){


}


add_filter('gform_custom_merge_tags', 'custom_merge_tags', 10, 4);
function custom_merge_tags($merge_tags, $form_id, $fields, $element_id) {
  $merge_tags[] = array('label' => 'PDF Download Link', 'tag' => '{pdf_download_link}');
  return $merge_tags;
}


add_filter('gform_replace_merge_tags', 'pdf_download_link', 10, 7);
function pdf_download_link($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {

  $custom_merge_tag = '{pdf_download_link}';

  if(strpos($text, $custom_merge_tag) === false)
    return $text;

  $download_link = gform_get_meta($entry['id'], 'gf_pdf_url');
  $download_link = "<a href='$download_link'> Download PDF Here</a>";
  $text = str_replace($custom_merge_tag, $download_link, $text);

  return $text;
}

add_action('gform_entry_created', 'render_pdfs', 10, 2);
function render_pdfs($entry, $form) {
  $form_id = $_GET["fid"] = $entry["form_id"] ;
  $lead_id = $_GET["lid"] = $entry["id"];

  $dompdf = process_print_view();
  $pdf = $dompdf -> output();
  $folder = WP_CONTENT_DIR . "/rendered_forms/$form_id/$lead_id/";
  $filename = "form-$form_id-entry-$lead_id.pdf";
  $url = content_url() . "/rendered_forms/$form_id/$lead_id/".$filename ;
  $full_path = $folder. $filename;
  print( mkdir($folder, 0777, true));

  $fp = fopen($full_path, "a+"); 
  fwrite($fp, $pdf); 
  fclose($fp);

  gform_update_meta($lead_id, "gf_pdf_filename", $full_path);
  gform_update_meta($lead_id, "gf_pdf_url", $url);

}

?>