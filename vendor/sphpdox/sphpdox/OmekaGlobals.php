<?php

/**
 * PMJ bonus hack to use the CommentParser from sphpdox to parse out some basics
 * for Omeka global.php. 
 * 
 * 
 */

define('PATH_TO_OMEKA_GLOBALS', '/var/www/html/Omeka/application/libraries/globals.php' );
define('PATH_TO_SPHDOX', '/var/www/html/sphpdox/' );
define('PATH_TO_DOCUMENTATION_GLOBALS', "/var/www/html/Documentation/source/Reference/libraries/globals/");

require_once '/var/www/html/Omeka/paths.php';
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
        $parameters = $this->reflection->getParameters();

        $this->functionName = $functionName;
        $file = PATH_TO_DOCUMENTATION_GLOBALS . $this->functionName . ".rst";
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
        $template .= $this->getRest() . "\n\n";
        $template .= $this->getUsage() . "\n\n";
        $template .= $this->getExamples() . "\n\n"; 
        return $template;
    }
    
    public function getFunctionName()
    {
        return $this->reflection->getName();
    }
    
    public function getRest()
    {
        $fcnElement = new FunctionElement($this->reflection);
        return $fcnElement;
    }
    
    public function getUsage()
    {
        $rst = "*****\nUsage\n*****\n\n";
        if ($usage = $this->getUsageFile()) {
            $rst .= $usage;
        }
        return $rst;        
    }
    
    public function getExamples()
    {
        $rst = "********\nExamples\n********\n\n";
        if ($usage = $this->getExamplesFile()) {
            $rst .= $examples;
        }
        return $rst;        
        
    }
    
    public function getUsageFile()
    {
        $file = PATH_TO_DOCUMENTATION_GLOBALS . "usage/" . $this->functionName . ".rst";
        if(file_exists($file)) {
            return file_get_contents($file);
        }
        return false;        
    }
    
    public function getExamplesFile()
    {
        $file = PATH_TO_DOCUMENTATION_GLOBALS . "examples/" . $this->functionName . ".rst";
        if(file_exists($file)) {
            return file_get_contents($file);
        }
        return false;
    }
}
$allFunctions = $arr = get_defined_functions();
$globals = $allFunctions['user'];
//$globals = array('is_allowed');
//$globals = array('insert_item');
foreach($globals as $function) {
    echo $function;
   $fcn = new OmekaGlobalsDocumentor($function);
}

