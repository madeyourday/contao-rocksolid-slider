<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

use Contao\CoreBundle\Framework\Adapter;
use MadeYourDay\RockSolidSlider\SlideProvider\Bridge\ContaoEvents;

/**
 * Provides slides from events archives.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class EventsSlideProvider implements SlideProviderInterface
{
    /**
     * @var Adapter|\Contao\ModuleModel
     */
    private $modelAdapter;

    /**
     * Create a new instance.
     *
     * @param Adapter $modelAdapter
     */
    public function __construct(Adapter $modelAdapter)
    {
        $this->modelAdapter = $modelAdapter;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'rsts_events';
    }

    /**
     * {@inheritDoc}
     */
    public function getSlides(array $config)
    {
        // FIXME: unmockable ContaoEvents bridge in use here.
        $bridge = new ContaoEvents($this->modelAdapter->findByPk($config['id']), $config['slider-column']);

        return $bridge->getSlides();
    }
}
