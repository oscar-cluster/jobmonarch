# Where to build
#
TMPDIR = /tmp

# Where to install
DESTDIR = 

# Install prefix
PREFIX = /usr/local

BIN_DIR = $(PREFIX)/sbin
JOBMOND = $(BIN_DIR)/jobmond
JOBARCHIVED = $(BIN_DIR)/jobarchived

# What is the location of the Ganglia web frontend
# i.e.: where to we install Job Monarch's web frontend addon
# 
GANGLIA_ROOT = $(PREFIX)/ganglia
GANGLIA_USER = ganglia.ganglia
HTTPD_USER   = apache.apache

# Where jobarchived RRDS are stored
JOBARCHIVE_RRDS = $(PREFIX)/jobmonarch

# Clear this if you don't want to use ${FAKEROOT}
#
FAKEROOT = fakeroot

VERSION = 1.1
RELEASE = 1

REQUIRED = ./jobarchived ./jobmond ./web

all:	tarball rpm srpm deb

tarball:	tarball-gzip tarball-bzip

$(TMPDIR)/.monarch_buildroot: ${REQUIRED} Makefile
	@mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	@( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	@sed -i -e 's|__VERSION__|$(VERSION)|g' -e 's/__RELEASE__/$(RELEASE)/g' \
		${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}/jobmond/jobmond.py \
		${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}/jobarchived/jobarchived.py \
		${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}/web/addons/job_monarch/version.php \
		${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}/pkg/rpm/jobmonarch.spec \
		${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}/debian/changelog


tarball-gzip:	$(TMPDIR)/.monarch_buildroot ${REQUIRED}
	@( cd ${TMPDIR}/.monarch_buildroot; tar zcf ganglia_jobmonarch-${VERSION}.tar.gz ./ganglia_jobmonarch-${VERSION} )
	@mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.gz ..
	@rm -rf ${TMPDIR}/.monarch_buildroot
	@echo "Wrote: ../ganglia_jobmonarch-${VERSION}.tar.gz"

tarball-bzip:	$(TMPDIR)/.monarch_buildroot ${REQUIRED}
	@( cd ${TMPDIR}/.monarch_buildroot; tar jcf ganglia_jobmonarch-${VERSION}.tar.bz2 ./ganglia_jobmonarch-${VERSION} )
	@mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.bz2 ..
	@rm -rf ${TMPDIR}/.monarch_buildroot
	@echo "Wrote: ../ganglia_jobmonarch-${VERSION}.tar.bz2"

rpm: tarball-bzip
	# Binary package will reflect most distro where ganglia default location is /usr/share/ganglia
	rpmbuild -tb --define '%custom_web_prefixdir /usr/share/ganglia' ../ganglia_jobmonarch-${VERSION}.tar.bz2

srpm: tarball-bzip
	rpmbuild -ts --define '%dist %{nil}' ../ganglia_jobmonarch-${VERSION}.tar.bz2

debchangelog: ./debian/changelog

deb: ${REQUIRED} $(TMPDIR)/.monarch_buildroot ./debian
	@( cd ${TMPDIR}/.monarch_buildroot; dpkg-buildpackage -b -uc -us )
	@rm -rf ${TMPDIR}/.monarch_buildroot
	@echo "Wrote:"
	@ls -1 ${TMPDIR}/jobmonarch*$(VERSION)*.deb

install:  ${REQUIRED}
	@#
	@# Set the correct GANGLIA_PATH.
	@#
	@echo
	@echo "Using $(GANGLIA_ROOT) as Ganglia root installation path. If it's not what"
	@echo "you want, use make GANGLIA_ROOT=/path/to/your/ganglia/root ."
	@sed -e 's|__GANGLIA_ROOT__|$(GANGLIA_ROOT)/|g' web/conf.php.in > web/addons/job_monarch/conf.php
	@#
	@# Set the correct JOBARCHIVE_RRDS in jobarchve.conf and ganglia conf.php
	@#
	@echo
	@echo "Using $(JOBARCHIVE_RRDS) as jobarchive path to  store rrds files. If it's not what"
	@echo "you want, use make JOBARCHIVE_RRDS=/path/to/you/jobarchived/rrdsfiles ."
	@sed -i -e 's|/var/lib/jobarchive|$(JOBARCHIVE_RRDS)|g' jobarchived/jobarchived.conf web/addons/job_monarch/conf.php
	@#
	@# Files in SBIN_DIR
	@#
	@echo
	@echo "Installing jobmond.py and jobarchived.py to $(PREFIX)/sbin"
	@install -m 0755 -d $(DESTDIR)$(PREFIX)/sbin || true
	@install -m 0755 jobmond/jobmond.py $(DESTDIR)$(PREFIX)/sbin/ || true
	@install -m 0755 jobarchived/jobarchived.py $(DESTDIR)$(PREFIX)/sbin/ || true
	@(cd $(DESTDIR)$(PREFIX)/sbin/; ln -s jobmond.py jobmond; ln -s jobarchived.py jobarchived) || true
	@#
	@# Files specific to distros if /etc/redhat-release => rpm else (/etc/debian_version => debian)
	@#
	@echo
	@echo "Installing service files in /etc"
	@sed -i -e 's|DAEMON=.*|DAEMON=$(JOBMOND)|g' pkg/deb/init.d/jobmond
	@sed -i -e 's|DAEMON=.*|DAEMON=$(JOBARCHIVED)|g' pkg/deb/init.d/jobarchived
	@sed -i -e 's|DAEMON=.*|DAEMON=$(JOBMOND)|g' pkg/rpm/init.d/jobmond
	@sed -i -e 's|DAEMON=.*|DAEMON=$(JOBARCHIVED)|g' pkg/rpm/init.d/jobarchived
	@if test -r /etc/redhat-release; then \
		install -m 0755 -d $(DESTDIR)/etc/rc.d/init.d; \
		install -m 0755 pkg/rpm/init.d/jobmond $(DESTDIR)/etc/rc.d/init.d/; \
		install -m 0755 pkg/rpm/init.d/jobarchived $(DESTDIR)/etc/rc.d/init.d/; \
		install -m 0755 -d $(DESTDIR)/etc/sysconfig; \
		install -m 0755 pkg/rpm/sysconfig/jobmond $(DESTDIR)/etc/sysconfig; \
		install -m 0755 pkg/rpm/sysconfig/jobarchived $(DESTDIR)/etc/sysconfig; \
	else \
		install -m 0755 -d $(DESTDIR)/etc/init.d; \
		install -m 0755 pkg/deb/init.d/jobmond $(DESTDIR)/etc/init.d/; \
		install -m 0755 pkg/deb/init.d/jobarchived $(DESTDIR)/etc/init.d/; \
		install -m 0755 -d $(DESTDIR)/etc/default; \
		install -m 0755 pkg/deb/default/jobmond $(DESTDIR)/etc/default; \
		install -m 0755 pkg/deb/default/jobarchived $(DESTDIR)/etc/default; \
	fi
	@#
	@# Files in /etc
	@#
	@echo
	@echo "Installing config files jobmond.conf jobarchived.conf in /etc"
	@install -m 0644 jobmond/jobmond.conf $(DESTDIR)/etc
	@install -m 0644 jobarchived/jobarchived.conf $(DESTDIR)/etc
	@#
	@# Files in /usr/share
	@#
	@echo
	@echo "Installing job_dbase.sql in $(PREFIX)/share/jobarchived"
	@install -m 0755 -d $(DESTDIR)$(PREFIX)/share/jobarchived
	@install -m 0755 jobarchived/job_dbase.sql $(DESTDIR)$(PREFIX)/share/jobarchived/
	@#
	@# Create the /var/lib/jobarchive directory where rrds are stored.
	@#
	@echo
	@echo "Creating the directory where RRDs will be stored: $(JOBARCHIVE_RRDS)"
	@install -m 0755 -d $(DESTDIR)$(JOBARCHIVE_RRDS)
	@#
	@# Files for ganglia
	@#
	@echo
	@echo "Installing Ganglia web interface to $(GANGLIA_ROOT) ."
	@install -m 0755 -d $(DESTDIR)$(GANGLIA_ROOT)
	@chown -R $(GANGLIA_USER) ./web
	@chown $(HTTPD_USER) ./web/addons/job_monarch/dwoo/compiled
	@chown $(HTTPD_USER) ./web/addons/job_monarch/dwoo/cache
	@chmod 775 ./web/addons/job_monarch/dwoo/cache
	@(cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php ./addons ./templates $(DESTDIR)$(GANGLIA_ROOT)/)
	@#
	@echo
	@echo "Installation complete."
	@echo

clean:
	@rm -rf ${TMPDIR}/.monarch_buildroot
	@(cd ./debian; rm -rf files *.log *.substvars jobmonarch/ jobmonarch-jobmond/ jobmonarch-jobarchived/ jobmonarch-webfrontend/ tmp/)
	@rm -f web/addons/job_monarch/conf.php
