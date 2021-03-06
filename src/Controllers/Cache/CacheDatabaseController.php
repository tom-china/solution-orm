<?php

namespace SolutionORM\Controllers\Cache;

use SolutionORM\Interfaces\CacheInterface;

class CacheDatabaseController implements CacheInterface {

    private $connection;

    function __construct(PDO $connection) {
        $this->connection = $connection;
    }

    function load($key) {
        $result = $this->connection->prepare("SELECT data FROM solutionorm WHERE id = ?");
        $result->execute(array($key));
        $return = $result->fetchColumn();
        if (!$return) {
            return null;
        }
        return unserialize($return);
    }

    function save($key, $data) {
        // REPLACE is not supported by PostgreSQL and MS SQL
        $parameters = array(serialize($data), $key);
        $result = $this->connection->prepare("UPDATE solutionorm SET data = ? WHERE id = ?");
        $result->execute($parameters);
        if (!$result->rowCount()) {
            $result = $this->connection->prepare("INSERT INTO solutionorm (data, id) VALUES (?, ?)");
            try {
                @$result->execute($parameters); // @ - ignore duplicate key error
            } catch (PDOException $e) {
                if ($e->getCode() != "23000") { // "23000" - duplicate key
                    throw $e;
                }
            }
        }
    }

}
