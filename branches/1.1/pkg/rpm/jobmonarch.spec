
# The following options are supported:
#   --with httpd_user=<username>       # defaults to: apache
#   --with httpd_group=<group>         # defaults to: apache
#   --with ganglia_user=<username>     # defaults to: ganglia
#   --with ganglia_group=<group>       # defaults to: ganglia
#   --with web_prefixdir=<path>        # defaults to: /usr/share/ganglia-webfrontend

# example: rpmbuild -tb jobmonarch-1.1.2.tar.gz --with httpd_user=www-data --with httpd_group=www-data --with web_prefixdir=/srv/www/ganglia

# Default value for web_prefixdir depending on distro.
%if 0%{?suse_version}
%define web_prefixdir /srv/www/htdocs/ganglia
%else
%define web_prefixdir /usr/share/ganglia-webfrontend
%endif

# Default value for httpd user and group used by ganglia.
%define httpd_user apache
%define httpd_group apache
%define ganglia_user ganglia
%define ganglia_group ganglia

# Read the provided --with tags if any (overriding default values).
%{?_with_httpd_user:%define httpd_user %(set -- %{_with_httpd_user}; echo $2 | cut -d= -f2)}
%{?_with_httpd_group:%define httpd_group %(set -- %{_with_httpd_group}; echo $2 | cut -d= -f2)}
%{?_with_httpd_user:%define ganglia_user %(set -- %{_with_ganglia_user}; echo $2 | cut -d= -f2)}
%{?_with_httpd_group:%define ganglia_group %(set -- %{_with_ganglia_group}; echo $2 | cut -d= -f2)}
%{?_with_web_prefixdir:%define web_prefixdir %(set -- %{_with_web_prefixdir}; echo $2 | cut -d= -f2)}

# Don't need debuginfo RPM
%define debug_package %{nil}
%define __check_files %{nil}

%define gangliaroot        %{web_prefixdir}
%define gangliatemplatedir %{gangliaroot}/templates
%define gangliaaddonsdir   %{gangliaroot}/addons

Summary: Tools and addons to Ganglia to monitor and archive batch job info
Name: jobmonarch
Version: __VERSION__
URL: https://oss.trac.surfsara.nl/jobmonarch
Release: __RELEASE__%{?dist}
License: GPL
Packager: Job Monarch Development team <jobmonarch-developers@lists.sourceforge.net>
Group: Applications/Base
Source: ganglia_jobmonarch-%{version}.tar.bz2
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}
BuildRequires: fakeroot

AutoReqProv: no

%description
Job Monarch is a set of tools to monitor and optionally archive (batch) job
information. It is a addon for the Ganglia monitoring system and plugs into an
existing Ganglia setup.

%package -n jobmonarch-jobarchived
Summary: jobarchived is the archiving daemon for jobmonarch.
Version: __VERSION__
URL: https://oss.trac.surfsara.nl/jobmonarch
Release: __RELEASE__%{?dist}
License: GPL
Packager: Job Monarch Development team <jobmonarch-developers@lists.sourceforge.net>
Group: Applications/Base
Requires: postgresql >= 8.1.22
Requires: postgresql-server >= 8.1.22
Requires: ganglia-gmetad >= 3.3.8
Requires: python >= 2.5
Requires: python-psycopg2
Requires: rrdtool-python rrdtool
Requires: jobmonarch-webfrontend

%description -n jobmonarch-jobarchived
Job-Monarch is a set of tools to monitor and optionally archive (batch) job
information. It is a addon for the Ganglia monitoring system and plugs into an
existing Ganglia setup.
jobarchived is the Job-Monarch's job archiving daemon. It listens to Ganglia's
XML stream and archives the job and node statistics. It stores the job
statistics in a Postgres SQL database and the node statistics in RRD
files. Through this daemon, users are able to lookup a old/finished job and
view all it's statistics.

%package -n jobmonarch-jobmond
Summary: jobmond is the job monitoring daemon for jobmonarch.
Version: __VERSION__
URL: https://oss.trac.surfsara.nl/jobmonarch
Release: __RELEASE__%{?dist}
License: GPL
Packager: Job Monarch Development team <jobmonarch-developers@lists.sourceforge.net>
Group: Applications/Base
Requires: python >= 2.5
Requires: ganglia-gmond >= 3.3.8
#Requires(hint): pbs_python
#Requires(hint): python-pylsf
#Requires(hint): python-pyslurm

%description -n jobmonarch-jobmond
Job-Monarch is a set of tools to monitor and optionally archive (batch) job
information. It is a addon for the Ganglia monitoring system and plugs into an
existing Ganglia setup.
jobmond is the Job-Monarch's job monitoring daemon that gathers PBS/Torque/SLURM/LSF/SGE
batch statistics on jobs/nodes and submits them into Ganglia's XML stream.

%package -n jobmonarch-webfrontend
Summary: webfrontend is the ganglia webfrontend for jobmonarch.
Version: __VERSION__
URL: https://oss.trac.surfsara.nl/jobmonarch
Release: __RELEASE__%{?dist}
License: GPL
Packager: Job Monarch Development team <jobmonarch-developers@lists.sourceforge.net>
Group: Applications/Base
Requires: ganglia-web >= 3.3.8
Requires: php >= 5.3.0
Requires: php-pgsql
%if 0%{?suse_version}
Requires: php5-gd >= 2.0
%else
Requires: php-gd >= 2.0
# php-mbstring, while required by Dwoo is not needed by jobmonarch-webfrontend as
# it doesn't uses Dwoo functions that require mbstring.
#Requires: php-mbstring
%endif

%description -n jobmonarch-webfrontend
Job-Monarch is a set of tools to monitor and optionally archive (batch)job
information. It is a addon for the Ganglia monitoring system and plugs into an
existing Ganglia setup.
webfrontend is The Job-Monarch's web frontend interfaces with the
jobmond data and (optionally) the jobarchived and presents the data and
graphs. It does this in a similar layout/setup as Ganglia itself, so the
navigation and usage is intuitive.

%prep
%setup -q -n ganglia_jobmonarch-%{version}

%build

%install
rm -rf $RPM_BUILD_ROOT


# Fix rrdtool web link in footer:
#sed -i -e 's|http:/www.rrdtool.com/|http:/oss.oetiker.ch/rrdtool/|g' ./web/addons/job_monarch/templates/footer.tpl

# Install files in RPM_BUILD_ROOT
fakeroot %__make install \
        PREFIX=/usr \
        GANGLIA_ROOT=%{gangliaroot} \
        GANGLIA_USER=%{ganglia_user}.%{ganglia_group} \
        HTTPD_USER=%{httpd_user}.%{httpd_group} \
        JOBARCHIVE_RRDS=%{_sharedstatedir}/jobarchive \
        DESTDIR=$RPM_BUILD_ROOT

%clean
%__rm -rf $RPM_BUILD_ROOT

%post -n jobmonarch-jobmond
# $1 = 1 => install ($1 = 2 => upgrade)
if [ "$1" = 1 ]; then
    # Enable the service
%if 0%{?_unitdir:1}
    /usr/bin/systemctl enable jobmond.service
%else
    /sbin/chkconfig --add jobmond
%endif
    echo ""
    echo "Additional manual changes are required to setup jobmond:"
    echo ""
    echo "1) Edit /etc/jobmond.conf to reflect your local settings and setup:"
    echo "   - BATCH_API: pbs, slurm, sge (experimental), lsf (experimental)"
    echo "2) Install the python interface to the selected batch queuing system"
    echo "   - pbs_python (for pbs or torque)"
    echo "   - python-pyslurm (for slurm)"
    echo "   - python-pylsf (for lsf)"
    echo ""
elif [ "$1" = 2 ]; then
    echo "Restarting jobmond if needed..."
%if 0%{?_unitdir:1}
    /usr/bin/systemctl --system daemon-reload
    /usr/bin/systemctl reload-or-try-restart jobmond.service
%else
    /sbin/service jobmond condrestart
%endif
fi

%post -n jobmonarch-jobarchived
# $1 = 1 => install ($1 = 2 => upgrade)
if [ "$1" = 1 ]; then
    # Enable the service
%if 0%{?_unitdir:1}
    /usr/bin/systemctl enable jobarchived.service
%else
    /sbin/chkconfig --add jobarchived
%endif
    echo "Generating random password and updating apropriate files"
    # Generate a 8 char password for the database:
    export DB_PASSWD=$(tr -dc A-Za-z0-9_< /dev/urandom |head -c 8 | xargs)
    # Set the password in the SQL script 
    sed -i -e '/^.*modify me:.*$/d' -e 's/^-- ALTER/ALTER/g' -e "s/'';/'$DB_PASSWD';/g" %{_datadir}/jobarchived/job_dbase.sql
    # Set the password in the jobarchived config.
    sed -i -e "s/^#JOB_SQL_PASSWORD.*$/JOB_SQL_PASSWORD\t\t: $DB_PASSWD/g" %{_sysconfdir}/jobarchived.conf
    # Set the password in the ganglia conf.php
    sed -i -e "s|^//\$JOB_ARCHIVE_SQL_PASSWORD.*|\$JOB_ARCHIVE_SQL_PASSWORD = \"$DB_PASSWD\"|g" %{gangliaaddonsdir}/job_monarch/conf.php
    echo ""
    echo "Additional manual changes are required to setup jobarchived:"
    echo ""
    echo "1) Edit /etc/jobarchived.conf to reflect your local settings and setup:"
    echo "   - ARCHIVE_DATASOURCES and ARCHIVE_PATH"
    echo ""
    echo "2) Create a 'jobarchive' database and create jobarchived's tables:" 
    echo "   - createdb jobarchive"
    echo "   - psql -f /usr/share/jobarchived/job_dbase.sql jobarchive"
    echo "   - Update /var/lib/pgsql/data/pg_hba.conf by adding the following lines:"
    echo "     local   jobarchive      jobarchive                              trust"
    echo "     host    jobarchive      jobarchive      127.0.0.1/32            trust"
    echo "     host    jobarchive      jobarchive      ::1/128                 trust"
    echo "   - Restart the postgresql service"
    echo ""
elif [ "$1" = 2 ]; then
    echo "Restarting jobarchived if needed..."
%if 0%{?_unitdir:1}
    /usr/bin/systemctl --system daemon-reload
    /usr/bin/systemctl reload-or-try-restart jobarchived.service
%else
    /sbin/service jobarchived condrestart
%endif
    exit 0
fi

%post -n jobmonarch-webfrontend
if [ "$1" = 1 ]; then
    echo "Make sure to set your Ganglia template to job_monarch now"
    echo ""
    echo "In your Ganglia conf.php, set this line:"
    echo "\$template_name = \"job_monarch\";"
fi

%preun -n jobmonarch-jobmond
if [ "$1" = 0 ]; then
%if 0%{?_unitdir:1}
    /usr/bin/systemctl --no-reload disable jobmond.service
    /usr/bin/systemctl stop jobmond.service
%else
    if [ -x /sbin/chkconfig ]; then
	/sbin/service jobmond stop
	/sbin/chkconfig --del jobmond
    fi
%endif
fi

%preun -n jobmonarch-jobarchived
if [ "$1" = 0 ]; then
%if 0%{?_unitdir:1}
    /usr/bin/systemctl --no-reload disable jobarchived.service
    /usr/bin/systemctl stop jobarchived.service
%else
    if [ -x /sbin/chkconfig ]; then
	/sbin/service jobarchived stop
	/sbin/chkconfig --del jobarchived
    fi
%endif
fi

%preun -n jobmonarch-webfrontend
if [ "$1" = 0 ]; then
    echo "Make sure to set your Ganglia template to previous config now"
    echo ""
    echo "In your Ganglia conf.php, restore your previous template:"
    echo "\$template_name = \"default\";"
fi

%files -n jobmonarch-jobmond
%doc jobmond/examples
%doc AUTHORS CHANGELOG INSTALL LICENSE README TODO UPGRADE
%config(noreplace) %{_sysconfdir}/jobmond.conf
%{_sysconfdir}/sysconfig/jobmond
%{_sbindir}/jobmond.py
%{_sbindir}/jobmond
%%if 0%{?_unitdir:1}
%{_unitdir}/jobmond.service
%else
%{_initrddir}/jobmond
%endif

%files -n jobmonarch-jobarchived
%doc jobarchived/examples
%doc AUTHORS CHANGELOG INSTALL LICENSE README TODO UPGRADE
%config(noreplace) %{_sysconfdir}/jobarchived.conf
%{_sysconfdir}/sysconfig/jobarchived
%dir %{_datadir}/jobarchived
%{_sbindir}/jobarchived.py
%{_sbindir}/jobarchived
%{_datadir}/jobarchived/*
%dir %{_sharedstatedir}/jobarchive
%%if 0%{?_unitdir:1}
%{_unitdir}/jobarchived.service
%else
%{_initrddir}/jobarchived
%endif

%files -n jobmonarch-webfrontend
%doc AUTHORS CHANGELOG INSTALL LICENSE README TODO UPGRADE
%dir %{gangliatemplatedir}/job_monarch
%dir %{gangliaaddonsdir}/job_monarch
%{gangliatemplatedir}/job_monarch/cluster_extra.tpl
%{gangliatemplatedir}/job_monarch/host_extra.tpl
%dir %{gangliatemplatedir}/job_monarch/images
%{gangliatemplatedir}/job_monarch/images/logo.jpg
%config(noreplace) %{gangliaaddonsdir}/job_monarch/conf.php
%{gangliaaddonsdir}/job_monarch/ajax-loader.gif
%{gangliaaddonsdir}/job_monarch/cal.gif
%{gangliaaddonsdir}/job_monarch/clusterconf
%{gangliaaddonsdir}/job_monarch/document_archive.jpg
%dir %{gangliaaddonsdir}/job_monarch/dwoo
%{gangliaaddonsdir}/job_monarch/dwoo/dwooAutoload.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/plugins
%dir %{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin
%dir %{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/filters
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/filters/html_format.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/helper.array.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/processors
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/processors/pre.smarty_compat.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/forelse.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/capture.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/if.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/elseif.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/block.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/smartyinterface.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/foreachelse.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/loop.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/textformat.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/template.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/withelse.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/with.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/strip.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/for.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/a.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/dynamic.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/else.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/topLevelBlock.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/auto_escape.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/section.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/blocks/foreach.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/cat.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/fetch.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/extendsCheck.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/count_characters.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/regex_replace.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/truncate.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/escape.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/safe.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/replace.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/return.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/math.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/isset.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/strip_tags.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/capitalize.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/dump.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/cycle.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/upper.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/eval.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/mailto.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/counter.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/spacify.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/default.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/optional.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/include.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/eol.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/reverse.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/lower.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/extends.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/wordwrap.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/load_templates.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/count_paragraphs.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/indent.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/assign.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/count_sentences.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/tif.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/nl2br.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/string_format.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/whitespace.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/count_words.php
%{gangliaaddonsdir}/job_monarch/dwoo/plugins/builtin/functions/date_format.php
%dir %attr(775,apache,apache) %{gangliaaddonsdir}/job_monarch/dwoo/compiled
%dir %attr(775,apache,apache) %{gangliaaddonsdir}/job_monarch/dwoo/cache
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/IPluginProxy.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Filter.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Compiler.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Compilation
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Compilation/Exception.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Template
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Template/String.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Template/File.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/IDataProvider.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/ICompilable.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Block
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Block/Plugin.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/ICompiler.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/ICompilable
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/ICompilable/Block.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Processor.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Smarty
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Smarty/Adapter.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Exception.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Plugin.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Core.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/ILoader.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Data.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Security
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Security/Policy.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Security/Exception.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/IElseable.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/ITemplate.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Loader.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/ZendFramework
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/ZendFramework/README
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/ZendFramework/PluginProxy.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/ZendFramework/View.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/ZendFramework/Dwoo.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/views
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/views/dwoowelcome.tpl
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/views/page.tpl
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/controllers
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/controllers/dwoowelcome.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/libraries
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/libraries/Dwootemplate.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/README
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/config
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CodeIgniter/config/dwootemplate.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CakePHP
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CakePHP/README
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/CakePHP/dwoo.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/Agavi
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/Agavi/README
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/Agavi/DwooRenderer.php
%dir %{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/Agavi/dwoo_plugins
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/Agavi/dwoo_plugins/t.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo/Adapters/Agavi/dwoo_plugins/url.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo.compiled.php
%{gangliaaddonsdir}/job_monarch/dwoo/Dwoo.php
%{gangliaaddonsdir}/job_monarch/footer.php
%{gangliaaddonsdir}/job_monarch/graph.php
%{gangliaaddonsdir}/job_monarch/host_view.php
%{gangliaaddonsdir}/job_monarch/image.php
%{gangliaaddonsdir}/job_monarch/index.php
%{gangliaaddonsdir}/job_monarch/jobmonarch.gif
%{gangliaaddonsdir}/job_monarch/libtoga.js
%{gangliaaddonsdir}/job_monarch/libtoga.php
%{gangliaaddonsdir}/job_monarch/logo_ned.gif
%{gangliaaddonsdir}/job_monarch/next.gif
%{gangliaaddonsdir}/job_monarch/overview.php
%{gangliaaddonsdir}/job_monarch/prev.gif
%{gangliaaddonsdir}/job_monarch/redcross.jpg
%{gangliaaddonsdir}/job_monarch/search.php
%{gangliaaddonsdir}/job_monarch/styles.css
%dir %{gangliaaddonsdir}/job_monarch/templates
%{gangliaaddonsdir}/job_monarch/templates/footer.tpl
%{gangliaaddonsdir}/job_monarch/templates/header.tpl
%{gangliaaddonsdir}/job_monarch/templates/host_view.tpl
%{gangliaaddonsdir}/job_monarch/templates/overview.tpl
%{gangliaaddonsdir}/job_monarch/templates/search.tpl
%{gangliaaddonsdir}/job_monarch/ts_picker.js
%{gangliaaddonsdir}/job_monarch/ts_validatetime.js
%{gangliaaddonsdir}/job_monarch/version.php

%changelog
* Wed Mar 05 2014 Olivier Lahaye <olivier.lahaye@free.fr> 1.1.3-1
- Update default ganglia root.
- Add native systemd support.
- update to 1.1.3
- Add --with switch to allow tunning at rpmbuild. Parameters are similar to ganglia ones.

* Fri Feb 14 2014 Ramon Bastiaans <ramon.bastiaans@surfsara.nl> 1.1.2-1
- New version

* Fri Sep 20 2013 Olivier Lahaye <olivier.lahaye@free.fr> 1.1.1-1
- update to 1.1.1
- Allow for custom ganglia user. (default: ganglia.ganglia)

* Wed May 22 2013 Ramon Bastiaans <ramon.bastiaans@surfsara.nl> 1.1-1
- update to 1.1
- set version requirement for Ganglia
- removed jobmond dep from webfrontend pkg

%changelog
* Wed Apr 24 2013 Olivier Lahaye <olivier.lahaye@free.fr> 1.0-3
- Use make install to install the files
- Fix the correct gangliaroot path
- Fix the correct jobarchive rrd file path.
- Generate a password for the database and update config files accordingly.
- Set the correct permissions for %{gangliaaddonsdir}/job_monarch/dwoo/compiled

* Tue Apr 23 2013 Olivier Lahaye <olivier.lahaye@free.fr> 1.0-2
- Package missing files (/etc/sysconfig/{job{mond,archived}} and /etc/init.d scripts)
- Fix Requires:
  - Added missing python-psycopg2 require in jobarchived
  - Removed pyPgSQL require (replaced by psycopg2)
  - Removed useless requires: pbs_python from jobarchived
  - Removed useless requires: python-rrdtools from jobmond

* Mon Apr 22 2013 Olivier Lahaye <olivier.lahaye@free.fr> 1.0-1
- Major rewrite of the spec file (sub packages)
- Final upstream release.

* Wed Mar 13 2013 Olivier Lahaye <olivier.lahaye1@free.fr> 0.4-0.4
- Added Requires: pbs_python

* Mon Mar  4 2013 Olivier Lahaye <olivier.lahaye1@free.fr> 0.4-0.3
- Added Requires: pyPgSQL python-rrdtool
- Fixed postinstall (Postgress initdb if required)
- Fixed gangliaaddonsdir
- Add %dir in file sections for gangliaaddonsdir and gangliatemplatedir
  so rpm -qf know those dirs belong to jobmonarch package.
- Fix web/addons/job_monarch/conf.php (GANGLIA_PATH and JOB_ARCHIVE_DIR)
- Fix default gmond.conf path (/etc/ganglia/gmond.conf)
- Mark %{_sharedstatedir}/jobarchived directory as part of the package
- Fix rrdtool web URL in footer
- Fix VERSION (it is a 0.4-pre, not a 0.3.1)
- Patch from Daems Dirk: new pbs_python with arrays
- Patch from Jeffrey J. Zahari: jobs attributes retrieval

* Fri May 11 2012 Olivier Lahaye <olivier.lahaye1@free.fr> 0.4-0.2
- Update to support EPEL/RF ganglia rpm.
- Using 0.4 prerelease as there is an important bugfix over 0.3.1
- Use macros

* Fri Jul 29 2011 Olivier Lahaye <olivier.lahaye1@free.fr> 0.4-0.1
- Update to V0.4SVN

* Sun Aug 12 2006 Babu Sundaram <babu@cs.uh.edu> 0.3.1-1
- Prepare first rpm for Job Monarch's jobmond Daemon

