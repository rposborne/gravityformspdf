<?php
/*
Plugin Name: Gravity Forms PDF Add On
Plugin URI: http://blog.burningpony.com
Description: Adds PDF rendering to the Gravity Forms Admin Page.
Version: 0.0.1alpha1
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
?>