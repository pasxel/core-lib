<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\NotificationBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\NotificationBundle\Api\AbstractNotificationApi;
use Mautic\NotificationBundle\Event\NotificationSendEvent;
use Mautic\NotificationBundle\Model\NotificationModel;
use Mautic\NotificationBundle\NotificationEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var AbstractNotificationApi
     */
    protected $notificationApi;

    /**
     * CampaignSubscriber constructor.
     *
     * @param MauticFactory           $factory
     * @param LeadModel               $leadModel
     * @param NotificationModel       $notificationModel
     * @param AbstractNotificationApi $notificationApi
     */
    public function __construct(
        MauticFactory $factory,
        LeadModel $leadModel,
        NotificationModel $notificationModel,
        AbstractNotificationApi $notificationApi
    ) {
        $this->leadModel         = $leadModel;
        $this->notificationModel = $notificationModel;
        $this->notificationApi   = $notificationApi;

        parent::__construct($factory);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD              => ['onCampaignBuild', 0],
            NotificationEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0]
        ];
    }

    /**
     * @param CampaignBuilderEvent $sendEvent
     */
    public function onCampaignBuild(CampaignBuilderEvent $sendEvent)
    {
        if ($this->factory->getParameter('notification_enabled')) {
            $sendEvent->addAction(
                'notification.send_notification',
                [
                    'label'            => 'mautic.notification.campaign.send_notification',
                    'description'      => 'mautic.notification.campaign.send_notification.tooltip',
                    'eventName'        => NotificationEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                    'formType'         => 'notificationsend_list',
                    'formTypeOptions'  => ['update_select' => 'campaignevent_properties_notification'],
                    'formTheme'        => 'MauticNotificationBundle:FormTheme\NotificationSendList',
                    'timelineTemplate' => 'MauticNotificationBundle:SubscribedEvents\Timeline:index.html.php'
                ]
            );
        }
    }

    /**
     * @param CampaignExecutionEvent $sendEvent
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $sendEvent)
    {
        $lead = $sendEvent->getLead();

        if ($this->leadModel->isContactable($lead, 'notification') !== DoNotContact::IS_CONTACTABLE) {
            return $sendEvent->setFailed('mautic.notification.campaign.failed.not_contactable');
        }

        // If lead has subscribed on multiple devices, get all of them.
        /** @var \Mautic\NotificationBundle\Entity\PushID[] $pushIDs */
        $pushIDs = $lead->getPushIDs();

        $playerID = [];

        foreach ($pushIDs as $pushID) {
            $playerID[] = $pushID->getPushID();
        }

        if (empty($playerID)) {
            return $sendEvent->setFailed('mautic.notification.campaign.failed.not_subscribed');
        }

        $notificationId = (int) $sendEvent->getConfig()['notification'];

        /** @var \Mautic\NotificationBundle\Entity\Notification $notification */
        $notification = $this->notificationModel->getEntity($notificationId);

        if ($notification->getId() !== $notificationId) {
            return $sendEvent->setFailed('mautic.notification.campaign.failed.missing_entity');
        }

        $url = $this->notificationApi->convertToTrackedUrl(
            $notification->getUrl(),
            [
                'notification' => $notification->getId(),
                'lead'         => $lead->getId()
            ]
        );

        $tokenEvent = $this->dispatcher->dispatch(
            NotificationEvents::TOKEN_REPLACEMENT,
            new TokenReplacementEvent(
                $notification->getMessage(),
                $lead,
                ['channel' => ['notification', $notification->getId()]]
            )
        );

        $sendEvent = $this->dispatcher->dispatch(
            NotificationEvents::NOTIFICATION_ON_SEND,
            new NotificationSendEvent($tokenEvent->getContent(), $notification->getHeading(), $lead)
        );

        $response = $this->notificationApi->sendNotification(
            $playerID,
            $sendEvent->getMessage(),
            $sendEvent->getHeading(),
            $url
        );

        $sendEvent->setChannel('notification', $notification->getId());

        // If for some reason the call failed, tell mautic to try again by return false
        if ($response->code !== 200) {

            return $sendEvent->setResult(false);
        }

        $this->notificationModel->createStatEntry($notification, $lead);
        $this->notificationModel->getRepository()->upCount($notificationId);

        $result = [
            'status'  => 'mautic.notification.timeline.status.delivered',
            'type'    => 'mautic.notification.notification',
            'id'      => $notification->getId(),
            'name'    => $notification->getName(),
            'heading' => $sendEvent->getHeading(),
            'content' => $sendEvent->getMessage(),
        ];

        $sendEvent->setResult($result);
    }
}
