<?php
//http://localhost:8080/Entartes/azure-php-pdf-worker/pdfgen.php
// Load autoloader (using Composer)
require __DIR__ . '/vendor/autoload.php';
$pdf = new TCPDF();                 // create TCPDF object with default constructor args
$pdf->AddPage();                    // pretty self-explanatory
$pdf->Write(1, 'Hello world');      // 1 is line height

$pdf->Output('hello_world.pdf');    // send the file inline to the browser (default).

?>