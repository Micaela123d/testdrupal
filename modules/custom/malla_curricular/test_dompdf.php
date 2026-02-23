<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml('<h1>DOMPDF funciona!</h1>');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("test.pdf");

echo "DOMPDF instalado correctamente";