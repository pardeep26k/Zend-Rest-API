<?php

namespace Database;

/**
 * Description of Cache
 *
 * @author ankitvishwakarma
 */
trait Cache
{

    /**
     * Get a single row by passed parameter
     * @param string $key
     * @param string|int $value
     * @return array
     */
    public function getByFieldFromCache($key, $value)
    {
        $objectID = $this->getIDByField($key, $value);
        return $this->getByIDFromCache($objectID);
    }

    /**
     * Get row by fields
     * 
     * @param array $keysAndValues
     * @return array single row
     */
    public function getByFieldsFromCache($keysAndValues)
    {
        $objectID = $this->getIDByFields($keysAndValues);
        return $this->getByIDFromCache($objectID);
    }

    public function getManyByFieldFromCache($key, $value)
    {
        $objectIDs = $this->getIDsByField($key, $value);
        return $this->getByIDsFromCache($objectIDs);
    }

    public function getManyByFieldsFromCache($keysAndValues)
    {
        $objectIDs = $this->getIDsByFields($keysAndValues);
        return $this->getByIDsFromCache($objectIDs);
    }

    /**
     * Get rows by ids
     * @param array $ids
     * @return array
     */
    public function getByIDsFromCache($ids = [])
    {
        if(empty($ids))
        {
            return [];
        }

        $results = array();
        foreach ($ids as $id)
        {
            $results[$id] = $this->getByIDFromCache($id);
        }
        return $results;
    }

    /**
     * Get the single row by id
     * 
     * @param int $id
     * @return array 
     */
    public function getByIDFromCache($id = null)
    {
        if(is_null($id))
        {
            return [];
        }

        $memoryKey = $this->getTable() . '_' . $id;
        $row       = static::getInstance()->getItem($memoryKey);
        
        if ($row === null)
        {
            $row = $this->getByField('id', $id);
            
            static::getInstance()->setItem($memoryKey,$row);
        }  
        return $row;
    }

    
    
    public function removeFromCache($id)
    {
        $key = $this->getTable() . '_' . $id;
        static::getInstance()->removeItem($key);
    }
    
    public function remove($id)
    {
        $this->removeFromDb($id);
        $this->removeFromCache($id);
    }
    
    public function save($data = [],$where = [])
    {
        $id = 0;
        isset($data['id']) && $id = (int) $data['id'];
        
        if ($id == 0 && empty($where))
        {
            return $this->saveDb($data, $where);
        }
        else
        {
            $condition = [];
            if(!empty($id))
            {
                $condition['id'] = $id;
            }
            if(!empty($where))
            {
                $condition = array_merge($where, $condition);
            }
            
            $records = $this->getManyByFields($condition);
            foreach($records as $record)
            {
                $this->saveDb($data, array('id' => $record['id']));
                $this->removeFromCache($record['id']);
            }
            return true;
        }
    }
    
    
    public function removeByWhereFromCache($condition)
    {
        $rows = $this->getManyByFields($condition);
        foreach($rows as $row)
        {
            $this->remove($row['id']);
        }
    }
}
