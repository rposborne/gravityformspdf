<?php
$dompdf = process_print_view();

if ($_GET["download"] == false) {

  $dompdf -> stream($filename, array("Attachment" => false));
  exit(0);
}else{

  $dompdf -> stream($filename, array("Attachment" => true));
  exit(0);
}
?>