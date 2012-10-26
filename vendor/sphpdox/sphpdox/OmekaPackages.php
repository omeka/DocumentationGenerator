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


function sortByClassName($a, $b) {
    
    
}

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

    $namedRefs = flatten($fileRefs);
    
    $index = "";

    $packageNameLength = strlen($packageName);
    $headingBar = "";
    for($i = 0; $i < $packageNameLength; $i++) {
        $headingBar .= "#";
    }
    
    $index = "$headingBar\n";
    $index .= $packageName . "\n";
    $index .= "$headingBar\n\n";
    $index .= ".. toctree::\n";
    $index .= "    :maxdepth: 1\n\n";
    
    foreach($namedRefs as $name=>$path) {
        $index .= "   $path\n";
    }

    $path = "/var/www/html/Documentation/source/Reference/packages/$packageDir";
    if(!is_dir($path)) {
        mkdir($path, 0777 ,true);
    }
    
    file_put_contents($path . '/index.rst', $index);
}

/*

//PMJ hack for packages. lamely saving a second copy of the same file so the indices
//are easier to make
$parser = $this->getParser();
$package = $parser->getPackage();
if($package)  {
    $packagePath = str_replace("\\", "/", $package);
    $escapedPackage = str_replace("\\", "\\\\", $package);
    $path = "/var/www/html/Documentation/source/Reference/packages/$packagePath";
    if(!is_dir($path)) {
        mkdir($path, 0777 ,true);
    }
    $file = $path . '/' . $realPath;
    file_put_contents($file, $this->__toString());

    file_put_contents($path . '/index.rst', $index);
}

*/