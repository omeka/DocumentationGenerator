<?php

/**
 * PMJ bonus hack to use the CommentParser from sphpdox to parse out some basics
 * for Omeka global.php.
 *
 *
 */
use TokenReflection\Broker;

error_reporting(E_ALL);
ini_set('display_errors', 1);
$path = realpath(__DIR__ . '/../../..');
define('DOCGENERATOR_DIR', $path );
define('SPHPDOX_DIR', __DIR__);
define('PATH_TO_OMEKA_GLOBALS', '/var/www/html/Omeka/application/libraries/globals.php' );
define('PATH_TO_DOCUMENTATION_GLOBALS', "/var/www/html/Documentation/source/Reference/libraries/globals/");

//require_once '/var/www/Omeka/bootstrap.php';
//require_once(PATH_TO_OMEKA_GLOBALS);
require_once(DOCGENERATOR_DIR . '/vendor/sphpdox/sphpdox/lib/Sphpdox/CommentParser.php');
require_once(DOCGENERATOR_DIR . '/vendor/sphpdox/sphpdox/lib/Sphpdox/Element/Element.php');
require_once(DOCGENERATOR_DIR . '/vendor/sphpdox/sphpdox/lib/Sphpdox/Element/MethodElement.php');
require_once(DOCGENERATOR_DIR . '/vendor/autoload.php');
class FunctionElement extends Sphpdox\Element\MethodElement
{
    public $package = null;
    public $annotations = null;

    public function __construct($functionReflection)
    {
        $this->reflection = $functionReflection;
        $this->annotations = $this->getParser()->getAnnotations();
    }

    public function getPackage()
    {
        $packageArray = $this->getParser()->getAnnotationsByName('package');
        $exploded = explode(' ', $packageArray[0]);
        $package = $exploded[1];
        $package = str_replace("Omeka\\", '', $package);
        return $package;
    }

    public function getShortDescription()
    {
        return $this->getParser()->getShortDescription();
    }

    public function getAnnotationsByName($name)
    {
        return $this->getParser()->getAnnotationsByName($name);
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

    public function __construct($reflection) {

        $this->reflection = $reflection;
        $functionName = $this->getFunctionName();
        $file = PATH_TO_DOCUMENTATION_GLOBALS . $this->getFunctionName() . ".rst";
        $this->buildSubFiles();
        $rst = $this->buildFile();
        file_put_contents($file, $rst);
        //PMJ hack for packages. lamely saving a second copy of the same file so the indices
        //are easier to make
        $package = $this->getPackage();
        if($package)  {



            //PMJ build the info for package links
            //arrays of package name and references to where in the documentation the
            //actual file is.
            //a separate script will need to read the array once all the
            //documentation has been built and from that build the packages
            //directory with correct :doc: references back to the file


            $package = str_replace('\\', '/', $package);
            $serializedMap = file_get_contents(SPHPDOX_DIR .  '/serializedPackagesMap.txt');

            $packagesMap = unserialize($serializedMap);
            $packagesMap['Function'] = array();
            $packagesMap[$package][] = array('name' => $functionName,
                    'path' => '/Reference/libraries/globals/' . $functionName
                    );

            file_put_contents(SPHPDOX_DIR . '/serializedPackagesMap.txt', serialize($packagesMap));



            $packagePath = str_replace("\\", "/", $package);
            $path = "/var/www/html/Documentation/source/Reference/packages/$packagePath";
            if(!is_dir($path)) {
                mkdir($path, 0777 ,true);
            }
        }
    }

    public function buildFile()
    {
        $functionName = $this->getFunctionName();
        if($functionName == '__') {
            $functionName = '__ (double underscore)';
        }
        $template = "";
        $rstObject = $this->getRest();
        //phpdomain collides function ids and header ids
        //so for globals documnetation put in a hack label
        //to be different, and we'll just have to remember to use
        //:ref: instead of :php:func for globals in documentation

        $label = "f" . str_replace('_', '', $functionName);

        $template .= ".. _$label:\n\n";

        $description = $rstObject->getShortDescription();
        $functionAndDescription = "``$functionName``" . ' — ' . $description;
        $headingLength = strlen($functionAndDescription);
        $headingBar = "";
        for($i = 0; $i < $headingLength; $i++) {
            $headingBar .= "#";
        }
        $template .= "$headingBar\n";
        $template .=  "$functionAndDescription\n";
        $template .= "$headingBar\n\n";

        $sinceArray = $rstObject->getAnnotationsByName('since');
        if (!empty($sinceArray)) {
            $since = $sinceArray[0];
            $exploded = explode(' ', $since);
            $since = $exploded[1];
            $template .= ".. versionadded:: $since\n\n";
        }

        $package = $this->getPackage();

        if($package) {
            $exploded = explode('\\', $package);
            $packagePart =  array_pop($exploded);
            $packageText = "$packagePart-related functions";
            $packagePath = str_replace("\\", "/", $package);
            $template .= ":doc:`$packageText </Reference/packages/$packagePath/index>`";
            $template .= "\n\n";
        } else {
            echo "\nNo package: $functionName \n";
        }
        $template .= $this->getSummary() . "\n\n";
        $template .= $rstObject . "\n\n";
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
        $rst .= ".. include:: /Reference/libraries/globals/summary/" . $this->getFunctionName() . ".rst";
        return $rst;
    }

    public function getSeeAlso()
    {
            $rst = "********\nSee Also\n********\n\n";
            $rst .= ".. include:: /Reference/libraries/globals/see_also/" . $this->getFunctionName() . ".rst";
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
        $rst .= ".. include:: /Reference/libraries/globals/usage/" . $this->getFunctionName() . ".rst";
        return $rst;
    }

    public function getExamples()
    {
        $rst = "********\nExamples\n********\n\n";
        $rst .= ".. include:: /Reference/libraries/globals/examples/" . $this->getFunctionName() . ".rst";
        return $rst;
    }

    public function getPackage()
    {
        $functionEl = $this->getRest();
        $package = $functionEl->getPackage();
        return $package;

    }

}
$broker = new \TokenReflection\Broker(new \TokenReflection\Broker\Backend\Memory());
$file = $broker->processFile(PATH_TO_OMEKA_GLOBALS, true);
$functions = $broker->getFunctions();
foreach($functions as $function) {
   try {
       $fcn = new OmekaGlobalsDocumentor($function);
   } catch (Exception $e) {
       echo $e->getMessage();
   }
}

