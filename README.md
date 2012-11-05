This repo contains the files used for the automatic generation of Omeka's documentation.

The full documentation also contains manually written documentation.

# The build process

The `doc.sh` script runs through all of the build process, which works roughly like this:

1. Documentation for classes is built by running through all the appropriate directories and using a super hacked and modified Sphpdox generator.

2. Documentation for global functions is also built with via the `OmekaGlobals.php` file, which relies on same Sphpdox generator and some related classes in that file. However, in order to be able to generate documentation without clobbering the hand-written documentation, it generates a file structured to include separate files for the summary, examples, see also, and usage sections. The hand-written sections appear in subdirectories of the globals documentation directory, with the same name as the generated function's documentation.


2. The packages documentation directories are built by a two-step process. First, the above two steps generate an array, stored in the file `serializedPackagesMap.txt`, which maps the packages to the correct files. `OmekaPackages.php` then loads that array and writes the packages documentation. 

3. To completely regenerate the documentation, all of the directories should be emptied *except* for any `index.rst` files, and *except* for the manually written documentation sections for global functions.

Unfortunately, there are still some hard-coded references to paths scattered throughout the files.


