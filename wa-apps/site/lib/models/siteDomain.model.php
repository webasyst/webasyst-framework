<?php 

class siteDomainModel extends waModel
{
    protected $table = 'site_domain';

    public function getByName($name)
    {
        return $this->getByField('name', $name);
    }
}