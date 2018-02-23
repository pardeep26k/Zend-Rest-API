<?php

namespace Database;

use Config\Config as Config;

/**
 * @author Pardeep Kumar 
 */

class MongoAdapter 
{
    /**
     * @var array
     */
    protected $_options = array(
        "connect" => true,
        "timeout" => 5000,
    );

    /**
     * @var string
     */
    protected $_serverClass;

    /**
     * @var \Mongo
     */
    public $_connection;

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var \MongoDB
     */
    protected $_db;

    /**
     *
     * @param array $config
     *
     * @return \Mongo
     */
    public function __cosntruct(array $config)
    {
        try{
                $this->_checkRequiredOptions($config);
                $this->_config = $config;
                foreach ($config as $key => $value) {
                    if (empty($value)) {
                        unset($config[$key]);
                    }
                }
                $this->_serverClass = class_exists('\MongoClient') ? '\MongoClient' : '\Mongo';
                $this->_connection =new $this->_serverClass('mongodb://' . $config['host']);
                $this->setUpDatabase($config['dbname']);
                return $this->_connection->$config['dbname'];
           }
        catch (Exception $ex) 
            {
                 return false;
            }
    }

    public function getDbName()
    {
        return $this->_config['db'];
    }

    public function getPassword()
    {
        return $this->_config['password'];
    }

    public function getUsername()
    {
        return $this->_config['username'];
    }

    public function getHost()
    {
        return $this->_config['host'];
    }

    public function getPort()
    {
        return $this->_config['port'];
    }
    public function getConnection(){
        return $this->_connection;
    }
    public function setUpDatabase($db = null)
    {
        $conn = $this->getConnection();

        if ($db !== null) {
            $this->_config['db'] = $db;
        }

        $this->_db = $conn->selectDB($this->getDbName());

        return $this->_db;
    }

    public function getMongoDB()
    {
        return $this->_db;
    }

    public function query($query, $bind = array())
    {
        return $this->_db->execute($query);
    }

    
    protected function _checkRequiredOptions(array $config)
    {
        if (!array_key_exists('dbname', $config)) {
            throw new Exception(
                "Configuration array must have a key for 'dbname' that names the database instance"
            );
        }
        if (!array_key_exists('password', $config)) {
            throw new Exception(
                "Configuration array must have a key for 'password' for login credentials"
            );
        }
        if (!array_key_exists('username', $config)) {
            throw new Exception(
                "Configuration array must have a key for 'username' for login credentials"
            );
        }
        if (!array_key_exists('host', $config)) {
            throw new Exception(
                "Configuration array must have a key for 'host'"
            );
        }
        if (!array_key_exists('port', $config)) {
            throw new Exception(
                "Configuration array must have a key for 'port'"
            );
        }
    }

    /** Abstract methods implementations **/

    /**
     * Returns a list of the collections in the database.
     *
     * @return array
     */
    public function listTables()
    {
        return $this->_db->listCollections();
    }

    /**
     * @see self::listTables()
     */
    public function listCollections()
    {
        return $this->listTables();
    }

    /**
     * @todo improve
     */
    public function describeTable($tableName, $schemaName = null)
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("Not implemented yet");
    }

    /**
     * {@inheritdoc}
     */
    protected function _connect()
    {
        if ($this->_options["connect"] == false) {
            $this->_connection->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->_connection->connected;
    }

    /**
     * {@inheritdoc}
     */
    public function closeConnection()
    {
        return $this->_connection->close();
    }

    public function prepare($sql)
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("Cannot prepare statements in MongoDB");
    }

    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("Not implemented yet");
    }

    protected function _beginTransaction()
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("There are no transactions in MongoDB");
    }

    protected function _commit()
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("There are no commits(ie: transactions) in MongoDB");
    }

    protected function _rollBack()
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("There are no rollbacks(ie: transactions) in MongoDB");
    }

    /**
     * @todo improve
     */
    public function setFetchMode($mode)
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("Not implemented yet");
    }

    /**
     * @todo improve
     */
    public function limit($sql, $count, $offset = 0)
    {
        throw new Zend_Db_Adapter_Mongodb_Exception("Not implemented yet");
    }

    /**
     * @todo improve
     */
    public function supportsParameters($type)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        if ($this->_serverClass === '\MongoClient') {
            return \MongoClient::VERSION;
        }

        return \Mongo::VERSION;
    }
}
