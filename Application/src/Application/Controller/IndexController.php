<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Admin for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * 
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 * 
 * @version
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Helper\Utilities;
use Zend\Db\Sql\Expression;
use Zend\Authentication\AuthenticationService;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        set_time_limit ( 0 );
        
        $auth = new AuthenticationService();
        $userId = $auth->getIdentity()->id;
        $organizationId = $auth->getIdentity()->id_organization;
        
        $mtd = Utilities::getMonthToDateInterval();
        $wtd = Utilities::getWeekToDateInterval();
        $weekly = Utilities::getLast7DaysInterval();
        $intraweek = Utilities::getIntraweekInterval();
        
        // Total Score per Project (MTD)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $organizationId;
        $projectsScoresMTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), 'l.id_project');
        
        // Global Total Score per Week (last 12 weeks) & Sample Size
        $filter = array();
        $filter['active'] = 1;
        $filter['date_interval_custom'] = 'DATE(l.created) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -12 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))';
        $filter['organization'] = $organizationId;
        $globalTotalScorePerWeek = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('l.created ASC'), new Expression("WEEKOFYEAR(l.created)"));
        
        // Listenings per QA Agent (WTD)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $wtd['start'];
        $filter['date_to'] = $wtd['end'];
        $filter['organization'] = $organizationId;
        $qaAgentsScoresWTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('u1.name ASC'), 'l.id_qa_agent');
        
        // Total QA Hours (WTD)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $wtd['start'];
        $filter['date_to'] = $wtd['end'];
        $filter['organization'] = $organizationId;
        $totalQAHoursWTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('l.created ASC'), new Expression('DATE(l.created)'));
        
        // Bottom 10 Agents per Total Score (MTD)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $organizationId;
        $bottomAgentsMTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(true, $filter, array('score ASC','agent ASC'), 'l.id_agent');
        
        // Top 10 Agents per Total Score (MTD)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $mtd['start'];
        $filter['date_to'] = $mtd['end'];
        $filter['organization'] = $organizationId;
        $topAgentsMTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(true, $filter, array('score DESC','agent ASC'), 'l.id_agent');
        
        // Projects Overview (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $wtd['start'];
        $filter['date_to'] = $wtd['end'];
        $filter['agent'] = $userId;
        $filter['organization'] = $organizationId;
        $projectsScoresWTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('p.name ASC'), 'l.id_project');
        
        // Personal Total QA Hours (WTD) 
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $wtd['start'];
        $filter['date_to'] = $wtd['end'];
        $filter['qa_agent'] = $userId;
        $filter['organization'] = $organizationId;
        $personalQAHoursWTD = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAllOverview(false, $filter, array('l.created ASC'), new Expression('DATE(l.created)'));
        
        // Top 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $organizationId;
        $topScoringQuestions = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score DESC', new Expression('q.name ASC')), 5);
        
        // Bottom 5 Scoring Questions (Weekly)
        $filter = array();
        $filter['active'] = 1;
        $filter['date_from'] = $weekly['start'];
        $filter['date_to'] = $weekly['end'];
        $filter['organization'] = $organizationId;
        $bottomScoringQuestions = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchQuestionsScoreAverage($filter, array('score ASC', new Expression('q.name ASC')), 5);
        
        // Highest Intraweek Drop (Operator)
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $organizationId;
        $highestIntraweekDropOperator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsIntraweekDropRise(true, $filter, 1);
        
        // Highest Intraweek Rise (Operator)
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $organizationId;
        $highestIntraweekRiseOperator = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchAgentsIntraweekDropRise(false, $filter, 1);
        
        // Highest Intraweek Drop (Question)
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $organizationId;
        $highestIntraweekDropQuestion = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchQuestionsIntraweekDropRise(true, $filter, 1);
        
        // Highest Intraweek Rise (Question)
        $filter = array();
        $filter['date_from1'] = $intraweek['start1'];
        $filter['date_to1'] = $intraweek['end1'];
        $filter['date_from2'] = $intraweek['start2'];
        $filter['date_to2'] = $intraweek['end2'];
        $filter['organization'] = $organizationId;
        $highestIntraweekRiseQuestion = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchQuestionsIntraweekDropRise(false, $filter, 1);
        
        // Score Needed To Reach Target
        $filter = array();
        $filter['organization'] = $organizationId;
        $scoresToTarget = $this->getServiceLocator()->get('Application\Model\ReportTable')->fetchScoreToTarget($filter);
        
        return new ViewModel(array(
            'projectsScoresMTD' => $projectsScoresMTD,
            'globalTotalScorePerWeek' => $globalTotalScorePerWeek,
            'qaAgentsScoresWTD' => $qaAgentsScoresWTD,
            'totalQAHoursWTD'=>$totalQAHoursWTD,
            'bottomAgentsMTD'=>$bottomAgentsMTD,
            'topAgentsMTD'=>$topAgentsMTD,
            'projectsScoresWTD'=>$projectsScoresWTD,
            'personalQAHoursWTD'=>$personalQAHoursWTD,
            'topScoringQuestions'=>$topScoringQuestions,
            'bottomScoringQuestions'=>$bottomScoringQuestions,
            'highestIntraweekDropOperator'=>$highestIntraweekDropOperator,
            'highestIntraweekRiseOperator'=>$highestIntraweekRiseOperator,
            'highestIntraweekDropQuestion'=>$highestIntraweekDropQuestion,
            'highestIntraweekRiseQuestion'=>$highestIntraweekRiseQuestion,
            'scoresToTarget'=>$scoresToTarget,
        ));
    }

    
    public function faqsAction()
    {
        return new ViewModel();
    }
}
