<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\SmsBundle\Api\AbstractSmsApi;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\SmsEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticSmsBundle
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var SmsModel
     */
    protected $smsModel;

    /**
     * @var AbstractSmsApi
     */
    protected $smsApi;

    /**
     * CampaignSubscriber constructor.
     *
     * @param MauticFactory $factory
     * @param LeadModel $leadModel
     * @param SmsModel $smsModel
     * @param AbstractSmsApi $smsApi
     */
    public function __construct(MauticFactory $factory, LeadModel $leadModel, SmsModel $smsModel, AbstractSmsApi $smsApi)
    {
        $this->leadModel = $leadModel;
        $this->smsModel  = $smsModel;
        $this->smsApi    = $smsApi;

        parent::__construct($factory);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            SmsEvents::ON_CAMPAIGN_TRIGGER => array('onCampaignTrigger', 0)
        );
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        if ($this->factory->getParameter('sms_enabled')) {
            $event->addAction(
                'sms.send_text_sms',
                array(
                    'label'            => 'mautic.campaign.sms.send_text_sms',
                    'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
                    'eventName'        => SmsEvents::ON_CAMPAIGN_TRIGGER,
                    'formType'         => 'smssend_list',
                    'formTypeOptions'  => array('update_select' => 'campaignevent_properties_sms'),
                    'formTheme'        => 'MauticSmsBundle:FormTheme\SmsSendList',
                    'timelineTemplate' => 'MauticSmsBundle:SubscribedEvents\Timeline:index.html.php'
                )
            );
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTrigger(CampaignExecutionEvent $event)
    {
        $lead = $event->getLead();

        if ($this->leadModel->isContactable($lead, 'sms') !== DoNotContact::IS_CONTACTABLE) {
            $event->setResult(['failed' => 1]);
        }

        $leadPhoneNumber = $lead->getFieldValue('mobile');

        if (empty($leadPhoneNumber)) {
            $leadPhoneNumber = $lead->getFieldValue('phone');
        }

        if (empty($leadPhoneNumber)) {
            $event->setResult(['failed' => 1]);
        }

        $smsId = (int) $event->getConfig()['sms'];
        $sms   = $this->smsModel->getEntity($smsId);

        if ($sms->getId() !== $smsId) {
            $event->setResult(['failed' => 1]);
        }

        $smsEvent = new SmsSendEvent($sms->getMessage(), $lead);
        $smsEvent->setSmsId($smsId);

        $this->dispatcher->dispatch(SmsEvents::SMS_ON_SEND, $smsEvent);
        $metadata = $this->smsApi->sendSms($leadPhoneNumber, $smsEvent->getContent());

        // If there was a problem sending at this point, it's an API problem and should be requeued
        if ($metadata === false) {
            $event->setResult(false);
        }

        $event->setResult([
            'type' => 'mautic.sms.sms',
            'status' => 'mautic.sms.timeline.status.delivered',
            'id' => $sms->getId(),
            'name' => $sms->getName(),
            'content' => $smsEvent->getContent()
        ]);
    }
}