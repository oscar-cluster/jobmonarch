Buildroot: 
Name: jobmonarch-jobmond
Version: 
Release: 
Summary: Job Monitoring Daemon
License: see /usr/share/doc/jobmonarch-jobmond/copyright
Distribution: Debian
Group: Converted/misc

%define _rpmdir ../
%define _rpmfilename %%{NAME}-%%{VERSION}-%%{RELEASE}.%%{ARCH}.rpm
%define _unpackaged_files_terminate_build 0

%post
#!/bin/sh

PATH=/bin:/sbin:/usr/bin:/usr/sbin

if [ -x /etc/init.d/jobmond ]
	then

		chkconfig jobmond on

fi

/etc/init.d/jobmond restart


%preun
#!/bin/sh

/etc/init.d/jobmond stop
chkconfig jobmond off


%description


Job MonArch's monitoring daemon.

%files
"/etc/init.d/jobmond"
%config "/etc/sysconfig/jobmond"
"/usr/sbin/jobmond.py"
"/usr/sbin/jobmond"
%config "/etc/jobmond.conf"
