# Where to build
#
TMPDIR = /tmp

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

deb:	deb-jobmond deb-jobarchived deb-webfrontend
rpm:	rpm-jobmond rpm-jobarchived rpm-webfrontend

all:	tarball deb rpm

tarball:	tarball-gzip tarball-bzip

tarball-gzip:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar zcvf ganglia_jobmonarch-${VERSION}.tar.gz ./ganglia_jobmonarch-${VERSION} )
	mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.gz ..

tarball-bzip:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}
	( rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION} )
	( cd ${TMPDIR}/.monarch_buildroot; tar jcvf ganglia_jobmonarch-${VERSION}.tar.bz2 ./ganglia_jobmonarch-${VERSION} )
	mv ${TMPDIR}/.monarch_buildroot/ganglia_jobmonarch-${VERSION}.tar.bz2 ..

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

rpm-jobmond:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/sysconfig >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/sbin >/dev/null
	install -m 755 jobmond/jobmond.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/sbin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/usr/sbin; \
	ln -s jobmond.py jobmond || true)
	install jobmond/jobmond.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc
	install pkg/rpm/init.d/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/init.d
	install pkg/rpm/sysconfig/jobmond ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}/etc/sysconfig
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
	${FAKEROOT} rpmbuild -bb jobmonarch-jobmond-${VERSION}-${RELEASE}.spec )
	mv ${TMPDIR}/.monarch_buildroot/jobmonarch-jobmond-${VERSION}-${RELEASE}.rpm ..

rpm-jobarchived:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc/init.d >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc/sysconfig >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/sbin >/dev/null
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/share/jobarchived >/dev/null
	install -m 755 jobarchived/jobarchived.py ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/sbin
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/usr/sbin; \
	ln -s jobarchived.py jobarchived || true)
	install jobarchived/jobarchived.conf ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc
	install pkg/rpm/init.d/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc/init.d
	install pkg/rpm/sysconfig/jobarchived ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}/etc/sysconfig
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
	${FAKEROOT} rpmbuild -bb jobmonarch-jobarchived-${VERSION}-${RELEASE}.spec )
	mv ${TMPDIR}/.monarch_buildroot/jobmonarch-jobarchived-${VERSION}-${RELEASE}.rpm ..

rpm-webfrontend:	${REQUIRED}
	mkdir -p ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend-${VERSION}-${RELEASE}/${WEBDIR} >/dev/null
	( cd web; \
	rsync -a --exclude=.svn --exclude=*_test* --exclude=*-example.php \
	. ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend-${VERSION}-${RELEASE}/${WEBDIR} )
	cp pkg/rpm/jobmonarch-webfrontend.spec \
	${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend-${VERSION}-${RELEASE}/jobmonarch-webfrontend-${VERSION}-${RELEASE}.spec
	( cd ${TMPDIR}/.monarch_buildroot/; \
	cat jobmonarch-webfrontend-${VERSION}-${RELEASE}/jobmonarch-webfrontend-${VERSION}-${RELEASE}.spec \
	| sed "s/^Buildroot:.*$//Buildroot: \${TMPDIR}\/\.monarch_buildroot\/jobmonarch-webfrontend-${VERSION}-${RELEASE}/g" \
	| sed "s/^Version:.*$//Version: ${VERSION}/g" \
	| sed "s/^Release:.*$//Release: ${RELEASE}/g" \
	| sed "s+/var/www/ganglia+${WEBDIR}+g" \
	>jobmonarch-webfrontend-${VERSION}-${RELEASE}/jobmonarch-webfrontend-${VERSION}-${RELEASE}.spec.new; \
	mv jobmonarch-webfrontend-${VERSION}-${RELEASE}/jobmonarch-webfrontend-${VERSION}-${RELEASE}.spec.new \
	jobmonarch-webfrontend-${VERSION}-${RELEASE}/jobmonarch-webfrontend-${VERSION}-${RELEASE}.spec )
	( cd ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend-${VERSION}-${RELEASE}; \
	${FAKEROOT} rpmbuild -bb jobmonarch-webfrontend-${VERSION}-${RELEASE}.spec )
	mv ${TMPDIR}/.monarch_buildroot/jobmonarch-webfrontend-${VERSION}-${RELEASE}.rpm ..

clean:	${TMPDIR}/.monarch_buildroot
	rm -rf ${TMPDIR}/.monarch_buildroot
