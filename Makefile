TMPDIR = /tmp
WEBDIR = var/www/ganglia

VERSION = 0.2.1
RELEASE = 1

REQUIRED = ./jobarchived ./jobmond ./web

debian:	deb-jobmond deb-jobarchived deb-webfrontend

rpm: rpm-jobmond rpm-jobarchived

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
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${WEBDIR} >/dev/null
	( cd web; rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/${WEBDIR} )
	( cd pkg/deb/web/DEBIAN; \
	rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control \
	| sed "s/^Version:.*$//Version: ${VERSION}-${RELEASE}/g" >jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control.new; \
	mv jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control.new \
	jobmonarch-webfrontend_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; fakeroot dpkg -b jobmonarch-webfrontend_${VERSION}-${RELEASE} )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend_${VERSION}-${RELEASE}.deb .

deb-jobarchived:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/bin >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/share/jobarchived >/dev/null
	install -m 755 jobarchived/jobarchived.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/bin; \
	ln -s jobarchived.py jobarchived || true)
	install jobarchived/jobarchived.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc
	install pkg/init.d/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/etc/init.d
	install jobarchived/job_dbase.sql \
	${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/usr/share/jobarchived
	( cd pkg/deb/jobarchived/DEBIAN; \
	rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control \
	| sed "s/^Version:.*$//Version: ${VERSION}-${RELEASE}/g" >jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control.new; \
	mv jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control.new \
	jobmonarch-jobarchived_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; fakeroot dpkg -b jobmonarch-jobarchived_${VERSION}-${RELEASE} )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived_${VERSION}-${RELEASE}.deb .

deb-jobmond:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/bin >/dev/null
	install -m 755 jobmond/jobmond.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/usr/bin; \
	ln -s jobmond.py jobmond || true)
	install jobmond/jobmond.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc
	install pkg/init.d/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/etc/init.d
	( cd pkg/deb/jobmond/DEBIAN; \
	rsync -a --exclude=.svn . ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN )
	( cd ${TMPDIR}/.monarch_buildroot/; cat jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control \
	| sed "s/^Version:.*$//Version: ${VERSION}-${RELEASE}/g" >jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control.new; \
	mv jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control.new jobmonarch-jobmond_${VERSION}-${RELEASE}/DEBIAN/control )
	( cd ${TMPDIR}/.monarch_buildroot/; fakeroot dpkg -b jobmonarch-jobmond_${VERSION}-${RELEASE} )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond_${VERSION}-${RELEASE}.deb .

rpm-jobmond:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/bin >/dev/null
	install -m 755 jobmond/jobmond.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/bin; \
	ln -s jobmond.py jobmond || true)
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

rpm-jobarchived:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/bin >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/share/jobarchived >/dev/null
	install -m 755 jobarchived/jobarchived.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/bin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/bin; \
	ln -s jobarchived.py jobarchived || true)
	install jobarchived/jobarchived.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc
	install pkg/init.d/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc/init.d
	install jobarchived/job_dbase.sql \
	${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/share/jobarchived
	cp pkg/rpm/jobmonarch-jobarchived.spec \
	${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec
	( cd ${TMPDIR}/.monarch_buildroot/; \
	cat jobmonarch-jobarchived-${VERSION}-${RELEASE}/jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec \
	| sed "s/^Buildroot:.*$//Buildroot: \${TMPDIR}\/\.monarch_buildroot\/jobmonarch-jobarchived-${VERSION}-${RELEASE}/g" \
	| sed "s/^Version:.*$//Version: ${VERSION}/g" \
	| sed "s/^Release:.*$//Release: ${RELEASE}/g" \
	>jobmonarch-jobarchived-${VERSION}-${RELEASE}/jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec.new; \
	mv jobmonarch-jobarchived-${VERSION}-${RELEASE}/jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec.new \
	jobmonarch-jobarchived-${VERSION}-${RELEASE}/jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec )
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}; \
	fakeroot rpmbuild -bb jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec )
	cp ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}.*.rpm .

clean:	${TMPDIR}/.monarch_buildroot
	rm -rf ${TMPDIR}/.monarch_buildroot
