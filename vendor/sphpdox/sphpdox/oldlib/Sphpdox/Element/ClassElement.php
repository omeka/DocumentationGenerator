<?php

namespace Sphpdox\Element;

use TokenReflection\ReflectionClass;

use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class element
 */
class ClassElement extends Element
{
    /**
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * Constructor
     *
     * @param string $classname
     * @throws InvalidArgumentException
     */
    public function __construct(ReflectionClass $reflection)
    {
        parent::__construct($reflection);
    }

    public function getPath()
    {
        return $this->reflection->getShortName() . '.rst';
    }

    public function getPackage()
    {
        //need this public so can grab the info from the NamespaceElement loop
        //through classes
        return $this->getParser()->getPackage();
    }
    
    /**
     * @param string $basedir
     * @param OutputInterface $output
     */
    public function build($basedir, OutputInterface $output)
    {
        //PMJ hacks to figure out directory structure from class name structure

        $class = $this->getPath();
        $classParts = explode('_', $class);
        $realPath = '';
        $classPartsCount = count($classParts);

        //trying bottom-up directory processing, so should only need the last part of class parts
        $realPath = $classParts[$classPartsCount - 1];
        $file = $basedir . $realPath;
        if(!is_dir($basedir)) {
            mkdir($basedir);
        }

        $this->file = $file;
        // end PMJ hacks
        file_put_contents($file, $this->__toString());

    }

    /**
     * @see Sphpdox\Element.Element::__toString()
     */
    public function __toString()
    {
        $name = $this->reflection->getName();
        $string = str_repeat('-', strlen($name)) . "\n";
        $string .= $name . "\n";
        $string .= str_repeat('-', strlen($name)) . "\n\n";
        $string .= '.. php:class:: ' . $name;
        $parser = $this->getParser();



        /* PMJ hacks to add @package info */
        $package = $parser->getPackage();
        $escapedPackage = str_replace("\\", "\\\\", $package);
        $packageNameLength = strlen($escapedPackage);
        $packagePath = str_replace("\\", "/", $package);
        
        if($package) {
            $string .= "\n\n";
            $string .= $this->indent("Package: :doc:`$escapedPackage </Reference/packages/$packagePath/index>`", 4);
        }
                
        if ($description = $parser->getDescription()) {
            $string .= "\n\n";
            $string .= $this->indent($description, 4);
        }
        
        foreach ($this->getSubElements() as $element) {
            $e = $element->__toString();            
            if ($e) {
                $string .= "\n\n";
                $string .= $this->indent($e, 4);
            }
        }
        
        return $string;
    }

    //PMJ added to access the name when I build package info in NamespaceElement
    public function getName()
    {
        return $this->reflection->getName();
    }
    
    protected function getSubElements()
    {
        $elements = array_merge(
            $this->getConstants(),
            $this->getProperties(),
            $this->getMethods()
        );

        return $elements;
    }

    protected function getConstants()
    {
        return array_map(function ($v) {
            return new ConstantElement($v);
        }, $this->reflection->getConstantReflections());
    }

    protected function getProperties()
    {
        return array_map(function ($v) {
            return new PropertyElement($v);
        }, $this->reflection->getProperties());
    }

    protected function getMethods()
    {
        return array_map(function ($v) {
            return new MethodElement($v);
        }, $this->reflection->getMethods());
    }
}