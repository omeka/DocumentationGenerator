<?php

/* This script exists to create the packages directories, with indexes and
 * links to the class documentation files.
 * 
 *  doc.sh creates an array for package names and files called serializedPackagesMap.txt as
 *  it processes the classes
 *  
 *  that forms the basic data used here to build the documentation files by package
 * 
 */


function flatten($fileRefs) {
    $flattened = array();
    foreach($fileRefs as $fileRef) {
        $flattened[$fileRef['name']] = $fileRef['path'];
    }
    ksort($flattened);
    return $flattened;    
}

$serializedMap = file_get_contents('/var/www/html/sphpdox/vendor/sphpdox/sphpdox/serializedPackagesMap.txt');
$packageMap = unserialize($serializedMap);
foreach($packageMap as $packageDir=>$fileRefs) {
    
    $packageName = str_replace("/", "\\\\", $packageDir);
    $isFunctionPackage = false;
    $exploded = explode('\\', $packageName);
    if(in_array('Function', $exploded)) {
        $packagePart =  array_pop($exploded);
        if($packagePart == 'Function') {
            $packageName = "Global Functions";
        } else {
            $packageName = "$packagePart-related functions";
        }        
        $isFunctionPackage = true;
               
    }

    $namedRefs = flatten($fileRefs);
    
    $index = "";

    $packageNameLength = strlen($packageName);
    $headingBar = "";
    for($i = 0; $i < $packageNameLength; $i++) {
        $headingBar .= "#";
    }
    
    $index .= "$headingBar\n";
    $index .= $packageName . "\n";
    $index .= "$headingBar\n\n";

    $index .= "Up to :doc:`../index`\n\n";    
    $index .= ".. toctree::\n";
    if($isFunctionPackage) {
        $index .= "   :maxdepth: 1\n\n";        
    } else {
        $index .= "\n\n";
    }


    
    foreach($namedRefs as $name=>$path) {
        $index .= "   $path\n";
    }
    
    $index .= "\n.. toctree::\n";
    $index .= "   :glob:\n";
    if($isFunctionPackage) {
        $index .= "   :maxdepth: 2\n\n";
    } else {
        $index .= "\n\n";
    }
    
    $index .= "   */index\n";
    
    $path = "/var/www/html/Documentation/source/Reference/packages/$packageDir";
    if(!is_dir($path)) {
        mkdir($path, 0777 ,true);
    }

    file_put_contents($path . '/index.rst', $index);
}

$packageDirs = array_keys($packageMap);

sort($packageDirs);

$topIndex = "";

$topIndex .= "########\n";
$topIndex .= "Packages\n";
$topIndex .= "########\n\n";

$topIndex .= ".. toctree::\n";
$topIndex .= "    :maxdepth: 1\n\n";
foreach($packageDirs as $dir) {
   // $dir = str_replace("/", "\\\\", $dir);    
    $topIndex .= "    $dir/index\n";
    
}

file_put_contents("/var/www/html/Documentation/source/Reference/packages/index.rst", $topIndex);
