<?php

class SQL extends PDO {
    protected $driver;
    protected $serverName;
    protected $loginName;
    protected $loginPWD;
    protected $databaseName;

    public function __construct($driver, $serverName, $loginName, $loginPWD, $databaseName) {
        $this->driver = $driver;
        $this->serverName = $serverName;
        $this->databaseName = $databaseName;
        $this->loginName = $loginName;
        $this->loginPWD = $loginPWD;
        parent::__construct($this->driver.':host='.$this->serverName.';dbname='.$this->databaseName, $this->loginName, $this->loginPWD);
    }

    /** @return PDOStatement */
    public function exec($query, $paramArray=null) {
        $statement = $this->prepare($query);
        $statement->execute($paramArray);
        return $statement;
    }

    public function execToClasses($className, $query, $paramArray=null) {
        $statement = $this->exec($query, $paramArray);
        return $statement->fetchAll(PDO::FETCH_CLASS, $className);
    }
}

?>
