<?php

namespace moonland\phpexcel;

use yii\helpers\ArrayHelper;

/**
 * Excel Widget for generate Excel File or for load Excel File.
 * 
 * Usage
 * -----
 * 
 * Once the extension is installed, simply use it in your code by  :
 * 
 * ~~~
 * 
 * 
 * \moonland\phpexcel\Excel::widget([
 * 		'models' => $allModels,
 * 		'columns' => ['column1','column2','column3'],
 * 		//without header working, because the header will be get label from attribute label.
 * 		'header' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 * ]);
 * 
 * ~~~
 * 
 * 
 * Exporting data into an excel file.
 * 
 * ~~~
 * 
 * \moonland\phpexcel\Excel::export([
 * 		'models' => $allModels,
 * 		'columns' => ['column1','column2','column3'],
 * 		//without header working, because the header will be get label from attribute label.
 * 		'header' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 * ]);
 * 
 * ~~~
 * 
 * 
 * Import file excel and return into an array.
 * 
 * ~~~
 * 
 * $data = \moonland\phpexcel\Excel::import($fileName, $config); // $config is an optional
 * 
 * $data = \moonland\phpexcel\Excel::import($fileName, ['setFirstRecordAsKeys' => true]);
 * 
 * ~~~
 * 
 * @author Moh Khoirul Anam <moh.khoirul.anaam@gmail.com>
 * @copyright 2014
 * @since 1
 */
class Excel extends \yii\base\Widget
{
	/**
	 * @var string mode is an export mode or import mode. valid value are 'export' and 'import'.
	 */
	public $mode = 'export';
	/**
	 * @var boolean for set the export excel with multiple sheet.
	 */
	public $isMultipleSheet=false;
	/**
	 * @var array properties for set property on the excel object.
	 */
	public $properties;
	/**
	 * @var \yii\base\Model with much data.
	 */
	public $models;
	/**
	 * @var array columns data from models
	 */
	public $columns;
	/**
	 * @var array header to set the header column on first line
	 */
	public $headers;
	/**
	 * @var string name for file name to export or save.
	 */
	public $fileName;
	/**
	 * @var string save path is a directory to save the file or you can blank this to set the file as attachment.
	 */
	public $savePath;
	/**
	 * @var string format for excel to export. Valid value are 'Excel5','Excel2007','Excel2003XML','00Calc','Gnumeric'.
	 */
	public $format;
	/**
	 * @var boolean to set the title column on the first line.
	 */
	public $setFirstTitle = true;
	/**
	 * @var boolean to set the file excel to download mode.
	 */
	public $asAttachment = true;
	/**
	 * @var boolean to set the first record on excel file to a keys of array per line.
	 */
	public $setFistRecordAsKeys = true;
	
	/**
	 * Setting data from models
	 */
	public function executeColumns(&$activeSheet = null, $models, $columns = [], $headers = [])
	{
		if ($activeSheet == null) {
			$activeSheet = $this->activeSheet;
		}
		$hasHeader = false;
		$row = 1;
		$char = 26;
		foreach ($models as $model) {
			if ($this->setFirstTitle && !$hasHeader) {
				$isPlus = false;
				$colplus = 0;
				$colnum = 1;
				foreach ($columns as $key=>$column) {
					$col = '';
					if ($colnum > $char) {
						$colplus += 1;
						$colnum = 1;
						$isPlus = true;
					}
					if ($isPlus) {
						$col .= chr(64+$colplus);
					}
					$col .= chr(64+$colnum);
					if (isset($headers[$column])) {
						$activeSheet->setCellValue($col.$row,$headers[$column]);
					} else {
						$activeSheet->setCellValue($col.$row,$model->getAttributeLabel($column));
					}
					$colnum++;
				}
				$hasHeader=true;
				$row++;
			}
			$isPlus = false;
			$colplus = 0;
			$colnum = 1;
			foreach ($columns as $key=>$column) {
				$col = '';
				if ($colnum > $char) {
					$colplus++;
					$colnum = 1;
					$isPlus = true;
				}
				if ($isPlus) {
					$col .= chr(64+$colplus);
				}
				$col .= chr(64+$colnum);
				$activeSheet->setCellValue($col.$row,$model->{$column});
				$colnum++;
			}
			$row++;
		}
	}
	
	/**
	 * Setting label or keys on every record if setFirstRecordAsKeys is true.
	 * @param array $sheetData
	 * @return multitype:multitype:array
	 */
	public function executeArrayLabel($sheetData)
	{
		$keys = ArrayHelper::remove($sheetData, '1');
		
		$new_data = [];
		
		foreach ($sheetData as $values)
		{
			$new_data[] = array_combine($keys, $values);
		}
		
		return $new_data;
	}
	
	/**
	 * Setting header to download generated file xls
	 */
	public function setHeaders()
	{
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $this->getFileName() .'"');
		header('Cache-Control: max-age=0');
	}
	
	/**
	 * Getting the file name of exporting xls file
	 * @return string
	 */
	public function getFileName()
	{
		$fileName = 'exports.xls';
		if (isset($this->fileName)) {
			$fileName = $this->fileName;
			if (strpos($fileName, '.xls') === false)
				$fileName .= '.xls';
		}
		return $fileName;
	}
	
	/**
	 * Setting properties for excel file
	 * @param PHPExcel $objectExcel
	 * @param array $properties
	 */
	public function properties(&$objectExcel, $properties = [])
	{
		foreach ($properties as $key => $value)
		{
			$keyname = "set" . ucfirst($key);
			$objectExcel->getProperties()->{$keyname}($value);
		}
	}
	
	/**
	 * saving the xls file to download or to path
	 */
	public function writeFile($sheet)
	{
		if (!isset($this->format))
			$this->format = 'Excel2007';
		$objectwriter = \PHPExcel_IOFactory::createWriter($sheet, $this->format);
		$path = 'php://output';
		if (isset($this->savePath) && $this->savePath != null) {
			$path = $this->savePath . '/' . $this->getFileName();
		}
		$objectwriter->save($path);
		exit();
	}
	
	/**
	 * reading the xls file
	 */
	public function readFile($fileName)
	{
		if (!isset($this->format))
			$this->format = PHPExcel_IOFactory::identify($fileName);
		$objectreader = \PHPExcel_IOFactory::createReader($this->format);
		$objectPhpExcel = $objectreader->load($fileName);
		
		$sheetCount = $objectPhpExcel->getSheetCount();
		
		$sheetDatas = [];
		
		if ($sheetCount > 1) {
			foreach ($objectPhpExcel->getSheetNames() as $sheetIndex => $sheetName) {
				$objectPhpExcel->setActiveSheetIndexByName($sheetName);
				$sheetDatas[$sheetIndex] = $objectPhpExcel->getActiveSheet()->toArray(null, true, true, true);
				if ($this->setFistRecordAsKeys) {
					$sheetDatas[$sheetIndex] = $this->executeArrayLabel($sheetDatas[$sheetIndex]);
				}
			}
		} else {
			$sheetDatas = $objectPhpExcel->getActiveSheet()->toArray(null, true, true, true);
			if ($this->setFistRecordAsKeys) {
				$sheetDatas = $this->executeArrayLabel($sheetDatas);
			}
		}
		
		return $sheetDatas;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Widget::run()
	 */
    public function run()
    {
    	if ($this->mode == 'export') 
    	{
	    	$sheet = new \PHPExcel();
	    	
	    	if (isset($this->properties))
	    	{
	    		$this->properties($sheet, $this->properties);
	    	}
	    	
	    	if ($this->isMultipleSheet) {
	    		$index = 0;
	    		foreach ($this->models as $title => $models) {
	    			$sheet->createSheet($index);
	    			$sheet->getSheet($index)->setTitle($title);
	    			$columns = isset($this->columns[$title]) ? $this->columns[$title] : [];
	    			$headers = isset($this->headers[$title]) ? $this->headers[$title] : [];
	    			$this->executeColumns($sheet->getSheet($index), $models, $columns, $headers);
	    			$index++;
	    		}
	    	} else {
	    		$this->executeColumns($sheet->getActiveSheet(), $this->columns, $this->headers);
	    	}
	    	
	    	if ($this->asAttachment) {
	    		$this->setHeaders();
	    	}
	    	$this->writeFile($sheet);
    	} 
    	elseif ($this->mode == 'import') 
    	{
    		if (is_array($this->fileName)) {
    			$datas = [];
    			foreach ($this->fileName as $filename) {
    				$datas[] = $this->readFile($filename);
    			}
    		} else {
    			return $this->readFile($this->fileName);
    		}
    	}
    }
    
    /**
     * Exporting data into an excel file.
     * 
	 * ~~~
	 * 
	 * \moonland\phpexcel\Excel::export([
	 * 		'models' => $allModels,
	 * 		'columns' => ['column1','column2','column3'],
	 * 		//without header working, because the header will be get label from attribute label.
	 * 		'header' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
	 * ]);
	 * 
	 * ~~~
	 * 
     * @param array $config
     * @return string
     */
    public static function export($config=[])
    {
    	$config = ArrayHelper::merge(['mode' => 'export'], $config);
    	return self::widget($config);
    }
    
    /**
     * Import file excel and return into an array.
     * 
	 * ~~~
	 * 
	 * $data = \moonland\phpexcel\Excel::import($fileName, ['setFirstRecordAsKeys' => true]);
	 * 
	 * ~~~
	 * 
     * @param string $fileName to load.
     * @param array $config is a more configuration.
     * @return string
     */
    public static function import($fileName, $config=[])
    {
    	$config = ArrayHelper::merge(['mode' => 'import', 'fileName' => $fileName], $config);
    	return self::widget($config);
    }
}
