<?php

namespace Fire\Db\Collection\Logic;

use Fire\Db\Collection\Helper\FileSystem;
use Fire\Db\Collection\Logic\Indexing;
use Fire\FireDbException;

/**
 * This helper class is used to contain all query logic for the collection.
 */

class Query
{

    /**
     * Filesystem helper
     * @var Fire\Db\Collection\Helper\FileSystem
     */
    private $_filesystem;

    /**
     * Indexing logic helper
     * @var Fire\Db\Collection\Logic\Indexing
     */
    private $_indexing;

    /**
     * The Constructor
     * @param Fire\Db\Collection\Helper\FileSystem $filesystem
     * @param firedb\collection\logic\indexing $indexing
     */
    public function __construct(FileSystem $filesystem, Indexing $indexing)
    {
        $this->_filesystem = $filesystem;
        $this->_indexing = $indexing;
    }

    /**
     * Inserts a document into the collection. Updates the document if it already exists.
     * @param object $document
     * @param string $documentId
     * @return object
     */
    public function upsertDocument($document, $documentId = null)
    {
        if (!is_object($document)) {
            throw new FireDbException('The document you are trying to insert/update must be an object.');
        }
        $document = $this->_filesystem->writeDocument($documentId, $document);
        if ($this->_filesystem->documentExists($document->__id)) {
            $currentDocument = $this->_filesystem->getDocument($document->__id);
            $this->_indexing->removeDocumentIndex($currentDocument);
        }
        $this->_indexing->indexDocument($document);
        $this->_filesystem->writeDocumentMetaData($document->__id, $document->__revision);
        return $document;
    }

    /**
     * Returns a document.
     * @param  string $documentId
     * @return object|null
     */
    public function getDocument($documentId)
    {
        return $this->_filesystem->getDocument($documentId);
    }

    /**
     * Returns a subset of documents from the collection that matches the filter object.
     * @param  mixed $filterObj
     * @param  integer $offset
     * @param  integer $length
     * @param  boolean $reverseOrder
     * @return array
     */
    public function getDocumentsByFilter($filterObj, $offset, $length, $reverseOrder)
    {
        $documentIds = [];
        foreach (get_object_vars($filterObj) as $property => $value) {
            $indexLookupId = $this->_indexing->generateIndexLookupId($property, $value);
            $index = $this->_indexing->buildIndexByLookupId($indexLookupId);
            if (empty($index)) {
                return null;
            }
            if (count($documentIds) === 0) {
                $documentIds = $index;
            } else {
                $documentIds = array_intersect($documentIds, $index);
            }
        }

        if ($reverseOrder) {
            rsort($documentIds, SORT_STRING);
        } else {
            sort($documentIds, SORT_STRING);
        }
       //start at the offset and return only the amount of requested objects
       $documentIds = array_slice($documentIds, $offset, $length);
       return $this->_mapDocuments($documentIds);
    }

    /**
     * Returns a subset of unfiltered documents.
     * @param integer $offset
     * @param integer $length
     * @param boolean $reverseOrder
     * @return array
     */
    public function getAllDocuments($offset, $length, $reverseOrder)
    {
        $documentIds = $this->_indexing->getCollectionIndexedRegistry($offset, $length, $reverseOrder);
        return $this->_mapDocuments($documentIds);
    }

    /**
     * Deletes a document in a collection.
     * @param string $documentId
     * @return void
     */
    public function deleteDocument($documentId)
    {
        if ($this->_filesystem->documentExists($document->__id)) {
            $currentDocument = $this->_filesystem->getDocument($document->__id);
            $this->_indexing->removeDocumentIndex($currentDocument);
        }
        $this->_filesystem->deleteDocumentMetaData($documentId);
    }

    /**
     * Helper method used to map documents to an array of document ids.
     * @param  array $documentIds
     * @return array
     */
    private function _mapDocuments($documentIds)
    {
        $documentObjects = array_filter(array_map([$this, 'getDocument'], $documentIds));
        return $documentObjects;
    }

}
