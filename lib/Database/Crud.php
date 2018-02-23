<?php

/**
 * Description of Crud
 *
 * @author ankitvishwakarma
 */
namespace Database;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Predicate\Operator;

trait Crud
{   
    private $predicates = [];
    
    private $resultSet = null;
    
    private $lastQueryString = null;
    
    /**
     * Fetch all the records of a table
     * @return array
     */
    public function fetchAll()
    {
        return $this->getManyByFields();
    }
    
    public function getIDByField($key, $value)
    {
        return current($this->getIDsByFields([$key => $value]));
    }
    
    public function getIDByFields($parameters)
    {
        return current($this->getIDsByFields($parameters));
    }
    
    public function getIDsByField($key, $value)
    {
        return $this->getIDsByFields([$key => $value]);
        
    }
    
    public function getIDsByFields($parameters)
    {
        $res = $this->select(function(Select $select) use ($parameters) {
                    $select->columns(['id']);
                    $this->setPredicates($select);
                    $select->where($parameters);
                })->toArray();
                
                
        return $this->grepFieldValues('id', $res);
    }
    
    public function grepFieldValues($field, $data)
    {
        $fv = [];
        foreach($data as $rec)
        {
            $fv[] = $rec[$field];
        }
        
        return $fv;
    }
    
    
    public function setColumns(array $values)
    {
        $this->predicates['columns'] = $values;
        return $this;
    }
    
    public function setOrder($string, $overwrite = false)
    {
        if($overwrite)
        {
            unset($this->predicates['orderBy']);
        }
        $this->predicates['orderBy'][] = $string;
        return $this;
    }
    
    public function setGroup($string, $overwrite = false)
    {
        if($overwrite)
        {
            unset($this->predicates['groupBy']);
        }
        
        $this->predicates['groupBy'][] = $string;
        return $this;
    }
    
    public function setIn($key, array $values, $overwrite = false)
    {
        if($overwrite)
        {
            unset($this->predicates['in']);
        }
        $this->predicates['in'][][$key] = $values;
        return $this;
    }
    
    public function setNotIn($key, array $values, $overwrite = false)
    {
        if($overwrite)
        {
            unset($this->predicates['notIn']);
        }
        if(!empty($values))
        {
            $this->predicates['notIn'][][$key] = $values;
        }
        return $this;
    }
    
    public function setNotEqual($key, $value, $overwrite = false)
    {
        if($overwrite)
        {
            unset($this->predicates['notEqual']);
        }
        $this->predicates['notEqual'][][$key] = $value;
        return $this;
    }
    
    public function setLimit($limit)
    {
        $this->predicates['limit']= $limit;
        return $this;
    }
    
    public function setOffset($offset)
    {
        $this->predicates['offset']= $offset;
        return $this;
    }
    
    
    public function setWhere($key, $value = null, $operator = '=', $overwrite = false)
    {
        if($overwrite)
        {
            unset($this->predicates['where']);
        }
        if(is_array($key))
        {
            $this->predicates['where'][] = $key;
        }
        elseif((is_string($key)) && !empty($value))
        {
            $this->predicates['where'][][$key] = [$value, $operator] ;
        }
        elseif(is_object($key))
        {
            $this->predicates['whereObject'][][serialize($key)] = [$value, $operator] ;
        }
        return $this;
    }
    
    public function setPredicates(Select &$select)
    {
        
        if(!empty($this->predicates['columns']))
        {
            $select->columns($this->predicates['columns']);
        }
        
        if (!empty($this->predicates['where']))
        {
            while(list(, $where) = each($this->predicates['where']))
            {
                 
                foreach ($where as $key => $value)
                {
                        if(is_array($value))
                        {
                            $operator = $value[1];
                            $value = $value[0];
                        }
                        else
                        {
                            $value = $value;
                            $operator = '=';
                        }
                        
                        $select->where(new Operator($key, $operator, $value));
                    
                    
                }
            }
        }
        
        if (!empty($this->predicates['whereObject']))
        {
            while(list(, $where) = each($this->predicates['whereObject']))
            {
                 
                foreach ($where as $key => $value)
                {
                        if(is_array($value))
                        {
                            $operator = $value[1];
                            $value = $value[0];
                        }
                        else
                        {
                            $value = $value;
                            $operator = '=';
                        }
                        
                        $select->where(unserialize($key));
                }
            }
        }

        
        if (!empty($this->predicates['expression']))
        {
            while(list(, $expression) = each($this->predicates['expression']))
            {
                foreach ($expression as $key => $value)
                {
                    $select->where->addPredicate($value);
                }
            }
        }
        
        if (!empty($this->predicates['orderBy']))
        {
                foreach ($this->predicates['orderBy'] as $value)
                {
                    $select->order($value);
                }
            
        }
        
        if (!empty($this->predicates['groupBy']))
        {
                foreach ($this->predicates['groupBy'] as $value)
                {
                    $select->group($value);
                }
            
        }
        
        if (!empty($this->predicates['join']))
        {
                foreach ($this->predicates['join'] as $value)
                {
                    list($table, $condition, $column, $type) = $value;
                    $select->join($table, $condition, $column, $type);
                }
            
        }
        
        if (!empty($this->predicates['in']))
        {
            while(list(, $in) = each($this->predicates['in']))
            {
                foreach ($in as $key => $value)
                {
                    $select->where(new In($key, $value));
                }
            }
        }

        if (!empty($this->predicates['notIn']))
        {
            while(list(, $notIn) = each($this->predicates['notIn']))
            {
                foreach ($notIn as $key => $value)
                {
                    $select->where(new NotIn($key, $value));
                }
            }
        }

        if (!empty($this->predicates['notEqual']))
        {
            while(list(, $notEqual) = each($this->predicates['notEqual']))
            {
                foreach ($notEqual as $key => $value)
                {
                    $select->where($select->where->notEqualTo($key, $value));
                }
            }
        }
        
        if(!empty($this->predicates['limit']))
        {
            $select->limit((int)$this->predicates['limit']);
        }
        
        if(!empty($this->predicates['offset']))
        {
            $select->offset((int)$this->predicates['offset']);
        }
    }

    public function getPredicates()
    {
        return $this->predicates; 
    }
    
    /**
     * Get the row by id
     * 
     * @param int $id
     * @return array
     */
    public function getByID($id)
    {
        return $this->getByField('id', $id);
    }
    
    /**
     * Get the rows by ids
     * 
     * @param array $ids
     * @return array
     */
    public function getByIDs($ids = [])
    {
        return $this->select(function(Select $select) use ($ids) {
                    $select->where(new In('id', $ids));
                    $this->setPredicates($select);
                })->toArray();
    }

    /**
     * Get the single row by key and value
     * @param string $key
     * @param string|int $value
     * @return row
     */
    public function getByField($key, $value)
    {
        return $this->getByFields([$key => $value]);
    }
    
    /**
     * Get the single row by passed condition array as parameters
     * 
     * @param array $parameters
     * @return array
     */
    public function getByFields($parameters = [])
    {
        return current($this->getManyByFields($parameters));
    }
    
    /**
     * Get multiple rows by the passed key and value
     * 
     * @param string $key
     * @param string|int $value
     * @return array
     */
    public function getManyByField($key, $value)
    {
        return $this->getManyByFields([$key => $value]);
    }
    
    /**
     * Get multiple rows by the passed set of condition as parameters array
     * 
     * @param array $parameters
     * @return array
     */
    public function getManyByFields($parameters = [])
    {
//        error_reporting(E_ALL);
        $this->resultSet = $this->select(function(Select $select) use ($parameters) {
                    $this->getDefaultCondition();
                    $this->setPredicates($select);
                    $select->where($parameters);
                    $this->lastQueryString = str_replace('"', '', $select->getSqlString());
                     //echo str_replace('"', '', $select->getSqlString()); exit;
                });
        return $this->result();
    }
    
    public function getLastQueryString()
    {
        return $this->lastQueryString;
    }
    
    public function get()
    {
        $this->resultSet = $this->select(function(Select $select){
                    $this->setPredicates($select);
                });
        return $this;
    }
    
    public function result()
    {
        return $this->resultSet->toArray();
    }
    
    public function resultOne()
    {
        return current($this->resultSet->toArray());
    }
    
    public function queryString()
    {
        return $this->resultSet->getDataSource()->getResource();
    }
    
    
    
    /**
     * Insert or update data into the table 
     * 
     * @param array $data
     * @throws \Exception
     */
    public function save($data = [], $where = [])
    {
        if(!$this->isValid())
        {
            return 0;
        }
        
        $id = 0;
        isset($data['id']) && $id = (int) $data['id'];
        
        if ($id == 0 && empty($where))
        {
            $this->insert($data);
            return $this->lastInsertValue;
        }
        else
        {
            $condition = [];
            if($id !== 0)
            {
                $condition = [
                  'id' => $id,
                   
                ];
            }
            $condition = array_merge($condition, $where);
            try{
                return $this->update($data, $condition);
            }
            catch(\Exception $e)
            {
                return $e->getMessage();
            }
        }
    }
    
    /**
     * Remove the row from the table on the basis of the $id passed as parameter.
     * @param int|array $id
     * @throws \LogicException Only int or array allowed
     */
    public function remove($id)
    {
        if(is_array($id))
        {
            $where = $id;
        }
        elseif(is_int($id))
        {
            $where = ['id' => $id];
        }
        else
        {
            throw new \LogicException('Only integer or array allowed');
        }
        
        $this->delete($where);
    }
    
    /**
     * Remove the row from the table by the passed condition array
     * @param array $where
     */
    public function removeByWhere($where)
    {
        $this->remove($where);
    }
    
     /**
     * Put a where clause in an storage to apply this condition in any select query
     * @return array
     */
    protected function getDefaultCondition()
    {
        return false;
    }
    
    public function setJoin($table, $condition, $column, $type)
    {
        
        $this->predicates['join'][]= [$table, $condition, $column, $type];
        return $this;
    
    }
    
}
