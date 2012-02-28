<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Git_ForkCrossProject_Test extends TuleapTestCase {
    
    public function testExecutes_ForkCrossProject_ActionWithForkRepositoriesView() {
        $groupId = 101;
        
        $toProjectId = 100;
        $toProject = new MockProject();
        $toProject->setReturnValue('getId', $toProjectId);
        
        $repo  = new GitRepository();
        $repos = array($repo);
        $repo_ids = array(200);
        
        $user = new User();
        
        $usermanager = new MockUserManager();
        $usermanager->setReturnValue('getCurrentUser', $user);
        
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $toProject, array($toProjectId));
        
        $repositoryFactory = new MockGitRepositoryFactory();
        $repositoryFactory->setReturnValue('getRepository', $repo, array($groupId, $repo_ids[0]));
        
        $request = new Codendi_Request(array(
                                        'choose_destination' => 'project',
                                        'to_project' => $toProjectId,
                                        'repos' => $repo_ids));
        
        $git = new GitSpy();
        $git->setGroupId($groupId);
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setProjectManager($projectManager);
        $git->setFactory($repositoryFactory);
        
        $git->expectOnce('addAction', array('forkCrossProjectRepositories', array($repos, $toProject, $user, $GLOBALS['HTML'])));
        $git->expectOnce('addView', array('forkRepositories'));
        
        $git->_dispatchActionAndView('do_fork_repositories', null, null, $user);
    }
    
    public function testAddsErrorWhenRepositoriesAreMissing() {
        $group_id = 11;
        
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter_repos', '*'));
        
        $git = new GitSpyForErrors();
        $git->setGroupId($group_id);
        $git->setFactory(new MockGitRepositoryFactory());
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git/?group_id='.$group_id));

        $request = new Codendi_Request(array('to_project' => 234));

        $git->_doDispatchForkCrossProject($request, null);
    }
    
    public function testAddsErrorWhenDestinationProjectIsMissing() {
        $group_id = 11;
        
        $invalidRequestError = 'Invalid request';
        $GLOBALS['Language']->setReturnValue('getText', $invalidRequestError, array('plugin_git', 'missing_parameter_to_project', '*'));
        
        $git = new GitSpyForErrors();
        $git->setGroupId($group_id);
        $git->expectOnce('addError', array($invalidRequestError));
        $git->expectOnce('redirect', array('/plugins/git/?group_id='.$group_id));

        $request = new Codendi_Request(array('repos' => array('qdfj')));

        $git->_doDispatchForkCrossProject($request, null);
    }
    
    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks() {
        $git = TestHelper::getPartialMock('Git', array('checkSynchronizerToken'));
        $git->throwOn('checkSynchronizerToken', new Exception());
        $this->expectException();
        $git->_doDispatchForkCrossProject(null, null);
    }
}
?>
