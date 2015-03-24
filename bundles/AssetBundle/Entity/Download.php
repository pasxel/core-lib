<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class Download
 *
 * @package Mautic\AssetBundle\Entity
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Download
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $dateDownload;

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $referer;

    /**
     * @var string
     */
    private $trackingId;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $sourceId;

    /**
     * @var \Mautic\EmailBundle\Entity\Email
     */
    private $email;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('asset_downloads')
            ->setCustomRepositoryClass('Mautic\AssetBundle\Entity\DownloadRepository');

        $builder->createField('id', 'integer')
            ->isPrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('dateDownload', 'datetime')
            ->columnName('date_download')
            ->build();

        $builder->createManyToOne('asset', 'Asset')
            ->addJoinColumn('asset_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addIpAddress();

        $builder->addLead(true, 'SET NULL');

        $builder->addField('code', 'integer');

        $builder->createField('referer', 'string')
            ->nullable()
            ->build();

        $builder->createField('trackingId', 'string')
            ->columnName('tracking_id')
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable();

        $builder->createManyToOne('email', 'Mautic\EmailBundle\Entity\Email')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * Set dateDownload
     *
     * @param \DateTime $dateDownload
     *
     * @return Download
     */
    public function setDateDownload ($dateDownload)
    {
        $this->dateDownload = $dateDownload;

        return $this;
    }

    /**
     * Get dateDownload
     *
     * @return \DateTime
     */
    public function getDateDownload ()
    {
        return $this->dateDownload;
    }

    /**
     * Set code
     *
     * @param integer $code
     *
     * @return Download
     */
    public function setCode ($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer
     */
    public function getCode ()
    {
        return $this->code;
    }

    /**
     * Set referer
     *
     * @param string $referer
     *
     * @return Download
     */
    public function setReferer ($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer
     *
     * @return string
     */
    public function getReferer ()
    {
        return $this->referer;
    }

    /**
     * Set asset
     *
     * @param Asset $asset
     *
     * @return Download
     */
    public function setAsset (Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return Asset
     */
    public function getAsset ()
    {
        return $this->asset;
    }

    /**
     * Set ipAddress
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     *
     * @return Download
     */
    public function setIpAddress (\Mautic\CoreBundle\Entity\IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress ()
    {
        return $this->ipAddress;
    }

    /**
     * Set trackingId
     *
     * @param integer $trackingId
     *
     * @return Download
     */
    public function setTrackingId ($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * Get trackingId
     *
     * @return integer
     */
    public function getTrackingId ()
    {
        return $this->trackingId;
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
    public function getSource ()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource ($source)
    {
        $this->source = $source;
    }

    /**
     * @return integer
     */
    public function getSourceId ()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId ($sourceId)
    {
        $this->sourceId = (int)$sourceId;
    }

    /**
     * @return mixed
     */
    public function getEmail ()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail (Email $email)
    {
        $this->email = $email;
    }
}
