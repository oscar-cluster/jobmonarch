TMPDIR = /tmp
WEBDIR = /var/www/ganglia

VERSION = 0.2.1
RELEASE = 1

OPWD = `pwd`

REQUIRED = ./jobarchived ./jobmond ./web

debian:	debian-jobmond

#rpm-binary:

#all:	tarball debian-binary rpm-binary

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

debian-jobmond:	${REQUIRED}
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

clean:	${TMPDIR}/.monarch_buildroot
	rm -rf ${TMPDIR}/.monarch_buildroot
