<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TriggerCampaignCommand
 */
class TriggerCampaignCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:trigger')
            ->setAliases(
                array(
                    'mautic:campaign:trigger',
                    'mautic:trigger:campaigns',
                    'mautic:trigger:campaign'
                )
            )
            ->setDescription('Trigger timed events for published campaigns.')
            ->addOption(
                '--campaign-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Trigger events for a specific campaign.  Otherwise, all campaigns will be triggered.',
                null
            )
            ->addOption('--scheduled-only', null, InputOption::VALUE_NONE, 'Trigger only scheduled events')
            ->addOption('--negative-only', null, InputOption::VALUE_NONE, 'Trigger only negative events, i.e. with a "no" decision path.')
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 100.', 100)
            ->addOption(
                '--max-events',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of events to process per campaign for this script execution. Defaults to all.',
                0
            )
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory = $container->get('mautic.factory');

        /** @var \Mautic\CampaignBundle\Model\EventModel $model */
        $model = $factory->getModel('campaign.event');
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $factory->getModel('campaign');
        $translator    = $factory->getTranslator();
        $em            = $factory->getEntityManager();

        $id           = $input->getOption('campaign-id');
        $scheduleOnly = $input->getOption('scheduled-only');
        $negativeOnly = $input->getOption('negative-only');
        $batch        = $input->getOption('batch-limit');
        $max          = $input->getOption('max-events');
        $force        = $input->getOption('force');

        // Prevent script overlap
        $checkFile      = $checkFile = $container->getParameter('kernel.cache_dir').'/../script_executions.json';
        $command        = 'mautic:campaign:trigger';
        $key            = ($id) ? $id : 'all';
        $executionTimes = array();

        if (file_exists($checkFile)) {
            // Get the time in the file
            $executionTimes = json_decode(file_get_contents($checkFile), true);
            if (!is_array($executionTimes)) {
                $executionTimes = array();
            }

            if ($force || empty($executionTimes['in_progress'][$command][$key])) {
                // Just started
                $executionTimes['in_progress'][$command][$key] = time();
            } else {
                // In progress
                $check = $executionTimes['in_progress'][$command][$key];

                if ($check + 1800 <= time()) {
                    // Has been 30 minutes so override
                    $executionTimes['in_progress'][$command][$key] = time();
                } else {
                    $output->writeln('<error>Script in progress. Use -f or --force to force execution.</error>');

                    return 0;
                }
            }
        } else {
            // Just started
            $executionTimes['in_progress'][$command][$key] = time();
        }

        file_put_contents($checkFile, json_encode($executionTimes));

        if ($id) {
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            $campaign = $campaignModel->getEntity($id);

            if ($campaign !== null && $campaign->isPublished()) {
                $totalProcessed = 0;

                if (!$negativeOnly && !$scheduleOnly) {
                    //trigger starting action events for newly added leads
                    $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.starting').'</info>');
                    $processed = $model->triggerStartingEvents($campaign, $batch, $max, $output);
                    $totalProcessed += $processed;
                    $output->writeln(
                        '<info>'.$translator->trans('mautic.campaign.trigger.events_executed', array('%events%' => $processed)).'</info>'."\n"
                    );
                }

                if ((!$max || $totalProcessed < $max) && !$negativeOnly) {
                    //trigger scheduled events
                    $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.scheduled').'</info>');
                    $processed = $model->triggerScheduledEvents($campaign, $totalProcessed, $batch, $max, $output);
                    $totalProcessed += $processed;
                    $output->writeln(
                        '<info>'.$translator->trans('mautic.campaign.trigger.events_executed', array('%events%' => $processed)).'</info>'."\n"
                    );
                }

                if ((!$max || $totalProcessed < $max) && !$scheduleOnly) {
                    //find and trigger "no" path events
                    $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.negative').'</info>');
                    $processed = $model->triggerNegativeEvents($campaign, $totalProcessed, $batch, $max, $output);
                    $output->writeln(
                        '<info>'.$translator->trans('mautic.campaign.trigger.events_executed', array('%events%' => $processed)).'</info>'."\n"
                    );
                }
            } else {
                $output->writeln('<error>'.$translator->trans('mautic.campaign.rebuild.not_found', array('%id%' => $id)).'</error>');
            }
        } else {
            $campaigns = $model->getEntities(
                array(
                    'iterator_mode' => true
                )
            );

            while (($c = $campaigns->next()) !== false) {
                $totalProcessed = 0;

                if ($c[0]->isPublished()) {
                    if (!$negativeOnly && !$scheduleOnly) {
                        //trigger starting action events for newly added leads
                        $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.starting').'</info>');
                        $processed = $model->triggerStartingEvents($c[0], $batch, $max, $output);
                        $totalProcessed += $processed;
                        $output->writeln(
                            '<info>'.$translator->trans('mautic.campaign.trigger.events_executed', array('%events%' => $processed)).'</info>'."\n"
                        );
                    }

                    if ($max && $totalProcessed >= $max) {

                        continue;
                    }

                    if (!$negativeOnly) {
                        //trigger scheduled events
                        $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.scheduled').'</info>');
                        $processed = $model->triggerScheduledEvents($c[0], $totalProcessed, $batch, $max, $output);
                        $totalProcessed += $processed;
                        $output->writeln(
                            '<info>'.$translator->trans('mautic.campaign.trigger.events_executed', array('%events%' => $processed)).'</info>'."\n"
                        );
                    }

                    if ($max && $totalProcessed >= $max) {

                        continue;
                    }

                    if (!$scheduleOnly) {
                        //find and trigger "no" path events
                        $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.negative').'</info>');
                        $processed = $model->triggerNegativeEvents($c[0], $totalProcessed, $batch, $max, $output);
                        $output->writeln(
                            '<info>'.$translator->trans('mautic.campaign.trigger.events_executed', array('%events%' => $processed)).'</info>'."\n"
                        );
                    }
                }

                $em->detach($c[0]);
                unset($c);
            }

            unset($campaigns);
        }

        unset($executionTimes['in_progress'][$command][$key]);
        file_put_contents($checkFile, json_encode($executionTimes));

        return 0;
    }
}
