<?php

use firetest\testcase;
use firedb\FireDbException;
use firedb\db;
use firedb\collection;

class DatabaseIntegrationTests extends testcase {

    private $_collectionDir;
    private $_db;

    public function beforeEach() {
        $this->_collectionDir = __DIR__ . '/collections';
        if (is_dir($this->_collectionDir)) {
            $this->_removeDir($this->_collectionDir);
        }
        mkdir($this->_collectionDir);
        $this->_db = new db($this->_collectionDir);
    }

    public function afterEach() {
        $this->_removeDir($this->_collectionDir);
    }

    public function testConnectionToDatabase() {
        try {
            $db = new db(__DIR__ . '/nondirectory');
        } catch (FireDbException $e) {
            $should = 'We should see an error if db directory does not exist.';
            $this->assert($e instanceof FireDbException, $should);
        }

        $db = new db($this->_collectionDir);
        $should = 'We Should retrieve a database object if db directory exists.';
        $this->assert($db instanceof db, $should);
    }

    public function testConnectionToCollection() {
        $collection = $this->_db->collection('myCollection');
        $should = 'We should always receive back a collection object when we connect to a collection.';
        $this->assert($collection instanceof collection, $should);
    }

    public function testDocumentInsert() {
        $collection = $this->_db->collection('myCollection');
        try {
            $collection->insert([]);
        } catch (FireDbException $e) {
            $should = 'We should get an error when we try to insert a document that is an array.';
            $this->assert($e instanceof FireDbException, $should);
        }
        try {
            $collection->insert('mydocument');
        } catch (FireDbException $e) {
            $should = 'We should get an error when we try to insert a document that is a string.';
            $this->assert($e instanceof FireDbException, $should);
        }
        try {
            $collection->insert(0);
        } catch (FireDbException $e) {
            $should = 'We should get an error when we try to insert a document that is a integer.';
            $this->assert($e instanceof FireDbException, $should);
        }

        $document = (object) [
            'id' => 'myDocument'
        ];
        $should = 'When we insert a document, we should always get returned the same document.';
        $result = $collection->insert($document);
        $this->assert($result->id === 'myDocument', $should);
        $should = 'When we insert a document, we should always get a document back with an __id appended.';
        $this->assert(isset($result->__id), $should);
        $should = 'When we insert a document, we should always get a document back with an __timestamp appended.';
        $this->assert(isset($result->__timestamp), $should);
        $should = 'When we insert a document, we should always get a document back with an __revision appended.';
        $this->assert(isset($result->__revision), $should);
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
