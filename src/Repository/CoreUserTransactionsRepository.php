<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Adteam\Core\Credits\Result\Repository;

/**
 * Description of CoreUserTransactionsRepository
 *
 * @author dev
 */
use Doctrine\ORM\EntityRepository;
use Adteam\Core\Credits\Result\Entity\OauthUsers;
use Adteam\Core\Credits\Result\Entity\CoreUserTransactions;
use Adteam\Core\Credits\Result\Entity\PmrRules;

class CoreUserTransactionsRepository extends EntityRepository
{
    /**
     *
     * @var type 
     */
    public $errors= [];
    
    /**
     * 
     * @param type $key
     * @param type $data
     * @param type $fileType
     * @param type $fileId
     */
    public function create($key,$data,$fileType,$fileId)
    {
        try{
            $user = $this->getUser($data['user_id']);
            $data['user_id'] = $user['id'];
            $userId = $this->_em->getReference(OauthUsers::class, $user['id']);
            $this->validate($data);
            $this->insertPmrRules($data,$userId);
            $balance = $this->getBalanceSnapshot($data['user_id']) ?: 0;
            $newTransaction = new CoreUserTransactions();
            $newTransaction->setUser($userId);
            $newTransaction->setAmount($data['puntos']);
            $newTransaction->setCorrelationId($fileId); //@todo insert file Id
            $newTransaction->setBalanceSnapshot($balance);
            $newTransaction->setDetails(''); //@todo insert details if requested
            $newTransaction->setType(CoreUserTransactions::TYPE_RESULT);
            $newTransaction->setCreatedAt(new \DateTime());
            $newTransaction->setAppliedAt(new \DateTime(
                    $data['anio'].'-'.$data['mes']));
            $this->_em->persist($newTransaction);
            $this->_em->flush();     
        } catch (\Exception $ex) {
            $this->errors[]='Fila '.($key).': '.$ex->getMessage().PHP_EOL;
        }
    }
    
    /**
     * 
     * @param array $data
     * @param type $user
     * @throws \InvalidArgumentException
     */
    public function insertPmrRules($data,$user)
    {
        $data['user_id'] =$user;
        $Table = $this->_em->getRepository(PmrRules::class);
        if($Table->getUniqueKey($data)){
            $this->_em->getRepository(PmrRules::class)->create($data); 
        }else{
            throw new \InvalidArgumentException(
                    'El usuario '.$data['user_id']->getUsername().
                    ' no debe de tener mas de un registro para el mes '.
                    $data['mes'].' año '.$data['anio']);             
        }
    }

    /**
     * 
     * @param type $data
     */
    public function validate($data){
        $this->isNumeric($data);
        $this->hasRegex(
                '/^(19|20)\d\d$/', 
                $data, 'anio',
                'Formato año debe ser a 4 digitos');
        $this->hasRegex(
                '/^([1-9]|1[012])$/', 
                $data, 'mes',
                'Formato mes debe de 1 digito');        
    } 
    
    /**
     * 
     * @param type $username
     * @return type
     * @throws \InvalidArgumentException
     */
    public function getUser($username)
    {
        try{
            return $this->_em->getRepository(OauthUsers::class)
                ->createQueryBuilder('T')
                ->select('T.id,T.username')
                ->where('T.username LIKE :username')
                ->setParameter('username', $username)
                ->andWhere('T.enabled = :enabled')
                ->setParameter('enabled', 1) 
                ->andWhere('T.deletedAt IS NULL')      
                ->getQuery()
                ->getSingleResult();     
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException(
                    ' El usuario '.$username.' no existe o esta deshabilitado');
        }
    }  


    /**
     * verifica especificos campos que sean numericos
     * 
     * @param type $field
     * @param type $value
     * @throws \InvalidArgumentException
     */
    public function isNumeric($data)
    {
        if(!(is_numeric($data['mes'])
                &&is_numeric($data['anio'])
                &&is_numeric($data['puntos']))){
           throw new \InvalidArgumentException(
                'El valor mes, año y puntos se requiere numerico');
        }
    }  
    
    /**
     * 
     * @param type $pattern
     * @param type $data
     * @param type $key
     * @param type $msg
     * @throws \InvalidArgumentException
     */
    public function hasRegex($pattern,$data,$key,$msg)
    {
        if(preg_match($pattern, $data[$key])!==1){
           throw new \InvalidArgumentException($msg); 
        }
    } 
    
    /**
     * Get User Transaction balance
     * 
     * @param integer $userId
     * @return integer
     */
    private function getBalanceSnapshot($userId) 
    {
        return $this->createQueryBuilder('T')
            ->select('SUM(T.amount)')
            ->where('T.user = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }    
}
