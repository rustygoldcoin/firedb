<?php
namespace Fire\Db;

use Fire\Db\Collection\Helper\FileSystem;
use Fire\Db\Collection\Logic\Indexing;
use Fire\Db\Collection\Logic\Query;
use Fire\Db\Collection\Config;
use Fire\FireDbException;

class Collection
{

    /**
     * Filesystem helper
     * @var Fire\Db\Collection\Helper\FileSystem
     */
    private $_filesystem;

    /**
     * Index logic helper
     * @var Fire\Db\Collection\Logic\Indexing
     */
    private $_indexing;

    /**
     * Query logic helper
     * @var Fire\Db\Collection\Logic\Query
     */
    private $_query;

    /**
     * Class constructor
     * @param String $directory The path to the collection in the filesystem
     */
    public function __construct($directory)
    {
        $this->_filesystem = new FileSystem($directory);
        $this->_indexing = new Indexing($this->_filesystem);
        $this->_query = new Query($this->_filesystem, $this->_indexing);
    }

    /**
     * Find documents in the collection.
     * @param String|Object $filter The filter you want to use to apply to find documents
     * @param Integer $offset The offset you want to apply to the returned documents (used for pagination)
     * @param Integer $length The length of returned documents in the collection (used for pagination)
     * @param Boolean $reverseOrder The order in which the documents should be returned
     * @return Object|Array<Object>|NULL
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
    public function find($filter = null, $offset = 0, $length = 10, $reverseOrder = true)
    {
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
     * @param Object $document The document you want to store
     * @return Object The document updated with collection properties like "__id", "__revision", and "__created"
     * @throws Fire\FireDbException If $document is not an object
     *
     * Example:
     * $document = (object) [
     *     'property' => 'value'
     * ];
     * $collection->insert($document);
     */
    public function insert($document)
    {
        return $this->_query->upsertDocument($document, null);
    }

    /**
     * Updates a document in the collection.
     * @param String $id The ID of the document you would like to update
     * @param Object $document The document you want to store
     * @return Object The document updated with collection properties like "__id", "__revision", and "__created"
     * @throws Fire\FireDbException If $document is not an object
     *
     * Example:
     * $document = (object) [
     *     'property' => 'value'
     * ];
     * $collection->update('201708250622024656847159a0247a71b12', $document);
     */
    public function update($id, $document)
    {
        return $this->_query->upsertDocument($document, $id);
    }

    /**
     * Deletes a document from the collection.
     * @param  String $documentId The ID of the document you want to delete
     *
     * Example:
     * $collection->delete('201708250622024656847159a0247a71b12');
     */
    public function delete($documentId)
    {
        $this->_query->deleteDocument($documentId);
    }

    /**
     * Sets the configurations you would like to set for the database.
     * @param Fire\Db\Collection\Config $config
     *
     * Example:
     * $config = new Fire\Db\Collection\Config();
     * $config->setIndexable(['rand']);
     * $collection->setConfiguration($config);
     */
    public function setConfiguration(Config $config)
    {
        $this->_filesystem->setConfiguration($config);
    }

    /**
     * Returns the current configuration for the collection.
     * @return Fire\Db\Collection\Config
     *
     * Example:
     * $config = $collection->getConfiguration();
     */
    public function getConfiguration()
    {
        return $this->_filesystem->getConfiguration();
    }

}
