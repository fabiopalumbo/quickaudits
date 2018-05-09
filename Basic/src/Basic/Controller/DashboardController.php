<?php
namespace Basic\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Helper\Utilities;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql\Expression;

class DashboardController extends AbstractActionController
{
    var $auth;
    var $organizationId;
    
    public function __construct()
    {
        set_time_limit ( 0 );
        
        $this->auth = new AuthenticationService();
        $this->organizationId = $this->auth->getIdentity()->id_organization;

    }

    public function customerSatisfactionAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');

        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
              
        $cs = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchCustomerSatisfaction($filter);

        $viewModel = new ViewModel(['cs'=>$cs]);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function nesCurrentMonthAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');

        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
              
        $nes = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchNESMonthScore($filter);

        $viewModel = new ViewModel(['nes'=>$nes]);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function scoreNeededAction()
    {
        
        
        // Total Score per Project (MTD)
        // Score Needed To Reach Target
        $filter = array();
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $scoresToTarget = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchScoreToTarget($filter);
        $viewModel = new ViewModel(array('scoresToTarget'=>$scoresToTarget));
        
        $viewModel->setTerminal(true);

        return $viewModel;
    }
    
    public function todayScoreAction()
    {
        $filter = array();
        $filter['active'] = 1;
        $filter['date'] = date('Y-m-d');
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $todayScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('DATE(l.created)'));
        
        $viewModel = new ViewModel(array('todayScore'=>$todayScore));
        
        $viewModel->setTerminal(true);
        
        return $viewModel;
    }
    
    public function mtdScoreAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
        
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('MONTH(l.created)'));
        
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
        
        $viewModel->setTerminal(true);
        
        return $viewModel;
    }
    
    public function numberSamplesMtdAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('MONTH(l.created)'));
    
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function globalDailyProgressAction()
    {
        $weekly = Utilities::getLast7DaysInterval();

        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $globalDailyProgress = $this->getServiceLocator()
            ->get('Basic\Model\ReportTable')
            ->fetchAllOverview(false, $filter, array('l.created ASC'), new Expression("DATE(l.created)"));

        $viewModel = new ViewModel(array('globalDailyProgress'=>$globalDailyProgress));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekRiseQuestionAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
        
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekRiseQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsIntraweekDropRise(false, $filter, 1);
        
        $viewModel = new ViewModel(array('highestIntraweekRiseQuestion'=>$highestIntraweekRiseQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekDropQuestionAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekDropQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsIntraweekDropRise(true, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekDropQuestion'=>$highestIntraweekDropQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function topScoringQuestionsAction()
    {
        $weekly = Utilities::getLast7DaysInterval();
        
        // Top 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $topScoringQuestions = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score DESC', new Expression('q.name ASC')), 5);
        
        $viewModel = new ViewModel(array('topScoringQuestions'=>$topScoringQuestions));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function bottomScoringQuestionsAction()
    {
        $weekly = Utilities::getLast7DaysInterval();
    
        // Bottom 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $bottomScoringQuestions = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score ASC', new Expression('q.name ASC')), 5);
    
        $viewModel = new ViewModel(array('bottomScoringQuestions'=>$bottomScoringQuestions));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function questionPerformanceRankingAction()
    {
        $weekly = Utilities::getLast7DaysInterval();
    
        // Top 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $questionsPerformanceRanking = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score DESC', new Expression('q.name ASC')));
    
        $viewModel = new ViewModel(array('questionsPerformanceRanking'=>$questionsPerformanceRanking));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function projectOverviewAction() {
        
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $projectsOverview=$this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('p.name ASC'), 'l.id_project');
        
        $viewModel = new ViewModel(array('projectsOverview'=>$projectsOverview));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekRiseProjectAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekRiseProject = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchProjectsIntraweekDropRise(false, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekRiseProject'=>$highestIntraweekRiseProject));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekDropProjectAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekDropProject = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchProjectsIntraweekDropRise(true, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekDropProject'=>$highestIntraweekDropProject));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function mtdScorePerProjectAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('MONTH(l.created), l.id_project'));
    
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function mtdScorePerQuestionGroupAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchProjectsGroupsOverview($filter);
    
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }

    public function numberSamplesMtdPerProjectAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('MONTH(l.created), l.id_project'));
    
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function topScoringQuestionsGlobalAction()
    {
        $weekly = Utilities::getLast7DaysInterval();
    
        // Top 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $topScoringQuestions = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score DESC', new Expression('q.name ASC')), 5);
    
        $viewModel = new ViewModel(array('topScoringQuestions'=>$topScoringQuestions));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function bottomScoringQuestionsGlobalAction()
    {
        $weekly = Utilities::getLast7DaysInterval();
    
        // Bottom 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $bottomScoringQuestions = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score ASC', new Expression('q.name ASC')), 5);
    
        $viewModel = new ViewModel(array('bottomScoringQuestions'=>$bottomScoringQuestions));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekRiseQuestionGlobalAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekRiseQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsIntraweekDropRise(false, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekRiseQuestion'=>$highestIntraweekRiseQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekDropQuestionGlobalAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekDropQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionsIntraweekDropRise(true, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekDropQuestion'=>$highestIntraweekDropQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function todayScoreGlobalAction()
    {
        $filter = array();
        $filter['active'] = 1;
        $filter['date'] = date('Y-m-d');
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;

        $todayScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('DATE(l.created)'));
    
        $viewModel = new ViewModel(array('todayScore'=>$todayScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function todayScorePerProjectAction()
    {
        $filter = array();
        $filter['active'] = 1;
        $filter['date'] = date('Y-m-d');
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;

        $todayScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('DATE(l.created), l.id_project'));
    
        $viewModel = new ViewModel(array('todayScore'=>$todayScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function todayScorePerQuestionGroupAction()
    {
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = date('Y-m-d');
        $filter['date_to'] = date('Y-m-d');
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $todayScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchProjectsGroupsOverview($filter);
    
        $viewModel = new ViewModel(array('todayScore'=>$todayScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function mtdScoreGlobalAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;

        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('MONTH(l.created)'));
    
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function numberSamplesMtdGlobalAction()
    {
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;

        $mtdScore = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), new Expression('MONTH(l.created)'));
    
        $viewModel = new ViewModel(array('mtdScore'=>$mtdScore));
    
        $viewModel->setTerminal(true);
    
        return $viewModel;
    }
    
    public function highestIntraweekRiseQuestionGroupsAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekRiseQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionGroupsIntraweekDropRise(false, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekRiseQuestion'=>$highestIntraweekRiseQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekDropQuestionGroupsAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;
        
        $highestIntraweekDropQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionGroupsIntraweekDropRise(true, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekDropQuestion'=>$highestIntraweekDropQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekRiseQuestionGroupsGlobalAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;

        $highestIntraweekRiseQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionGroupsIntraweekDropRise(false, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekRiseQuestion'=>$highestIntraweekRiseQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function highestIntraweekDropQuestionGroupsGlobalAction()
    {
        $intraweek = Utilities::getIntraweekInterval();
    
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $this->organizationId;
        
        $config = $this->getServiceLocator()->get('config');
        if ($this->auth->getIdentity()->id_role == $config['roles']['agent'])
            $filter['agent'] = $this->auth->getIdentity()->id;

        $highestIntraweekDropQuestion = $this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchQuestionGroupsIntraweekDropRise(true, $filter, 1);
    
        $viewModel = new ViewModel(array('highestIntraweekDropQuestion'=>$highestIntraweekDropQuestion));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function topAgentsAction() {
    
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
    
        $projectsOverview=$this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('score DESC'), 'l.id_agent');

        $viewModel = new ViewModel(array('projectsOverview'=>$projectsOverview));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function bottomAgentsAction() {
    
        $mtd = Utilities::getMonthToDateInterval();
    
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $this->organizationId;
        $filter['project'] = $this->params()->fromQuery('project');
    
        $projectsOverview=$this->getServiceLocator()->get('Basic\Model\ReportTable')->fetchAllOverview(true, $filter, array('score ASC'), 'l.id_agent');

        $viewModel = new ViewModel(array('projectsOverview'=>$projectsOverview));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    
}