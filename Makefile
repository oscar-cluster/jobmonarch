# Where to build
#
TMPDIR = /tmp
DESTDIR = /usr/local

# Clear this if you don't want to use ${FAKEROOT}
#
FAKEROOT = fakeroot

# What is the location of the Ganglia web frontend
# i.e.: where to we install Job Monarch's web frontend addon
# 
WEBDIR = /var/www/ganglia

VERSION = 1.0
RELEASE = 1

REQUIRED = ./jobarchived ./jobmond ./web

deb:	tarball-bzip
rpm:	tarball-bzip

all:	tarball deb rpm

tarball:	tarball-gzip tarball-bzip

tarball-gzip:	${REQUIRED} rpmspec
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar zcvf ganglia_jobmonarch-${VERSION}.tar.gz ./ganglia_jobmonarch-${VERSION} )
	mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.gz ..

tarball-bzip:	${REQUIRED} rpmspec
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar jcvf ganglia_jobmonarch-${VERSION}.tar.bz2 ./ganglia_jobmonarch-${VERSION} )
	mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.bz2 ..

rpmspec: pkg/rpm/jobmonarch.spec.in
	sed -e 's/__VERSION__/$(VERSION)/g' -e 's/__RELEASE__/$(RELEASE)/g' ./pkg/rpm/jobmonarch.spec.in > jobmonarch.spec

debpkgdir: pkg/deb/debian
	rsync -a --exclude=.svn pkg/deb/debian debian
	sed -i -e 's/^Version:.*$//Version: $(VERSION)-$(RELEASE)/g' ./debian/control

rpm: tarball-bzip
	rpmbuild -tb ../ganglia_jobmonarch-${VERSION}.tar.bz2

deb: debpkgdir
	dpkg-buildpackage -b

install:
	# Files in SBIN_DIR
	install -m 0755 -d $(DESTDIR)/usr/sbin
	install -m 0755 jobmond/jobmond.py $(DESTDIR)/usr/sbin/
	install -m 0755 jobarchived/jobarchived.py $(DESTDIR)/usr/sbin/
	(cd $(DESTDIR)/usr/sbin/; ln -s jobmond.py jobmond; ln -s jobarchived.py jobarchived)
	#
	# Files specific to distros if /etc/sysconfig => rpm else (/etc/default => debian)
	if test -d /etc/sysconfig; then \
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
	#
	# Files in /etc
	#
	install -m 0644 jobmond/jobmond.conf $(DESTDIR)/etc; \
	install -m 0644 jobarchived/jobarchived.conf $(DESTDIR)/etc; \
	#
	# Files in /usr/share
	#
	install -m 0755 -d $(DESTDIR)/usr/share/jobarchived/
	install -m 0755 jobarchived/job_dbase.sql $(DESTDIR)/usr/share/jobarchived/
	#
	# Create the /var/lib/jobarchive directory where rrds are stored.
	#
	install -m 0755 -d $(DESTDIR)/var/lib/jobarchive/
	#
	# Files for ganglia (adapt to rpm or deb or unknown ganglia packaging)
	#
	if test -d /etc/sysconfig; then \
		install -m 0755 -d $(DESTDIR)/usr/share/ganglia; \
		(cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php . $(DESTDIR)/usr/share/ganglia); \
	elif test -d /etc/default; then \
		install -m 0755 -d $(DESTDIR)/usr/share/ganglia-webfrontend; \
		(cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php . $(DESTDIR)/usr/share/ganglia-webfrontend); \
	else \
		install -m 0755 -d $(DESTDIR)/$(WEBDIR); \
		(cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php . $(DESTDIR)/$(WEBDIR)); \
	fi


deb-webfrontend:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${WEBDIR} >/dev/null
	( cd web; rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${WEBDIR} )
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
	rm -rf ./jobmonarch.spec
	rm -rf ./debian
