<?php
namespace firedb;

use firedb\collection\helper\filesystem;
use firedb\collection\logic\indexing;
use firedb\collection\logic\query;
use firedb\FireDbException;

class collection {

    /**
     * Filesystem helper
     * @var firedb\collection\helper\filesystem
     */
    private $_filesystem;

    /**
     * Index logic helper
     * @var firedb\collection\logic\indexing
     */
    private $_indexing;

    /**
     * Query logic helper
     * @var firedb\collection\logic\query
     */
    private $_query;

    /**
     * Class constructor
     * @param string $directory The path to the collection in the filesystem
     */
    public function __construct($directory) {
        $this->_filesystem = new filesystem($directory);
        $this->_indexing = new indexing($this->_filesystem);
        $this->_query = new query($this->_filesystem, $this->_indexing);
    }

    /**
     * Find documents in the collection.
     * @param string|object $filter The filter you want to use to apply to find documents
     * @param integer $offset The offset you want to apply to the returned documents (used for pagination)
     * @param integer $length The length of returned documents in the collection (used for pagination)
     * @param boolean $reverseOrder The order in which the documents should be returned
     * @return object|array<object>|null
     *
     * Example:
     * 1) Returns a single document if the document exists. Returns null if the document doesn't exist:
     *    $collection->find('201708250622024656847159a0247a71b12');
     *
     * 2) Returns an array of documents that contains the property "rand" with the value of "1". Returns an
     *    empty array if the collection doesn't contain documents that match the filter criteria:
     *    $filter = (object)[
     *        'rand' => 1
     *    ];
     *    $collection->find($filter);
     *
     *  3) Returns an array of all documents within the collection:
     *     $collection->find(null);
     */
    public function find($filter = null, $offset = 0, $length = 10, $reverseOrder = true) {
        if (is_string($filter)) {
            return $this->_query->getDocument($filter);
        } else if (is_object($filter)) {
            return $this->_query->getDocumentsByFilter($filter, $offset, $length, $reverseOrder);
        } else if (is_null($filter)) {
            return $this->_query->getAllDocuments($offset, $length, $reverseOrder);
        }
        return null;
    }

    /**
     * Inserts a document into the collection.
     * @param object $document The document you want to store
     * @return object The document updated with collection properties like "__id", "__revision", and "__created"
     * @throws firedb\FireDbException If $document is not an object
     *
     * Example:
     * $document = (object) [
     *     'property' => 'value'
     * ];
     * $collection->insert($document);
     */
    public function insert($document) {
        return $this->_query->upsertDocument($document, null);
    }

    /**
     * Updates a document in the collection.
     * @param string $id The ID of the document you would like to update
     * @param object $document The document you want to store
     * @return object The document updated with collection properties like "__id", "__revision", and "__created"
     * @throws firedb\FireDbException If $document is not an object
     *
     * Example:
     * $document = (object) [
     *     'property' => 'value'
     * ];
     * $collection->update('201708250622024656847159a0247a71b12', $document);
     */
    public function update($id, $document) {
        return $this->_query->upsertDocument($document, $id);
    }

    /**
     * Deletes a document from the collection.
     * @param  string $documentId The ID of the document you want to delete
     * @return void
     *
     * Example:
     * $collection->delete('201708250622024656847159a0247a71b12');
     */
    public function delete($documentId) {
        $this->_query->deleteDocument($documentId);
    }

    /**
     * Sets what properties should be allowed to be indexed on objects you are inserting/updating into the collection.
     * @param array $indexable An array of properties that should be indexed
     * @return void
     * @throws firedb\FireDbException If $indexable is not an array
     *
     * Example:
     * $indexable = [
     *     'rand',
     *     'name'
     * ];
     * $collection->setIndexable($indexable);
     */
    public function setIndexable($indexable) {
        $this->_indexing->setIndexable($indexable);
    }

    /**
     * Returns the indexable values configured for the collection.
     * @return array An array of indexable properties
     *
     * Example:
     * $indexable = $collection->getIndexable();
     */
    public function getIndexable() {
        return $this->_indexing->getIndexable();
    }
}
