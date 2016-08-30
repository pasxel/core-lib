<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'main' => [
            'mautic_category_index'  => [
                'path'       => '/categories/{bundle}/{page}',
                'controller' => 'MauticCategoryBundle:Category:index',
                'defaults'   => [
                    'bundle' => 'category',
                ],
            ],
            'mautic_category_action' => [
                'path'       => '/categories/{bundle}/{objectAction}/{objectId}',
                'controller' => 'MauticCategoryBundle:Category:executeCategory',
                'defaults'   => [
                    'bundle' => 'category',
                ],
            ],
        ],
        'api'    => [
            'standard_entity' => [
                'name'       => 'categories',
                'path'       => '/categories',
                'controller' => 'MauticCategoryBundle:Api\CategoryApi'
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'mautic.category.menu.index' => [
                'route'     => 'mautic_category_index',
                'access'    => 'category:categories:view',
                'iconClass' => 'fa-folder',
                'id'        => 'mautic_category_index',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.category.subscriber' => [
                'class' => 'Mautic\CategoryBundle\EventListener\CategorySubscriber',
            ],
        ],
        'forms'  => [
            'mautic.form.type.category'              => [
                'class'     => 'Mautic\CategoryBundle\Form\Type\CategoryListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'category',
            ],
            'mautic.form.type.category_form'         => [
                'class'     => 'Mautic\CategoryBundle\Form\Type\CategoryType',
                'alias'     => 'category_form',
                'arguments' => [
                    'translator',
                    'session'
                ]
            ],
            'mautic.form.type.category_bundles_form' => [
                'class'     => 'Mautic\CategoryBundle\Form\Type\CategoryBundlesType',
                'arguments' => 'mautic.factory',
                'alias'     => 'category_bundles_form',
            ],
        ],
        'models' => [
            'mautic.category.model.category' => [
                'class'     => 'Mautic\CategoryBundle\Model\CategoryModel',
                'arguments' => [
                    'request_stack',
                ],
            ],
        ],
    ],
];
