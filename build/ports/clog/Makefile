PORTNAME=		clog
PORTVERSION=	1.0
CATEGORIES=		sysutils
COMMENT=		Circular log file tool
MAINTAINER=		zoon01@xigmanas.com

DISTFILES?=

NO_PKG_REGISTER=yes

.include <bsd.port.pre.mk>
do-build:
.if ${OSVERSION} >= 1200000
	@${TAR} -zxvf ${FILESDIR}/syslogd-clog-11.3.tar.gz -C /usr/src/usr.bin
	@${TAR} -zxvf ${FILESDIR}/clog-1.0.1-11.1.tar.gz -C /usr/src/usr.bin
.elif ${OSVERSION} >= 1102000
	@${TAR} -zxvf ${FILESDIR}/syslogd-clog-11.2.tar.gz -C /usr/src/usr.bin
	@${TAR} -zxvf ${FILESDIR}/clog-1.0.1-11.1.tar.gz -C /usr/src/usr.bin
.elif ${OSVERSION} >= 1101000
	@${TAR} -zxvf ${FILESDIR}/syslogd-clog-11.1.tar.gz -C /usr/src/usr.bin
	@${TAR} -zxvf ${FILESDIR}/clog-1.0.1-11.1.tar.gz -C /usr/src/usr.bin
.elif ${OSVERSION} >= 1100000
	@${TAR} -zxvf ${FILESDIR}/syslogd-clog-11.tar.gz -C /usr/src/usr.bin
	@${TAR} -zxvf ${FILESDIR}/clog-1.0.1-11.tar.gz -C /usr/src/usr.bin
.elif ${OSVERSION} >= 1000000
	@${TAR} -zxvf ${FILESDIR}/syslogd-clog-10.tar.gz -C /usr/src/usr.bin
	@${TAR} -zxvf ${FILESDIR}/clog-1.0.1-11.tar.gz -C /usr/src/usr.bin
.else
	@${TAR} -zxvf ${FILESDIR}/syslogd-clog.tar.gz -C /usr/src/usr.bin
	@${TAR} -zxvf ${FILESDIR}/clog-1.0.1.tar.gz -C /usr/src/usr.bin
.endif

	@${MAKE} -C /usr/src/usr.bin/syslogd
	@${MAKE} -C /usr/src/usr.bin/clog

do-install:
# XigmaNAS® Install
	@echo "Start install to ${XIGMANAS_ROOTFS}"
	@${INSTALL_PROGRAM} -v /usr/src/usr.bin/syslogd/syslogd ${XIGMANAS_ROOTFS}/usr/sbin
	@${INSTALL_PROGRAM} -v /usr/src/usr.bin/clog/clog ${XIGMANAS_ROOTFS}/usr/sbin
# XigmaNAS® end install
	@echo "End install to ${XIGMANAS_ROOTFS}"

.include <bsd.port.post.mk>
