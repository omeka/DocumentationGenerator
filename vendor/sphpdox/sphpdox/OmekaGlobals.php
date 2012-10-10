<?php

/**
 * PMJ bonus hack to use the CommentParser from sphpdox to parse out some basics
 * for Omeka global.php. 
 * 
 * 
 */

error_reporting(~E_ALL);
define('PATH_TO_OMEKA_GLOBALS', '/var/www/html/Omeka/application/libraries/globals.php' );
define('PATH_TO_SPHDOX', '/var/www/html/sphpdox/' );
define('PATH_TO_DOCUMENTATION_GLOBALS', "/var/www/html/Documentation/source/Reference/libraries/globals/");

require_once '/var/www/html/Omeka/bootstrap.php';
require_once(PATH_TO_OMEKA_GLOBALS);
require_once(PATH_TO_SPHDOX . '/vendor/sphpdox/sphpdox/lib/Sphpdox/CommentParser.php');
require_once(PATH_TO_SPHDOX . '/vendor/sphpdox/sphpdox/lib/Sphpdox/Element/Element.php');
require_once(PATH_TO_SPHDOX . '/vendor/sphpdox/sphpdox/lib/Sphpdox/Element/MethodElement.php');

class FunctionElement extends Sphpdox\Element\MethodElement
{
    
    public function __construct($functionReflection)
    {
        $this->reflection = $functionReflection;
    }
    
    protected function getParameterInfo()
    {
        $params = array();
    
        $parameters = $this->reflection->getParameters();
        foreach ($parameters as $parameter) {
            $params[$parameter->getName()] = array(
                    'name' => $parameter->getName(),
                    'type' => null //PMJ hack
            );
    
            if ($parameter->isDefaultValueAvailable()) {
                $params[$parameter->getName()]['default'] = $parameter->getDefaultValue();
            }
        }
        
        $annotations = array_filter($this->getParser()->getAnnotations(), function ($v) {
            $e = explode(' ', $v);
            return isset($e[0]) && $e[0] == '@param';
        });

        foreach ($annotations as $parameter) {   
            $parts = explode(' ', $parameter);

            if (count($parts) < 3) {
                continue;
            }

            $type = $parts[1];
            $name = str_replace('$', '', $parts[2]);
            $comment = implode(' ', array_slice($parts, 3));
           
                  
            if (isset($params[$name])) {
                if ($params[$name]['type'] == null) {
                    $params[$name]['type'] = $type;
                }
                $params[$name]['comment'] = $comment;
            }
        }
  
        return $params;
    }    
    public function __toString()
    {
        $string = sprintf(".. php:function:: %s(%s)\n\n", $this->reflection->getName(), $this->getArguments());
    
        $parser = $this->getParser();
    
        if ($description = $parser->getDescription()) {
            $string .= $this->indent($description . "\n\n", 4, true);
        }
    
        $return = $this->getReturnValue();
    
        $annotations = array_merge(
                        $this->getParameters(),
                        $return ? array($return) : array()
        );
    
        if ($annotations) {
            $string .= $this->indent(implode("\n", $annotations), 4) . "\n";
        }
    
        return trim($string);
    }    
}



class OmekaGlobalsDocumentor {
    
    public $parser;
    public $reflection;
    public $functionName;
    
    public function __construct($functionName) {


        $reflection = new ReflectionFunction($functionName);
        $this->reflection = $reflection;
     //   $parameters = $this->reflection->getParameters();

     //   $this->functionName = $functionName;
        $file = PATH_TO_DOCUMENTATION_GLOBALS . $this->getFunctionName() . ".rst";
        echo "\n$file\n";
        $this->buildSubFiles();
        $rst = $this->buildFile();
        file_put_contents($file, $rst);

    }

    public function buildFile()
    {
        $functionName = $this->getFunctionName();
        if($functionName == '__') {
            $functionName = '__ (double underscore)';
        }        
        $template = "";
        $functionNameLength = strlen($functionName);
        $headingBar = "";
        for($i = 0; $i < $functionNameLength; $i++) {
            $headingBar .= "#";
        }
        $template .= "$headingBar\n";
        $template .=  $functionName . "\n";
        $template .= "$headingBar\n\n";
        $template .= $this->getSummary() . "\n\n";
        $template .= $this->getRest() . "\n\n";
        $template .= $this->getUsage() . "\n\n";
        $template .= $this->getExamples() . "\n\n"; 
        $template .= $this->getSeeAlso() . "\n\n";
        return $template;
    }
    
    public function buildSubFiles()
    {
        $fileName = $this->getFunctionName() . ".rst";
        $subdirs = array('examples', 'usage', 'see_also', 'summary');
        foreach($subdirs as $section) {
            if(!is_dir(PATH_TO_DOCUMENTATION_GLOBALS . "$section")) {
                mkdir(PATH_TO_DOCUMENTATION_GLOBALS . "$section");
            }
            $file = PATH_TO_DOCUMENTATION_GLOBALS . "$section/" . $this->getFunctionName() . ".rst";
            if(! file_exists($file)) {
                file_put_contents($file, '');
            }
        }
    }
    
    public function getFunctionName()
    {
        return $this->reflection->getName();
    }
    
    public function getSummary()
    {
        $rst = "*******\nSummary\n*******\n\n";
        $rst .= ".. include:: summary/" . $this->getFunctionName() . ".rst";
        return $rst;        
    }
    
    public function getSeeAlso()
    {
            $rst = "********\nSee Also\n********\n\n";
            $rst .= ".. include:: see_also/" . $this->getFunctionName() . ".rst";
            return $rst;
    }
    
    public function getRest()
    {
        $fcnElement = new FunctionElement($this->reflection);
        return $fcnElement;
    }

    
    public function getUsage()
    {
        $rst = "*****\nUsage\n*****\n\n";
        $rst .= ".. include:: usage/" . $this->getFunctionName() . ".rst";
        return $rst;        
    }
    
    public function getExamples()
    {
        $rst = "********\nExamples\n********\n\n";            
        $rst .= ".. include:: examples/" . $this->getFunctionName() . ".rst";
        return $rst;                
    }    
    
}

$allFunctions = get_defined_functions();
$globals = $allFunctions['user'];
//$globals = array('is_allowed');
//$globals = array('fire_plugin_hook');
foreach($globals as $function) {
   //echo $function;
   $fcn = new OmekaGlobalsDocumentor($function);

}

