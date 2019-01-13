<?php

namespace Techsemicolon;

use Techsemicolon\LatextException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Latex
{
	/**
	 * Stub view file path
	 * @var string
	 */
    private $stubPath;

    /**
     * Data to pass to the stub
     * 
     * @var array
     */
    private $data;

    /**
     * Rendered tex file
     * 
     * @var string
     */
    private $renderedTex;

    /**
     * Construct the instance
     * 
     * @param string $stubPath
     */
    public function __construct($stubPath){

    	$this->stubPath = $stubPath;
    }

    /**
     * Set the with data
     * 
     * @param  array $data
     * 
     * @return void
     */
    public function with($data){

    	$this->data = $data;

    	return $this;
    }

    /**
     * Render the stub with data
     * 
     * @return string
     */
    public function render(){

    	$this->renderedTex = view($this->stubPath, $this->data)->render();

    	return $this->renderedTex;
    }

    /**
     * Save generated PDF
     * 
     * @param  string $location
     * 
     * @return boolean
     */
    public function savePdf($location)
    {
    	$this->render();

    	$pdfPath = $this->generate();

    	return \File::move($pdfPath, $location);
    }

    /**
     * Download file as a response
     * 
     * @return Illuminate\Http\Response
     */
    public function download()
    {	
    	$this->render();

    	$pdfPath = $this->generate();

    	return \Response::file($pdfPath);
    }

    /**
     * Generate the PDF
     * 
     * @return string
     */
    private function generate(){

    	$fileName = str_random(10);
        $tmpfname = tempnam(sys_get_temp_dir(), $fileName);
        $tmpDir = sys_get_temp_dir();
        chmod($tmpfname, 0755);

        \File::put($tmpfname, $this->renderedTex);

        // $process = new Process("pdflatex $tmpfname");
        $process = new Process("pdflatex -output-directory $tmpDir $tmpfname");
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
        	
        	$this->parseError($tmpfname, $process);
        }

        return $tmpfname.'.pdf';
    }

    /**
     * Throw error from log gile
     * 
     * @param  string $tmpfname
     * 
     * @throws \LatextException
     */
    private function parseError($tmpfname, $process){

    	$logFile = $tmpfname.'.log';

    	if(!\File::exists($logFile)){

    		throw new \LatextException($process->getOutput());
    	}

    	$error = \File::get($tmpfname.'.log');
    	throw new \LatextException($error);
    }
}