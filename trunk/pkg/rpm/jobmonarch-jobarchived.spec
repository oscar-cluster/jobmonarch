Buildroot: 
Name: jobmonarch-jobarchived
Version: 
Release: 
Summary: Job Archiving Daemon
License: see /usr/share/doc/jobmonarch-jobarchived/copyright
Distribution: Debian
Group: Converted/misc

%define _rpmdir ../
%define _rpmfilename %%{NAME}-%%{VERSION}-%%{RELEASE}.rpm
%define _unpackaged_files_terminate_build 0

%post
#!/bin/sh

PATH=/bin:/sbin:/usr/bin:/usr/sbin

if [ -x /etc/init.d/jobarchived ]
	then

		chkconfig jobarchived on

fi

echo ""
echo "Additional manual changes are required to setup jobarchived:"
echo ""
echo "1) Edit /etc/jobarchived.conf to reflect your local settings and setup:"
echo "   - ARCHIVE_DATASOURCES and ARCHIVE_PATH"
echo ""
echo "2) Create a 'jobarchive' database and create jobarchived's tables:" 
echo "   - createdb jobarchive"
echo "   - psql -f /usr/share/jobarchived/job_dbase.sql jobarchive"
echo ""


%preun
#!/bin/sh

/etc/init.d/jobarchived stop
chkconfig jobarchived off


%description


Job MonArch's archive daemon

%files
"/usr/sbin/jobarchived.py"
"/usr/sbin/jobarchived"
"/etc/init.d/jobarchived"
%config "/etc/jobarchived.conf"
%config "/etc/sysconfig/jobarchived"
%dir "/usr/share/jobarchived/"
"/usr/share/jobarchived/job_dbase.sql"
