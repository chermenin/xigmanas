PORTNAME=			cdialog
PORTVERSION=	1.3.20230209
PORTEPOCH=	1
CATEGORIES=			devel
COMMENT=			An enhanced version of 'dialog' to work with ncurses
MAINTAINER=		zoon01@xigmanas.com

MASTER_SITES=	ftp://ftp.invisible-island.net/${PORTNAME:S|^c||}/
DISTNAME=			${PORTNAME:S|^c||}-${PORTVERSION:R}-${PORTVERSION:E}

EXTRACT_SUFX=	.tgz
NO_PKG_REGISTER=yes
USES=           libtool:build
GNU_CONFIGURE=		yes
GNU_CONFIGURE_MANPREFIX=${PREFIX}/share
USE_LDCONFIG=			yes
CONFIGURE_ARGS=		--enable-widec \
		--includedir=${PREFIX}/include/${PORTNAME} \
			--with-libtool \
			--with-ncursesw \
			--with-package=${PORTNAME}
MAKEFILE=	makefile

post-patch:
	@${FIND} ${WRKSRC}/samples/ -type f -exec ${REINPLACE_CMD} -i "" "s|dialog|cdialog|g" {} \;

do-install:
# XigmaNAS® Install
	@echo "Start install to ${XIGMANAS_ROOTFS}"
	@${INSTALL_PROGRAM} -v ${WRKSRC}/.libs/cdialog ${XIGMANAS_ROOTFS}${PREFIX}/bin/cdialog
	@${INSTALL_PROGRAM} -v ${WRKSRC}/.libs/libcdialog.so.* ${XIGMANAS_ROOTFS}${PREFIX}/lib
# XigmaNAS® end install
	@echo "End install to ${XIGMANAS_ROOTFS}"

.include <bsd.port.mk>