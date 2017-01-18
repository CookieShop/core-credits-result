<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Adteam\Core\Credits\Result\Repository;

/**
 * Description of CoreFileUploadsRepository
 *
 * @author dev
 */
use Doctrine\ORM\EntityRepository;
use Adteam\Core\Credits\Result\Entity\CoreUserTransactions;
use Adteam\Core\Credits\Result\Entity\CoreFileUploads;
use Adteam\Core\Credits\Result\Entity\OauthUsers;

class CoreFileUploadsRepository extends EntityRepository{
    
    public function create($items,$data,$filename,$identity)
    {
        $currentRepo = $this; 
        $this->_em->transactional(
            function ($em) use($items,$data,$identity,$filename,$currentRepo) {
                $user = $currentRepo->getUser($identity['user_id']);
                $id = $currentRepo->InsertFileUpload($data, $user, $filename); 
                foreach ($items as $key => $item){
                    $currentRepo->insertItems($key,$item,$id);                    
                }
            }
        );
        return $this->getErrors();
    }
    
    /**
     * 
     * @param type $data
     * @param type $user
     * @param type $filename
     */
    public function InsertFileUpload($data,$user,$filename)
    {            
        $pathinfo = pathinfo($filename['tmp_name']); 
        $entities = new CoreFileUploads(); 
        $userId = $this->_em->getReference(OauthUsers::class, $user['id']);
        $entities->setDescription($data->description)
                 ->setFileName($pathinfo['basename'])
                 ->setFileType(CoreFileUploads::TYPE_RESULTS)
                 ->setUser($userId)->setUploadedAt(new \DateTime());         
        $this->_em->persist($entities);
        $this->_em->flush();
        return $entities->getId();
    }

        
    public function insertItems($key,$data,$fileId)
    {
        $Table = $this->_em->getRepository(CoreUserTransactions::class);
        $Table->create($key,$data,CoreFileUploads::TYPE_RESULTS,$fileId);          
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
                    ' el usuario '.$username.
                    ' no existe o esta deshabilitado',422);
        }
    }
    
    public function getErrors()
    {
        return $this->_em->getRepository(CoreUserTransactions::class)
                ->errors; 
    } 
}
