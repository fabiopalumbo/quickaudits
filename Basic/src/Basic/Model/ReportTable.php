<?php
namespace Basic\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Authentication\AuthenticationService;

class ReportTable
{
    /**
     * 
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $dbAdapter;

    /**
     * 
     * @param \Zend\Db\Adapter\Adapter
     */
    public function __construct(Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    }
    
    /**
     * 
     * @return \Zend\Db\Sql\Select
     */
    public function getCurrentUserProjectsSelect() {
        
        $auth = new AuthenticationService();
        
        $sql = new \Zend\Db\Sql\Sql($this->dbAdapter);
        
        return $sql->select()
        ->from(array('p'=>'projects'))
        ->join(array('up'=>'users_projects'), 'p.id=up.id_project', array())
        ->where(array('up.id_user'=>$auth->getIdentity()->id,'up.active'=>1))
        ->group('p.id');        
    }

    /*
    *
    *   Consulta específica para el formulario 309.
    *   Se toman las respuestas de las preguntas 3250, 3251, 3252 y 3253
    */
    public function fetchCustomerSatisfaction($filter = [])
    {
        $where = ['la.id_question IN (3275,3276,3277,3278,3279)'];
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }

        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';

        if (isset($filter['organization']))
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';

        //$groupby[] = 'l.id'; 

        $sql = new \Zend\Db\Sql\Sql($this->dbAdapter);

        $select = $sql->select()
        ->from(array('l' => 'listenings'))
        ->columns(array('id_project','created'=>new Expression('DATE(l.created)'),
            'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
            'month'=>new Expression("MONTH(l.created)")
        ))
        ->join(array('p'=>$this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name','min_performance_required'))
        ->join(['la' => 'listenings_answers'], 'la.id_listening = l.id', [
            'id_question',
            'score' => new Expression('SUM(answer)') 
        ])
        ->join(['q'=>'questions'], 'la.id_question = q.id', ['question' => 'name'])
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(($order?:'la.id_question ASC'))
        ->group(['la.id_question']);
        if (!is_null($groupby))
            $select1->group($groupby);

       
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $results->buffer();
        return $results;        

    }




    /*
    *
    *   Consulta específica para el formulario 309.
    *   Se toman las respuestas de las preguntas 3250, 3251, 3252 y 3253
    */
    public function fetchNESMonthScore($filter = [])
    {
        $where = ['la.id_question IN (3250,3251,3252,3253)'];
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $where[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }

        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';

        if (isset($filter['organization']))
            $where[] = '(l.id_organization = \''.$filter['organization'].'\')';

        $groupby[] = 'l.id'; 

        $sql = new \Zend\Db\Sql\Sql($this->dbAdapter);

        $select1 = $sql->select()
        ->from(array('l' => 'listenings'))
        ->columns(array('id_project','created'=>new Expression('DATE(l.created)'),
            'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
            'month'=>new Expression("MONTH(l.created)")
        ))
        ->join(array('p'=>$this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name','min_performance_required'))
        ->join(['la' => 'listenings_answers'], 'la.id_listening = l.id', ['total' => new Expression('SUM(CONVERT(free_answer, SIGNED INTEGER))')])
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(($order?:'l.id DESC'));
        if (!is_null($groupby))
            $select1->group($groupby);

        $select = $sql->select()
        ->from(['s'=>$select1])
        ->columns(['id_project', 'created', 'cases' => new Expression('SUM(samples)'), 'month', 'project', 'score'=>new Expression("CASE WHEN total<=4 THEN 'Bajo' WHEN total<=8 THEN 'Medio bajo' WHEN total <= 10 THEN 'Medio típico' ELSE 'Alto/Medio alto' END")])
        ->group('score');
        
        
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $results->buffer();
        return $results;        

    }



    /**
     * 
     * @param string $paginated
     * @param unknown $filter
     * @param string $order
     * @param string $groupby
     * @return \Zend\Paginator\Paginator|\Zend\Db\ResultSet\ResultSet
     */
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
        
        if (isset($filter['date']))
            $where[] = '(DATE(l.created) = \''.date('Y-m-d',strtotime($filter['date'])).'\')';
        
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
        
        if (isset($filter['subject']))
        {
            $where[] = '(l.id_subject = \''.$filter['subject'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $where[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $where[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            if (is_array($filter['agent']))
                $where[] = '(l.id_agent IN ('.implode(',', $filter['agent']).'))';
            else
                $where[] = '(l.id_agent = \''.$filter['agent'].'\')';
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
        ->columns(array('id_project','id_channel',/*'id_agent'*/'id_subject','id_qa_agent','id_language','id_form','date','created'=>new Expression('DATE(l.created)'),
            'score'=>new Expression('ROUND(AVG(score),2)'),'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
            'week'=> new Expression("WEEKOFYEAR(l.created)"),'month'=>new Expression("MONTH(l.created)"),
            'total_time'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)), 2)'),
            'total_hours'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)) / 60, 2)'),
        ))
        
        ->join(array('p'=>$this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name','min_performance_required'))
        ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
        ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'),'left')
        ->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'),'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(($order?:'l.id DESC'));
        
//print_r( $sql->getSqlStringForSqlObject($select));die;

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


    public function fetchWeeksGroupScoreAverage($filter = array())
    {
        $where = array();
        $subQueryWhere = array();

        $subQueryWhere[] = "q.type IN ('closed', 'binary', 'inverted')";

        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['subject']))
        {
            $subQueryWhere[] = '(l.id_subject = \''.$filter['subject'].'\')';
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
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent']) . '))';
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
            ->from(array('p' => $this->getCurrentUserProjectsSelect()))
            //->columns(array('score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, fq.answers - 1))) / COUNT(DISTINCT(l.id)) ,2)')))
            ->columns(array(
//                'score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, IF (fq.answers = 2, fq.answers - 1,fq.answers)))) / COUNT(DISTINCT(l.id)) ,2)')))
                'score'=>new Expression('ROUND(SUM(IF (la.answer<0, 0, (la.answer * IF ( l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) )/(IF (l.score = 0 AND qg.is_fatal = 1,1,IF (fq.answers = 2, fq.answers - 1, fq.answers))))) / COUNT(DISTINCT(l.id)) ,2)')))
            ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('week'=>new Expression('WEEKOFYEAR(l.created)')))
            ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q' => 'questions'), 'la.id_question=q.id', array())
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( array('qg.id',new Expression('WEEKOFYEAR(l.created)')));
        

        $select = $sql->select()
            ->from(array('w' => $selectWeeks))
            ->columns(array('week'))
            ->join(array('qg' => 'questions_groups'), new Expression('1'), array('id_group'=>'id','question_group'=>'name'))
            ->join(array('scores'=>$selectScores), 'w.week=scores.week AND qg.id=scores.id_group', array('score'), 'left')
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order(array('qg.order ASC','qg.id ASC','w.week ASC'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results;
    }

    public function fetchScoreTotal($filter=array(),$groupby=array())
    {
        $where = array();
        $subQueryWhere = array("q.type IN ('closed', 'binary', 'inverted')");

        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $subQueryWhere[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['subject']))
        {
            $subQueryWhere[] = '(l.id_subject = \''.$filter['subject'].'\')';
        }
        
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent']) . '))';
        }
        
        // group by options
        $group = array();
        
        if (in_array('id_group', $groupby))
            $group[] = 'qg.id';
        
        if (in_array('id_question', $groupby))
            $group[] = 'q.id';
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
            ->from(array('p' => $this->getCurrentUserProjectsSelect()))
//             ->columns(array('score'=>new Expression('ROUND( SUM( (la.answer * 100 ) / (fq.answers - 1) ) / COUNT(l.id) ,2)')))

            ->columns(
                    array(
//                        'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                        'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0, IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
                    )
                )
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
        
        if (isset($filter['subject']))
        {
            $where[] = '(l.id_subject = \''.$filter['subject'].'\')';
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
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent']) . '))';
        }
        
        $sql = new Sql($this->dbAdapter);
        
        $select = $sql->select()
                        ->from(array('p' => $this->getCurrentUserProjectsSelect()))
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
    
        if (isset($filter['subject']))
        {
            $subQueryWhere[] = '(l.id_subject = \''.$filter['subject'].'\')';
        }
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
        
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent']) . '))';
        }

        $subQueryWhere[] = "q.type IN ('binary', 'closed', 'inverted')";
        
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
            ->from(array('p' => $this->getCurrentUserProjectsSelect()))
//             ->columns(array('score'=>new Expression('ROUND( SUM((la.answer * 100 ) / (fq.answers - 1) ) / COUNT(DISTINCT(l.id)) ,2)')))
            ->columns(
                array(
//                    'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                    'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0,  IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
                )
            )
            ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('week'=>new Expression('WEEKOFYEAR(l.created)')))
            ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q' => 'questions'), 'la.id_question=q.id', array('id_question'=>'id'))
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array())
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( array('q.id',new Expression('WEEKOFYEAR(l.created)')));
        
        $select = $sql->select()
        ->from(array('w' => $selectWeeks))
        ->columns(array('week'))
        ->join(array('q' => 'questions'), new Expression('1'), array('id_question'=>'id','question'=>'name'))
        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id','question_group'=>'name'))
        ->join(array('scores'=>$selectScores), 'w.week=scores.week AND q.id=scores.id_question', array('score'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('qg.order ASC','qg.id ASC','q.name ASC','w.week ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
//         print"<pre>";print_r($selectString);print"</pre>";die;
        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
    public function fetchDailyQuestionScoreAverage($filter = array())
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
    
        if (isset($filter['subject']))
        {
            $subQueryWhere[] = '(l.id_subject = \''.$filter['subject'].'\')';
        }
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
    
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent'] ) . '))';
        }
    
        //$subQueryWhere[] = "q.type IN ('closed', 'bynary', 'inverted')";

        // condition to select ONLY questions answered by the agent
        // if this condition if removed, then it will return all questions no matter if the question belongs or not to the agent
        $where[] = 'q.id IN (SELECT la.id_question FROM listenings l INNER JOIN listenings_answers la ON l.id=la.id_listening WHERE '.implode(' AND ', $subQueryWhere).')';
    
        $sql = new Sql($this->dbAdapter);
    
        // select weeks from date range
        $selectDates = '(SELECT DATE(A.DATE) AS created
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH)) AND DATE(NOW())
                        GROUP BY DATE(A.DATE))';
    
        $selectScores = $sql->select()
        ->from(array('p' => $this->getCurrentUserProjectsSelect()))
        ->columns(
            array(
                //'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0,IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
            )
        )
        ->join(array('l' => 'listenings'), 'p.id=l.id_project', array('created'=>new Expression('DATE(l.created)')))
        ->join(array('la' => 'listenings_answers'), 'l.id=la.id_listening', array())
        ->join(array('fq' => 'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q' => 'questions'), 'la.id_question=q.id', array('id_question'=>'id'))
        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array())
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
        ->group( array('q.id',new Expression('DATE(l.created)')));
                 
        $select = $sql->select()
        ->from(array('d' => $selectDates))
        ->columns(array('created'))
        ->join(array('q' => 'questions'), new Expression('1'), array('id_question'=>'id','question'=>'name'))
        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id','question_group'=>'name'))
        ->join(array('scores'=>$selectScores), 'd.created=scores.created AND q.id=scores.id_question', array('score'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('qg.order ASC','q.name ASC','d.created ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
    
        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }

    public function fetchQuestionsScoreAverage($filter=array(), $order=null, $limit=null)
    {
        $where = array();
        
        $where[] = '(l.score>0)';
        $where[] = "(q.type IN ('closed', 'binary', 'inverted'))";
        
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
        
        if (isset($filter['project']))
        {
            $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $where[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
        
        $sql = new Sql($this->dbAdapter);

        // RIGHT WAY TO CALCULATE SCORE PER QUESTION!!!
        $select = $sql->select()
            ->from(array('l'=>'listenings'))
//             ->columns(array('score'=>new Expression('ROUND(SUM((la.answer*100)/(fq.answers-1))/COUNT(DISTINCT(l.id)),2)')))
            ->columns(
                array(
                    //'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                    'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0,IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
                )
            )
            ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q'=>'questions'), 'la.id_question=q.id', array('question'=>'name','id_question'=>'id'))
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->group(array('q.id'))
            ->order(!is_null($order)?$order:array(new Expression('q.name ASC')));
        
        if (!is_null($limit))
            $select->limit($limit);
        
        $selectString = $sql->getSqlStringForSqlObject($select);

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

        if (isset($filter['subject']))
        {
            $subQueryWhere[] = '(l.id_subject = \''.$filter['subject'].'\')';
        }
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
    
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(p.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent']) . '))';
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
/*    
        $selectScores = $sql->select()
            ->from(array('l' => 'listenings'))
            ->columns(array('id_project','id_channel','id_subject','id_qa_agent','id_language','id_form','date','created'=>new Expression('DATE(l.created)'),
                            'score'=>new Expression('ROUND(AVG(score),2)'),'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
                            'week'=>new Expression("WEEKOFYEAR(l.created)"),'month'=>new Expression("MONTH(l.created)"),
                            'total_time'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)), 2)'),
                            'total_hours'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)) / 60, 2)'),
            ))
            ->join(array('p' => $this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name'))
            ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
            ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'),'left')
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( new Expression('WEEKOFYEAR(l.created)'));
*/

        $selectScores = $sql->select()
            ->from(['l' => 'listenings'])
            ->columns([
                'id_project',
                'id_channel',
                'id_subject',
                'id_qa_agent',
                'id_language',
                'id_form',
                'date',
                'created'=>new Expression('DATE(l.created)'),
                //'score'=>new Expression('ROUND(AVG(score),2)'),
                'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
                'week'=>new Expression("WEEKOFYEAR(l.created)"),
                'month'=>new Expression("MONTH(l.created)"),
                'total_time'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)), 2)'),
                'total_hours'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)) / 60, 2)'),
            ])
            ->join(['lgs' => 'listenings_group_scores'], 'l.id = lgs.id_listening', ['score'=>new Expression('SUM(lgs.score)')])
            ->join(['p' => $this->getCurrentUserProjectsSelect()], 'l.id_project=p.id', ['project'=>'name'])
            ->join(['c' => 'channels'], 'l.id_channel=c.id', ['channel'=>'name'])
            ->join(['u1' => 'users'], 'l.id_qa_agent=u1.id', ['qa_agent'=>'name'],'left')
            ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
            ->group( new Expression('WEEKOFYEAR(l.created)'));
/*
SELECT `l`.`id_project` AS `id_project`, AVG(`lgs`.`score`) AS `score`, (SUM(lgs.score)/SUM(IF(lgs.weight>0,1,0))) * MIN(IF(lgs.weight>0,lgs.weight,999))/100 AS `score` FROM `listenings` AS `l` INNER JOIN `listenings_group_scores` AS `lgs` ON `l`.`id` = `lgs`.`id_listening` WHERE (l.id_project = '210') AND (DATE(l.created) >= '2018-03-01') AND (DATE(l.created) <= '2018-03-19') AND (l.active = '1') AND (l.id_organization = '53') GROUP BY `l`.`id_project`, `lgs`.`id_queston_group`
*/

        $select = $sql->select()
        ->from(array('w' => $selectWeeks))
        ->columns(array('week'))
        ->join(array('scores'=>$selectScores), 'w.week=scores.week', array('scores.score','samples'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('w.week ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);
    
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $results->buffer();
        
        return $results;
    }
    
    public function fetchDailyScoreAverage($filter = array())
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
    
//         if (isset($filter['agent']))
//         {
//             $subQueryWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
//         }

        if (isset($filter['subject']))
        {
            $subQueryWhere[] = '(l.id_subject = \''.$filter['subject'].'\')';
        }
    
        if (isset($filter['date_interval_custom']))
        {
            $subQueryWhere[] = '('.$filter['date_interval_custom'].')';
        }
    
        if (isset($filter['organization']))
        {
            $subQueryWhere[] = '(p.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
                $subQueryWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
            else
                $subQueryWhere[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $subQueryWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $subQueryWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $subQueryWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $subQueryWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere[] = '(l.id_agent IN (' . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent'] ) . '))';
        }

        $sql = new Sql($this->dbAdapter);
    
        // select weeks from date range
        $selectDates = '(SELECT DATE(A.DATE) AS created
                        FROM (
                            SELECT CURDATE() - INTERVAL (A.A + (10 * B.A) + (100 * C.A)) DAY AS DATE
                    		FROM (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS A
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS B
                            CROSS JOIN (SELECT 0 AS A UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS C
                        ) A
                        WHERE DATE(A.DATE) BETWEEN DATE(DATE_ADD(NOW(),INTERVAL -1 MONTH)) AND DATE(NOW())
                        GROUP BY DATE(A.DATE))';
    
        $selectScores = $sql->select()
        ->from(array('l' => 'listenings'))
        ->columns(array('id_project','id_channel',/*'id_agent'*/'id_subject','id_qa_agent','id_language','id_form','date','created'=>new Expression('DATE(l.created)'),
            'score'=>new Expression('ROUND(AVG(score),2)'),'samples'=>new Expression('COUNT(DISTINCT(l.id))'),
            'created'=>new Expression("DATE(l.created)"),'month'=>new Expression("MONTH(l.created)"),
            'total_time'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)), 2)'),
            'total_hours'=>new Expression('ROUND((SUM(l.time_recording_minutes) + ROUND(SUM(l.time_recording_seconds) / 60, 2)) / 60, 2)'),
        ))
        ->join(array('p' => $this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name'))
        ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
        ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'),'left')
        ->where(!empty($subQueryWhere) ? implode(' AND ', $subQueryWhere) : 1)
        ->group( new Expression('DATE(l.created)'));
         
        $select = $sql->select()
        ->from(array('d' => $selectDates))
        ->columns(array('created'))
        ->join(array('scores'=>$selectScores), 'd.created=scores.created', array('score','samples'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('d.created ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $selectString = str_replace('`(', '(', $selectString);
        $selectString = str_replace(')`', ')', $selectString);

        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $results->buffer();
    
        return $results;
    }

    public function fetchQuestionsIntraweekDropRise($drop=true, $filter=array(), $limit=null)
    {
        $order = $drop ? new Expression('(w2.score-w1.score) ASC') : new Expression('(w2.score-w1.score) DESC');
    
        $where = array();
        $subQueryWhere1 = array('la.free_answer');
        $subQueryWhere2 = array();
    
        $where[] = '(active=\'1\')';
        $subQueryWhere1[] = '(l.active=\'1\')';
        $subQueryWhere2[] = '(l.active=\'1\')';

        $subQueryWhere1[] = "(q.type IN ('closed', 'binary', 'inverted'))";
        $subQueryWhere2[] = "(q.type IN ('closed', 'binary', 'inverted'))";
    
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
        
        if (isset($filter['project']))
        {
            $subQueryWhere1[] = '(l.id_project = \''.$filter['project'].'\')';
            $subQueryWhere2[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere1[] = '(l.id_agent = \''.$filter['agent'].'\')';
            $subQueryWhere2[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        $selectWeek1 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(
                array(
//                    'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                    'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0,IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
                )
            )
        ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array('id_question'))
        ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q'=>'questions'), 'la.id_question=q.id', array())
        ->where(!empty($subQueryWhere1) ? implode(' AND ', $subQueryWhere1) : 1)
        ->group('q.id');
        
        $selectWeek2 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(
            array(
//                'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0, IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
            )
        )
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
    
    /**
     * 
     * @param array $filter
     * @return \Zend\Db\ResultSet\ResultSet
     */
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
        
        if (isset($filter['project']))
        {
            $where[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['agent'])) {
            $where[] = "(l.id_agent = '" . $filter['agent'] . "')";
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
        ->join(array('p'=>$this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name','min_performance_required'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->group('l.id_project')
        ->order('p.name');
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $results->buffer();
        
        return $results;
    }

    /**
     * 
     * @param array $filter
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchProjectsGroupsOverview($filter=array())
    {
        $where = array();
        $projectsGroupsWhere = array();
        $questionsGroupsWhere = array();
        
        if (isset($filter['project']))
        {
            if (is_array($filter['project']))
            {
                $where[] = '(p.id IN ('.implode(',', $filter['project']).'))';
                $projectsGroupsWhere[] = '(l.id_project IN ('.implode(',', $filter['project']).'))';
                $questionsGroupsWhere[] = '(p.id IN ('.implode(',', $filter['project']).'))';
            }
            else
            {
                $where[] = '(p.id = \''.$filter['project'].'\')';
                $projectsGroupsWhere[] = '(l.id_project = \''.$filter['project'].'\')';
                $questionsGroupsWhere[] = '(p.id = \''.$filter['project'].'\')';
            }
                
        }
    
        if (isset($filter['channel']))
        {
            if (is_array($filter['channel']))
                $projectsGroupsWhere[] = '(l.id_channel IN ('.implode(',', $filter['channel']).'))';
            else
                $projectsGroupsWhere[] = '(l.id_channel = \''.$filter['channel'].'\')';
        }
    
        if (isset($filter['date_from']))
            $projectsGroupsWhere[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
    
        if (isset($filter['date_to']))
            $projectsGroupsWhere[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
    
        if (isset($filter['active']))
            $projectsGroupsWhere[] = '(l.active = \''.$filter['active'].'\')';
    
        if (isset($filter['organization']))
        {
            $where[] = '(p.id_organization = \''.$filter['organization'].'\')';
            $projectsGroupsWhere[] = '(l.id_organization = \''.$filter['organization'].'\')';
            $questionsGroupsWhere[] = '(p.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['qa_agent']))
        {
            if (is_array($filter['qa_agent']))
                $projectsGroupsWhere[] = '(l.id_qa_agent IN ('.implode(',', $filter['qa_agent']).'))';
            else
                $projectsGroupsWhere[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            if (is_array($filter['agent']))
                $projectsGroupsWhere[] = '(l.id_agent IN ('.implode(',', $filter['agent']).'))';
            else
                $projectsGroupsWhere[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        $sql = new \Zend\Db\Sql\Sql($this->dbAdapter);
/*        
        // select project groups scores
        $projectsGroupsSelect_old = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(array('id_project','score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, IF (fq.answers = 2, fq.answers - 1,fq.answers)))) / COUNT(DISTINCT(l.id)) ,2)')))
        ->columns(array('id_project','score'=>new Expression('ROUND( SUM(IF(la.answer<0,0,(la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, IF (fq.answers = 2, fq.answers - 1,fq.answers))))) / COUNT(DISTINCT(l.id)) ,2)')))
        ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array())
        ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q'=>'questions'), 'la.id_question=q.id', array())
        ->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
        ->where(!empty($projectsGroupsWhere) ? implode(' AND ', $projectsGroupsWhere) : 1)
        ->group(array('l.id_project','qg.id'));
*/

        $projectsGroupsSelect = $sql->select()
        ->from(['l'=>'listenings'])
        ->columns(['id_project'])
        ->join(['lgs' => 'listenings_group_scores'], 'l.id = lgs.id_listening', ['id_group' => 'id_question_group', 'score' => new Expression('SUM(lgs.score) / SUM(IF(lgs.weight>0,1,0))')])
        ->where(!empty($projectsGroupsWhere) ? implode(' AND ', $projectsGroupsWhere) : 1)
        ->group(array('l.id_project','lgs.id_question_group'));

        //Verificamos si hay totales por grupo, usando la tabla nueva
        $selectString = $sql->getSqlStringForSqlObject($projectsGroupsSelect);
        
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        if(count($results->toArray())==0){
            //Si no hay resultados, usamos el sistema anterior
            $projectsGroupsSelect = $sql->select()
            ->from(array('l'=>'listenings'))
            ->columns(array('id_project','score'=>new Expression('ROUND( SUM((la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, IF (fq.answers = 2, fq.answers - 1,fq.answers)))) / COUNT(DISTINCT(l.id)) ,2)')))
            ->columns(array('id_project','score'=>new Expression('ROUND( SUM(IF(la.answer<0,0,(la.answer * IF (l.score = 0 AND qg.is_fatal = 1, 100, fq.weight_percentage) ) / (IF (l.score = 0 AND qg.is_fatal = 1, 1, IF (fq.answers = 2, fq.answers - 1,fq.answers))))) / COUNT(DISTINCT(l.id)) ,2)')))
            ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array())
            ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
            ->join(array('q'=>'questions'), 'la.id_question=q.id', array())
            ->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('id_group'=>'id'))
            ->where(!empty($projectsGroupsWhere) ? implode(' AND ', $projectsGroupsWhere) : 1)
            ->group(array('l.id_project','qg.id'));
        }

        // select organization question groups
        $questionGroupsSelect = $sql->select()
        ->from(array('p' => $this->getCurrentUserProjectsSelect()))
        ->columns(array())
        ->join(array('pc'=>'projects_channels'), 'p.id=pc.id_project', array())
        ->join(array('fq'=>'forms_questions'), 'pc.id_form=fq.id_form', array())
        ->join(array('q'=>'questions'), 'fq.id_question=q.id', array())
        ->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('id','name'))
        ->where(!empty($questionsGroupsWhere) ? implode(' AND ', $questionsGroupsWhere) : 1)
        ->group('qg.id');
        
        $select = $sql->select()
        ->from(array('p'=>$this->getCurrentUserProjectsSelect()))
        ->columns(array('id_project'=>'id','project'=>'name'))
        ->join(array('qg'=>$questionGroupsSelect), new Expression('1'), array('id_group'=>'id','question_group'=>'name'))
        ->join(array('pg'=>$projectsGroupsSelect), 'p.id=pg.id_project AND qg.id=pg.id_group', array('score'), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(array('p.name'=>'ASC','qg.name'=>'ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results      = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        return $results;
    }
    
    public function fetchProjectsIntraweekDropRise($drop=true, $filter=array(), $limit=null)
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
            $where[] = '(p.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere1[] = '(l.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere2[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere1[] = '(l.id_agent = \''.$filter['agent'].'\')';
            $subQueryWhere2[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        $selectWeek1 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(
            array(
                'score'=>new Expression('ROUND(AVG(l.score),2)'),
                'id_project'
            )
        )
        ->where(!empty($subQueryWhere1) ? implode(' AND ', $subQueryWhere1) : 1)
        ->group('l.id_project');
    
        $selectWeek2 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(
            array(
                'score'=>new Expression('ROUND(AVG(l.score),2)'),
                'id_project'
            )
        )
        ->where(!empty($subQueryWhere2) ? implode(' AND ', $subQueryWhere2) : 1)
        ->group('l.id_project');
    
        $select = $sql->select()
        ->from(array('p' => $this->getCurrentUserProjectsSelect()))
        ->columns(array('project'=>'name','score'=>new Expression('(w2.score-w1.score)')))
        ->join(array('w1' => $selectWeek1), 'p.id=w1.id_project', array('week1_score'=>'score'))
        ->join(array('w2' => $selectWeek2), 'p.id=w2.id_project', array('week2_score'=>'score'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1);
    
        if (!is_null($order))
            $select->order($order);
    
        if (!is_null($limit))
            $select->limit($limit);
    
        $selectString = $sql->getSqlStringForSqlObject($select);
   
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
    public function fetchQuestionGroupsIntraweekDropRise($drop=true, $filter=array(), $limit=null)
    {
        $order = $drop ? new Expression('(w2.score-w1.score) ASC') : new Expression('(w2.score-w1.score) DESC');
    
        $where = array();
        $subQueryWhere1 = array();
        $subQueryWhere2 = array();
    
        $where[] = '(active=\'1\')';
        $subQueryWhere1[] = '(l.active=\'1\')';
        $subQueryWhere2[] = '(l.active=\'1\')';
        $subQueryWhere1[] = "q.type IN ('closed', 'binary', 'inverted')";
        $subQueryWhere2[] = "q.type IN ('closed', 'binary', 'inverted')";
    
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
            $where[] = '(qg.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere1[] = '(l.id_organization = \''.$filter['organization'].'\')';
            $subQueryWhere2[] = '(l.id_organization = \''.$filter['organization'].'\')';
        }
    
        if (isset($filter['project']))
        {
            $subQueryWhere1[] = '(l.id_project = \''.$filter['project'].'\')';
            $subQueryWhere2[] = '(l.id_project = \''.$filter['project'].'\')';
        }
        
        if (isset($filter['agent']))
        {
            $subQueryWhere1[] = '(l.id_agent = \''.$filter['agent'].'\')';
            $subQueryWhere2[] = '(l.id_agent = \''.$filter['agent'].'\')';
        }
    
        $sql = new Sql($this->dbAdapter);
    
        $selectWeek1 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(
            array(
//                'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0, IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
            )
        )
        ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array())
        ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q'=>'questions'), 'la.id_question=q.id', array('id_group'))
        ->where(!empty($subQueryWhere1) ? implode(' AND ', $subQueryWhere1) : 1)
        ->group('q.id_group');
    
        $selectWeek2 = $sql->select()
        ->from(array('l'=>'listenings'))
        ->columns(
            array(
//                'score'=>new Expression('ROUND(AVG(IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers)))*100,2)')
                'score'=>new Expression('ROUND(AVG(IF(la.answer<0,0, IF (fq.answers=2, (la.answer/(fq.answers-1)), (la.answer/fq.answers))))*100,2)')
            )
        )
        ->join(array('la'=>'listenings_answers'), 'l.id=la.id_listening', array())
        ->join(array('fq'=>'forms_questions'), 'l.id_form=fq.id_form AND la.id_question=fq.id_question', array())
        ->join(array('q'=>'questions'), 'la.id_question=q.id', array('id_group'))
        ->where(!empty($subQueryWhere2) ? implode(' AND ', $subQueryWhere2) : 1)
        ->group('q.id_group');
    
        $select = $sql->select()
        ->from(array('qg' => 'questions_groups'))
        ->columns(array('question_group'=>'name','score'=>new Expression('(w2.score-w1.score)')))
        ->join(array('w1' => $selectWeek1), 'qg.id=w1.id_group', array('week1_score'=>'score'))
        ->join(array('w2' => $selectWeek2), 'qg.id=w2.id_group', array('week2_score'=>'score'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1);
    
        if (!is_null($order))
            $select->order($order);
    
        if (!is_null($limit))
            $select->limit($limit);
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $results = $this->dbAdapter->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
}