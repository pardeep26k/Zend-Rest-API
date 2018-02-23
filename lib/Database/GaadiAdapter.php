<?php

namespace Database;

use Config\Config as Config;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;
use Zend\Db\TableGateway\Feature\FeatureSet;

abstract class GaadiAdapter extends AbstractTableGateway
{
    public function __construct()
    {
        GlobalAdapterFeature::setStaticAdapter(Config::getGaadiAdapter());
        $this->featureSet = new FeatureSet();
        $this->featureSet->addFeature(new GlobalAdapterFeature());
        $this->initialize();
    }
}
