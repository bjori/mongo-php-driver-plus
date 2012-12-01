<?php

class MongoWriteConfirmationPolicyCleanup implements MongoWriteConfirmationPolicy {
    protected $maxRetries  = 0;
    protected $retriesLeft = 0;

    protected $logWarningCallback = null;
    protected $logErrorCallback   = null;

    function __construct($retry = 3, callable $warning = null, callable $error = null) {
        $this->retriesLeft =
            $this->maxRetries  = (int)$retry;
        $this->logWarningCallback = $warning;
        $this->logErrorCallback   = $error;
    }

    function decreaseRetries() {
        return --$this->retriesLeft;
    }

    function onWTimeout(MongoWriteConfirmation $writer, $errorDocument) {
        $this->logError("WriteConcern timedout, rollbacking the write on primary", $errorDocument);
        $writer->rollback(new MongoWriteConfirmationPolicyIgnore);
        return false;
    }

    function onFailure(MongoWriteConfirmation $writer) {
        if ($this->decreaseRetries() > 0) {
            $this->logWarning("WriteConcern failed. Retrying..");
            $retval = $writer->retry();
            return $retval;
        }

        $this->logError("WriteConcern failed. Hit retry limit", NULL);
        $this->resetPolicy();
        /* No need to cleanup the data, the write failed all the time */
        return false;
    }

    function onSuccess($retval) {
        $this->resetPolicy();
        return $retval;
    }

    function getWriteOptions() {
        return array();
    }

    function resetPolicy() {
        /* Reset the retries so we can reuse the same policy object */
        $this->retriesLeft = $this->maxRetries;
        return $this;
    }

    function logWarning($msg) {
        if ($this->logWarningCallback) {
            return call_user_func_array($this->logWarningCallback, array($msg));
        }
        return false;
    }
    function logError($msg, $errorDocument) {
        if ($this->logErrorCallback) {
            return call_user_func_array($this->logErrorCallback, array($msg, $errorDocument));
        }
        return false;
    }
}

