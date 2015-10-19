<?php
/**
 * Copyright (c) Enalean 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class MediawikiMLEBExtensionManager {

    /** @var Mediawiki_Migration_MediawikiMigrator */
    private $migrator;

    /** @var MediawikiMLEBExtensionDao */
    private $mleb_dao;

    /** @var ProjectManager */
    private $project_manager;

    /** @var MediawikiVersionManager */
    private $version_manager;

    /** @var MediawikiLanguageManager */
    private $language_manager;

    public function __construct(
        Mediawiki_Migration_MediawikiMigrator $migrator,
        MediawikiMLEBExtensionDao $mleb_dao,
        ProjectManager $project_manager,
        MediawikiVersionManager $version_manager,
        MediawikiLanguageManager $language_manager
    ) {
        $this->migrator         = $migrator;
        $this->mleb_dao         = $mleb_dao;
        $this->project_manager  = $project_manager;
        $this->version_manager  = $version_manager;
        $this->language_manager = $language_manager;
    }

    public function isMLEBExtensionInstalled() {
        return is_dir(forge_get_config('extension_mleb_path', 'mediawiki'));
    }

    public function isMLEBExtensionAvailableForProject(Project $project) {
        return $this->isMLEBExtensionInstalled()
            && $this->version_manager->getVersionForProject($project) == MediawikiVersionManager::MEDIAWIKI_123_VERSION
            && $this->getMLEBUsageForProject($project)
            && $this->language_manager->getUsedLanguageForProject($project);
    }

    private function getMLEBUsageForProject(Project $project) {
        $row = $this->mleb_dao->getMLEBUsageForProject($project->getID());

        if ($row) {
            return $row['extension_mleb'];
        }

        return false;
    }

    public function activateMLEBForProject(Project $project) {
        if (! $this->isMLEBExtensionInstalled()) {
            return;
        }

        $this->runUpdate($project);
        return $this->saveMLEBActivationForProject($project);
    }

    public function saveMLEBActivationForProject(Project $project) {
        return $this->mleb_dao->saveMLEBActivationForProject($project->getID());
    }

    public function activateMLEBForCompatibleProjects(BackendLogger $logger) {
        foreach ($this->getProjectsEligibleToMLEBExtensionActivation() as $project) {
            if ($this->activateMLEBForProject($project)) {
                $project_id = $project->getID();
                $logger->info("Activated MLEB extension for project $project_id");
            }
        }
    }

    private function runUpdate(Project $project) {
        $this->migrator->runUpdateScript($project);
    }

    private function getProjectsEligibleToMLEBExtensionActivation() {
        return $this->mleb_dao->getProjectIdsEligibleToMLEBExtensionActivation()->instanciateWith(array($this, "instantiateProjectFromRow"));
    }

    public function instantiateProjectFromRow(array $row) {
        return $this->project_manager->getProject($row['project_id']);
    }
}