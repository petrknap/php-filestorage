<?php

namespace PetrKnap\Php\FileStorage\Test;

use League\Flysystem\Adapter\Local;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\Profiler\SimpleProfiler;

class PerformanceTest extends AbstractTestCase
{
    /**
     * @dataProvider performanceIsNotIntrusiveDataProvider
     * @param FileSystem $fileSystem
     * @param int $from
     * @param int $to
     */
    public function testPerformanceIsNotIntrusive(FileSystem $fileSystem, $from, $to)
    {
        $profilerWasEnabled = SimpleProfiler::start();
        if (!$profilerWasEnabled) {
            SimpleProfiler::enable();
        }

        #region Build storage
        for ($i = $from; $i < $to; $i++) {
            $file = "/file_{$i}.tmp";

            #region Create file
            SimpleProfiler::start();
            $fileSystem->write($file, null);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(5, $profile->absoluteDuration);
            #endregion

            #region Write content
            SimpleProfiler::start();
            $fileSystem->update($file, sha1($i, true));
            $fileSystem->update($file, md5($i, true), ["append" => true]);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(10, $profile->absoluteDuration);
            #endregion

            #region Read content
            SimpleProfiler::start();
            $fileSystem->read($file);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(5, $profile->absoluteDuration);
            #endregion
        }
        #endregion

        #region Iterate all files
        SimpleProfiler::start();
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($fileSystem->listContents() as $unused) ;
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
        $iMax = 2048;
        $step = 512;
        $output = [];
        $fileSystem = new FileSystem(new Local($this->getTemporaryDirectory()));
        for ($i = 0; $i < $iMax; $i += $step) {
            $output[] = [$fileSystem, $i, $i + $step];
        }
        return $output;
    }
}
