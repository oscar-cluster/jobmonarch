Buildroot: 
Name: jobmonarch-webfrontend
Version: 
Release: 
Summary: Job MonArch Web Frontend
License: GPL
Distribution: Fedora
Group: Applications/Internet

%define _rpmdir ../
%define _rpmfilename %%{NAME}-%%{VERSION}-%%{RELEASE}.rpm
%define _unpackaged_files_terminate_build 0

%post
#!/bin/sh

echo "Make sure to set your Ganglia template to job_monarch now"
echo "" 
echo "In your Ganglia conf.php, set this line:"
echo "$template_name = \"job_monarch\";"


%postun
#!/bin/sh

echo "Dont forget to set your Ganglia template back to default"


%description


Job Monarch's web frontend.

%files
%dir "/var/www/ganglia/templates/job_monarch/"
%dir "/var/www/ganglia/templates/job_monarch/images/"
"/var/www/ganglia/templates/job_monarch/cluster_extra.tpl"
"/var/www/ganglia/templates/job_monarch/host_extra.tpl"
%dir "/var/www/ganglia/addons/"
%dir "/var/www/ganglia/addons/job_monarch/"
%dir "/var/www/ganglia/addons/job_monarch/clusterconf/"
"/var/www/ganglia/addons/job_monarch/clusterconf/example.php"
%dir "/var/www/ganglia/addons/job_monarch/templates/"
"/var/www/ganglia/addons/job_monarch/templates/overview.tpl"
"/var/www/ganglia/addons/job_monarch/templates/search.tpl"
"/var/www/ganglia/addons/job_monarch/templates/footer.tpl"
"/var/www/ganglia/addons/job_monarch/templates/header.tpl"
"/var/www/ganglia/addons/job_monarch/templates/host_view.tpl"
"/var/www/ganglia/addons/job_monarch/templates/index.tpl"
%config "/var/www/ganglia/addons/job_monarch/conf.php"
"/var/www/ganglia/addons/job_monarch/search.php"
"/var/www/ganglia/addons/job_monarch/libtoga.php"
"/var/www/ganglia/addons/job_monarch/version.php"
"/var/www/ganglia/addons/job_monarch/cal.gif"
"/var/www/ganglia/addons/job_monarch/document_archive.jpg"
"/var/www/ganglia/addons/job_monarch/graph.php"
"/var/www/ganglia/addons/job_monarch/header.php"
"/var/www/ganglia/addons/job_monarch/host_view.php"
"/var/www/ganglia/addons/job_monarch/image.php"
"/var/www/ganglia/addons/job_monarch/libtoga.js"
"/var/www/ganglia/addons/job_monarch/logo_ned.gif"
"/var/www/ganglia/addons/job_monarch/next.gif"
"/var/www/ganglia/addons/job_monarch/prev.gif"
"/var/www/ganglia/addons/job_monarch/redcross.jpg"
"/var/www/ganglia/addons/job_monarch/ts_picker.js"
"/var/www/ganglia/addons/job_monarch/ts_validatetime.js"
"/var/www/ganglia/addons/job_monarch/footer.php"
"/var/www/ganglia/addons/job_monarch/styles.css"
"/var/www/ganglia/addons/job_monarch/index.php"
"/var/www/ganglia/addons/job_monarch/overview.php"
"/var/www/ganglia/addons/job_monarch/jobmonarch.gif"
"/var/www/ganglia/templates/job_monarch/images/logo.jpg"
