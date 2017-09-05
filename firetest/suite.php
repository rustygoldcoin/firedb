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

    public function __construct($dir) {
        $this->_dir = $dir;
        $this->_testClasses = [];
        $this->_loadTestFiles();
    }

    public function run() {
        $this->_log('Starting test suite located at "' . $this->_dir . '".');
        foreach($this->_testClasses as $testClass) {
            $testClass->setUp();
            $testMethods = $testClass->getTestMethods();
            foreach ($testMethods as $testMethod) {
                $testClass->beforeEach();
                $this->_log('***Running ' . get_class($testClass) . '::' . $testMethod . '()');
                $this->_log('*');
                $testClass->{$testMethod}();

                $results = $testClass->getResults();
                $testClass->resetResults();
                $fails = $results['failed'];
                if (count($fails) > 0) {
                    foreach ($fails as $failed) {
                        $this->_log('* [FAILED] ' . $failed);
                    }
                }
                $passes = $results['passed'];
                if (count($passes) > 0) {
                    foreach ($passes as $passed) {
                        $this->_log('* [PASSED] ' . $passed);
                    }
                }
                $this->_log('*');
                $passFail = (count($fails) === 0) ? 'PASSED' : 'FAILED';
                $this->_log(
                    '***' . $passFail . ' | (Passed: '. count($results['passed'])
                    . ', Failed: '
                    . count($results['failed']) . ')'
                    . ' | ' . get_class($testClass) . '::' . $testMethod . '()'
                );
                $testClass->afterEach();
            }
            $testClass->tearDown();
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
