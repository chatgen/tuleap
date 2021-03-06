<?php
/**
 * Copyright (c) Enalean, 2015 — 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tracker\FormElement\Field\ArtifactLink\Nature\NatureConfigPresenter;
use Tracker\FormElement\Field\ArtifactLink\Nature\NatureCreator;
use Tracker\FormElement\Field\ArtifactLink\Nature\NatureFactory;
use Tracker\FormElement\Field\ArtifactLink\Nature\UnableToCreateNatureException;

class TrackerPluginConfigController {

    private static $TEMPLATE = 'siteadmin-config';

    /** @var TrackerPluginConfig */
    private $config;

    /** @var Config_LocalIncFinder */
    private $localincfinder;

    /** @var EventManager */
    private $event_manager;

    /** @var NatureCreator */
    private $nature_creator;

    /** @var NatureFactory */
    private $nature_factory;

    public function __construct(
        TrackerPluginConfig $config,
        Config_LocalIncFinder $localincfinder,
        EventManager $event_manager,
        NatureCreator $nature_creator,
        NatureFactory $nature_factory
    ) {
        $this->config         = $config;
        $this->localincfinder = $localincfinder;
        $this->event_manager  = $event_manager;
        $this->nature_creator = $nature_creator;
        $this->nature_factory = $nature_factory;
    }

    public function index(CSRFSynchronizerToken $csrf, Response $response) {
        $title  = $GLOBALS['Language']->getText('plugin_tracker_config', 'title');
        $params = array(
            'title' => $title
        );
        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);

        $response->header($params);
        $renderer->renderToPage(
            self::$TEMPLATE,
            new TrackerPluginConfigPresenter(
                $csrf,
                $title,
                $this->localincfinder->getLocalIncPath(),
                $this->config,
                $this->getNatureConfigPresenter()
            )
        );
        $response->footer($params);
    }

    public function update(Codendi_Request $request, Response $response) {
        if ($request->exist('create-nature')) {
            $this->createNature($request, $response);
        } else {
            $this->updateEmailGatewayMode($request, $response);
        }

        $response->redirect($_SERVER['REQUEST_URI']);
    }

    private function createNature(Codendi_Request $request, Response $response) {
        try {
            $this->nature_creator->create(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );
        } catch (UnableToCreateNatureException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_error',
                    $exception->getMessage()
                )
            );
        }
    }

    private function updateEmailGatewayMode(Codendi_Request $request, Response $response) {
        $emailgateway_mode = $request->get('emailgateway_mode');
        if ($emailgateway_mode && $this->config->setEmailgatewayMode($emailgateway_mode)) {
            $response->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_tracker_config', 'successfully_updated'));
        }
        $this->event_manager->processEvent(Event::UPDATE_ALIASES, null);
    }

    /** @return NatureConfigPresenter */
    private function getNatureConfigPresenter() {
        $natures = array(
            new NaturePresenter(
                Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', '_is_child_forward'),
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', '_is_child_reverse')
            )
        );

        foreach ($this->nature_factory->getAllNatures() as $nature) {
            $natures[] = $nature;
        }

        return new NatureConfigPresenter($natures);
    }
}
