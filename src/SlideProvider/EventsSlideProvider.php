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
use MadeYourDay\RockSolidSlider\SliderContent;

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
    public function process(array $config, SliderContent $content)
    {
        $bridge = $this->getBridge($this->modelAdapter->findByPk($config['id']), $config['slider-column']);

        $content->addSlides($bridge->getSlides());
    }

    /**
     * Initialize the bridge.
     *
     * @param \Contao\ModuleModel $module
     * @param string              $column
     *
     * @return ContaoEvents
     */
    protected function getBridge($module, $column)
    {
        return new ContaoEvents($module, $column);
    }
}
