<?php

namespace firetest;

use firetest\FireTestException;
use firetest\testcase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class suite {

    private $_dir;

    private $_testClasses;

    private $_totalPassCount;

    private $_totalFailCount;

    private $_allFailedTests;

    public function __construct($dir) {
        $this->_dir = $dir;
        $this->_testClasses = [];
        $this->_totalPassCount = 0;
        $this->_totalFailCount = 0;
        $this->_allFailedTests = [];
        $this->_loadTestFiles();
    }

    public function run() {
        $this->_log('Starting test suite located at "' . $this->_dir . '".');
        foreach($this->_testClasses as $testClass) {
            $testClass->setUp();
            $testMethods = $testClass->getTestMethods();
            foreach ($testMethods as $testMethod) {
                $testClass->beforeEach();
                $testName = get_class($testClass) . '::' . $testMethod . '()';
                $this->_log('[RUNNING] ' . $testName);
                $testClass->{$testMethod}();

                $results = $testClass->getResults();
                $testClass->resetResults();
                $fails = $results['failed'];
                $failedCount = count($fails);
                $this->_totalFailCount += $failedCount;
                if ($failedCount > 0) {
                    foreach ($fails as $failed) {
                        $this->_allFailedTests[] = $failed;
                        $this->_log('[FAILED] ' . $failed);
                    }
                }
                $passes = $results['passed'];
                $passedCount = count($passes);
                $this->_totalPassCount += $passedCount;
                if ($passedCount > 0) {
                    foreach ($passes as $passed) {
                        $this->_log('[PASSED] ' . $passed);
                    }
                }
                $passFail = (count($fails) === 0) ? 'PASSED' : 'FAILED';

                $this->_log('[RESULT] (Passed: '. $passedCount . ', Failed: ' . $failedCount . ')');
                $testClass->afterEach();
            }
            $testClass->tearDown();
        }
        if ($this->_totalFailCount > 0) {
            $this->_log('********************************************');
            $this->_log('███████╗ █████╗ ██╗██╗     ███████╗██████╗');
            $this->_log('██╔════╝██╔══██╗██║██║     ██╔════╝██╔══██╗');
            $this->_log('█████╗  ███████║██║██║     █████╗  ██║  ██║');
            $this->_log('██╔══╝  ██╔══██║██║██║     ██╔══╝  ██║  ██║');
            $this->_log('██║     ██║  ██║██║███████╗███████╗██████╔╝');
            $this->_log('╚═╝     ╚═╝  ╚═╝╚═╝╚══════╝╚══════╝╚═════╝');
            $i = 0;
            foreach ($this->_allFailedTests as $failedTest) {
                $this->_log('[#' . $i . '] ' . $failedTest);
                $i++;
            }
            $this->_log('********************************************');
        } else {
            $this->_log('***********************************************************');
            $this->_log('███████╗██╗   ██╗ ██████╗ ██████╗███████╗███████╗███████╗');
            $this->_log('██╔════╝██║   ██║██╔════╝██╔════╝██╔════╝██╔════╝██╔════╝');
            $this->_log('███████╗██║   ██║██║     ██║     █████╗  ███████╗███████╗');
            $this->_log('╚════██║██║   ██║██║     ██║     ██╔══╝  ╚════██║╚════██║');
            $this->_log('███████║╚██████╔╝╚██████╗╚██████╗███████╗███████║███████║');
            $this->_log('╚══════╝ ╚═════╝  ╚═════╝ ╚═════╝╚══════╝╚══════╝╚══════╝');
            $this->_log('***********************************************************');
        }
        $this->_log('[FINAL] (Passed: '. $this->_totalPassCount . ', Failed: ' . $this->_totalFailCount . ')');

        if ($this->_totalFailCount > 0) {
            exit(1);
        }

    }

    private function _loadTestFiles() {
        $rDir = new RecursiveDirectoryIterator($this->_dir);
        $iDir = new RecursiveIteratorIterator($rDir);
        $iFiles = new RegexIterator($iDir, '/^.+\.firetest\.php$/i', RegexIterator::GET_MATCH);
        foreach($iFiles as $file) {
            $require = $file[0];
            require_once $require;
            $className = str_replace('.firetest.php', '', basename($require));
            if (!class_exists($className)) {
                throw new FireTestException('Test class "' . $className . '" cannot be found.');
            }
            $testInstance = new $className();
            if (!($testInstance instanceof testcase)) {
                throw new FireTestException('Test class "' . $className . '" must extend firetest\testcase.');
            }
            $this->_testClasses[] = new $className();
        }
    }

    private function _log($text) {
        if (php_sapi_name() == "cli") {
            echo 'FireTest Log: ' . $text . "\n";
        } else {
            // Not in cli-mode
        }
    }

}
