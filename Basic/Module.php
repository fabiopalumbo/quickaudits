<?php
namespace Basic;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Basic\Model\ListeningTable;
use Zend\Db\ResultSet\ResultSet;
use Basic\Model\Listening;
use Zend\Db\TableGateway\TableGateway;
use Basic\Model\ReportTable;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Basic\Model\ListeningTable' =>  function($sm) {
                    $tableGateway = $sm->get('ListeningTableGateway');
                    $table = new ListeningTable($tableGateway);
                    return $table;
                },
                'ListeningTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Listening());
                    return new TableGateway('listenings', $dbAdapter, null, $resultSetPrototype);
                },
                'Basic\Model\ReportTable' =>  function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ReportTable($dbAdapter);
                    return $table;
                },                
            ),
        );
    }
}