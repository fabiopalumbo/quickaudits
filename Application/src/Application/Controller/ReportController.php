<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
// use Application\Model\ReportTable;
use Zend\Db\Sql\Expression;
use Zend\Authentication\AuthenticationService;
use Application\Helper\Utilities;
use Zend\Session\Container;

/**
 * ReportController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class ReportController extends AbstractActionController
{
    
    protected $auth;
    protected $session;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }

    public function projectOverviewAction()
    {
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(true, $filter, array('p.name ASC'), 'l.id_project');
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        // get question group scores for each project in paginator
        $filter2 = $filter;
        unset($filter2['project']);
        
        $projects = array();
        foreach ($paginator as $item) {    
            array_push($projects, $item->id_project);
        }
        
        $filter2['project'] = $projects;

        // get the scores 
        $projectsGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filter2);
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        $organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
        $organizations = $organizationTable->fetchAll();
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'projectsGroupsScores' => $projectsGroupsScores,
            'questionsGroups' => $questionsGroups,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
            'organizations' => $organizations,
        ));
    }
    
    public function agentOverviewAction()
    {
        set_time_limit ( 0 );
        
        $agent = $this->params()->fromQuery('agent');
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        
	$filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;

	$agentlist = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllAgents();

        if ($agent)
        {
            $filter['agent'] = $agent;
            
            //get project overview for current filters
            $agentProjectsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), 'l.id_project');
            
            // get the scores
            $projectsGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filter);

            // create 4 weeks history report
            $historyFilter = array();
            $historyFilter['active'] = 1;
            $historyFilter['agent'] = $agent;
            $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -4 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
            $historyFilter['organization'] = $organization;
            
//             $weeksGrandTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
            $weeksGrandTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchWeeksScoreAverage($historyFilter);
            $weekGroupScoreAvg = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchWeeksGroupScoreAverage($historyFilter);
            $groupScoreTotalTemp = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchScoreTotal($historyFilter, array('id_group'));
            $groupScoreGrandTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($historyFilter);
            $weekQuestionScoreAvg = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchWeeksQuestionScoreAverage($historyFilter);
            $questionScoreTotalTemp = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchScoreTotal($historyFilter, array('id_question'));
            
            $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -11 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
            $weekRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
            
            $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))';
            $monthRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("MONTH(l.created)"));
            
            $groupScoreTotal = array();
            foreach ($groupScoreTotalTemp as $item)
            {
                $groupScoreTotal[$item->id_group]=$item->score;
            }
            
            $questionScoreTotal = array();
            foreach ($questionScoreTotalTemp as $item)
            {
                $questionScoreTotal[$item->id_question]=$item->score;
            }
        }
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $agents = $userTable->fetchAllProjectAgents(null, $organization);
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'agentlist' => $agentlist,	
            'agentProjectsScores' => $agentProjectsScores,
            'projectsGroupsScores' => $projectsGroupsScores,
            'questionsGroups' => $questionsGroups,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
            'agents' => $agents,
            'weeksGrandTotal' => $weeksGrandTotal,
            'weekGroupScoreAvg' => $weekGroupScoreAvg,
            'groupScoreTotal' => $groupScoreTotal,
            'groupScoreGrandTotal' => $groupScoreGrandTotal,
            'weekQuestionScoreAvg' => $weekQuestionScoreAvg,
            'questionScoreTotal' => $questionScoreTotal,
            'weekRollingScoreTotal' => $weekRollingScoreTotal,
            'monthRollingScoreTotal' => $monthRollingScoreTotal
        ));
    }




    public function agentRankingAction()
    {
        $auth = new AuthenticationService();
        $agent = $auth->getIdentity()->id;
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;

        if ($agent)
        {
            $filter['agent'] = $agent;
            
            //get project overview for current filters
            $agentProjectsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), 'l.id_project');
            
            // get the scores
            $projectsGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filter);

            // create 4 weeks history report
            $historyFilter = array();
            $historyFilter['active'] = 1;
            $historyFilter['agent'] = $agent;
            $historyFilter['organization'] = $organization;
                        
            $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -11 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
            $weekRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
            
            $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))';
            $monthRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("MONTH(l.created)"));
            
        }
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $agents = $userTable->fetchAllProjectAgents();
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'agentProjectsScores' => $agentProjectsScores,
            'projectsGroupsScores' => $projectsGroupsScores,
            'questionsGroups' => $questionsGroups,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
            'agents' => $agents,
            'weekRollingScoreTotal' => $weekRollingScoreTotal,
            'monthRollingScoreTotal' => $monthRollingScoreTotal
        ));
    }
    
    public function globalRankingAction()
    {
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(true, $filter, array('u2.name ASC'), 'l.id_agent');
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);

        // get question group scores for each agent in paginator
        $filter2 = $filter;
        unset($filter2['agent']);
        
        $agents = array();
        foreach ($paginator as $item) {
            array_push($agents, $item->id_agent);
        }
        
        $filter2['agent'] = $agents;
        
        // get the scores 
        $projectsGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filter2);
                
        // create 4 weeks history report
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['organization'] = $organization;
        
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -12 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
        $weekRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
        
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))';
        $monthRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("MONTH(l.created)"));
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'projectsGroupsScores' => $projectsGroupsScores,
            'questionsGroups' => $questionsGroups,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
            'weekRollingScoreTotal' => $weekRollingScoreTotal,
            'monthRollingScoreTotal' => $monthRollingScoreTotal
        ));
    }
    
    public function projectIntervalAction()
    {
        set_time_limit ( 0 );
        
        $project = $this->params()->fromQuery('project');
        
        $filter = array();
        
        if ($project)
        {            
            $filter['active'] = 1;
            $filter['project'] = $project;
            $filter['organization'] = $this->auth->getIdentity()->id_organization;
            
            $filterDaily = array();
            $filterMonthly = array();
            $filterWeekly = array();
            $filterQuarterly = array();
            $filterMTD = array();
            $filterWTD = array();
            
            $dateRange = Utilities::getYesterdayInterval();
            $filterDaily['date_from'] = $dateRange['start'];
            $filterDaily['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getLastWeekInterval();
            $filterWeekly['date_from'] = $dateRange['start'];
            $filterWeekly['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getLastMonthInterval();
            $filterMonthly['date_from'] = $dateRange['start'];
            $filterMonthly['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getLastQuarterInterval();
            $filterQuarterly['date_from'] = $dateRange['start'];
            $filterQuarterly['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getMonthToDateInterval();
            $filterMTD['date_from'] = $dateRange['start'];
            $filterMTD['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getWeekToDateInterval();
            $filterWTD['date_from'] = $dateRange['start'];
            $filterWTD['date_to'] = $dateRange['end'];
            
            $filterDaily = array_merge($filter, $filterDaily);
            $filterWeekly = array_merge($filter, $filterWeekly);
            $filterMonthly = array_merge($filter, $filterMonthly);
            $filterQuarterly = array_merge($filter, $filterQuarterly);
            $filterMTD = array_merge($filter, $filterMTD);
            $filterWTD = array_merge($filter, $filterWTD);
            
            // get the scores
            $dailyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filterDaily);
            $dailyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterDaily);
            
            $weeklyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filterWeekly);
            $weeklyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterWeekly);
            
            $monthlyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filterMonthly);
            $monthlyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterMonthly);
            
            $quaterlyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filterQuarterly);
            $quaterlyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterQuarterly);
            
            $mtdGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filterMTD);
            $mtdGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterMTD);
            
            $wtdGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchProjectsGroupScoreAverage($filterWTD);
            $wtdGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterWTD);
            
            $report = array();
            $report[0]['name'] = 'Daily';
            $report[1]['name'] = 'Weekly';
            $report[2]['name'] = 'Monthly';
            $report[3]['name'] = 'Quarterly';
            $report[4]['name'] = 'Month To Date';
            $report[5]['name'] = 'Week To Date';
            
            $report[0]['groups'] = $dailyGroupsScores;
            $report[0]['total'] = $dailyGroupsScoresTotal;
            $report[1]['groups'] = $weeklyGroupsScores;
            $report[1]['total'] = $weeklyGroupsScoresTotal;
            $report[2]['groups'] = $monthlyGroupsScores;
            $report[2]['total'] = $monthlyGroupsScoresTotal;
            $report[3]['groups'] = $quaterlyGroupsScores;
            $report[3]['total'] = $quaterlyGroupsScoresTotal;
            $report[4]['groups'] = $mtdGroupsScores;
            $report[4]['total'] = $mtdGroupsScoresTotal;
            $report[5]['groups'] = $wtdGroupsScores;
            $report[5]['total'] = $wtdGroupsScoresTotal;
        }
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$this->auth->getIdentity()->id_organization));
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$this->auth->getIdentity()->id_organization), 'qg.order ASC');
        
        return new ViewModel(array(
            'report' => $report,
            'filter' => $filter,
            'projects' => $projects,
            'questionsGroups' => $questionsGroups
        ));
    }
    
    public function agentIntervalAction()
    {
        set_time_limit ( 0 );
        
        $agent = $this->params()->fromQuery('agent');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        if ($agent)
        {            
            $filter['active'] = 1;
            $filter['agent'] = $agent;
            $filter['organization'] = $organization;
            
            $filterDaily = array();
            $filterMonthly = array();
            $filterWeekly = array();
            $filterQuarterly = array();
            $filterMTD = array();
            $filterWTD = array();
            
            $dateRange = Utilities::getYesterdayInterval();
            $filterDaily['date_from'] = $dateRange['start'];
            $filterDaily['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getLastWeekInterval();
            $filterWeekly['date_from'] = $dateRange['start'];
            $filterWeekly['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getLastMonthInterval();
            $filterMonthly['date_from'] = $dateRange['start'];
            $filterMonthly['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getLastQuarterInterval();
            $filterQuarterly['date_from'] = $dateRange['start'];
            $filterQuarterly['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getMonthToDateInterval();
            $filterMTD['date_from'] = $dateRange['start'];
            $filterMTD['date_to'] = $dateRange['end'];
            
            $dateRange = Utilities::getWeekToDateInterval();
            $filterWTD['date_from'] = $dateRange['start'];
            $filterWTD['date_to'] = $dateRange['end'];
            
            $filterDaily = array_merge($filter, $filterDaily);
            $filterWeekly = array_merge($filter, $filterWeekly);
            $filterMonthly = array_merge($filter, $filterMonthly);
            $filterQuarterly = array_merge($filter, $filterQuarterly);
            $filterMTD = array_merge($filter, $filterMTD);
            $filterWTD = array_merge($filter, $filterWTD);
            
            // get the scores
            $dailyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filterDaily);
            $dailyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterDaily);
            
            $weeklyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filterWeekly);
            $weeklyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterWeekly);
            
            $monthlyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filterMonthly);
            $monthlyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterMonthly);
            
            $quaterlyGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filterQuarterly);
            $quaterlyGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterQuarterly);
            
            $mtdGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filterMTD);
            $mtdGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterMTD);
            
            $wtdGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filterWTD);
            $wtdGroupsScoresTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchGroupScoreGrandTotal($filterWTD);
            
            $report = array();
            $report[0]['name'] = 'Daily';
            $report[1]['name'] = 'Weekly';
            $report[2]['name'] = 'Monthly';
            $report[3]['name'] = 'Quarterly';
            $report[4]['name'] = 'Month To Date';
            $report[5]['name'] = 'Week To Date';
            
            $report[0]['groups'] = $dailyGroupsScores;
            $report[0]['total'] = $dailyGroupsScoresTotal;
            $report[1]['groups'] = $weeklyGroupsScores;
            $report[1]['total'] = $weeklyGroupsScoresTotal;
            $report[2]['groups'] = $monthlyGroupsScores;
            $report[2]['total'] = $monthlyGroupsScoresTotal;
            $report[3]['groups'] = $quaterlyGroupsScores;
            $report[3]['total'] = $quaterlyGroupsScoresTotal;
            $report[4]['groups'] = $mtdGroupsScores;
            $report[4]['total'] = $mtdGroupsScoresTotal;
            $report[5]['groups'] = $wtdGroupsScores;
            $report[5]['total'] = $wtdGroupsScoresTotal;
        }
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $agents = $userTable->fetchAllProjectAgents(null, $organization);
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        return new ViewModel(array(
            'report' => $report,
            'filter' => $filter,
            'agents' => $agents,
            'questionsGroups' => $questionsGroups
        ));
    }
    
    public function qaAgentAction()
    {
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;

        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(true, $filter, array('u1.name ASC'), 'l.id_qa_agent');
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
        ));
    }
    
    public function fatalsAction()
    {
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        $listenings = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchFatalListenings(true, $filter);
        // set the current page to what has been passed in query string, or to 1 if none set
        $listenings->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $listenings->setItemCountPerPage(10);
        $listenings->setPageRange(5);
        
        $topFatalQuestions = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchFatalQuestionsTotals($filter);
        
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -12 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
        $historyFilter['organization'] = $organization;
        $weeksProjectsFatalsTotals = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchWeeksProjectsFatalsTotals($historyFilter);
        
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))';
        $historyFilter['organization'] = $organization;
        $monthsProjectsFatalsTotals = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchMonthsProjectsFatalsTotals($historyFilter);
        
        // select filters
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'listenings' => $listenings,
            'topFatalQuestions' => $topFatalQuestions,
            'weeksProjectsFatalsTotals' => $weeksProjectsFatalsTotals,
            'monthsProjectsFatalsTotals' => $monthsProjectsFatalsTotals,

            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
        ));
    }
    
    public function projectDetailAction()
    {
        $project = $this->params()->fromQuery('project', 0);
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;

        try {
            $oProject = $this->getServiceLocator()->get('Application\Model\ProjectTable')->getById($project);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('home');
        }
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(true, $filter, array('u2.name ASC'), 'l.id_agent');
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);

        // get question group scores for each agent in paginator
        $filter2 = $filter;
        unset($filter2['agent']);
        
        $agents = array();
        foreach ($paginator as $item) {
            array_push($agents, $item->id_agent);
        }
        
        $filter2['agent'] = $agents;
        
        // get the scores 
        $projectsGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsGroupScoreAverage($filter2);
        
        // create 4 weeks history report
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['organization'] = $organization;
        
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -11 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
        $weekRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
        
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))';
        $monthRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("MONTH(l.created)"));
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'project' => $oProject,
            'paginator' => $paginator,
            'projectsGroupsScores' => $projectsGroupsScores,
            'questionsGroups' => $questionsGroups,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
            'weekRollingScoreTotal' => $weekRollingScoreTotal,
            'monthRollingScoreTotal' => $monthRollingScoreTotal
        ));
    }
    
    public function agentProjectDetailAction()
    {
        $agent = $this->params()->fromQuery('agent', 0);
        $project = $this->params()->fromQuery('project');
        $organization = $this->auth->getIdentity()->id_organization;
        
        if (!$agent || !$project)
            return $this->redirect()->toRoute('home');
        
        try {
            $oAgent = $this->getServiceLocator()->get('Application\Model\UserTable')->getUser($agent);
            $oProject = $this->getServiceLocator()->get('Application\Model\ProjectTable')->getById($project);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('home');
        }
        
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($agent)
            $filter['agent'] = $agent;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        //get project overview for current filters
        $agentDateScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('l.created ASC'), 'l.created');
        
        // get the scores
        $datesGroupsScores = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchDatesGroupScoreAverage($filter);

        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        return new ViewModel(array(
            'agentDatesScores' => $agentDateScores,
            'datesGroupsScores' => $datesGroupsScores,
            'questionsGroups' => $questionsGroups,
            'filter' => $filter,
            'project' => $oProject,
            'agent' => $oAgent
        ));
    }
    
    public function qaAgentRollingAction()
    {
        $qaAgent = $this->params()->fromQuery('qa_agent', 0);
        
        if (!$qaAgent)
            return $this->redirect()->toRoute('home');
        
        try {
            $oQaAgent = $this->getServiceLocator()->get('Application\Model\UserTable')->getUser($qaAgent);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('home');
        }
        
        // create 4 weeks history report
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['qa_agent'] = $qaAgent;
        
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -11 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
        $weekRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
        
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))';
        $monthRollingScoreTotal = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $historyFilter, array('l.created ASC'), new Expression("MONTH(l.created)"));
        
        return new ViewModel(array(
            'qaAgent' => $oQaAgent,
            'weekRollingScoreTotal' => $weekRollingScoreTotal,
            'monthRollingScoreTotal' => $monthRollingScoreTotal
        ));
    }
    
    public function totalFatalsPerAgentAction()
    {
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchFatalAgents(true, $filter);
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $vm = new ViewModel();
        $vm->setVariables(array('paginator'=>$paginator, 'filter'=>$filter))->setTerminal(true);
        
        return $vm;
    }
    
}