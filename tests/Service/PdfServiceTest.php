<?php

namespace App\Tests\Service;

use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PdfServiceTest extends KernelTestCase
{
    public function testRendersTemplateToPdf()
    {
        self::bootKernel();

        $pdfService = new PdfService(static::getContainer()->get('twig'));

        $pdf = $pdfService->render('pdf/base.html.twig');

        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
