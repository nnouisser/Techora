<?php

namespace App\service;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private $domPdf;

    public function __construct() {
        $this->domPdf = new DomPdf();

        $pdfOptions = new Options();

        $pdfOptions->set('defaultFont', 'Garamond');

        $this->domPdf->setOptions($pdfOptions);
    }

    public function showPdfFile($html) {
        $this->domPdf->loadHtml($html);
        $this->domPdf->render();

        // Envoyer les headers PDF correctement à Symfony
        return new \Symfony\Component\HttpFoundation\Response(
            $this->domPdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="details.pdf"',
            ]
        );
    }


    public function generateBinaryPDF($html) {
        $this->domPdf->loadHtml($html);
        $this->domPdf->render();
        $this->domPdf->output();
    }

}