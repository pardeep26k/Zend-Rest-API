<?php
namespace Database;

use Config\Config as Config;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\Feature;

use Zend\Db\Adapter\ParameterContainer;

/**
 * @author Ankit Vishwakarma
 */
abstract class DealerAdapter extends AbstractTableGateway
{
    use Crud;
    
    private $validationMessage = [];
    private $skipValidation = false;
    
    public function __construct()
    { 
        Feature\GlobalAdapterFeature::setStaticAdapter(Config::getAdapter());
        $this->featureSet = new Feature\FeatureSet();
        $this->featureSet->addFeature(new Feature\GlobalAdapterFeature());
        $this->featureSet->addFeature(new Feature\MasterSlaveFeature(Config::getSlaveAdapter()));
        $this->initialize();
    }
     /**
     * Use INSERT ... ON DUPLICATE KEY UPDATE Syntax
     * @since mysql 5.1
     * @param array $insertData For insert array('field_name' => 'field_value')
     * @param array $updateData For update array('field_name' => 'field_value_new')
     * @return bool
     */
    public function insertOrUpdate(array $insertData, array $updateData)
    {
        $sqlStringTemplate = 'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s';
        $adapter           =  $this->getAdapter(); /* Get adapter from tableGateway */
		       
        $driver            = $adapter->getDriver();
        $platform          = $adapter->getPlatform();

        $tableName          = $platform->quoteIdentifier($this->getTable());

        $parameterContainer = new ParameterContainer();
        $statementContainer = $adapter->createStatement();
        $statementContainer->setParameterContainer($parameterContainer);
        /* Preparation insert data */
        $insertQuotedValue   = [];
        $insertQuotedColumns = [];
        foreach ($insertData as $column => $value)
        {
            $insertQuotedValue[]   = $driver->formatParameterName($column);
            $insertQuotedColumns[] = $platform->quoteIdentifier($column);
            $parameterContainer->offsetSet($column, $value);
        }

        /* Preparation update data */
        $updateQuotedValue = [];
        foreach ($updateData as $column => $value)
        {
            $updateQuotedValue[] = $platform->quoteIdentifier($column) . '=' . $driver->formatParameterName('update_' . $column);
            $parameterContainer->offsetSet('update_' . $column, $value);
        }

        /* Preparation sql query */
        $query = sprintf(
                $sqlStringTemplate, $tableName, implode(',', $insertQuotedColumns), implode(',', array_values($insertQuotedValue)), implode(',', $updateQuotedValue)
        );
        $statementContainer->setSql($query);
        return $statementContainer->execute();
    }
    
    
    protected function validate()
    {
        //must use the class if want to validate 
    }
    
    public function isValid()
    {
        if($this->skipValidation) return true;
        $this->validate();
        return empty($this->validationMessage);
    }
    
    protected function setValidationMessage($name, $message)
    {
        $this->validationMessage[$name] = $message;
    }
    
    public function getValidationMessage()
    {
        return $this->validationMessage;
    }
    
    public function skipValidation($flag = false)
    {
        $this->skipValidation = $flag;
    }
   
}
