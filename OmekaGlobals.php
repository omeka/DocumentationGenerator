<?php

/**
 * PMJ bonus hack to use the CommentParser from sphpdox to parse out some basics
 * for Omeka global.php.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);


class CommentParser
{
    protected $comment;
    protected $shortDescription;
    protected $longDescription = null;
    protected $annotations = array();

    /**
     * A separately indexed array of annotations by name, for ease of use
     *
     * The @suffix is also removed
     *
     * @var array<string => array<string>>
     */
    protected $annotationsByName = array();

    /**
     * Constructor
     *
     * @param string $docblock
     */
    public function __construct($comment)
    {
        $this->comment = $comment;
        $this->parse();
    }

    /**
     * Parses the docblock
     */
    protected function parse()
    {
        $content = $this->comment;

        // Rewrite newlines
        $content = preg_replace('/\r\n/', "\n", $content);

        // Rewrite tabs
        $content = preg_replace('/\t/', '    ', $content);

        // Remove initial whitespace
        $content = preg_replace('/^\s*/m', '', $content);

        // Remove start and end comment markers
        $content = preg_replace('/\s*\/\*\*#?@?\+?\s*/m', '', $content);
        $content = preg_replace('/\s*\*\/\s*/m', '', $content);

        // Remove start of line comment markers
        $content = preg_replace('/^\* ?/m', '', $content);

        // Split the comment into parts
        $this->split($content);
    }

    protected function addAnnotation($annotation)
    {
        if ($annotation) {
            if (preg_match('/@(\w+)/', $annotation, $matches)) {
                $this->annotationsByName[$matches[1]][] = $annotation;
            }
            $this->annotations[] = $annotation;
        }
    }

    /**
     * Splits the simplified comment string into parts (annotations plus
     *     descriptions)
     *
     * @param string $content
     */
    protected function split($content)
    {
        // Pull off all annotation lines
        $continuation = false;
        $annotation = '';

        $lines = explode("\n", $content);
        $remaining = $lines;

        foreach ($lines as $i => $line) {
            if (!$line) {
                $continuation = false;
                continue;
            }

            if ($line[0] == '@') {
                $this->addAnnotation($annotation);
                $annotation = '';
                $continuation = true;
            } elseif ($continuation) {
                $annotation .= ' ';
            } else {
                continue;
            }

            $annotation .= trim($line);
            unset($remaining[$i]);
        }

        $this->addAnnotation($annotation);

        // Split remaining lines by paragrah
        $remaining = implode("\n", $remaining);
        $parts = preg_split("/(\n\n|\r\n\r\n)/", $remaining, -1, PREG_SPLIT_NO_EMPTY);

        // Into two parts
        if ($parts) {
            $first = $parts[0];
            $this->shortDescription = trim($first);

            $rest = array_slice($parts, 1);
            if ($rest) {
                $long = implode("\n\n", $rest);
                $long = preg_replace('/(\w) *\n *(\w)/', '\1 \2', $long);
                $long = trim($long);
                $this->longDescription = $long;
            }
        }
    }

    /**
     * Gets all the annotations on the method
     *
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Gets all annotations of the specified name, if there are any
     *
     * @param string $name
     * @return array<string>
     */
    public function getAnnotationsByName($name)
    {
        if (isset($this->annotationsByName[$name])) {
            return $this->annotationsByName[$name];
        }
        return array();
    }

    /**
     * Whether the comment has at least one annotation of the given name
     *
     * @param string $name
     * @return boolean
     */
    public function hasAnnotation($name)
    {
        return !empty($this->annotationsByName[$name]);
    }

    /**
     * Gets the short and long description in the comment
     *
     * The full comment if you like. If this docblock were processed,
     * the former paragraph and this one would be returned.
     *
     * @return string
     */
    public function getDescription()
    {
        $description = $this->getShortDescription();
        if ($this->hasLongDescription()) {
            $description .= "\n\n" . $this->getLongDescription();
        }
        return $description;
    }

    /**
     * Gets the short description in the comment
     *
     * That's just the first paragraph
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Gets the long description
     *
     * Some methods dont have descriptions, in which case this will be null
     *
     * @return string|null
     */
    public function getLongDescription()
    {
        return $this->longDescription;
    }

    /**
     * Whether the comment has any sort of description, long or short
     *
     * @return boolean
     */
    public function hasDescription()
    {
        return (boolean)$this->shortDescription;
    }

    /**
     * Whether the comment has a long description
     *
     * @return boolean
     */
    public function hasLongDescription()
    {
        return $this->longDescription != null;
    }
}

/**
 * Represents a code element that can be documented with PHPDoc/Sphinx
 */
abstract class Element
{
    protected $reflection;

    /**
     * Constructor
     *
     * @param Reflector $reflection
     */
    public function __construct(Reflector $reflection)
    {
        $this->reflection = $reflection;
    }

    public function getReflection()
    {
        return $this->reflection;
    }
    /**
     */
    protected function getParser()
    {
        return new CommentParser($this->reflection->getDocComment());
    }

    /**
     * Gets ReST markup for this element
     */
    abstract public function __toString();

    /**
     * Indents the given lines
     *
     * @param string $output
     * @param int $level
     */
    protected function indent($output, $spaces = 3, $rewrap = false)
    {
        if (!$output) {
            return '';
        }

        $line = 78;
        $spaces = str_pad(' ', $spaces);

        if ($rewrap) {
            $existing_indent = '';
            if (preg_match('/^( +)/', $output, $matches)) {
                $spaces .= $matches[1];
            }
            $output = preg_replace('/^ +/m', '', $output);
            $output = wordwrap($output, $line - strlen($spaces));
        }

        $output = preg_replace('/^/m', $spaces, $output);

        return $output;
    }
}

/**
 * Method element
 */
class MethodElement extends Element
{
    /**
     * Constructor
     *
     * @param Reflector $method
     */
    public function __construct(Reflector $method)
    {
        parent::__construct($method);
    }

    /**
     * Gets an array of simplified information about the parameters of this
     * method
     *
     * @return array
     */
    protected function getParameterInfo()
    {
        $params = array();

        $parameters = $this->reflection->getParameters();
        foreach ($parameters as $parameter) {
            $params[$parameter->getName()] = array(
                'name'      => $parameter->getName(),
                'hint_type' => $parameter->getType(),
                'type'      => $parameter->getType(),
                'comment'   => null
            );

            if ($parameter->isDefaultValueAvailable()) {
                $default = $parameter->getDefaultValue();
                if ($parameter->isDefaultValueConstant()) {
                    $defaultString = $parameter->getDefaultValueConstantName();
                } elseif (is_scalar($default)) {
                    $defaultString = var_export($default, true);
                } elseif (is_null($default)) {
                    $defaultString = 'null';
                } elseif (is_array($default)) {
                    $defaultString = 'array(';
                    foreach ($default as $key => $value) {
                        if ($defaultString !== 'array(') {
                            $defaultString .= ', ';
                        }
                        $defaultString .= var_export($key, true) . ' => ' . var_export($value, true);
                    }
                    $defaultString .= ')';
                } else {
                    throw new RuntimeException("oops: can't render default value: " . var_export($default, true));
                } 
                $params[$parameter->getName()]['default'] = trim($defaultString);
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

            $type = trim($parts[1]);
            $name = trim(str_replace('$', '', $parts[2]));
            $comment = trim(implode(' ', array_slice($parts, 3)));

            if (isset($params[$name])) {
                if ($params[$name]['type'] == null && $type) {
                    $params[$name]['type'] = $type;
                }
                if ($comment) {
                    $params[$name]['comment'] = $comment;
                }
            }
        }

        return $params;
    }

    /**
     * Gets the formal signature/declaration argument list ReST output
     *
     * @return string
     */
    protected function getArguments()
    {
        $strings = array();

        foreach ($this->getParameterInfo() as $name => $parameter) {
            $string = '';

            if ($parameter['hint_type']) {
                $string .= $parameter['hint_type'] . ' ';
            }

            $string .= '$' . $name;

            if (isset($parameter['default'])) {
                if ($parameter['default'] == '~~NOT RESOLVED~~') {
                    $parameter['default'] = '';
                }
                $string .= ' = ' . $parameter['default'];
            }

            $strings[] = $string;
        }

        return implode(', ', $strings);
    }

    /**
     * Gets an array of parameter information, in ReST format
     *
     * @return array
     */
    protected function getParameters()
    {
        $strings = array();

        foreach ($this->getParameterInfo() as $name => $parameter) {
            if ($parameter['type']) {
                $strings[] = ':type $' . $name . ': ' . $parameter['type'];
            }

            $string = ':param $' . $name . ':';

            if (isset($parameter['comment']) && $parameter['comment']) {
                $string .= ' ' . $parameter['comment'];
            }

             $strings[] = $string;
        }

        return $strings;
    }

    /**
     * Gets the return value ReST notation
     *
     * @return boolean|string
     */
    protected function getReturnValue()
    {
        $annotations = array_filter($this->getParser()->getAnnotations(), function ($v) {
            $e = explode(' ', $v);
            return isset($e[0]) && $e[0] == '@return';
        });
        foreach ($annotations as $parameter) {
            $parts = explode(' ', $parameter);

            if (count($parts) < 2) {
                continue;
            }

            $type = array_slice($parts, 1, 1);
            $type = $type[0];

            $comment = implode(' ', array_slice($parts, 2));

            $string = ':returns:';

            return sprintf(
                ':returns: %s%s',
                $type ?: 'unknown',
                $comment ? ' ' . $comment : ''
            );
        }

        return false;
    }

    /**
     * @see \Sphpdox\Element\Element::__toString()
     */
    public function __toString()
    {
        try {
            $arguments = $this->getArguments();
        } catch (\Exception $e) {
            $arguments = '';
        }

        $string = sprintf(".. php:method:: %s(%s)\n\n", $this->reflection->getName(), $arguments);

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

class FunctionElement extends MethodElement
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
    public $docsGlobalsPath;

    public function __construct($reflection, $docsGlobalsPath) {

        $this->reflection = $reflection;
        $this->docsGlobalsPath = $docsGlobalsPath;
        $functionName = $this->getFunctionName();
        $file = $docsGlobalsPath . '/' . $this->getFunctionName() . ".rst";
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
            if (!is_dir($this->docsGlobalsPath . "/$section")) {
                mkdir($this->docsGlobalsPath . "/$section");
            }
            $file = $this->docsGlobalsPath . "/$section/" . $fileName;
            if (!file_exists($file)) {
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

if ($argc !== 3) {
    echo "Error: must pass 2 args.\n";
    echo "php OmekaGlobals.php [path to Omeka Classic] [path to Omeka Classic docs]\n";
    exit(1);
}

$omekaPath = $argv[1];
$docsPath = $argv[2];

if (!is_dir($omekaPath) || !is_dir($docsPath)) {
    echo "Error: paths must be directories\n";
    exit(1);
}

$globalsPath = realpath($omekaPath . '/application/libraries/globals.php');
$docsGlobalsPath = realpath($docsPath . '/source/Reference/libraries/globals/');

if (!is_dir($docsGlobalsPath)) {
    echo "Error: incorrect docs path\n";
}

require_once $omekaPath . '/bootstrap.php';
require_once $globalsPath;

foreach(get_defined_functions()['user'] as $functionName) {
    $reflection = new ReflectionFunction($functionName);
    if ($reflection->getFileName() !== $globalsPath) {
        continue;
    }

    $fcn = new OmekaGlobalsDocumentor($reflection, $docsGlobalsPath);
}

