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
        $shortName = $this->reflection->getShortName();
        $exploded = explode('_', $shortName);
        return array_pop($exploded) . '.rst';
        return $this->reflection->getShortName() . '.rst';
    }

    /**
     * @param string $basedir
     * @param OutputInterface $output
     */
    public function build($basedir, OutputInterface $output)
    {
        $file = $basedir . DIRECTORY_SEPARATOR . $this->getPath();
        $this->file = $file;
        file_put_contents($file, $this->__toString());
    }

    /**
     * @see Sphpdox\Element.Element::__toString()
     */
    public function __toString()
    {
        $name = $this->reflection->getName();

        $title = str_replace('\\', '\\\\', $name);

        $string = str_repeat('-', strlen($title)) . "\n";
        $string .= $title . "\n";
        $string .= str_repeat('-', strlen($title)) . "\n\n";
        //$string .= $this->getNamespaceElement();
    
        /* PMJ hacks to add @package info */
        $package = $this->getPackage();
        
        $escapedPackage = str_replace("\\", "\\\\", $package);
        $packageNameLength = strlen($escapedPackage);
        $packagePath = str_replace("\\", "/", $package);
        
        if($package) {
            $string .= "Package: :doc:`$escapedPackage </Reference/packages/$packagePath/index>`";
            $string .= "\n\n";
        }
        
        //PMJ check if interface
        if ($this->reflection->isInterface()) {
            $string .= '.. php:interface:: ' . $this->reflection->getShortName();
        } else {
            $string .= '.. php:class:: ' . $this->reflection->getShortName();    
        }
        $parent = $this->reflection->getParentClassName();
        if ($parent) {
            $string .= "\n\n";
            $string .= "extends :php:class:`$parent`"; 
        }
        
        $implements = $this->reflection->getInterfaceNames();
        if (! empty($implements)) {
            $string .= "\n";
            foreach ($implements as $interface) {
                $string .= "\nimplements :php:interface:`$interface`";
            }
        }

        $parser = $this->getParser();

        
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

        $string .= "\n\n";

        // Finally, fix some whitespace errors
        $string = preg_replace('/^\s+$/m', '', $string);
        $string = preg_replace('/ +$/m', '', $string);

        return $string;
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

    public function getNamespaceElement()
    {
        return '.. php:namespace: '
            . str_replace('\\', '\\\\', $this->reflection->getNamespaceName())
            . "\n\n";
    }
    
    public function getPackage()
    {
        $packageArray = $this->getParser()->getAnnotationsByName('package');
        if (empty($packageArray)) {
            echo $this->getName();
        }
        $exploded = explode(' ', $packageArray[0]);
        $package = $exploded[1];
        $package = str_replace("Omeka\\", '', $package);
        return $package;
    }

    //PMJ added to access the name when I build package info in NamespaceElement
    public function getName()
    {
        return $this->reflection->getName();
    }
}