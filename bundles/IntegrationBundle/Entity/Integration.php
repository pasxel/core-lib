<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Integration
 * @ORM\Table(name="integrations")
 * @ORM\Entity(repositoryClass="Mautic\IntegrationBundle\Entity\IntegrationRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Integration
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    private $isEnabled = true;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $bundle;

    /**
     * @ORM\OneToMany(targetEntity="Connector", mappedBy="integration", indexBy="id", fetch="EXTRA_LAZY")
     */
    private $connectors;

    public function __construct()
    {
        $this->connectors  = new ArrayCollection();
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->id = null;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Integration
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return Integration
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set bundle
     *
     * @param string $bundle
     *
     * @return Integration
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * Get bundle
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Check the publish status of an entity based on publish up and down datetimes
     *
     * @return string published|unpublished
     */
    public function getPublishStatus()
    {
        return $this->getIsEnabled() ? 'published' : 'unpublished';
    }

    /**
     * @return mixed
     */
    public function getConnectors ()
    {
        return $this->connectors;
    }
}
