<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber
 *
 * @package Mautic\ReportBundle\EventListener
 */
class ReportSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            ReportEvents::REPORT_ON_BUILD    => array('onReportBuilder', 0),
            ReportEvents::REPORT_ON_GENERATE => array('onReportGenerate', 0),
            ReportEvents::REPORT_ON_GRAPH_GENERATE => array('onReportGraphGenerate', 0)
        );
    }

    /**
     * Add available tables and columns to the report builder lookup
     *
     * @param ReportBuilderEvent $event
     *
     * @return void
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if ($event->checkContext(array('forms', 'form.submissions'))) {
            // Forms
            $prefix  = 'f.';
            $columns = array(
                $prefix . 'alias' => array(
                    'label' => 'mautic.report.field.alias',
                    'type'  => 'int'
                )
            );
            $data    = array(
                'display_name' => 'mautic.form.form.report.table',
                'columns'      => array_merge($columns, $event->getStandardColumns($prefix), $event->getCategoryColumns())
            );
            $event->addTable('forms', $data);
            if ($event->checkContext('form.submissions')) {
                // Form submissions
                $submissionPrefix = 'fs.';
                $pagePrefix       = 'p.';

                $submissionColumns = array(
                    $submissionPrefix . 'date_submitted' => array(
                        'label' => 'mautic.form.report.submit.date_submitted',
                        'type'  => 'datetime'
                    ),
                    $submissionPrefix . 'referer'        => array(
                        'label' => 'mautic.form.report.submit.referer',
                        'type'  => 'string'
                    ),
                    $pagePrefix . 'id'                   => array(
                        'label' => 'mautic.form.report.page_id',
                        'type'  => 'int'
                    ),
                    $pagePrefix . 'name'                 => array(
                        'label' => 'mautic.form.report.page_name',
                        'type'  => 'string'
                    )
                );
                $data              = array(
                    'display_name' => 'mautic.form.report.submission.table',
                    'columns'      => array_merge($submissionColumns, $columns, $event->getLeadColumns(), $event->getIpColumn())
                );
                $event->addTable('form.submissions', $data);
            }
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        $context = $event->getContext();
        if ($context == 'forms') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'forms', 'f');
            $event->addCategoryLeftJoin($qb, 'f');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'form.submissions') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'form_submissions', 'fs')
                ->leftJoin('fs', MAUTIC_TABLE_PREFIX . 'forms', 'f', 'f.id = fs.form_id')
                ->leftJoin('fs', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'p.id = fs.page_id');
            $event->addCategoryLeftJoin($qb, 'f');
            $event->addLeadLeftJoin($qb, 'fs');
            $event->addIpAddressLeftJoin($qb, 'fs');

            $event->setQueryBuilder($qb);
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        $report = $event->getReport();
        // Context check, we only want to fire for Forms reports
        if ($report->getSource() != 'form.submissions')
        {
            return;
        }

        $options = $event->getOptions();
        $submissionRepo = $this->factory->getEntityManager()->getRepository('MauticFormBundle:Submission');

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.form.graph.line.submissions') {
            // Generate data for submissions line graph
            $unit = 'D';
            $amount = 30;

            if (isset($options['amount'])) {
                $amount = $options['amount'];
            }

            if (isset($options['unit'])) {
                $unit = $options['unit'];
            }

            $data = GraphHelper::prepareDatetimeLineGraphData($amount, $unit, array('submissions'));

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'form_submissions', 'fs');
            $queryBuilder->leftJoin('fs', MAUTIC_TABLE_PREFIX . 'forms', 'f', 'f.id = fs.form_id');
            $queryBuilder->select('fs.form_id as form, fs.date_submitted as dateSubmitted');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('fs.date_submitted', ':date'))
                ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
            $submissions = $queryBuilder->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($data, $submissions, $unit, 0, 'dateSubmitted');
            $timeStats['name'] = 'mautic.form.graph.line.submissions';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.form.table.top.referrers') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $submissionRepo->getTopReferrers($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.form.table.top.referrers';
            $graphData['iconClass'] = 'fa-sign-in';
            $graphData['link'] = 'mautic_form_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.form.table.most.submitted') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $submissionRepo->getMostSubmitted($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.form.table.most.submitted';
            $graphData['iconClass'] = 'fa-check-square-o';
            $graphData['link'] = 'mautic_form_action';
            $event->setGraph('table', $graphData);
        }
    }
}
