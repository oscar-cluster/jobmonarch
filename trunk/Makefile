TMPDIR = /tmp
WEBDIR = var/www/ganglia

VERSION = 0.2.1
RELEASE = 1

OPWD = `pwd`

REQUIRED = ./jobarchived ./jobmond ./web

debian:	deb-jobmond deb-jobarchived deb-webfrontend

rpm: rpm-jobmond

all:	tarball debian rpm

tarball:	tarball-gzip tarball-bzip

tarball-gzip:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar zcvf ganglia_jobmonarch-${VERSION}.tar.gz ./ganglia_jobmonarch-${VERSION} )
	cp ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.gz .

tarball-bzip:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar jcvf ganglia_jobmonarch-${VERSION}.tar.bz2 ./ganglia_jobmonarch-${VERSION} )
	cp ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.bz2 .

deb-webfrontend:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${WEBDIR}>/dev/null >/dev/null
	( cd web; rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${WEBDIR} )
	( cp -a pkg/deb/web/DEBIAN ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/ )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control | sed "s/^Version:.*$//Version: 0.2.1-1/g" >jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control.new; mv jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control.new jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; fakeroot dpkg -b jobmonarch-webfrontend_${VERSION}-${RELEASE} )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}.deb .

deb-jobarchived:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}>/dev/null >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/bin >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/share/jobarchived >/dev/null
	install -m 755 jobarchived/jobarchived.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/bin; ln -s jobarchived.py jobarchived )
	install jobarchived/jobarchived.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc
	install pkg/init.d/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/init.d
	install jobarchived/job_dbase.sql ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/share/jobarchived
	( cp -a pkg/deb/jobarchived/DEBIAN ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/ )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control | sed "s/^Version:.*$//Version: 0.2.1-1/g" >jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control.new; mv jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control.new jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; fakeroot dpkg -b jobmonarch-jobarchived_${VERSION}-${RELEASE} )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}.deb .

deb-jobmond:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}>/dev/null >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/bin >/dev/null
	install -m 755 jobmond/jobmond.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/bin; ln -s jobmond.py jobmond )
	install jobmond/jobmond.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc
	install pkg/init.d/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/init.d
	( cp -a pkg/deb/jobmond/DEBIAN ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/ )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control | sed "s/^Version:.*$//Version: 0.2.1-1/g" >jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control.new; mv jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control.new jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; fakeroot dpkg -b jobmonarch-jobmond_${VERSION}-${RELEASE} )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}.deb .

rpm-jobmond:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}>/dev/null >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/bin >/dev/null
	install -m 755 jobmond/jobmond.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/bin; ln -s jobmond.py jobmond || true)
	install jobmond/jobmond.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc
	install pkg/init.d/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/init.d
	cp pkg/rpm/jobmonarch-jobmond.spec \
	${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/jobmonarch-jobmond-${VERSION}-${RELEASE}.spec
	( cd ${TMPDIR}/.monarch_buildroot/; \
	cat jobmonarch-jobmond-${VERSION}-${RELEASE}/jobmonarch-jobmond-${VERSION}-${RELEASE}.spec \
	| sed "s/^Buildroot:.*$//Buildroot: \${TMPDIR}\/\.monarch_buildroot\/jobmonarch-jobmond-${VERSION}-${RELEASE}/g" \
	| sed "s/^Version:.*$//Version: ${VERSION}/g" \
	| sed "s/^Release:.*$//Release: ${RELEASE}/g" \
	>jobmonarch-jobmond-${VERSION}-${RELEASE}/jobmonarch-jobmond-${VERSION}-${RELEASE}.spec.new; \
	mv jobmonarch-jobmond-${VERSION}-${RELEASE}/jobmonarch-jobmond-${VERSION}-${RELEASE}.spec.new \
	jobmonarch-jobmond-${VERSION}-${RELEASE}/jobmonarch-jobmond-${VERSION}-${RELEASE}.spec )
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}; \
	fakeroot rpmbuild -bb jobmonarch-jobmond-${VERSION}-${RELEASE}.spec )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}.*.rpm .

clean:	${TMPDIR}/.monarch_buildroot
	rm -rf ${TMPDIR}/.monarch_buildroot
