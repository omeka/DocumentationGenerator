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
echo "a:0:{}" > 'serializedPackagesMap.txt'

dirs=(
    libraries/Omeka
    libraries/Omeka/Acl
    libraries/Omeka/Acl/Assert
    libraries/Omeka/Application
    libraries/Omeka/Application/Resource
    libraries/Omeka/Application/Resource/Jobs
    libraries/Omeka/Auth
    libraries/Omeka/Auth/Adapter
    libraries/Omeka/Controller
    libraries/Omeka/Controller/Plugin
    libraries/Omeka/Controller/Exception
    libraries/Omeka/Controller/Router
    libraries/Omeka/Db
    libraries/Omeka/Db/Migration
    libraries/Omeka/Db/Select
    libraries/Omeka/File
    libraries/Omeka/File/Ingest
    libraries/Omeka/File/MimeType
    libraries/Omeka/File/MimeType/Detect
    libraries/Omeka/File/MimeType/Detect/Strategy
    libraries/Omeka/File/Derivative
    libraries/Omeka/File/Derivative/Strategy
    libraries/Omeka/Filter
    libraries/Omeka/Form
    libraries/Omeka/Form/Decorator
    libraries/Omeka/Form/Element
    libraries/Omeka/Http
    libraries/Omeka/Job
    libraries/Omeka/Job/Worker
    libraries/Omeka/Job/Dispatcher
    libraries/Omeka/Job/Dispatcher/Adapter
    libraries/Omeka/Job/Factory
    libraries/Omeka/Job/Process
    libraries/Omeka/Navigation
    libraries/Omeka/Navigation/Page
    libraries/Omeka/Navigation/Page/Uri
    libraries/Omeka/Output
    libraries/Omeka/Output/OmekaXml
    libraries/Omeka/Plugin
    libraries/Omeka/Plugin/Loader
    libraries/Omeka/Plugin/Broker
    libraries/Omeka/Plugin/Installer
    libraries/Omeka/Record
    libraries/Omeka/Record/Api
    libraries/Omeka/Record/Builder
    libraries/Omeka/Record/Mixin
    libraries/Omeka/Session
    libraries/Omeka/Session/SaveHandler
    libraries/Omeka/Storage
    libraries/Omeka/Storage/Adapter
    libraries/Omeka/Test
    libraries/Omeka/Test/Resource
    libraries/Omeka/Test/Helper
    libraries/Omeka/Validate
    libraries/Omeka/Validate/File
    libraries/Omeka/View
    libraries/Omeka/View/Helper
    models
	models/Api
    models/Builder
    models/Installer
    models/Installer/Task
    models/Job
    models/Mixin
    models/Output
    models/Table
    controllers
	controllers/api
    controllers/helpers
    views
    views/helpers)
#dirs=(    
#    libraries/Omeka/File
#    libraries/Omeka/File/Ingest
#    libraries/Omeka/File/MimeType
#    libraries/Omeka/File/MimeType/Detect
#    libraries/Omeka/File/MimeType/Detect/Strategy
#    libraries/Omeka/File/Derivative
#    libraries/Omeka/File/Derivative/Strategy
#)        
for dir in ${dirs[@]}
do
    ./sphpdox.php process -o /var/www/Documentation/source/Reference/${dir} -t ${dir} no-namespace /var/www/Omeka/application/${dir}  
done


php OmekaGlobals.php
php OmekaPackages.php

