<?php

namespace Sphpdox\Element;

use TokenReflection\ReflectionNamespace;
use Symfony\Component\Console\Output\OutputInterface;
use \DirectoryIterator;

class NamespaceElement extends Element
{
    /**
     * @var ReflectionNamespace
     */
    protected $reflection;

    protected $titles = array('`', ':', '\'', '"', '~', '^', '_', '*', '+', '#', '<', '>');

    public function __construct(ReflectionNamespace $namespace)
    {
        parent::__construct($namespace);
    }

    public function getPath()
    {
        //PMJ hack: ignore 'no-namespace'
        $name = $this->reflection->getName();
        
        if($name == 'no-namespace') {
            $name = '';
        }        
        return str_replace('\\', DIRECTORY_SEPARATOR, $name);
    }

    protected function getSubElements()
    {
        $elements = array_merge(
            $this->getConstants()
        );

        return $elements;
    }

    protected function getConstants()
    {
        return array_map(function ($v) {
            return new ConstantElement($v);
        }, $this->reflection->getConstants());
    }


    protected function getClasses()
    {
        return array_map(function ($v) {
            return new ClassElement($v);
        }, $this->reflection->getClasses());
    }


    public function __toString()
    {
        $string = '';

        foreach ($this->getSubElements() as $element) {
            $e = $element->__toString();
            if ($e) {
                $string .= $this->indent($e, 4);
                $string .= "\n\n";
            }
        }

        return $string;
    }

    /**
     * Ensures the build directory is in place
     *
     * @param string $path
     * @param OutputInterface $output
     * @return string The directory
     */
    protected function ensureBuildDir($path, OutputInterface $output)
    {
        $parts = explode(DIRECTORY_SEPARATOR, $this->getPath());

        foreach ($parts as $part) {
            if (!$part) continue;

            $path .= DIRECTORY_SEPARATOR . $part;

            if (!file_exists($path)) {
                $output->writeln(sprintf('<info>Creating namespace build directory: <comment>%s</comment></info>', $path));
                mkdir($path);
            }
        }

        return $path;
    }

    /**
     * Builds the class information
     *
     * @param unknown $basedir
     * @param OutputInterface $output
     */
    public function buildClasses($basedir, OutputInterface $output)
    {
        $target = $this->ensureBuildDir($basedir, $output);
        
        
        $serializedMap = file_get_contents(SPHPDOX_DIR . 'serializedPackagesMap.txt');
        $packagesMap = unserialize($serializedMap);
        if(!is_array($packagesMap)) {
            $packagesMap = array();
            //File package gets missed, because there is nothing with just package 'File'
            
            $packagesMap['File'] = array();
            //Same with Controller/ActionHelper
            $packagesMap['Controller/ActionHelper'] = array();
        }
        
        foreach ($this->getClasses() as $element) {
            //new PMJ hackery
            $explodedTarget = explode('/', trim($target, '/'));
            $className = $element->getReflection()->getName();
            $explodedClassName = explode('_', $className);
            $penultimateClassNameIndex = count($explodedClassName) -2; 
            $penultimateTargetIndex = count($explodedTarget) -1;
            
            $classNameMatch = strtolower($explodedClassName[$penultimateClassNameIndex]);
            $targetMatch = strtolower($explodedTarget[$penultimateTargetIndex]);
            $targetMatches = array($targetMatch, trim($targetMatch, 's'));
            //to get the right tree structure for /libraries/Omeka/
            
            if(empty($classNameMatch)) {
                //let controllers directory get processed
                
            } else {
                if ( ! in_array($classNameMatch, $targetMatches) )
                {
                    $output->writeln('skip this level ' . $target);
                    continue;
                }
            }
            
            $element->build($target, $output);
            
            //PMJ build the info for package links
            //arrays of package name and references to where in the documentation the
            //actual file is.
            //a separate script will need to read the array once all the 
            //documentation has been built and from that build the packages
            //directory with correct :doc: references back to the file
            
            /*
            $package = $element->getPackage();
            $package = str_replace('\\', '/', $package);
            
            $packagesMap[$package][] = array('name' => $element->getName(), 
                                              'path' => str_replace('/var/www/Documentation/source', '',  $element->file)
                                              );
            file_put_contents('/var/www/DocumentationGenerator/vendor/sphpdox/sphpdox/serializedPackagesMap.txt', serialize($packagesMap));
            */
            
            
        }
    }

    /**
     * Builds the index file
     */
    public function buildIndex($basedir, OutputInterface $output, array $options = array())
    {
        $target = $this->ensureBuildDir($basedir, $output);

        $built_iterator = new DirectoryIterator($target);
        $index = $target . DIRECTORY_SEPARATOR . 'index.rst';

        $title = str_replace('\\', '\\\\', $this->reflection->getName());
        if (isset($options['title'])) {
            $title = $options['title'];
        }

        $depth = substr_count($this->reflection->getName(), '\\');

        $template = str_repeat($this->titles[$depth], strlen($title)) . "\n";
        $template .= $title . "\n";
        $template .= str_repeat($this->titles[$depth], strlen($title)) . "\n\n";
        //PMJ remove the namespace because it is 'no-namespace'
        //$template .= $this->getNamespaceElement();

        $template .= ".. toctree::\n\n";

        foreach ($built_iterator as $file) {
            if ($file->isDot()) continue;
            if ($file->isFile() && !$file->getExtension() == 'rst') continue;
            if ($file->isFile() && substr($file->getBaseName(), 0, 1) == '.') continue;
            if ($file->getBaseName() == 'index.rst') continue;

            $template .= '   ' . pathinfo($file->getPathName(), PATHINFO_FILENAME);

            if ($file->isDir()) {
                $template .= '/index';
            }

            $template .= "\n";
        }

        file_put_contents($index, $template);
    }

    public function getNamespaceElement()
    {
        return '.. php:namespace: '
            . str_replace('\\', '\\\\', $this->reflection->getName())
            . "\n\n";
    }
}