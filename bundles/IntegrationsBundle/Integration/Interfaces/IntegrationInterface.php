<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

interface IntegrationInterface extends UnifiedIntegrationInterface
{
    /**
     * Return the integration's name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * @return bool
     */
    public function hasIntegrationConfiguration(): bool;

    /**
     * @return Integration
     */
    public function getIntegrationConfiguration(): Integration;

    /**
     * @param Integration $integration
     *
     * @return mixed
     */
    public function setIntegrationConfiguration(Integration $integration);
}
