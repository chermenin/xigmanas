PORTNAME=		smartmontools
PORTVERSION=	7.4
PORTREVISION?=	0
CATEGORIES=		sysutils
COMMENT=		S.M.A.R.T. disk monitoring tools
MAINTAINER=		zoon01@xigmanas.com

MASTER_SITES=		SF
CONFLICTS=		smartmontools-devel-[0-9]*

USE_GMAKE=		yes
NO_PKG_REGISTER=yes

GNU_CONFIGURE=	yes
CONFIGURE_ARGS=	--docdir=${DOCSDIR} --disable-sample \
			--with-nvme-devicescan=yes \
			--disable-dependency-tracking


CFLAGS:=		${CFLAGS:S/-O2/-O/} -Wno-write-strings


do-install:
# XigmaNAS® install
	@echo "Start install to ${XIGMANAS_ROOTFS}"
	@${INSTALL_PROGRAM} -v ${WRKSRC}/smartctl ${XIGMANAS_ROOTFS}/usr/local/sbin
	@${INSTALL_PROGRAM} -v ${WRKSRC}/smartd ${XIGMANAS_ROOTFS}/usr/local/sbin
	@${CP} -pv ${WRKSRC}/smartd_warning.sh ${XIGMANAS_ROOTFS}${PREFIX}/etc
# XigmaNAS® end install
	@echo "End install to ${XIGMANAS_ROOTFS}"

.include <bsd.port.mk>