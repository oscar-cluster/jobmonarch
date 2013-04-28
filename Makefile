# Where to build
#
TMPDIR = /tmp

# Where to install
DESTDIR = 

# Install prefix
PREFIX = /usr/local

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

VERSION = 1.0
RELEASE = 1

REQUIRED = ./jobarchived ./jobmond ./web

deb:	tarball-bzip
rpm:	tarball-bzip

all:	tarball deb rpm

tarball:	tarball-gzip tarball-bzip

tarball-gzip:	${REQUIRED} ./pkg/rpm/jobmonarch.spec
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar zcvf ganglia_jobmonarch-${VERSION}.tar.gz ./ganglia_jobmonarch-${VERSION} )
	mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.gz ..

tarball-bzip:	${REQUIRED} ./pkg/rpm/jobmonarch.spec
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar jcvf ganglia_jobmonarch-${VERSION}.tar.bz2 ./ganglia_jobmonarch-${VERSION} )
	mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.bz2 ..

rpmspec: ./pkg/rpm/jobmonarch.spec

./pkg/rpm/jobmonarch.spec: pkg/rpm/jobmonarch.spec.in
	sed -e 's/__VERSION__/$(VERSION)/g' -e 's/__RELEASE__/$(RELEASE)/g' ./pkg/rpm/jobmonarch.spec.in > ./pkg/rpm/jobmonarch.spec

rpm: tarball-bzip
	rpmbuild -tb ../ganglia_jobmonarch-${VERSION}.tar.bz2

srpm: tarball-bzip
	rpmbuild -ts --define '%dist %{nil}' ../ganglia_jobmonarch-${VERSION}.tar.bz2

deb: ${REQUIRED} ./debian
	# FIXME set $(VERSION)-$(RELEASE) in ./debian/changelog
	dpkg-buildpackage -b -uc -us

install:
	@#
	@# Set the correct GANGLIA_PATH.
	@#
	@echo "\nUsing $(GANGLIA_ROOT) as Ganglia root installation path. If it's not what"
	@echo "you want, use make GANGLIA_ROOT=/path/to/your/ganglia/root ."
	@sed -i -e 's|/var/www/ganglia/|$(GANGLIA_ROOT)/|g' web/addons/job_monarch/conf.php
	@#
	@# Set the correct JOBARCHIVE_RRDS in jobarchve.conf and ganglia conf.php
	@#
	@echo "\nUsing $(JOBARCHIVE_RRDS) as jobarchive path to  store rrds files. If it's not what"
	@echo "you want, use make JOBARCHIVE_RRDS=/path/to/you/jobarchived/rrdsfiles ."
	@sed -i -e 's|/var/lib/jobarchive|$(JOBARCHIVE_RRDS)|g' jobarchived/jobarchived.conf web/addons/job_monarch/conf.php
	@#
	@# Files in SBIN_DIR
	@#
	@echo "\nInstalling jobmond.py and jobarchived.py to $(PREFIX)/sbin"
	@install -m 0755 -d $(DESTDIR)$(PREFIX)/sbin
	@install -m 0755 jobmond/jobmond.py $(DESTDIR)$(PREFIX)/sbin/
	@install -m 0755 jobarchived/jobarchived.py $(DESTDIR)$(PREFIX)/sbin/
	@(cd $(DESTDIR)$(PREFIX)/sbin/; ln -s jobmond.py jobmond; ln -s jobarchived.py jobarchived)
	@#
	@# Files specific to distros if /etc/sysconfig => rpm else (/etc/default => debian)
	@#
	@echo "\nInstalling service files in $(DESTDIR)/etc"
	@if test -d /etc/sysconfig; then \
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
	@echo "\nInstalling config files jobmond.conf jobarchived.conf in $(DESTDIR)/etc"
	@install -m 0644 jobmond/jobmond.conf $(DESTDIR)/etc
	@install -m 0644 jobarchived/jobarchived.conf $(DESTDIR)/etc
	@#
	@# Files in /usr/share
	@#
	@echo "\nInstalling job_dbase.sql in $(PREFIX)/share/jobarchived"
	@install -m 0755 -d $(DESTDIR)$(PREFIX)/share/jobarchived
	@install -m 0755 jobarchived/job_dbase.sql $(DESTDIR)$(PREFIX)/share/jobarchived/
	@#
	@# Create the /var/lib/jobarchive directory where rrds are stored.
	@#
	@echo "\nCreating the directory where RRDs will be stored: $(JOBARCHIVE_RRDS)"
	@install -m 0755 -d $(DESTDIR)$(JOBARCHIVE_RRDS)
	@#
	@# Files for ganglia
	@#
	@echo "\nInstalling Ganglia web interface to $(GANGLIA_ROOT) ."
	@install -m 0755 -d $(DESTDIR)$(GANGLIA_ROOT)
	@chown -R $(GANGLIA_USER) ./web
	@chown $(HTTPD_USER) ./web/addons/job_monarch/dwoo/compiled
	@(cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php . $(DESTDIR)$(GANGLIA_ROOT)/)
	@#
	@echo "\nInstallation complete.\n"

deb-webfrontend:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${GANGLIA_ROOT} >/dev/null
	( cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${GANGLIA_ROOT} )
	( cd pkg/deb/web/DEBIAN; \
	rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control \
	| sed "s/^Version:.*$//Version: ${VERSION}-${RELEASE}/g" >jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control.new; \
	mv jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control.new \
	jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; ${FAKEROOT} dpkg -b jobmonarch-webfrontend_${VERSION}-${RELEASE} )
	mv ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}.deb ..

deb-jobarchived:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/default >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/sbin >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/share/jobarchived >/dev/null
	install -m 755 jobarchived/jobarchived.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/sbin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/sbin; \
	ln -s jobarchived.py jobarchived || true)
	install jobarchived/jobarchived.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc
	install pkg/deb/init.d/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/init.d
	install pkg/deb/default/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/default
	install jobarchived/job_dbase.sql \
	${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/share/jobarchived
	( cd pkg/deb/jobarchived/DEBIAN; \
	rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control \
	| sed "s/^Version:.*$//Version: ${VERSION}-${RELEASE}/g" >jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control.new; \
	mv jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control.new \
	jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; ${FAKEROOT} dpkg -b jobmonarch-jobarchived_${VERSION}-${RELEASE} )
	mv ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}.deb ..

deb-jobmond:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/default >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/sbin >/dev/null
	install -m 755 jobmond/jobmond.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/sbin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/sbin; \
	ln -s jobmond.py jobmond || true)
	install jobmond/jobmond.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc
	install pkg/deb/init.d/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/init.d
	install pkg/deb/default/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/default
	( cd pkg/deb/jobmond/DEBIAN; \
	rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control \
	| sed "s/^Version:.*$//Version: ${VERSION}-${RELEASE}/g" >jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control.new; \
	mv jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control.new jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; ${FAKEROOT} dpkg -b jobmonarch-jobmond_${VERSION}-${RELEASE} )
	mv ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}.deb ..

clean:
	rm -rf ${TMPDIR}/.monarch_buildroot
	rm -rf ./pkg/rpm/jobmonarch.spec
	rm -rf ./debian/{files,*.log,jobmonarch-jobmond/,jobmonarch-jobarchived/,jobmonarch-webfrontend/,tmp/}
