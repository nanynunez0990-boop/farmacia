<?php
require_once __DIR__ . '/fpdf/fpdf.php';

class PDF extends FPDF
{
    function Header()
    {
        // El contenido del recibo se coloca en index.php.
    }

    function Footer()
    {
        // Sin pie automático para mantener el formato de recibo.
    }
}
?>
