<?php

namespace firetest;

abstract class testcase {

    private $_passed;

    private $_failed;

    public function __construct() {
        $this->resetResults();
    }

    public function setUp() {}

    public function beforeEach() {}

    public function afterEach() {}

    public function tearDown() {}

    public function getTestMethods() {
        return array_filter(array_map([$this, '_filterTestMethods'], get_class_methods($this)));
    }

    public function getResults() {
        return [
            'passed' => $this->_passed,
            'failed' => $this->_failed
        ];
    }

    public function resetResults() {
        $this->_passed = [];
        $this->_failed = [];
    }

    protected function assert($trueStatement, $shouldStatement) {
        if ($trueStatement === true) {
            $this->_passed[] = $shouldStatement;
        } else {
            $this->_failed[] = $shouldStatement;
        }
    }

    private function _filterTestMethods($methodName) {
        return (substr($methodName, 0, 4) === 'test') ? $methodName : null;
    }

}
