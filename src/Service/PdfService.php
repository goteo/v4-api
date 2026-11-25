<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private Environment $twig,
    ) {}

    /**
     * Renders a Twig template into a PDF document.
     *
     * Remote resources are disabled on purpose: templates can print user data
     * and must not be able to make the server fetch URLs.
     *
     * @return string The raw PDF bytes
     */
    public function render(string $template, array $context = []): string
    {
        $dompdf = new Dompdf(new Options([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
        ]));

        $dompdf->loadHtml($this->twig->render($template, $context));
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
