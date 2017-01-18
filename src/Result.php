<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Adteam\Core\Credits\Result;

/**
 * Description of Import
 *
 * @author dev
 */
use Zend\ServiceManager\ServiceManager;
use Doctrine\ORM\EntityManager;
use Adteam\Core\Credits\Result\Entity\CoreFileUploads;
use Zend\View\Helper\ViewModel;

class Result 
{
    protected $services;
    
    protected $ViewHelperManager;


    public function __construct(ServiceManager $services)
    {
        $this->services = $services;
        $this->ViewHelperManager = $services->get('ViewHelperManager');
    }    
    
    public function create($data,$items,$filename)
    {
        $this->hasColumnsRequired($items);
        $result = $this->getEntitieManager()->getRepository(CoreFileUploads::class)
                    ->create($items,$data,$filename,$this->getIdentity()); 
        $this->createLogFile($result, $filename); 
        return $this->setResponseCreate($result, $filename);
    }
    
    public function getIdentity()
    {
        return $this->services->get('authentication')->getIdentity()
                ->getAuthenticationIdentity();
    }

    /**
     * 
     * @return type
     */
    public function getConfig()
    {
        $config =  $this->services->get('config');
        return isset($config['adteam-core-credits-result'])
                                  ?$config['adteam-core-credits-result']:[];
    }
    
    /**
     * Genera archivo csv apartir de nombres de columna de tabla
     * pmr_rules que es customizable
     * 
     * @param type $filename
     * @return type
     */
    public function getColumns()
    {
        $config = $this->getConfig();
        return isset($config['columns'])?$config['columns']:[];
    }     
    
    /**
     * 
     * @return type
     */
    public function getEntitieManager()
    {
        return $this->services->get(EntityManager::class);
    }
    
    /**
     * 
     * @param type $item
     * @return type
     */
    public function getUniqueKey($item){
        $config = $this->getConfig();
        $isUnikey = false;
        try{
            $this->_em->getRepository($config['entity'])
                ->createQueryBuilder('T')
                ->select('T')
                ->innerJoin('T.user', 'R')
                ->where('T.user = :user_id AND T.mes = :mes AND T.anio = :anio')
                ->setParameter('user_id', $item['user_id'])
                ->setParameter('mes', $item['mes'])
                ->setParameter('anio', $item['anio'])    
                ->getQuery()
                ->getSingleResult();  
        } catch (\Exception $ex) {
            $isUnikey = true;
        }
        return $isUnikey;

    }    
    
    public function hasColumnsRequired($items)
    {
        $columns = $this->getColumns();
        $items->rewind();
        $current = $items->current();
        foreach ($columns as $colum){
            if(!isset($current[$colum])||count($columns)!==count($current)){
                throw new \Exception(
                        'Las columnas necesarias no coinciden con las definidas'
                        ,422);
            }
        }
    }    
    
    /**
     * Create Log File for Adjustment Transactions
     * 
     * @param array $errors
     * @param string $fileName
     * @return boolean
     */
    private function createLogFile($errors, $fileName)
    {
        if(count($errors)>0){
            $config =  $this->services->get('config');
            $pathinfo = pathinfo($fileName['tmp_name']); 
            $fp = fopen(
                    $config['path'].'/public/logs/'.
                    $pathinfo['basename'].'.log', 'w+');
            foreach($errors as $error) {
                fwrite($fp, $error."\x0D\x0A");
            }                
            return fclose($fp);            
        }
    } 
    
    /**
     * 
     * @param type $result
     * @param type $filename
     * @return type
     */
    private function  setResponseCreate($result,$filename)
    {
        $pathinfo = pathinfo($filename['tmp_name']); 
        $response = ['message'=>'success'];
        $view = $this->ViewHelperManager->get(ViewModel::class)->getView();
        $url = $view->serverUrl().$view->basePath('logs');
        if(count($result)>0){
            $response = array_merge(
                        $response,
                        [
                            'message'=>'partial_success',
                            'errorCount'=>count($result),
                            'logFileUrl'=>$url.'/'.$pathinfo['basename'].'.log'
                        ]
                    );
        }
        return $response;
    }     
    
}
