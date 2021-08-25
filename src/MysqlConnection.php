<?php

/**
 * Abstraction of a Mysql connection
 */
class MysqlConnection
{
    private $host, $user, $password, $name;
    private $mysqli;
    private $connected;
    private $utf8Adapt;
    private $executedQueries = [];
    
    /**
     * 
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $name Database name
     */
    public function __construct($host, $user, $password, $name)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->name = $name;
        $this->utf8Adapt = false; //TODO: remove
        
        $this->connected = false;
        $this->mysqli = null;
    }
    
    public function __destruct()
    {
        if ($this->connected) {
            $this->mysqli->close();
            $this->mysqli = null;
            $this->connected = false;
        }
    }
    
    /**
     * Execute a mysql query that does not return results (like INSERT, UPDATE, DELETE but not SELECT).
     * For insert queries, return the id of the newly created line
     * 
     * @param string $sql
     * @return integer insert_id for insert queries, 0 otherwise
     */
    public function executeQuery($sql)
    {
        $this->executedQueries[] = $sql;
        
        return $this->getMysqli()->query($this->utf8Adapt ? utf8_decode($sql) : $sql) ? $this->getMysqli()->insert_id : 0;
    }
    
    /**
     * Execute a mysql query and return results in an array
     * 
     * @param string $sql
     * @param string $indexField query field to use to index results in the returned array
     * @return array
     * @throws \Exception
     */
    public function getQueryResults($sql, $indexField = 'id')
    {
        $items = [];
        $this->executedQueries[] = $sql;
        $result = $this->getMysqli()->query($this->utf8Adapt ? utf8_decode($sql) : $sql);
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $item = [];
                foreach ($row as $key => $value) {
                    $item[$key] = $this->utf8Adapt ? utf8_encode($value) : $value;
                }
                if (isset($item[$indexField])) {
                    $items[$item[$indexField]] = $item;
                } else {
                    $items[] = $item;
                }
            }
        } else {
            throw new \Exception('Error while executing "' . $sql . '": ' . $this->getMysqli()->error);
        }
        return $items;
    }
    
    /**
     * Return an array of all queries executed by the instance
     * 
     * @return array
     */
    public function getExecutedQueries()
    {
        return $this->executedQueries;
    }
    
    /**
     * Return underlying MySQLi object
     * 
     * @return \MySQLi
     * @throws \Exception
     */
    private function getMysqli()
    {
        if (!$this->connected) {
            $this->mysqli = @new \MySQLi($this->host, $this->user, $this->password, $this->name);
            if ($this->mysqli->connect_errno) {
                throw new \Exception('Could not connect to MySQL server: "' . $this->mysqli->connect_errno . '"');
            } else {
                $this->mysqli->set_charset('utf8');
                $this->connected = true;
            }
        }
        return $this->mysqli;
    }
    
    /**
     * Same as mysql_escape
     * 
     * @param string|array $input
     * @return string|array
     */
    public static function escapeValue($inp)
    { 
        if (is_array($inp)) return array_map(__METHOD__, $inp);

        if (!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
        } 

        return $inp;
    }
}
