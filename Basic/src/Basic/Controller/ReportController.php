<?php
namespace Basic\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Sql\Expression;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Application\Helper\ExcelReports;

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
    protected $translator;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }
    
    public function getTranslator()
    {
        
        if (!$this->translator) {
            $sm = $this->getServiceLocator();
            $this->translator = $sm->get('translator');
        }
        return $this->translator;
    }

    public function globalWeeklyProgressAction()
    {
        $subtitle = $this->getTranslator()->translate('Global Weekly Progress');
        
        $channel = $this->params()->fromQuery('channel');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        $excel = $this->params()->fromQuery('excel');
        $project = $this->params()->fromQuery('project');
        $auditor = $this->params()->fromQuery('auditor');
        $operator = $this->params()->fromQuery('operator');
        
        $filter = array();
    
        $filter['active'] = 1;
    
        if ($channel)
            $filter['channel'] = $channel;
    
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
    
        if ($dateTo)
            $filter['date_to'] = $dateTo;
    
        if ($organization)
            $filter['organization'] = $organization;
        
        if ($auditor)
            $filter['qa_agent'] = $auditor;
        
        if ($operator)
            $filter['agent'] = $operator;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($excel)
        {
            $excelReport = new ExcelReports($this->getServiceLocator());
            $excelReport->exportGlobalWeeklyProgress($this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('l.created ASC'), new Expression('WEEKOFYEAR(l.created)')));
        }
    
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('l.created ASC'), new Expression('WEEKOFYEAR(l.created)'));
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $membership = $membershipTable->getById($this->session->role->membership->id_membership);
        $hasAgents = $membership->hasAgents();
        
        if ($hasAgents) {
            $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
            $agents = $userTable->fetchAllProjectAgents();
        }
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $auditors = $userTable->fetchAllProjectAuditors(null, $organization);
    
        $vmVars = array(
            'paginator' => $paginator,
            'filter' => $filter,
            'channels' => $channels,
            'subtitle'=>$subtitle,
            'projects'=>$projects,
            'auditors'=>$auditors,
            'hasAgents'=>$hasAgents,
        );
        
        if ($hasAgents) {
            $vmVars['agents'] = $agents;
        }
        
        return new ViewModel($vmVars);
    }
    
    public function questionWeeklyProgressAction()
    {
        $channel = $this->params()->fromQuery('channel');
        $project = $this->params()->fromQuery('project');
        $auditor = $this->params()->fromQuery('auditor');
        $operator = $this->params()->fromQuery('operator');
        $excel = $this->params()->fromQuery('excel');
        
        $filter = array();
        if ($channel)
            $filter['channel']=$channel;
        if ($project)
            $filter['project']=$project;
        if ($auditor)
            $filter['qa_agent']=$auditor;
        if ($operator)
            $filter['agent']=$operator;
        
        $subtitle = $this->getTranslator()->translate('Question Weekly Progress');
        
        $organization = $this->auth->getIdentity()->id_organization;
        
        // create 4 weeks history report
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -4 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
        $historyFilter['organization'] = $organization;
        if ($channel)
            $historyFilter['channel']=$channel;
        if ($project)
            $historyFilter['project']=$project;
        if ($auditor)
            $historyFilter['qa_agent']=$auditor;
        if ($operator)
            $historyFilter['agent']=$operator;
        
        $weeksGrandTotal = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchWeeksScoreAverage($historyFilter);
        $weekGroupScoreAvg = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchWeeksGroupScoreAverage($historyFilter);
        $groupScoreTotalTemp = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchScoreTotal($historyFilter, array('id_group'));
        $groupScoreGrandTotal = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchGroupScoreGrandTotal($historyFilter);
        $weekQuestionScoreAvg = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchWeeksQuestionScoreAverage($historyFilter);
        $questionScoreTotalTemp = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchScoreTotal($historyFilter, array('id_question'));

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
        
        // get question groups
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization), 'qg.order ASC');
        
        $arr = array(
            'weeksGrandTotal' => $weeksGrandTotal,
            'weekGroupScoreAvg' => $weekGroupScoreAvg,
            'groupScoreTotal' => $groupScoreTotal,
            'groupScoreGrandTotal' => $groupScoreGrandTotal,
            'weekQuestionScoreAvg' => $weekQuestionScoreAvg,
            'questionScoreTotal' => $questionScoreTotal,
            'subtitle' => $subtitle,
        );
        
        if ($excel)
        {
            $excelReport = new ExcelReports($this->getServiceLocator());
            $excelReport->exportQuestionWeeklyProgress($arr);
        }
        
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $membership = $membershipTable->getById($this->session->role->membership->id_membership);
        $hasAgents = $membership->hasAgents();
        
        if ($hasAgents) {
            $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
            $agents = $userTable->fetchAllProjectAgents();
        }
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $auditors = $userTable->fetchAllProjectAuditors(null, $organization);
        
        $arr['channels']=$channels;
        $arr['projects']=$projects;
        $arr['auditors']=$auditors;
        $arr['filter']=$filter;
        $arr['hasAgents']=$hasAgents;
        
        if ($hasAgents) {
            $arr['agents'] = $agents;
        }
        
        return new ViewModel($arr);
    }
    
    public function sampleDailyOverviewAction()
    {
        $subtitle = $this->getTranslator()->translate('Sample Daily Overview');
        
        $auditor = $this->params()->fromQuery('auditor');
        $operator = $this->params()->fromQuery('operator');
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        $excel = $this->params()->fromQuery('excel');
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($auditor)
            $filter['qa_agent'] = $auditor;
        
        if ($operator)
            $filter['agent'] = $operator;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        if ($excel)
        {
            $excelReport = new ExcelReports($this->getServiceLocator());
            $excelReport->exportSampleDailyOverview($this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('l.created ASC'), new Expression('DAY(l.created)')));
        }
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('l.created ASC'), new Expression('DAY(l.created)'));
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $membership = $membershipTable->getById($this->session->role->membership->id_membership);
        $hasAgents = $membership->hasAgents();
        
        if ($hasAgents) {
            $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
            $agents = $userTable->fetchAllProjectAgents();
        }
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $auditors = $userTable->fetchAllProjectAuditors(null, $organization);
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $vmVars = array(
            'paginator' => $paginator,
            'filter' => $filter,
            'channels' => $channels,
            'auditors' => $auditors,
            'subtitle'=>$subtitle,
            'projects' => $projects,
            'hasAgents' => $hasAgents
        );
        
        if ($hasAgents) {
            $vmVars['agents'] = $agents;
        }
        
        return new ViewModel($vmVars);
    }
    
    public function globalDailyProgressAction()
    {
        $channel = $this->params()->fromQuery('channel');
        $project = $this->params()->fromQuery('project');
        $auditor = $this->params()->fromQuery('auditor');
        $operator = $this->params()->fromQuery('operator');
        $excel = $this->params()->fromQuery('excel');
        
        $filter = array();
        if ($channel)
            $filter['channel']=$channel;
        if ($project)
            $filter['project']=$project;
        if ($auditor)
            $filter['auditor']=$auditor;
        if ($operator)
            $filter['operator']=$operator;
        
        $subtitle = $this->getTranslator()->translate('Global Daily Progress');

        $organization = $this->auth->getIdentity()->id_organization;
    
        // create 4 weeks history report
        $historyFilter = array();
        $historyFilter['active'] = 1;
        $historyFilter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH)) AND DATE(NOW())';
        $historyFilter['organization'] = $organization;
        
        if ($channel)
            $historyFilter['channel']=$channel;
        if ($project)
            $historyFilter['project']=$project;
        if ($auditor)
            $historyFilter['qa_agent']=$auditor;
        if ($operator)
            $historyFilter['agent']=$operator;
    
        $dailyGrandTotal = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchDailyScoreAverage($historyFilter);
        $dailyQuestionScoreAvg = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchDailyQuestionScoreAverage($historyFilter);
        
        $vm = array(
            'dailyGrandTotal' => $dailyGrandTotal,
            'dailyQuestionScoreAvg' => $dailyQuestionScoreAvg,
            'subtitle'=>$subtitle,
        );
        
        if ($excel)
        {
            $excelReport = new ExcelReports($this->getServiceLocator());
            $excelReport->exportGlobalDailyProgress($vm);
        }
        
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $membership = $membershipTable->getById($this->session->role->membership->id_membership);
        $hasAgents = $membership->hasAgents();
        
        if ($hasAgents) {
            $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
            $agents = $userTable->fetchAllProjectAgents();
        }
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $auditors = $userTable->fetchAllProjectAuditors(null, $organization);
    
        $vm['channels']=$channels;
        $vm['projects']=$projects;
        $vm['auditors']=$auditors;
        $vm['filter']=$filter;
        $vm['hasAgents'] = $hasAgents;
        
        if ($hasAgents) {
            $vm['agents'] = $agents;
        }
        
        return new ViewModel($vm);
    }
    
    public function projectOverviewAction()
    {
        $subtitle = $this->getTranslator()->translate('Project Overview');
        
        $channel = $this->params()->fromQuery('channel');
        $project = $this->params()->fromQuery('project');
        $dateFrom = $this->params()->fromQuery('date_from')?:date("m/d/Y", strtotime(date("Y-m-d",strtotime(date("Y-m-d")))."-1 month"));
        $dateTo = $this->params()->fromQuery('date_to')?:date('m/d/Y');
        $organization = $this->auth->getIdentity()->id_organization;
        $excel = $this->params()->fromQuery('excel');
        $auditor = $this->params()->fromQuery('auditor');
        $operator = $this->params()->fromQuery('operator');
        
        $filter = array();
        
        $filter['active'] = 1;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($project)
            $filter['project'] = $project;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        if ($auditor)
            $filter['qa_agent'] = $auditor;
        
        if ($operator)
            $filter['agent'] = $operator;
        
        //get project overview for current filters
        $paginator = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('p.name ASC'), 'l.id_project');
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $projectsGroupsScores = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchProjectsGroupsOverview($filter);
        
        $projectsGroupsTotals = array();
        foreach ($paginator as $item)
        {
            $projectsGroupsTotals[$item->id_project]=array();
            $projectsGroupsTotals[$item->id_project]['samples']=$item->samples;
            $projectsGroupsTotals[$item->id_project]['score']=$item->score;
        }
        
        $filterGroups = array(
            'organization'=>$organization,
            'project'=>$project
        );
        $questionGroupsTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionGroups = $questionGroupsTable->fetchAllProjectsGroups($filterGroups);
        
        if ($excel)
        {
            $excelReport = new ExcelReports($this->getServiceLocator());
            $excelReport->exportProjectsGroupsOverview($this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchProjectsGroupsOverview($filter), $questionGroups, $projectsGroupsTotals);
        }
        
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $membership = $membershipTable->getById($this->session->role->membership->id_membership);
        $hasAgents = $membership->hasAgents();
        
        if ($hasAgents) {
            $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
            $agents = $userTable->fetchAllProjectAgents();
        }
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        $auditors = $userTable->fetchAllProjectAuditors(null, $organization);
        
        $vmVars = array(
            'paginator'=>$paginator,
            'filter'=>$filter,
            'channels'=>$channels,
            'projects'=>$projects,
            'subtitle'=>$subtitle,
            'projectsGroupsTotals'=>$projectsGroupsTotals,
            'projectsGroupsScores'=>$projectsGroupsScores,
            'questionGroups'=>$questionGroups,
            'auditors'=>$auditors,
            'hasAgents'=>$hasAgents
        );
        
        if ($hasAgents) {
            $vmVars['agents'] = $agents;
        }
        
        return new ViewModel($vmVars);        
    }
}