<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */


/**
* System Event classes
*
*/
class SystemEvent_PROJECT_CREATE extends SystemEvent {


    /**
     * Constructor
     * @param $id        : SystemEvent DB ID
     * @param $parameters: Event Parameter 
     * @param $priority  : Event priority
     * @param $status    : Event status
     */
    function SystemEvent_PROJECT_CREATE($id, $parameters, $priority, $status ) {
        $this->id        = $id;
        $this->type      = SystemEvent::PROJECT_CREATE;
        $this->parameters= $parameters;
        $this->priority  = $priority;
        $this->status    = $status;
    }



    /** 
     * Process stored event
     */
    function process() {
        // Check parameters
        $group_id=$this->getIdFromParam($this->parameters);
        
        if ($project = $this->getProject($group_id)) {
            
            $backendSystem = BackendSystem::instance();
            if (!$backendSystem->createProjectHome($group_id)) {
                $this->error("Could not create project home");
                return false;
            }
            $backendSystem->setProjectHomePrivacy($project, !$project->isPublic());
            
            
            if ($project->usesCVS()) {
                $backendCVS    = BackendCVS::instance();
                if (!$backendCVS->createProjectCVS($group_id)) {
                    $this->error("Could not create/initialize project CVS repository");
                    return false;
                }
                $backendCVS->setCVSRootListNeedUpdate();
                $project->setCVSPrivacy($project, !$project->isPublic() || $project->isCVSPrivate());
            }
            
            if ($project->usesSVN()) {
                $backendSVN    = BackendSVN::instance();
                if (!$backendSVN->createProjectSVN($group_id)) {
                    $this->error("Could not create/initialize project SVN repository");
                    return false;
                }
                $backendSVN->setSVNApacheConfNeedUpdate();
            }
            
            $this->done();
            return true;
        }
        return false;
    }

}

?>
