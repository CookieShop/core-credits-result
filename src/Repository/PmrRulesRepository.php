<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Adteam\Core\Credits\Result\Repository;

/**
 * Description of PmrRulesRepository
 *
 * @author dev
 */
use Doctrine\ORM\EntityRepository;
use Adteam\Core\Credits\Result\Entity\PmrRules;

class PmrRulesRepository extends EntityRepository{
    
    public function create($items)
    {
        $rules = new PmrRules();
        foreach($items as $field => $value) {
            $field = $field==='user_id'?'user':$field;
            if (method_exists($rules, 'set'.ucfirst($field))) {
                $rules->{'set'.ucfirst($field)}($value);
            }    
        }    
        $this->_em->persist($rules);          
        $this->_em->flush(); 
    }
    
    /**
     * 
     * @param type $data
     * @return type
     */
    public function getUniqueKey($data){
        $isUnikey = false;
        try{
            $result = $this
                ->createQueryBuilder('T')
                ->select('T')
                ->innerJoin('T.user', 'R')
                ->where('T.user = :user_id AND T.mes = :mes AND T.anio = :anio')
                ->setParameter('user_id', $data['user_id']->getId())
                ->setParameter('mes', $data['mes'])
                ->setParameter('anio', $data['anio'])    
                ->getQuery()
                ->getSingleResult(); 
        } catch (\Exception $ex) {
            $isUnikey = true;
        }
        return $isUnikey;
    }    
}
