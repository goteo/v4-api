<?php

namespace App\Tests\Service\Scout;

use App\Service\Scout\InvalidUriException;
use App\Service\Scout\NonCrawlableUriException;
use App\Service\Scout\ScoutService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ScoutServiceTest extends KernelTestCase
{
    private ScoutService $scout;

    public function setUp(): void
    {
        self::bootKernel();

        $this->scout = static::getContainer()->get(ScoutService::class);
    }

    /**
     * @dataProvider provideNotUrls
     */
    public function testThrowsExceptionOnUnrecognized(string $string)
    {
        $this->expectException(InvalidUriException::class);

        $this->scout->get($string);
    }

    public function provideNotUrls()
    {
        return [
            ['not a url'],
            ['media'],
            [''],
        ];
    }

    /**
     * @dataProvider provideNonCrawlablePaths
     */
    public function testThrowsExceptionOnNonCrawlablePaths(string $url)
    {
        $this->expectException(NonCrawlableUriException::class);
        $this->expectExceptionMessageMatches(\sprintf('/%s/', \preg_quote($url, '/')));

        $this->scout->get($url);
    }

    public function provideNonCrawlablePaths(): array
    {
        return [
            ['https://sialmaraz.es/wp-content/uploads/2025/01/MANIFESTACION-ALMARAZ-18_1.mp4'],
        ];
    }

    /**
     * @dataProvider provideVideoUrls
     */
    public function testGetsVideoCover(string $url)
    {
        $result = $this->scout->get($url);

        $this->assertNotEmpty((string) $result->image);
        $this->assertStringStartsWith('http', (string) $result->image);
        $this->assertNotEmpty((string) $result->cover);
        $this->assertStringStartsWith('http', (string) $result->cover);
        $this->assertNotEquals((string) $result->image, (string) $result->cover);
    }

    public function provideVideoUrls(): array
    {
        return [
            ['vimeo.com/814460460'],
            ['www.vimeo.com/943036493'],
            ['https://vimeo.com/1006675057?share=copy#t=0'],
            ['https://vimeo.com/1034003653'],
            ['https://vimeo.com/1047182474/5a2a1cb70b?share=copy'],
            ['youtube.com/watch?v=D14QGWH2CfY'],
            ['www.youtube.com/watch?si=hbrQ4ZazrdwccLTk&v=pdfGwxa5Kwc&feature=youtu.be'],
            ['https://www.youtube.com/watch?v=47et__pjxZ8&t=6s'],
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['https://www.youtube.com/watch?v=oyYOBXDYGmY&ab_channel=Fes%21Cultura'],
            ['youtu.be/CLSXLA7jUsQ'],
            ['https://youtu.be/_crFO_jZ95o'],
            ['https://youtu.be/_URpSvDyod4?feature=shared'],
        ];
    }
}
