<?php
namespace firedb;

use firedb\collection;
use firedb\FireDbException;

class db {

    /**
     * The database directory
     * @var string
     */
    protected $_dbDir;

    protected $_collections;

    /**
     * The constructor
     * @param string $dir The directory of the database
     * @throws FireDbException If we cannot write to the database directory
     */
    public function __construct($dir) {
        $this->_dbDir = $dir;
        $this->_collections = [];

        if (!file_exists($this->_dbDir) || !is_dir($this->_dbDir) || !is_writable($this->_dbDir)) {
            throw new FireDbException('FireDB cannot write to directory "' . $this->_dbDir . '"');
        }
    }

    /**
     * Provides access to a collection.
     * @param string $collectionName The collection name
     * @return collection
     */
    public function collection($collectionName) {
        $collectionDir = $this->_getCollectionDir($collectionName);
        if (!$this->has($collectionName)) {
            mkdir($collectionDir);
        }
        if (!isset($this->_collections[$collectionName])) {
            $this->_collections[$collectionName] = new collection($collectionDir);
        }

        return $this->_collections[$collectionName];
    }

    /**
     * Deteremines if this database has this collection.
     * @param string $collectionName The collection name
     * @return boolean
     */
    public function has($collectionName) {
        $collectionDir = $this->_getCollectionDir($collectionName);
        return file_exists($collectionDir) && is_dir($collectionDir) && is_writeable($collectionDir);
    }

    /**
     * Returns the collection directory based on the collection name.
     * @param string $collectionName The collection name
     * @return string
     */
    protected function _getCollectionDir($collectionName) {
        return $this->_dbDir . '/' . $collectionName;
    }

}