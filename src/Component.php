<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Adteam\Core\Credits\Result;

use Zend\ServiceManager\ServiceManager;
use Doctrine\ORM\EntityManager;
use Adteam\Core\Credits\Result\Entity\CoreFileUploads;
use Zend\View\Helper\ViewModel;
use Adteam\Core\Common\JsonPaginator;
use Adteam\Service\Importcsv;

/**
 * Description of Command
 *
 * @author dev
 */
 
class Component
{
    /**
     *
     * @var type 
     */
    protected $paginator;
    
    /**
     *
     * @var type 
     */
    protected $em;
    
    /**
     *
     * @var type 
     */
    protected $services;
    
    /**
     *
     * @var type 
     */
    protected $ViewHelperManager;

    /**
     *
     * @var type 
     */
    protected $csv;
    
    /**
     * 
     * @param ServiceManager $services
     */
    public function __construct(ServiceManager $services)
    {
        $this->services = $services;
        $this->em = $services->get(EntityManager::class);
        $this->ViewHelperManager = $services->get('ViewHelperManager');
        $this->paginator = new JsonPaginator();
        $this->csv = $services->get(Importcsv::class);
    } 
    
    /**
     * 
     * @param type $data
     * @param type $items
     * @param type $filename
     * @return type
     */
    public function create($data,$filename)
    {
        $config = $this->getConfig();
        $config['fileId'] =   CoreFileUploads::TYPE_RESULTS; 
        $items = $this->csv->importCsv($filename);
        $result = $this->getEntitieManager()->getRepository(CoreFileUploads::class)             
                    ->create($items,$data,$filename,$this->getIdentity(),$config); 
        $this->createLogFile($result, $filename); 
        return $this->setResponseCreate($result, $filename);
    }
    
    /**
     * 
     * @param type $params
     * @return type
     */
    public function getQbCollection($params)
    {
        $config = $this->getConfig();
        $query = $this->em->getRepository($config['entity'])->getQbCollection();  
        $this->paginator->setAdapterPaginatorOrm($query,$params);
        return $this->paginator->getResponse();        
    }        

    /**
     * 
     * @return type
     */
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

