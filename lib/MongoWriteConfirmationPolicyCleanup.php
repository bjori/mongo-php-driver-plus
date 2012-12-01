<?php

class MongoWriteConfirmationPolicyCleanup implements MongoWriteConfirmationPolicy {
    protected $maxRetries  = 0;
    protected $retriesLeft = 0;

    function __construct($retry = 3) {
        $this->retriesLeft =
            $this->maxRetries  = (int)$retry;
    }

    function decreaseRetries() {
        return --$this->retriesLeft;
    }

    function onWTimeout(MongoWriteConfirmation $writer, $errorDocument) {
        $writer->rollback(new MongoWriteConfirmationPolicyIgnore);
        return false;
    }

    function onFailure(MongoWriteConfirmation $writer) {
        if ($this->decreaseRetries() > 0) {
            $retval = $writer->retry();
            return $retval;
        }

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
}

