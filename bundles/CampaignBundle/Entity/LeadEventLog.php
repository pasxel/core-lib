<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Doctrine\Mapping\AssociationBuilder;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class LeadEventLog
 *
 * @package Mautic\CampaignBundle\Entity
 *
 * @Serializer\ExclusionPolicy("all")
 */
class LeadEventLog
{

    /**
     * @var Event
     */
    private $event;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \DateTime
     **/
    private $dateTriggered;

    /**
     * @var bool
     */
    private $isScheduled = false;

    /**
     * @var null|\DateTime
     */
    private $triggerDate;

    /**
     * @var bool
     */
    private $systemTriggered = false;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_lead_event_log')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\LeadEventLogRepository');

        $builder->createManyToOne('event', 'Event')
            ->isPrimaryKey()
            ->inversedBy('log')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->createManyToOne('campaign', 'Campaign')
            ->addJoinColumn('campaign_id', 'id', false)
            ->build();

        $builder->addIpAddress();

        $builder->createField('dateTriggered', 'datetime')
            ->columnName('date_triggered')
            ->nullable()
            ->build();

        $builder->createField('isScheduled', 'boolean')
            ->columnName('is_scheduled')
            ->build();

        $builder->createField('triggerDate', 'datetime')
            ->columnName('trigger_date')
            ->nullable()
            ->build();

        $builder->createField('systemTriggered', 'boolean')
            ->columnName('system_triggered')
            ->build();
    }

    /**
     * @return \DateTime
     */
    public function getDateTriggered ()
    {
        return $this->dateTriggered;
    }

    /**
     * @param \DateTime $dateTriggered
     */
    public function setDateTriggered ($dateTriggered)
    {
        $this->dateTriggered = $dateTriggered;
    }

    /**
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress ()
    {
        return $this->ipAddress;
    }

    /**
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     */
    public function setIpAddress ($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead ($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getEvent ()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent ($event)
    {
        $this->event = $event;
    }

    /**
     * @return bool
     */
    public function getIsScheduled ()
    {
        return $this->isScheduled;
    }

    /**
     * @param bool $isScheduled
     */
    public function setIsScheduled ($isScheduled)
    {
        $this->isScheduled = $isScheduled;
    }

    /**
     * @return mixed
     */
    public function getTriggerDate ()
    {
        return $this->triggerDate;
    }

    /**
     * @param mixed $triggerDate
     */
    public function setTriggerDate ($triggerDate)
    {
        $this->triggerDate = $triggerDate;
    }

    /**
     * @return mixed
     */
    public function getCampaign ()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     */
    public function setCampaign ($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered ()
    {
        return $this->systemTriggered;
    }

    /**
     * @param bool $systemTriggered
     */
    public function setSystemTriggered ($systemTriggered)
    {
        $this->systemTriggered = $systemTriggered;
    }
}
