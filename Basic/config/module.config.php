<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Basic\Controller\Index' => 'Basic\Controller\IndexController',
            'Basic\Controller\Listening' => 'Basic\Controller\ListeningController',
            'Basic\Controller\Report' => 'Basic\Controller\ReportController',
            'Basic\Controller\Form' => 'Basic\Controller\FormController',
            'Basic\Controller\Dashboard' => 'Basic\Controller\DashboardController',
        ),
    ),
    
    'router' => array(
        'routes' => array(
            'basic' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/basic',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Basic\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action[/:id]]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'     => '[0-9]+',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'template_path_stack' => array(
            'basic' => __DIR__ . '/../view',
        ),
    ),
    
);