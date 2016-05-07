<?php

namespace PetrKnap\Php\FileStorage\Test;

use PetrKnap\Php\FileStorage\Test\AbstractTestCase\TestFile;
use PetrKnap\Php\Profiler\SimpleProfiler;

class PerformanceTest extends AbstractTestCase
{
    /**
     * @dataProvider performanceIsNotIntrusiveDataProvider
     * @param string $directory
     * @param int $from
     * @param int $to
     */
    public function testPerformanceIsNotIntrusive($directory, $from, $to)
    {
        TestFile::setStorageDirectory($directory);

        $profilerWasEnabled = SimpleProfiler::start();
        if(!$profilerWasEnabled) {
            SimpleProfiler::enable();
        }

        // Build storage
        for ($i = $from; $i < $to; $i++) {
            SimpleProfiler::start();

            $file = $this->getFile();
            if ($file->exists()) {
                $file->delete();
            }
            $file->create();
            $file->write(sha1($i, true));
            $file->write(md5($i, true), FILE_APPEND);
            $file->read();

            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(250, $profile->absoluteDuration);
        }

        if (!$profilerWasEnabled) {
            SimpleProfiler::disable();
        }
        SimpleProfiler::finish();
    }

    public function performanceIsNotIntrusiveDataProvider()
    {
        srand(1462607969);
        $iMax = 16384;
        $step = 128;
        $output = [];
        $directory = $this->getTemporaryDirectory();
        for ($i = 0; $i < $iMax; $i += $step)
        {
            $output[] = [$directory, $i, $i + $step + rand(0, 16)];
        }
        return $output;
    }
}
