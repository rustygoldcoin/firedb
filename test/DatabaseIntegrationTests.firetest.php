<?php

use firetest\testcase;
use firedb\FireDbException;
use firedb\db;

class DatabaseIntegrationTests extends testcase {

    private $_collectionDir;

    public function setUp() {
        $this->_collectionDir = __DIR__ . '/collections';
        mkdir($this->_collectionDir);
    }

    public function testConnectionToDatabaseShould() {
        $should = 'We should see firedb\FireDbException thrown if db directory does not exist.';
        try {
            $db = new db(__DIR__ . '/nondirectory');
            $this->assert(false, $should);
        } catch (FireDbException $e) {
            $this->assert(true, $should);
        }

        $should = 'We Should retrieve a database object if db directory exists.';
        $db = new db($this->_collectionDir);
        $this->assert($db instanceof db, $should);
    }

    public function tearDown() {
        $this->_removeDir($this->_collectionDir);
    }

    private function _removeDir($dir) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if (filetype($dir . '/' . $file) === 'dir') {
                    $this->_removeDir($dir . '/' . $file);
                } else {
                    unlink($dir . '/' . $file);
                }
            }
        }
        reset($files);
        rmdir($dir);
    }

}
