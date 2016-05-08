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

        #region Build storage
        for ($i = $from; $i < $to; $i++) {
            $file = $this->getFile();

            #region Create file
            SimpleProfiler::start();
            $file->create();
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(5, $profile->absoluteDuration);
            #endregion

            #region Write content
            SimpleProfiler::start();
            $file->write(sha1($i, true));
            $file->write(md5($i, true), FILE_APPEND);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(10, $profile->absoluteDuration);
            #endregion

            #region Read content
            SimpleProfiler::start();
            $file->read();
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(5, $profile->absoluteDuration);
            #endregion
        }
        #endregion

        #region Iterate all files
        SimpleProfiler::start();
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach($this->getFile()->getFiles() as $unused);
        $profile = SimpleProfiler::finish();
        $this->assertLessThanOrEqual(5 * $to, $profile->absoluteDuration);
        #endregion

        if (!$profilerWasEnabled) {
            SimpleProfiler::disable();
        }
        SimpleProfiler::finish();
    }

    public function performanceIsNotIntrusiveDataProvider()
    {
        $iMax = 16384;
        $step = 128;
        $output = [];
        $directory = $this->getTemporaryDirectory();
        for ($i = 0; $i < $iMax; $i += $step)
        {
            $output[] = [$directory, $i, $i + $step];
        }
        return $output;
    }
}
