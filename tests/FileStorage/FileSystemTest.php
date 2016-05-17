<?php

namespace PetrKnap\Php\FileStorage\Test;

use PetrKnap\Php\FileStorage\FileSystem;

class FileSystemTest extends AbstractTestCase
{
    /**
     * @dataProvider getInnerPathWorksDataProvider
     * @param string $expectedInnerPath
     * @param string $path
     */
    public function testGetInnerPathWorks($expectedInnerPath, $path)
    {
        $this->assertEquals(
            $expectedInnerPath,
            FileSystem::getInnerPath($path)
        );
    }

    public function getInnerPathWorksDataProvider()
    {
        return [
            ["/fb/93/fc/d0/02/f3/dd/6c/71/33/7d/9c/5e/d9/fc/88/9f/fb/4d/1c-c55dfad9d356d075636ee88b7bfef6a2", "/path/to/node"],
            ["/ee/3f/2a/9c/f9/04/b8/4f/5b/51/e9/e0/2f/ad/20/0b/13/29/0d/7b-418b4dba27b974fb3ea528c0b51d5a63.e", "/path/to/node.e"],
            ["/0f/a4/56/b9/b2/f0/96/9f/fb/e8/81/35/19/45/09/a6/bb/db/f4/db-8fb80755bf024963125b766e994554fa.ex", "/path/to/node.ex"],
            ["/5e/43/12/5f/02/6e/00/48/ca/4d/91/19/fe/34/88/45/29/1d/b1/a8-86cf999499d3f842a1cee6e184d696d3.ext", "/path/to/node.ext"],
            ["/f6/e3/d5/06/c4/00/25/eb/dc/d1/89/4e/ca/f9/d7/58/c3/9a/f0/2e-ef8f1fbbc6706eb966cfe3589ca82742.extension", "/path/to/node.extension"],
            ["/e8/c2/86/10/2e/25/1d/be/05/08/5b/cd/9b/6e/31/28/ae/cd/4d/e8-285d8609dc6d5aa35a624cb1fee80c60.double", "/path/to/node.extension.double"],
            ["/26/cd/83/c8/49/79/ab/c0/25/b4/dd/b1/d3/80/f8/30/1d/bc/47/34-fc38e96209d033e8b387888a8f6a2e42", "/path/to/.node"],
            ["/07/27/67/be/91/bb/e4/64/b1/a1/dd/38/5f/b9/39/86/61/74/1e/45-e629df922768cc97b1c12d4fa590b676", "/path/to/node.€×t3ns10n"]
        ];
    }
}
