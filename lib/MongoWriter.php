<?php

class MongoWriter implements MongoWriteConfirmation {
    const WRITE_INSERT = 1;
    const WRITE_UPDATE = 2;
    const WRITE_SAVE   = 3;
    const WRITE_REMOVE = 4;

    // FIXME: Isn't this the timeout errorcode?
    // Surely doesn't look like its originating from the server, but this is the current behaviour
    // There is a ticket about fixing the wtimeout vs timeout mess: PHP-613
    const EXCEPTION_CODE_WTIMEOUT  = 4;
    const EXCEPTION_DUPLICATE_ID   = 11000;
    const EXCEPTION_CODE_NO_MASTER = 10058;

    protected $policy;
    protected $retryData = array();


    function __construct(MongoWriteConfirmationPolicy $policy) {
        $this->setPolicy($policy);
    }
    function getPolicy() {
        return $this->policy;
    }
    protected function setPolicy(MongoWriteConfirmationPolicy $policy) {
        $this->policy = $policy;
        return $this;
    }

    function insert(MongoCollection $collection, $document, array $options = array()) {
        return $this->_write(self::WRITE_INSERT, $collection, $document, $options);
    }
    function save(MongoCollection $collection, $document, array $options = array()) {
        return $this->_write(self::WRITE_SAVE, $collection, $document, $options);
    }
    function remove(MongoCollection $collection, $document, array $options = array()) {
        return $this->_write(self::WRITE_REMOVE, $collection, $document, $options);
    }
    function retry(MongoWriteConfirmationPolicy $oneTimePolicy = null) {
        return $this->_attempt($this->retryData[0], $oneTimePolicy);
    }
    function rollback(MongoWriteConfirmationPolicy $oneTimePolicy = null) {
        return $this->_attempt(self::WRITE_REMOVE, $oneTimePolicy);
    }

    protected function _write($store, MongoCollection $collection , $document, array $options = array()) {
        $this->retryData = array($store, $collection, $document, $options);

        $options = $this->getPolicy()->getWriteOptions() + $options;
        try {
            switch((int)$store) {
            case self::WRITE_INSERT:
                $retval = $collection->insert($document, $options);
                break;
            case self::WRITE_SAVE:
                $retval = $collection->save($document, $options);
                break;
            case self::WRITE_REMOVE:
                $retval = $collection->remove($document, $options);
                break;
            case self::WRITE_UPDATE:
                /* FIXME: Needs additional $criteria */
                throw new RuntimeException("Not implemented yet");
            default:
                throw new InvalidArgumentException("Unknown write operation: " .(int) $store);
            }

            return $this->getPolicy()->onSuccess($retval);
        } catch(MongoCursorException $e) {
            switch($e->getCode()) {
            case self::EXCEPTION_CODE_WTIMEOUT:
                // FIXME: Pass in $e->getErrorDocument() when implemented
                return $this->getPolicy()->onWTimeout($this, NULL);
            case self::EXCEPTION_CODE_NO_MASTER:
            default:
                return $this->getPolicy()->onFailure($this);
            }
        }
    }
    protected function _attempt($operation, MongoWriteConfirmationPolicy $oneTimePolicy = null) {
        if ($oneTimePolicy) {
            $backup = $this->getPolicy();
            $this->setPolicy($oneTimePolicy);
        }
        $retval = $this->_write($operation, $this->retryData[1], $this->retryData[2], $this->retryData[3]);
        if ($oneTimePolicy) {
            $this->setPolicy($backup);
        }
        return $retval;
    }
}

