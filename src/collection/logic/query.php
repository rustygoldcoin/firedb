<?php

namespace firedb\collection\logic;

use firedb\collection\helper\filesystem;
use firedb\collection\logic\indexing;
use FireDbException;

class query {

    private $_filesystem;

    private $_indexing;

    public function __construct(filesystem $filesystem, indexing $indexing) {
        $this->_filesystem = $filesystem;
        $this->_indexing = $indexing;
    }

    public function upsertDocument($document, $documentId = null) {
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

    public function getDocument($documentId) {
        return $this->_filesystem->getDocument($documentId);
    }

    public function getDocumentsByFilter($filterObj, $offset, $length, $reverseOrder) {
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

    public function getAllDocuments($offset, $length, $reverseOrder) {
        $documentIds = $this->_indexing->getCollectionIndexedRegistry($offset, $length, $reverseOrder);
        return $this->_mapDocuments($documentIds);
    }

    public function deleteDocument($documentId) {
        if ($this->_filesystem->documentExists($document->__id)) {
            $currentDocument = $this->_filesystem->getDocument($document->__id);
            $this->_indexing->removeDocumentIndex($currentDocument);
        }
        $this->_filesystem->deleteDocumentMetaData($documentId);
    }

    private function _mapDocuments($documentIds) {
        $documentObjects = array_filter(array_map([$this, 'getDocument'], $documentIds));
        return $documentObjects;
    }

}
