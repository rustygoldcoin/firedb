<?php

namespace firedb\collection\helper;

use firedb\collection\logic\indexing;
use DateTime;

class filesystem {

    const FILE_META = '/.meta';
    const DIRECTORY_INDEX = '/index';
    const DIRECTORY_DOCUMENT = '/document';
    const DIRECTORY_DOCUMENT_META = '/document/meta';

    private $_dir;

    private $_metaFile;

    private $_indexDir;

    private $_docsDir;

    private $_metaDir;

    private $_metaData;

    public function __construct($directory) {
        $this->_dir = $directory;
        $this->_metaFile = $this->_dir . self::FILE_META;
        $this->_indexDir = $this->_dir . self::DIRECTORY_INDEX;
        $this->_docsDir = $this->_dir . self::DIRECTORY_DOCUMENT;
        $this->_metaDir = $this->_dir . self::DIRECTORY_DOCUMENT_META;
        $this->_metaData = false;

        if (!is_dir($this->_indexDir)) {
            mkdir($this->_indexDir);
        }
        if (!is_dir($this->_docsDir)) {
            mkdir($this->_docsDir);
        }
        if (!is_dir($this->_metaDir)) {
            mkdir($this->_metaDir);
        }
        if (!$this->collectionMetaDataExists()) {
            $metaData = (object) [
                'name' => basename($this->_dir)
            ];
            $this->writeCollectionMetaData($metaData);
        }
    }

    public function collectionMetaDataExists() {
        return file_exists($this->_metaFile);
    }

    public function getCollectionMetaData() {
        if ($this->_metaData) {
            return $this->_metaData;
        }
        $this->_metaData = $this->_getPhpObjectFile($this->_metaFile);
        return $this->_metaData;
    }

    public function writeCollectionMetaData($metaData) {
        $this->_meataData = false;
        $this->_writePhpObjectFile($this->_metaFile, $metaData);
    }

    public function documentExists($documentId) {
        return file_exists($this->_metaDir . '/' . $documentId);
    }

    public function getDocumentMetaData($documentId) {
        $documentMetaFilePath = $this->_metaDir . '/' . $documentId;
        return $this->_getFile($documentMetaFilePath);
    }

    public function writeDocumentMetaData($documentId, $documentMetaFile) {
        $documentMetaFilePath = $this->_metaDir . '/' . $documentId;
        $this->_writeFile($documentMetaFilePath, $documentMetaFile);
    }

    public function deleteDocumentMetaData($documentId) {
        if($this->documentExists($documentId)) {
            unlink($this->_metaDir . '/' . $documentId);
        }
    }

    public function getDocument($documentId, $revision = null) {
        if ($this->documentExists($documentId)) {
            if (is_null($revision)) {
                $revision = $this->getDocumentMetaData($documentId);
            }
            $documentFile = $this->_docsDir . '/' . $documentId . '.' . $revision;
            if (file_exists($documentFile)) {
                return $this->_getJsonFile($documentFile);
            }
        }
        return null;
    }

    public function writeDocument($documendId, $document) {
        $documentId = (!is_null($documendId)) ? $documendId : $this->generateUniqueId();
        $revision = $this->_generateRevisionNumber();
        $created = $this->_generateTimestamp();
        $documentFile = $this->_docsDir . '/' . $documentId . '.' . $revision;
        $document->__id = $documentId;
        $document->__revision = $revision;
        $document->__timestamp = $created;
        $this->_writeJsonFile($documentFile, $document);

        return $document;
    }

    public function indexExists($indexId) {
        $indexFile = $this->_indexDir . '/' . $indexId;
        return file_exists($indexFile);
    }

    public function getIndex($indexId) {
        if ($this->indexExists($indexId)) {
            $indexFile = $this->_indexDir . '/' . $indexId;
            return $this->_getFile($indexFile);
        }

        return '';
    }

    public function writeIndex($indexId, $index) {
        $indexFile = $this->_indexDir . '/' . $indexId;
        $this->_writeFile($indexFile, $index);
    }

    public function generateUniqueId() {
        $rand = uniqid(rand(10, 99));
        $time = microtime(true);
        $micro = sprintf('%06d', ($time - floor($time)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $time));
        $id = $date->format('YmdHisu') . $rand;
        return $id;
    }

    private function _getPhpObjectFile($phpObjectFilePath) {
        return unserialize($this->_getFile($phpObjectFilePath));
    }

    private function _writePhpObjectFile($phpObjectFilePath, $phpObj) {
        $this->_writeFile($phpObjectFilePath, serialize($phpObj));
    }

   private function _getJsonFile($jsonFilePath) {
       return json_decode($this->_getFile($jsonFilePath));
   }

   private function _writeJsonFile($jsonFilePath, $jsonObj) {
       $this->_writeFile($jsonFilePath, json_encode($jsonObj));
   }

   private function _getFile($filePath) {
       return file_get_contents($filePath);
   }

   private function _writeFile($filePath, $file) {
       $success = file_put_contents($filePath, $file);
       if ($success === false) {
           $this->_writeFile($filePath, $file);
       }
   }

   private function _generateRevisionNumber() {
       return rand(1000001, 9999999);
   }

   private function _generateTimestamp() {
       $time = microtime(true);
       $micro = sprintf('%06d', ($time - floor($time)) * 1000000);
       $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $time));
       return $date->format("Y-m-d H:i:s.u");
   }

}
