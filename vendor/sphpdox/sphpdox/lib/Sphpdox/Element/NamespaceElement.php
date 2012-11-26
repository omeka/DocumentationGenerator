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
     * @param string $basedir
     * @param OutputInterface $output
     */
    public function build($basedir, OutputInterface $output, array $options = array())
    {
        $path = $basedir;
        $parts = explode(DIRECTORY_SEPARATOR, $this->getPath());

        $target = $basedir . DIRECTORY_SEPARATOR . $this->getPath();

        foreach ($parts as $part) {
            if (!$part) continue;

            $path .= DIRECTORY_SEPARATOR . $part;

            if (!file_exists($path)) {
                $output->writeln(sprintf('<info>Creating namespace build directory: <comment>%s</comment></info>', $path));
                mkdir($path);
            }
        }
        $serializedMap = file_get_contents('/var/www/html/sphpdox/vendor/sphpdox/sphpdox/serializedPackagesMap.txt');
        
        $packagesMap = unserialize($serializedMap);
        if(!is_array($packagesMap)) {
            $packagesMap = array();
            //File package gets missed, because there is nothing with just package 'File'
            
            $packagesMap['File'] = array();
            //Same with Controller/ActionHelper
            $packagesMap['Controller/ActionHelper'] = array();
        }
        foreach ($this->getClasses() as $element) { 
            
            //PMJ hackery
            //the classes for some reason include both the classes in the directory, and the classes below
            //this uses the name of the directory we're processing and the class name
            //to skip the classes from the subdirectory
            
            $explodedTarget = explode('/', trim($target, '/'));
            $lastTargetPart = $explodedTarget[count($explodedTarget) -1];            
            $class = $element->getPath();
            $classParts = explode('_', $class);

            //since the target is system-specific, count backward from then end of the explodedTarget
            //controllers and helpers
            $etCount = count($explodedTarget);
            
            //skip the helpers caught up in the controllers processing
            if($explodedTarget[$etCount - 1] == 'controllers') {
            
                if(isset($classParts[3]) && $classParts[3] == 'Helper') {
                    continue;
                } else {
                    $element->build($target, $output);
                    continue;
                }
            }
            //controller helpers
            if($explodedTarget[$etCount - 2] == 'controllers' ) {
                $element->build($target, $output);
                continue;
            
            }
           
            //this works for the models directory
            if(isset($classParts[count($classParts) -2] )) {
                $lastClassPart = $classParts[count($classParts) -2];
                if($lastClassPart != $lastTargetPart) {
                    //   echo "\n$target\n";
                    //   print_r($classParts);
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
            $package = $element->getPackage();
            $package = str_replace('\\', '/', $package);
            
            $packagesMap[$package][] = array('name' => $element->getName(), 
                                              'path' => str_replace('/var/www/html/Documentation/source', '',  $element->file)
                                              );
            file_put_contents('/var/www/html/sphpdox/vendor/sphpdox/sphpdox/serializedPackagesMap.txt', serialize($packagesMap));
            
        }

        
        $built_iterator = new DirectoryIterator($target);

        $index = $target . DIRECTORY_SEPARATOR . 'index.rst';

        $title = str_replace('\\', '\\\\', $this->reflection->getName());
        if (isset($options['title'])) {
            $title = $options['title'];
        }

        $depth = substr_count($this->reflection->getName(), '\\');
        
        $template = str_repeat($this->titles[$depth], strlen($title)) . "\n";
        
        if($title == 'no-namespace') {
            $template .= "Omeka \n";//PMJ hack to not show 'no-namespace' 
        } else {
            $template .= ucfirst($title) . "\n"; //PMJ hack use the directory passed in to doc.sh
        }
        
        $template .= str_repeat($this->titles[$depth], strlen($title)) . "\n\n";
        $template .= ".. toctree::\n\n";
        $files = array();
        foreach($built_iterator as $file) {
            $files[$file->getBaseName()] = $file;
        }
        ksort($files);
        $subDirectoriesTemplate = "\n\n.. toctree::\n\n";
        foreach ($files as $name=>$file) {

/* commenting out original code
            if ($file->isDot()) continue;
            if ($file->isFile() && !$file->getExtension() == 'rst') continue;
            if ($file->isFile() && substr($file->getBaseName(), 0, 1) == '.') continue;
            if ($file->getBaseName() == 'index.rst') continue;
*/
            //above ifs worked with DirectoryIterator, but to sort, had to drop into an array,
            //and that apparently borks all those methods

            if($name == '.') continue;
            if($name == '..') continue;
            if($name == 'index.rst') continue;

            $exploded = explode('.', $name);

            //index.rst is only autogenerated at the top level, so I'll drop the subdirectories to the end
            //and write the index.rst myself. Since it isn't autogenerated, it won't get clobbered on 
            //updates, but new files and classes will have to be added manually.
            
            if(isset($exploded[1])) {
                //it's .rst
                $template .= "    {$exploded[0]}\n";
            } else {

                //it's a directory, so keep track of it to drop it in at the end
                $subDirectoriesTemplate .= "    {$exploded[0]}/index\n";                
            }
            
                        
        }
        
        $template .= $subDirectoriesTemplate;
        file_put_contents($index, $template);
    }
}