<?php

use Fire\Test\Suite;
use Fire\Test\TestCase;
use Fire\FireDbException;
use Fire\Db;
use Fire\Db\Collection;

class FireDbIntegration extends TestCase {

    /**
     * The collection directory.
     * @var string
     */
    private $_collectionDir;

    /**
     * @var Fire\Db
     */
    private $_db;

    /**
     * Runs before each test is carried out. Sets up the environment by clearing out the
     * database collections from the last test.
     * @return void
     */
    public function beforeEach() {
        $this->_collectionDir = __DIR__ . '/../collections';
        if (is_dir($this->_collectionDir)) {
            $this->_removeDir($this->_collectionDir);
        }
        mkdir($this->_collectionDir);
        $this->_db = new Db($this->_collectionDir);
    }

    /**
     * Runs after each test is carried out. Removes the last tests database collection.
     * @return void
     */
    public function afterEach() {
        $this->_removeDir($this->_collectionDir);
    }

    /**
     * Test constuctor of new firedb\db();
     * @return void
     */
    public function testConnectionToDatabase() {
        try {
            $db = new db(__DIR__ . '/nondirectory');
        } catch (FireDbException $e) {
            $should = 'We should see an error if db directory does not exist.';
            $this->assert($e instanceof FireDbException, $should);
        }

        $db = new db($this->_collectionDir);
        $should = 'We should retrieve a database object if db directory exists.';
        $this->assert($db instanceof db, $should);
    }

    /**
     * Tests firedb\db::has() functionality.
     * @return void
     */
    public function testDatabaseHasCollection() {
        $result = $this->_db->has('nonExistantCollection');
        $should = 'We should see false when we use db::has() to check for a collection that does not exist.';
        $this->assert($result === false, $should);

        $collection = $this->_db->collection('myCollection');
        $result = $this->_db->has('myCollection');
        $should = 'We should see true when we use db::has() to check for a collection that exists.';
        $this->assert($result === true, $should);
    }

    /**
     * Tests the constructor of new firedb\collection().
     * @return void
     */
    public function testConnectionToCollection() {
        $collection = $this->_db->collection('myCollection');
        $should = 'We should always receive back a collection object when we connect to a collection.';
        $this->assert($collection instanceof Collection, $should);
    }

    /**
     * Tests firedb\collection::setIndexable() and firedb\collection::getIndexable() functionality.
     * @return void
     */
    public function testConfigureCollectionIndexable() {
        $collection = $this->_db->collection('myCollection');
        try {
            $collection->setIndexable((object)[]);
        } catch (FireDbException $e) {
            $should = 'We should get an error when we try to set indexable as an object.';
            $this->assert($e instanceof FireDbException, $should);
        }
        try {
            $collection->setIndexable('mydocument');
        } catch (FireDbException $e) {
            $should = 'We should get an error when we try to set indexable as a string.';
            $this->assert($e instanceof FireDbException, $should);
        }
        try {
            $collection->setIndexable(0);
        } catch (FireDbException $e) {
            $should = 'We should get an error when we try to set indexable as an interger.';
            $this->assert($e instanceof FireDbException, $should);
        }

        $indexable = ['property'];
        $collection->setIndexable($indexable);
        $result = $collection->getIndexable();
        $should = 'We should get back the same indexable configuration we just set.';
        $this->assert($result === $indexable, $should);
    }

    /**
     * Tests firedb\collection::insert() functionality.
     * @return void
     */
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

    /**
     * Tests firedb\collection::find('documentid') functionality.
     * @return void
     */
    public function testFindDocumentById() {
        $collection = $this->_db->collection('myCollection');
        $document = (object) [
            'id' => 'myDocument'
        ];
        $doc = $collection->insert($document);
        $result = $collection->find($doc->__id);
        $should = 'When we find a document by ID we should get back the same object we inserted.';
        $this->assert(json_encode($result) === json_encode($doc), $should);
    }

    /**
     * Tests firedb\collection::find((object)['rand'=>1]) functionality.
     * @return void
     */
    public function testFindDocumentByFilter() {
        $collection = $this->_db->collection('myCollection');
        $collection->setIndexable(['rand']);
        $countOnesInserted = $this->_insertFiveThousandDocuments();
        $filter = (object) [
            'rand' => 1
        ];
        $result = $collection->find($filter, 0, 5000);
        $onesCount = count($result);
        $should = 'When we try filter a collection by random number 1s generated, '
            . 'we should retrieve the same amount of documents back that were inserted.';
        $this->assert($onesCount === $countOnesInserted, $should);

        $lastIndex = 5001;
        $reverseOrderTestPassed = true;
        $firstIndex = $result[0]->index;
        $secondIndex = $result[1]->index;
        foreach ($result as $document) {
            if ($document->index >= $lastIndex) {
                $reverseOrderTestPassed = false;
            }
            $lastIndex = $document->index;
        }
        $should = 'When we try to filter a collection, we should retrieve them in reverse order by default.';
        $this->assert($reverseOrderTestPassed, $should);

        $result = $collection->find($filter, 0, 10);
        $should = 'When we try to filter a collection, we should only get back the amount of documents we request.';
        $this->assert(count($result) === 10, $should);
        $should = 'When we try to filter a collection, and when we ask to offset the collection at 0, we'
            . ' should get back the 2nd item in the collection.';
        $this->assert($result[0]->index === $firstIndex, $should);

        $result = $collection->find($filter, 1, 10);
        $should = 'When we try to filter a collection, and when we ask to offset the collection at 1, we'
            . ' should get back the 2nd item in the collection.';
        $this->assert($result[0]->index === $secondIndex, $should);

        $result = $collection->find($filter, 0, 5000, false);
        $lastIndex = 0;
        $naturalOrderTestPassed = true;
        foreach ($result as $document) {
            if ($document->index < $lastIndex) {
                $naturalOrderTestPassed = false;
            }
            $lastIndex = $document->index;
        }
        $should = 'When we try to filter a collection, we should retrieve them in natural order by when configured.';
        $this->assert($naturalOrderTestPassed, $should);

        $result = $collection->find($filter);
        $should = 'When we try to filter a collection, we should have an offeset of 0 and lenght of 10 by default.';
        $this->assert(
            $result[0]->index === $firstIndex
            && count($result) === 10,
            $should
        );
    }

    /**
     * Tests firedb\collection::find(null) functionality.
     * @return void
     */
    public function testFindAllDocuments() {

    }

    private function _insertFiveThousandDocuments() {
        Suite::log('Inserting 5000 Documents Into Database...');
        $collection = $this->_db->collection('myCollection');
        $ones = 0;
        for ($i = 0; $i < 5000; $i++) {
            $rand = rand(1,2);
            $document = (object) [
               'index' => $i,
               'rand' => $rand
            ];
            if ($rand === 1) {
                $ones++;
            }
            $doc = $collection->insert($document);
        }
        Suite::log('Finished Inserting 5000 Documents');
        return $ones;
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
