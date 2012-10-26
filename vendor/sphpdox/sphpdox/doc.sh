###########
#
# The hacking on sphpdox I did got it so that it will only read a single directory
# Originally it was reading a directory and its child, which messed up the indices
#
# So here there's a listing of each individual directory from which to build documentation.
# When building documentation locally, make sure to change the destination and target directories
#
########

# reset the serializedPackagesMap file
echo "" > 'serializedPackagesMap.txt'

dirs=(libraries/Omeka
    libraries/Omeka/Storage
    libraries/Omeka/Storage/Adapter
    libraries/Omeka/Auth
    libraries/Omeka/Auth/Adapter
    libraries/Omeka/Navigation
    libraries/Omeka/Navigation/Page
    libraries/Omeka/Controller
    libraries/Omeka/Controller/Plugin
    libraries/Omeka/Controller/Exception
    libraries/Omeka/Job
    libraries/Omeka/Job/Worker
    libraries/Omeka/Job/Dispatcher
    libraries/Omeka/Job/Dispatcher/Adapter
    libraries/Omeka/Job/Factory
    libraries/Omeka/Job/Process
    libraries/Omeka/Db
    libraries/Omeka/Db/Migration
    libraries/Omeka/Db/Select
    libraries/Omeka/Validate
    libraries/Omeka/Validate/File
    libraries/Omeka/File
    libraries/Omeka/File/Ingest
    libraries/Omeka/File/MimeType
    libraries/Omeka/File/Derivative
    libraries/Omeka/File/Derivative/Image
    libraries/Omeka/Form
    libraries/Omeka/Plugin
    libraries/Omeka/Plugin/Loader
    libraries/Omeka/Plugin/Broker
    libraries/Omeka/Plugin/Installer
    libraries/Omeka/Test
    libraries/Omeka/Test/Resource
    libraries/Omeka/Test/Helper
    libraries/Omeka/Http
    libraries/Omeka/Output
    libraries/Omeka/Output/OmekaXml
    libraries/Omeka/Record
    libraries/Omeka/Record/Builder
    libraries/Omeka/Record/Mixin
    libraries/Omeka/Application
    libraries/Omeka/Application/Resource
    libraries/Omeka/Application/Resource/Jobs
    libraries/Omeka/View
    libraries/Omeka/Acl
    libraries/Omeka/Acl/Assert
    libraries/Omeka/Validate
    libraries/Omeka/Filter
    libraries/Omeka/Session
    libraries/Omeka/Session/SaveHandler
    models/Installer
    models/Builder
    models/Installer/Task
    models/Installer
    models/Mixin
    models/Output
    models/Job
    models/Table
    models
    controllers/helpers
    controllers
    views/helpers)
#dirs=(views/helpers)        
for dir in ${dirs[@]}
do
    ./sphpdox.php process -o /var/www/html/Documentation/source/Reference/${dir} -t ${dir} no-namespace /var/www/html/Omeka/application/${dir}  
done


#php OmekaGlobals.php


