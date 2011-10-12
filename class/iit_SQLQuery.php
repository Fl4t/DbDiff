<?php

class iit_SQLQuery extends PDO {
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

    //public function execute($input = array ()) {
        //try {
            //return parent::execute($input);
        //}
        //catch(PDOException $Exception) {
            //LogMessage($Exception->getMessage().'-'.$Exception->getTraceAsString().'
                //- '.$query,1);
        //}
        //return null;
    //}
    /** @return PDOStatement */
    public function exec($query, $paramArray=null) {
        $statement = $this->prepare($query);
        $statement->execute($paramArray);
        return $statement;
    }
    //public function execNonQuery($query, $paramArray=null) {
        //$statement = $this->exec($query,$paramArray);
        //return $statement->rowCount();
    //}
    //public function execToArray($query, $paramArray=null) {
        //$statement = $this->exec($query,$paramArray);
        //$statement->setFetchMode(PDO::FETCH_ASSOC);
        //return $statement->fetchAll();
    //}
    //public function execToObject($query, $paramArray=null) {
        //$statement = $this->exec($query,$paramArray);
        //return $statement->fetch(PDO::FETCH_OBJ);
    //}
    //public function execToObjects($query, $paramArray=null) {
        //$statement = $this->exec($query,$paramArray);
        //return $statement->fetchAll(PDO::FETCH_OBJ);
    //}
    public function execToClasses($className, $query, $paramArray=null) {
        $statement = $this->exec($query,$paramArray);
        return $statement->fetchAll(PDO::FETCH_CLASS, $className);
    }
    //public function execIntoObject($obj, $query, $paramArray=null) {
        //$statement = $this->exec($query,$paramArray);
        //$statement->setFetchMode(PDO::FETCH_INTO, $obj);
        //return $statement->fetch(PDO::FETCH_INTO);
    //}
    //public function execNum($query) {
        //$statement = $this->exec($query);
        //$statement->setFetchMode(PDO::FETCH_NUM);
        //return $statement->fetchAll();
    //}
    //public function execColumn($query, $paramArray=null, $columnId=0) {
        //$statement = $this->exec($query,$paramArray);
        //return $statement->fetchAll(PDO::FETCH_COLUMN,$columnId);
    //}
    //public function execFetch($query) {
        //$statement = $this->exec($query);
        //$statement->setFetchMode(PDO::FETCH_BOTH);
        //return $statement->fetchAll();
    //}
}

?>
