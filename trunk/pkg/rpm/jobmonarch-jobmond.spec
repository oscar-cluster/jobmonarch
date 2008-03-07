Buildroot: 
Name: jobmonarch-jobmond
Version: 
Release: 
Summary: Job Monitoring Daemon
License: GPL
Distribution: Fedora
Group: Applications/System

%define _rpmdir ../
%define _rpmfilename %%{NAME}-%%{VERSION}-%%{RELEASE}.rpm
%define _unpackaged_files_terminate_build 0

%post
#!/bin/sh

PATH=/bin:/sbin:/usr/bin:/usr/sbin

if [ -x /etc/init.d/jobmond ]
	then

		chkconfig --add jobmond
		chkconfig jobmond on
		/etc/init.d/jobmond restart

fi

%preun
#!/bin/sh

/etc/init.d/jobmond stop
chkconfig jobmond off
chkconfig --del jobmond


%description


Job MonArch's monitoring daemon.

%files
"/etc/init.d/jobmond"
%config "/etc/sysconfig/jobmond"
"/usr/sbin/jobmond.py"
"/usr/sbin/jobmond"
%config "/etc/jobmond.conf"
