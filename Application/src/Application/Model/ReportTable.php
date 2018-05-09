<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class ReportTable
{
    /**
     * 
     * @var Zend\Db\Adapter\Adapter
     */
    protected $dbAdapter;

    /**
     * 
     * @param Zend\Db\Adapter\Adapter
     */
    public function __construct(Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    }

	public function fetchAllAgents() {

	        $sql = new \Zend\Db\Sql\Sql($this->dbAdapter);
    
	        $select = $sql->select()
	        ->from('users')		
		->columns(array('id','id_organization','id_role','name'))
		->where(array('id_role' => 19));

	$selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        $results->buffer();    	
	//var_dump($results);

        return $results;
	}


    public function fetchAllOverview($paginated=false, $filter=array(), $order=null, $groupby=null)
    {
        $where = array();
    
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
    
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $where[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $where[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }            
    
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $where[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $where[] = '(l.id_language = \''.$filter['language'].'\')';
        }       
    
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
    
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $where[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $where[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $where[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            $where[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['date_interval_custom']))
        {
            $where[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
    
        $sql = new \Zend\Db\Sql\Sql($this->dbAdapter);
    
        $select = $sql->select()
            ->from(array('l' => 'listenings'))
            ->columns(array('id_project','id_channel','id_agent','id_qa_agent','id_language','id_form','date','created'=>new Expression('DATE(l.created)'),
                            'score'=>new Expression('ROUND(AVG(score),2)'),'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
                            'week'=>new Expression("WEEKOFYEAR(l.date)"),'month'=>new Expression("MONTH(l.date)"),
                            'total_time'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)), 2)'),
                            'total_hours'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)) / 60, 2)'),
            ))
            ->join(array('p' => 'projects'), 'l.id_project=p.id', array('project'=>'name'))
            ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
            ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'))
            ->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'))
            ->join(array('la' => 'languages'), 'l.id_language=la.id', array('language'=>'name'))
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order(($order?:'l.id DESC'));
            
        if (!is_null($groupby))
            $select->group($groupby);
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            // create a new pagination adapter object
            $paginatorAdapter = new DbSelect(
                // our configured select object
                $select,
                // the adapter to run it against
                $this->dbAdapter,
                // the result set to hydrate
                $resultSetPrototype
            );
            $paginator = new Paginator($paginatorAdapter);
            return $paginator;
        }
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $results->buffer();   
        return $results;
    }
    
    public function fetchProjectsGroupScoreAverage($filter=array())
    {
        $where = array();
        $subQueryWhere = array();
        
        if (isset($filter['project']) && !empty($filter['project']))
        {
            if (is_array($filter['project']))
            {
                $subQueryWhere[] = 'l.id_project IN ('.implode(',', $filter['project']).')';
            }
            else
            {
                $subQueryWhere[] = 'l.id_project=\''.$filter['project'].'\'';
            }
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $subQueryWhere[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $subQueryWhere[] = '(l.id_language = \''.$filter['language'].'\')';
        }
        
        if (isset($filter['date_from']))
            $subQueryWhere[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $subQueryWhere[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $subQueryWhere[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $subQueryWhere[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(qg.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $selectProjects = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('id_project'=>new Expression('DISTINCT(l.id_project)')))
        ->join(array('p'=>'projects'), 'l.id_project=p.id',array('project'=>'name'))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1);
        
        $selectScores = $sql->select()
            ->from(array('p' => 'projects'))
            ->columns(array('id_project'=>'id','score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, fq.answers - 1))) / COUNT(DISTINCT(l.id)) ,2)')))
            ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('date'))
            ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q' => 'questions'), 'la.id_question=q.id', array())
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group(array('p.id','qg.id'));
         
        $select = $sql->select()
            ->from(array('p' => $selectProjects))
            ->columns(array('id_project','project'))
            ->join(array('qg' => 'questions_groups'), new Expression('1'), array('id_group'=>'id','question_group'=>'name'))
            ->join(array('scores'=>$selectScores), 'p.id_project=scores.id_project AND qg.id=scores.id_group', array('score'), 'left')
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order(array('p.project ASC','qg.order ASC'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
	
    }
    
    public function fetchWeeksGroupScoreAverage($filter = array())
    {
        $where = array();
        $subQueryWhere = array();
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(qg.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere[] = '(p.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        // select weeks from date range
        $selectWeeks = '(SELECT WEEKOFYEAR(A.DATE) AS week
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE    
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -4 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))
                        GROUP BY WEEKOFYEAR(A.DATE))';
        
        $selectScores = $sql->select()
            ->from(array('p' => 'projects'))
            ->columns(array('score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, fq.answers - 1))) / COUNT(DISTINCT(l.id)) ,2)')))
            ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('week'=>new Expression('WEEKOFYEAR(l.date)')))
            ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q' => 'questions'), 'la.id_question=q.id', array())
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( array('qg.id',new Expression('WEEKOFYEAR(l.date)')));
         
        $select = $sql->select()
            ->from(array('w' => $selectWeeks))
            ->columns(array('week'))
            ->join(array('qg' => 'questions_groups'), new Expression('1'), array('id_group'=>'id','question_group'=>'name'))
            ->join(array('scores'=>$selectScores), 'w.week=scores.week AND qg.id=scores.id_group', array('score'), 'left')
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order(array('qg.order ASC','w.week ASC'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }

    public function fetchScoreTotal($filter=array(),$groupby=array())
    {
        $where = array();
        $subQueryWhere = array();
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        // group by options
        $group = array();
        
        if (in_array('id_group', $groupby))
            $group[] = 'qg.id';
        
        if (in_array('id_question', $groupby))
            $group[] = 'q.id';
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
            ->from(array('p' => 'projects'))
            ->columns(array('score'=>new Expression('ROUND( SUM( (la.answer * 100 ) / (fq.answers - 1) ) / COUNT(l.id) ,2)')))
            ->join(array('l' => 'listenings'), 'p.id=l.id_project', array())
            ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q' => 'questions'), 'la.id_question=q.id', array('id_question'=>'id','question'=>'name'))
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id','question_group'=>'name'))
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group(!empty($group) ? new Expression(implode(' , ', $group)) : null)
            ->order(array('qg.order ASC','q.name ASC'));
         
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }
    
    public function fetchGroupScoreGrandTotal($filter=array())
    {
        $where = array();
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $where[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['date_interval_custom']))
        {
            $where[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $where[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $where[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
        
        if (isset($filter['organization']))
        {
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
                        ->from(array('p' => 'projects'))
                        ->columns(array('score'=>new Expression('ROUND(AVG(l.score), 2)')))
                        ->join(array('l' => 'listenings'), 'p.id=l.id_project', array())
                        ->where(!empty($where) ? implode(' AND ', $where) : 1);
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }
    
    public function fetchWeeksQuestionScoreAverage($filter = array())
    {
        $where = array();
        
        $subQueryWhere = array();
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
    
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        // condition to select ONLY questions answered by the agent
        // if this condition if removed, then it will return all questions no matter if the question belongs or not to the agent
        $where[] = 'q.id IN (SELECT la.id_question FROM listenings l INNER JOIN listenings_answers la ON l.id=la.id_listening WHERE '.implode(' AND ', $subQueryWhere).')';
    
        $sql = new Sql($this->dbAdapter);
    
        // select weeks from date range
        $selectWeeks = '(SELECT WEEKOFYEAR(A.DATE) AS week
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -4 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))
                        GROUP BY WEEKOFYEAR(A.DATE))';
    
        $selectScores = $sql->select()
            ->from(array('p' => 'projects'))
            ->columns(array('score'=>new Expression('ROUND( SUM((la.answer * 100 ) / (fq.answers - 1) ) / COUNT(DISTINCT(l.id)) ,2)')))
            ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('week'=>new Expression('WEEKOFYEAR(l.date)')))
            ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q' => 'questions'), 'la.id_question=q.id', array('id_question'=>'id'))
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array())
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( array('q.id',new Expression('WEEKOFYEAR(l.date)')));
         
        $select = $sql->select()
        ->from(array('w' => $selectWeeks))
        ->columns(array('week'))
        ->join(array('q' => 'questions'), new Expression('1'), array('id_question'=>'id','question'=>'name'))
        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id','question_group'=>'name'))
        ->join(array('scores'=>$selectScores), 'w.week=scores.week AND q.id=scores.id_question', array('score'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('qg.order ASC','q.name ASC','w.week ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
        
        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
//     public function fetchQuestionsGrandTotal($filter=array())
//     {
//         $where = null;
        
//         $sql = "SELECT 	q.id id_question, q.`name` question, qg.id id_group, qg.`name` `group`, DATE_FORMAT(l.date, '%u') `week`,
//                 				ROUND( SUM( (la.answer * 100 ) / (fq.answers - 1) ) / COUNT(DISTINCT(l.id)) ,2) avg
//                 FROM projects p
//                 INNER JOIN listenings l ON p.id = l.id_project
//                 INNER JOIN listenings_answers la ON l.id = la.id_listening
//                 INNER JOIN forms_questions fq ON l.id_form = fq.id_form and la.id_question = fq.id_question
//                 INNER JOIN forms f ON l.id_form = f.id
//                 INNER JOIN questions q ON la.id_question = q.id
//                 INNER JOIN questions_groups qg ON q.id_group = qg.id
//                 WHERE ".(!is_null($where) ? $where : 1 )."
//                 GROUP BY q.id
//                 ORDER BY qg.`order` ASC, q.`name` ASC, DATE_FORMAT(l.date, '%u');";
    
//         return $this->_db->fetchAll($sql);
//     }

    public function fetchQuestionsScoreAverage($filter=array(), $order=null, $limit=null)
    {
        $where = array();
        
        $where[] = '(l.score>0)';
        
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
            ->from(array('l'=>'listenings'))
            ->columns(array('score'=>new Expression('ROUND(SUM((la.answer*100)/(fq.answers-1))/COUNT(DISTINCT(l.id)),2)')))
            ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q'=>'questions'), 'la.id_question=q.id', array('question'=>'name'))
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->group('q.id')
            ->order(!is_null($order)?$order:array(new Expression('q.name ASC')));
        
        if (!is_null($limit))
            $select->limit($limit);

        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }
    
    public function fetchDatesGroupScoreAverage($filter=array())
    {
        $where = array();
        $subQueryWhere = array();
    
        if (isset($filter['project']) && !empty($filter['project']))
        {
            if (is_array($filter['project']))
            {
                $subQueryWhere[] = 'l.id_project IN ('.implode(',', $filter['project']).')';
            }
            else
            {
                $subQueryWhere[] = 'l.id_project=\''.$filter['project'].'\'';
            }
        }
    
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
    
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $subQueryWhere[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $subQueryWhere[] = '(l.id_language = \''.$filter['language'].'\')';
        }
    
        if (isset($filter['date_from']))
            $subQueryWhere[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
    
        if (isset($filter['date_to']))
            $subQueryWhere[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $subQueryWhere[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $subQueryWhere[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
    
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(qg.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
        
        $selectDates = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('created'=>new Expression('DISTINCT(DATE(l.created))')))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1);
        
        $selectScores = $sql->select()
        ->from(array('p' => 'projects'))
        ->columns(array('id_project'=>'id','score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, fq.answers - 1))) / COUNT(DISTINCT(l.id)) ,2)')))
        ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('created'=>new Expression('DATE(l.created)')))
        ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
        ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q' => 'questions'), 'la.id_question=q.id', array())
        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
        ->group(array(new Expression('DATE(l.created)'),'qg.id'));
         
        $select = $sql->select()
        ->from(array('d' => $selectDates))
        ->columns(array('created'))
        ->join(array('qg' => 'questions_groups'), new Expression('1'), array('id_group'=>'id','question_group'=>'name'))
        ->join(array('scores'=>$selectScores), 'd.created=scores.created AND qg.id=scores.id_group', array('score'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('d.created ASC','qg.order ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
    public function fetchAgentsGroupScoreAverage($filter=array())
    {
        $where = array();
        $subQueryWhere = array();
    
        if (isset($filter['project']) && !empty($filter['project']))
        {
            if (is_array($filter['project']))
            {
                $subQueryWhere[] = 'l.id_project IN ('.implode(',', $filter['project']).')';
            }
            else
            {
                $subQueryWhere[] = 'l.id_project=\''.$filter['project'].'\'';
            }
        }
    
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
    
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $subQueryWhere[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $subQueryWhere[] = '(l.id_language = \''.$filter['language'].'\')';
        }
    
        if (isset($filter['date_from']))
            $subQueryWhere[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
    
        if (isset($filter['date_to']))
            $subQueryWhere[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $subQueryWhere[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $subQueryWhere[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
    
        if (isset($filter['agent']))
        {
            if (is_array($filter['agent']) && !empty($filter['agent']))
            {
                $subQueryWhere[] = 'l.id_agent IN ('.implode(',', $filter['agent']).')';
            }
            elseif (is_numeric($filter['agent']))
            {
                $subQueryWhere[] = 'l.id_agent=\''.$filter['agent'].'\'';
            }
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(qg.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        $selectAgents = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('id_agent'=>new Expression('DISTINCT(l.id_agent)')))
        ->join(array('u'=>'users'), 'l.id_agent=u.id',array('agent'=>'name'))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1);
    
        $selectScores = $sql->select()
        ->from(array('p' => 'projects'))
        ->columns(array('score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, fq.answers - 1))) / COUNT(DISTINCT(l.id)) ,2)')))
        ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('id_agent'))
        ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
        ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q' => 'questions'), 'la.id_question=q.id', array())
        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
        ->group(array('l.id_agent','qg.id'));
         
        $select = $sql->select()
        ->from(array('a' => $selectAgents))
        ->columns(array('id_agent','agent'))
        ->join(array('qg' => 'questions_groups'), new Expression('1'), array('id_group'=>'id','question_group'=>'name'))
        ->join(array('scores'=>$selectScores), 'a.id_agent=scores.id_agent AND qg.id=scores.id_group', array('score'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('a.agent ASC','qg.order ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
    public function fetchFatalListenings($paginated=false,$filter=array())
    {
        $where = array();
        
        $where[] = '(l.score=0)';
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $where[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $where[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $where[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $where[] = '(l.id_language = \''.$filter['language'].'\')';
        }
        
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $where[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $where[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
            ->from(array('l'=>'listenings'))
            ->columns(array('id','recording_date'=>'date','questions'=>new Expression('GROUP_CONCAT(`q`.`name` SEPARATOR \', \')')))
            ->join(array('p'=>'projects'), 'p.id=l.id_project', array('project'=>'name'))
            ->join(array('c'=>'channels'), 'c.id=l.id_channel', array('channel'=>'name'))
            ->join(array('qa'=>'users'), 'qa.id=l.id_qa_agent', array('qa_agent'=>'name'))
            ->join(array('a'=>'users'), 'a.id=l.id_agent', array('agent'=>'name'))
            ->join(array('lang'=>'languages'), 'lang.id=l.id_language', array('language'=>'name'))
            ->join(array('la'=>'listenings_answers'), new Expression('l.id=la.id_listening AND la.answer = 1'), array())
            ->join(array('q'=>'questions'), 'la.id_question=q.id', array('question'=>'name'))
            ->join(array('qg'=>'questions_groups'), new Expression('q.id_group=qg.id AND qg.is_fatal = 1'), array())
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->group('l.id')
            ->order('p.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            // create a new pagination adapter object
            $paginatorAdapter = new DbSelect(
                // our configured select object
                $select,
                // the adapter to run it against
                $this->dbAdapter,
                // the result set to hydrate
                $resultSetPrototype
            );
            $paginator = new Paginator($paginatorAdapter);
            return $paginator;
        }
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }
    
    public function fetchFatalQuestionsTotals($filter=array())
    {
        $where = array();
        
        $where[] = '(l.score=0)';
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $where[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $where[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $where[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $where[] = '(l.id_language = \''.$filter['language'].'\')';
        }
        
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $where[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $where[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $select= $sql->select()
            ->from(array('l'=>'listenings'))
            ->columns(array('total'=>new Expression('COUNT(q.id)')))
            ->join(array('la'=>'listenings_answers'), new Expression('l.id=la.id_listening AND la.answer = 1'), array())
            ->join(array('q'=>'questions'), 'la.id_question=q.id', array('question'=>'name'))
//             ->join(array('qg'=>'questions_groups'), new Expression('q.id_group=qg.id AND qg.is_fatal = 1'), array())
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->group('q.id')
            ->order('total DESC')
            ->limit(10,0);
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }
    
    public function fetchFatalAgents($paginated=false, $filter=array())
    {
        $where = array();
    
        $where[] = '(l.score=0)';
    
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
    
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $where[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $where[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
    
        if (isset($filter['language']))
        {
            if (is_array($filter['language']))
                $where[] = '(l.id_language IN ('.implode(',', $filter['language']).'))';
            else
                $where[] = '(l.id_language = \''.$filter['language'].'\')';
        }
    
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
    
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
    
        if (isset($filter['recording_date_from']))
            $where[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
    
        if (isset($filter['recording_date_to']))
            $where[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        $select = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('total'=>new Expression('COUNT(a.id)')))
        ->join(array('a'=>'users'), 'a.id=l.id_agent', array('agent'=>'name'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->group('a.id')
        ->order('total desc');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            // create a new pagination adapter object
            $paginatorAdapter = new DbSelect(
                // our configured select object
                $select,
                // the adapter to run it against
                $this->dbAdapter,
                // the result set to hydrate
                $resultSetPrototype
            );
            $paginator = new Paginator($paginatorAdapter);
            return $paginator;
        }
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $results->buffer();
        return $results;
    }
    
    public function fetchWeeksProjectsFatalsTotals($filter = array())
    {
        $where = array();
        $subQueryWhere = array();
    
        $subQueryWhere[] = '(l.score=0)';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
    
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        if (isset($filter['date_from']))
            $subQueryWhere[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $subQueryWhere[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['recording_date_from']))
            $subQueryWhere[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
        
        if (isset($filter['recording_date_to']))
            $subQueryWhere[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(p.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
    
        $sql = new Sql($this->dbAdapter);
    
        // select weeks from date range
        $selectWeeks = '(SELECT WEEKOFYEAR(A.DATE) AS week
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -12 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))
                        GROUP BY WEEKOFYEAR(A.DATE))';
    
        $selectScores = $sql->select()
        ->from(array('p' => 'projects'))
        ->columns(array('id_project'=>'id','project'=>'name','total'=>new Expression('COUNT(DISTINCT(l.id))')))
        ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('week'=>new Expression('WEEKOFYEAR(l.date)')))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
        ->group( array('p.id',new Expression('WEEKOFYEAR(l.date)')));
         
        $select = $sql->select()
        ->from(array('w' => $selectWeeks))
        ->columns(array('week'))
        ->join(array('p' => 'projects'), new Expression('1'), array('id_project'=>'id','project'=>'name'))
        ->join(array('scores'=>$selectScores), 'w.week=scores.week AND p.id=scores.id_project', array('total'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('p.name ASC','w.week ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);

        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $results->buffer();
        
        return $results;
    }
    
    public function fetchMonthsProjectsFatalsTotals($filter = array())
    {
        $where = array();
        $subQueryWhere = array();
    
        $subQueryWhere[] = '(l.score=0)';
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
    
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        if (isset($filter['date_from']))
            $subQueryWhere[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
    
        if (isset($filter['date_to']))
            $subQueryWhere[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
    
        if (isset($filter['recording_date_from']))
            $subQueryWhere[] = '(DATE(l.date) >= \''.date('Y-m-d',strtotime($filter['recording_date_from'])).'\')';
    
        if (isset($filter['recording_date_to']))
            $subQueryWhere[] = '(DATE(l.date) <= \''.date('Y-m-d',strtotime($filter['recording_date_to'])).'\')';
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $where[] = '(p.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }    
    
        $sql = new Sql($this->dbAdapter);
    
        // select weeks from date range
        $selectWeeks = '(SELECT MONTH(A.DATE) AS month, YEAR(A.DATE) AS year
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -6 MONTH)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH))
                        GROUP BY MONTH(A.DATE))';
    
        $selectScores = $sql->select()
        ->from(array('p' => 'projects'))
        ->columns(array('id_project'=>'id','project'=>'name','total'=>new Expression('COUNT(DISTINCT(l.id))')))
        ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('month'=>new Expression('MONTH(l.date)')))
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
        ->group( array('p.id',new Expression('MONTH(l.date)')));
         
        $select = $sql->select()
        ->from(array('w' => $selectWeeks))
        ->columns(array('month'))
        ->join(array('p' => 'projects'), new Expression('1'), array('id_project'=>'id','project'=>'name'))
        ->join(array('scores'=>$selectScores), 'w.month=scores.month AND p.id=scores.id_project', array('total'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('p.name ASC','w.year ASC','w.month ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
    
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $results->buffer();
    
        return $results;
    }
    
    public function fetchWeeksScoreAverage($filter = array())
    {
        $where = array();
        $subQueryWhere = array();
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
    
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
    
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(p.id_organization = \''.$filter['organization'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        // select weeks from date range
        $selectWeeks = '(SELECT WEEKOFYEAR(A.DATE) AS week
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -4 WEEK)) AND DATE(DATE_ADD(NOW(),INTERVAL -1 WEEK))
                        GROUP BY WEEKOFYEAR(A.DATE))';
    
        $selectScores = $sql->select()
            ->from(array('l' => 'listenings'))
            ->columns(array('id_project','id_channel','id_agent','id_qa_agent','id_language','id_form','date','created'=>new Expression('DATE(l.created)'),
                            'score'=>new Expression('ROUND(AVG(score),2)'),'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
                            'week'=>new Expression("WEEKOFYEAR(l.date)"),'month'=>new Expression("MONTH(l.date)"),
                            'total_time'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)), 2)'),
                            'total_hours'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)) / 60, 2)'),
            ))
            ->join(array('p' => 'projects'), 'l.id_project=p.id', array('project'=>'name'))
            ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
            ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'))
            ->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'))
            ->join(array('la' => 'languages'), 'l.id_language=la.id', array('language'=>'name'))
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( new Expression('WEEKOFYEAR(l.date)'));
         
        $select = $sql->select()
        ->from(array('w' => $selectWeeks))
        ->columns(array('week'))
        ->join(array('scores'=>$selectScores), 'w.week=scores.week', array('score','samples'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('w.week ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
    
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $results->buffer();
        
        return $results;
    }

    public function fetchAgentsIntraweekDropRise($drop=true, $filter=array(), $limit=null)
    {
        $order = $drop ? new Expression('(w2.score-w1.score) ASC') : new Expression('(w2.score-w1.score) DESC');
        
        $where = array();
        $subQueryWhere1 = array();
        $subQueryWhere2 = array();
        
        $where[] = '(active=\'1\')';
        $subQueryWhere1[] = '(active=\'1\')';
        $subQueryWhere2[] = '(active=\'1\')';
        
        if (isset($filter['date_from1']) && isset($filter['date_from2']))
        {
            $subQueryWhere1[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from1'])).'\')';
            $subQueryWhere2[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from2'])).'\')';
        }
        
        if (isset($filter['date_to1']) && isset($filter['date_to2']))
        {
            $subQueryWhere1[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to1'])).'\')';
            $subQueryWhere2[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to2'])).'\')';
        }
            
        if (isset($filter['organization']))
        {
            $where[] = '(u.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere1[] = '(l.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere2[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $selectWeek1 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('score'=>new Expression('ROUND(AVG(l.score), 2)'),'id_agent'))
        ->where(!empty($subQueryWhere1) ? implode(' AND ', $subQueryWhere1) : 1)
        ->group('id_agent');
        
        $selectWeek2 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('score'=>new Expression('ROUND(AVG(l.score), 2)'),'id_agent'))
        ->where(!empty($subQueryWhere2) ? implode(' AND ', $subQueryWhere2) : 1)
        ->group('id_agent');
        
        $select = $sql->select()
        ->from(array('u' => 'users'))
        ->columns(array('user'=>'name','score'=>new Expression('(w2.score-w1.score)')))
        ->join(array('w1' => $selectWeek1), 'u.id=w1.id_agent', array('week1_score'=>'score'))
        ->join(array('w2' => $selectWeek2), 'u.id=w2.id_agent', array('week2_score'=>'score'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1);
        
        if (!is_null($order))
            $select->order($order);
        
        if (!is_null($limit))
            $select->limit($limit);
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }
    
    public function fetchQuestionsIntraweekDropRise($drop=true, $filter=array(), $limit=null)
    {
        $order = $drop ? new Expression('(w2.score-w1.score) ASC') : new Expression('(w2.score-w1.score) DESC');
    
        $where = array();
        $subQueryWhere1 = array();
        $subQueryWhere2 = array();
    
        $where[] = '(active=\'1\')';
        $subQueryWhere1[] = '(l.active=\'1\')';
        $subQueryWhere2[] = '(l.active=\'1\')';
    
        if (isset($filter['date_from1']) && isset($filter['date_from2']))
        {
            $subQueryWhere1[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from1'])).'\')';
            $subQueryWhere2[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from2'])).'\')';
        }
    
        if (isset($filter['date_to1']) && isset($filter['date_to2']))
        {
            $subQueryWhere1[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to1'])).'\')';
            $subQueryWhere2[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to2'])).'\')';
        }
    
        if (isset($filter['organization']))
        {
            $where[] = '(q.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere1[] = '(l.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere2[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        $selectWeek1 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('score'=>new Expression('ROUND(SUM((la.answer*100)/(fq.answers-1))/COUNT(DISTINCT(l.id)),2)')))
        ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array('id_question'))
        ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q'=>'questions'), 'la.id_question=q.id', array())
        ->where(!empty($subQueryWhere1) ? implode(' AND ', $subQueryWhere1) : 1)
        ->group('q.id');
        
        $selectWeek2 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('score'=>new Expression('ROUND(SUM((la.answer*100)/(fq.answers-1))/COUNT(DISTINCT(l.id)),2)')))
        ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array('id_question'))
        ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q'=>'questions'), 'la.id_question=q.id', array())
        ->where(!empty($subQueryWhere2) ? implode(' AND ', $subQueryWhere2) : 1)
        ->group('q.id');
    
        $select = $sql->select()
        ->from(array('q' => 'questions'))
        ->columns(array('question'=>'name','score'=>new Expression('(w2.score-w1.score)')))
        ->join(array('w1' => $selectWeek1), 'q.id=w1.id_question', array('week1_score'=>'score'))
        ->join(array('w2' => $selectWeek2), 'q.id=w2.id_question', array('week2_score'=>'score'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1);
    
        if (!is_null($order))
            $select->order($order);
    
        if (!is_null($limit))
            $select->limit($limit);
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
    public function fetchScoreToTarget($filter=array())
    {
        $where = array();
        
        $where[] = '(l.active=\'1\')';
        $where[] = '(p.active=\'1\')';
        $where[] = '(DATE(l.created) BETWEEN DATE_FORMAT(NOW() ,\'%Y-%m-01\') AND NOW())';
        
        if (isset($filter['organization']))
        {
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array(
            'id_project',
            'mtd_samples'=>new Expression('COUNT(DISTINCT l.id)'),
            'mtd_score'=>new Expression('ROUND(AVG(l.score), 2)'),
            'remaining_days'=>new Expression('DAY(NOW()) current_day, DATEDIFF(LAST_DAY(NOW()),DATE(NOW()))'),
            'projected_future_samples'=>new Expression('ROUND(((COUNT(DISTINCT l.id) / DAY(NOW())) * DATEDIFF(LAST_DAY(NOW()),DATE(NOW()))),2)'),
            'projected_total_samples'=>new Expression('ROUND(((COUNT(DISTINCT l.id) / DAY(NOW())) * DATEDIFF(LAST_DAY(NOW()),DATE(NOW()))) + COUNT(DISTINCT l.id),2)'),
            'target_score'=>new Expression('ROUND(p.min_performance_required + (((p.min_performance_required*ROUND(((COUNT(DISTINCT l.id) / DAY(NOW())) * DATEDIFF(LAST_DAY(NOW()),DATE(NOW()))) + COUNT(DISTINCT l.id),2))-(ROUND(((COUNT(DISTINCT l.id) / DAY(NOW())) * DATEDIFF(LAST_DAY(NOW()),DATE(NOW()))) + COUNT(DISTINCT l.id),2)*ROUND(AVG(l.score), 2)))/ROUND(((COUNT(DISTINCT l.id) / DAY(NOW())) * DATEDIFF(LAST_DAY(NOW()),DATE(NOW()))),2)),2)')
        ))
        ->join(array('p'=>'projects'), 'l.id_project=p.id', array('project'=>'name','min_performance_required'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->group('l.id_project')
        ->order('p.name');
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $results->buffer();
        return $results;
    } 
}