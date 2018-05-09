<?php
namespace Application\Helper;

use Zend\ServiceManager\ServiceManager;

class ExcelReports {
    
    protected $serviceManager;
    protected $translator;
    
    public function __construct(ServiceManager $sm)
    {
        $this->serviceManager = $sm;
    }

    /**
     * 
     * @return \Zend\Mvc\I18n\Translator
     */
    public function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->serviceManager->get('translator');
        }
        return $this->translator;
    }

    public function export($objPHPExcel, $filename)
    {
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
         
        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    

    /**
     * 
     * @param \Zend\Db\ResultSet\ResultSet $data
     */
    public function exportListenings($data)
     {
         // Create new PHPExcel object
         $objPHPExcel = new \PHPExcel();
     
         $objActiveSheet = $objPHPExcel->getActiveSheet();
     
         $col=0;
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Project'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Channel'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('QA Agent'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Agent'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Score'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Created'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Status'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Comments'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Room'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Name'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Company'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('City'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Country'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Recommend'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Arrival'));
         $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Departure'));
         $objActiveSheet->setCellValueByColumnAndRow($col,1,$this->getTranslator()->translate('Email'));
         $colTop = $col;
     
         $colFirst = \PHPExcel_Cell::stringFromColumnIndex(0);
         $colLast = \PHPExcel_Cell::stringFromColumnIndex($colTop);
     
         // set header styles
         $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
         ->getFill()
         ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
         ->getStartColor()
         ->setRGB('84BF4F');
         $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
         ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
     
         $row=2;
         foreach ($data as $item)
         {
             $col=0;
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->project);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->channel);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->qa_agent);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->agent);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->score);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->created);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->active ? $this->getTranslator()->translate('Enabled') : $this->getTranslator()->translate('Disabled'));
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->comments);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_room);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_name);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_company);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_city);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_recommend);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_country);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_arrival);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_departure);
             $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->pnorte_email);
     
             if ($item->score < $item->min_performance_required)
             {
                 $objActiveSheet->getStyle($colFirst.$row.':'.$colLast.$row)
                 ->getFill()
                 ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                 ->getStartColor()
                 ->setRGB('F2DEDE');
             }
     
             $row++;
         }
     
         // Set Auto Size to all columns
         for ($i=0;$i<=$colTop;$i++)
         {
             $objActiveSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
         }
     
        $this->export($objPHPExcel, $this->getTranslator()->translate('Listenings'));        
    }
    
    public function exportGlobalWeeklyProgress($data)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
         
        $objActiveSheet = $objPHPExcel->getActiveSheet();
         
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Week'));
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Score'));
        $objActiveSheet->setCellValueByColumnAndRow($col,1,$this->getTranslator()->translate('Samples'));
        $colTop = $col;
         
        $colFirst = \PHPExcel_Cell::stringFromColumnIndex(0);
        $colLast = \PHPExcel_Cell::stringFromColumnIndex($colTop);
         
        // set header styles
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
         
        $row=2;
        foreach ($data as $item)
        {
            $col=0;
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->week);
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->score);
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->samples);
             
            if ($item->score < $item->min_performance_required)
            {
                $objActiveSheet->getStyle($colFirst.$row.':'.$colLast.$row)
                ->getFill()
                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('F2DEDE');
            }
             
            $row++;
        }
         
        // Set Auto Size to all columns
        for ($i=0;$i<=$colTop;$i++)
        {
        $objActiveSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
         
        $this->export($objPHPExcel, $this->getTranslator()->translate('Global Weekly Progress'));
    }
    
    public function exportGlobalDailyProgress($data)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
         
        $objActiveSheet = $objPHPExcel->getActiveSheet();
         
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Date'));
        foreach ($data['dailyGrandTotal'] as $item)
        {
            $objActiveSheet->setCellValueByColumnAndRow($col++,1,$item->created);
        }
        $colTop = $col-1;
        
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,2,$this->getTranslator()->translate('Samples'));
        foreach ($data['dailyGrandTotal'] as $item)
        {
            $objActiveSheet->setCellValueByColumnAndRow($col++,2,number_format($item->samples));
        }
         
        $colFirst = \PHPExcel_Cell::stringFromColumnIndex(0);
        $colLast = \PHPExcel_Cell::stringFromColumnIndex($colTop);
         
        // set header styles
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'2')
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'2')
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        
        $row=3;
        $newDailyQuestionScoreAvg = $data['dailyQuestionScoreAvg']->current();
        while ($newDailyQuestionScoreAvg) {
            $oldGroup = $newDailyQuestionScoreAvg->id_group;
            while ($newDailyQuestionScoreAvg && $newDailyQuestionScoreAvg->id_group==$oldGroup) {   
                $oldQuestion = $newDailyQuestionScoreAvg->id_question;
                $col=0;
                $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$newDailyQuestionScoreAvg->question);
                while ($newDailyQuestionScoreAvg && $newDailyQuestionScoreAvg->id_group==$oldGroup && $newDailyQuestionScoreAvg->id_question==$oldQuestion) {
                    $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($newDailyQuestionScoreAvg->score,2));
                    $data['dailyQuestionScoreAvg']->next();
                    $newDailyQuestionScoreAvg = $data['dailyQuestionScoreAvg']->current();
                }
                $row++;
            }         
        }
         
        // Set Auto Size to all columns
        for ($i=0;$i<=$colTop;$i++)
        {
            $objActiveSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
         
        $this->export($objPHPExcel, $this->getTranslator()->translate($data['subtitle']));
    }
    
    public function exportQuestionWeeklyProgress($data)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
         
        $objActiveSheet = $objPHPExcel->getActiveSheet();
        
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Weeks'));
        foreach ($data['weeksGrandTotal'] as $item)
        {
            $objActiveSheet->setCellValueByColumnAndRow($col++,1,$item->week);
        }
        $objActiveSheet->setCellValueByColumnAndRow($col,1,$this->getTranslator()->translate('Grand Total'));
        $colTop = $col;
        
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,2,$this->getTranslator()->translate('Samples'));
        $totalSamples=0;
        foreach ($data['weeksGrandTotal'] as $item)
        {
            $totalSamples+=$item->samples;
            $objActiveSheet->setCellValueByColumnAndRow($col++,2,number_format($item->samples,0));
        }
        $objActiveSheet->setCellValueByColumnAndRow($col,2,number_format($totalSamples,0));
        
        $colFirst = \PHPExcel_Cell::stringFromColumnIndex(0);
        $colLast = \PHPExcel_Cell::stringFromColumnIndex($colTop);
         
        // set header styles
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'2')
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'2')
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        
        $row=3;
        $newWeekGroupScoreAvg = $data['weekGroupScoreAvg']->current();
        while ($newWeekGroupScoreAvg) {
            $oldWeekGroupScoreAvg = $newWeekGroupScoreAvg;
            $col=0;            
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$newWeekGroupScoreAvg->question_group);
            while ($newWeekGroupScoreAvg && $newWeekGroupScoreAvg->id_group==$oldWeekGroupScoreAvg->id_group) {
                $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($newWeekGroupScoreAvg->score,2));
                $data['weekGroupScoreAvg']->next();
                $newWeekGroupScoreAvg = $data['weekGroupScoreAvg']->current();
            }
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($data['groupScoreTotal'][$oldWeekGroupScoreAvg->id_group],2));
            $row++;
        }
        
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$this->getTranslator()->translate('Total Score'));
        $totalSamples=0;
        foreach ($data['weeksGrandTotal'] as $item)
        {
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($item->score,2));
        }
        $objActiveSheet->setCellValueByColumnAndRow($col,$row++,number_format($data['groupScoreGrandTotal']->current()->score,2));
        
        $objActiveSheet->getStyle($colFirst.($row-1).':'.$colLast.($row-1))
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.($row-1).':'.$colLast.($row-1))
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');        
        
        $newWeekQuestionScoreAvg = $data['weekQuestionScoreAvg']->current();
        while ($newWeekQuestionScoreAvg) {
            $oldGroup = $newWeekQuestionScoreAvg->id_group;
            $col=0;
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row++,$newWeekQuestionScoreAvg->question_group);
            while ($newWeekQuestionScoreAvg && $newWeekQuestionScoreAvg->id_group==$oldGroup) {
                $oldQuestion = $newWeekQuestionScoreAvg->id_question;
                $col=0;
                $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$newWeekQuestionScoreAvg->question);
                while ($newWeekQuestionScoreAvg && $newWeekQuestionScoreAvg->id_group==$oldGroup && $newWeekQuestionScoreAvg->id_question==$oldQuestion) {
                    $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($newWeekQuestionScoreAvg->score,2));
                    $data['weekQuestionScoreAvg']->next();
                    $newWeekQuestionScoreAvg = $data['weekQuestionScoreAvg']->current();
                }
                $objActiveSheet->setCellValueByColumnAndRow($col,$row++,number_format($data['questionScoreTotal'][$oldQuestion],2));
            }
        }
        
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$this->getTranslator()->translate('Total Score'));
        $totalSamples=0;
        foreach ($data['weeksGrandTotal'] as $item)
        {
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($item->score,2));
        }
        $objActiveSheet->setCellValueByColumnAndRow($col,$row++,number_format($data['groupScoreGrandTotal']->current()->score,2));
        
        $objActiveSheet->getStyle($colFirst.($row-1).':'.$colLast.($row-1))
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.($row-1).':'.$colLast.($row-1))
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        
        // Set Auto Size to all columns
        for ($i=0;$i<=$colTop;$i++)
        {
            $objActiveSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        
        $this->export($objPHPExcel, $this->getTranslator()->translate($data['subtitle']));
    }
    
    public function exportSampleDailyOverview($data)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
         
        $objActiveSheet = $objPHPExcel->getActiveSheet();
         
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Date'));
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Score'));
        $objActiveSheet->setCellValueByColumnAndRow($col,1,$this->getTranslator()->translate('Samples'));
        $colTop = $col;
         
        $colFirst = \PHPExcel_Cell::stringFromColumnIndex(0);
        $colLast = \PHPExcel_Cell::stringFromColumnIndex($colTop);
         
        // set header styles
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
         
        $row=2;
        foreach ($data as $item)
        {
            $col=0;
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->created);
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->score);
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$item->samples);
             
            if ($item->score < $item->min_performance_required)
            {
                $objActiveSheet->getStyle($colFirst.$row.':'.$colLast.$row)
                ->getFill()
                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('F2DEDE');
            }
             
            $row++;
        }
         
        // Set Auto Size to all columns
        for ($i=0;$i<=$colTop;$i++)
        {
        $objActiveSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
         
        $this->export($objPHPExcel, $this->getTranslator()->translate('Sample Daily Overview'));
    }
    
    public function exportProjectsGroupsOverview($projectsGroupsScores, $questionGroups, $projectsGroupsTotals)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
         
        $objActiveSheet = $objPHPExcel->getActiveSheet();
         
        $col=0;
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Project / Group'));
        foreach ($questionGroups as $item)
        {
            $objActiveSheet->setCellValueByColumnAndRow($col++,1,$item->name);
        }
        $objActiveSheet->setCellValueByColumnAndRow($col++,1,$this->getTranslator()->translate('Samples'));
        $objActiveSheet->setCellValueByColumnAndRow($col,1,$this->getTranslator()->translate('Score'));        
        $colTop = $col;
         
        $colFirst = \PHPExcel_Cell::stringFromColumnIndex(0);
        $colLast = \PHPExcel_Cell::stringFromColumnIndex($colTop);
         
        // set header styles
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('84BF4F');
        $objActiveSheet->getStyle($colFirst.'1:'.$colLast.'1')
        ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        
        $row=2;
        $newProjectsGroupsScores = $projectsGroupsScores->current();
        while ($newProjectsGroupsScores) {
            $oldProject = $newProjectsGroupsScores->id_project;
            $col=0;
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,$newProjectsGroupsScores->project);
            while ($newProjectsGroupsScores && $newProjectsGroupsScores->id_project==$oldProject) {
                $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($newProjectsGroupsScores->score,2));
                $projectsGroupsScores->next();
                $newProjectsGroupsScores = $projectsGroupsScores->current();
            }
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($projectsGroupsTotals[$oldProject]['samples']));
            $objActiveSheet->setCellValueByColumnAndRow($col++,$row,number_format($projectsGroupsTotals[$oldProject]['score'],2));
            $row++;
        }
         
        // Set Auto Size to all columns
        for ($i=0;$i<=$colTop;$i++)
        {
            $objActiveSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
         
        $this->export($objPHPExcel, $this->getTranslator()->translate('Projects Overview'));
    }
}