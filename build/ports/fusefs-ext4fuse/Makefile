PORTNAME=		ext4fuse
PORTVERSION=	0.1.3
DISTVERSIONPREFIX=	v
PORTREVISION=	1
PORTEPOCH=	1
CATEGORIES=	sysutils
PKGNAMEPREFIX=	fusefs-

MAINTAINER=	zoon01@xigmanas.com
COMMENT=	Read-only ext4 implementation for FUSE

LICENSE=	GPLv2

USES=		fuse pkgconfig gmake
USE_GITHUB=	yes
GH_ACCOUNT=	gerard

# explicitly empty to fix a cross compiling related issue (PR: 165480)
MAKE_ENV+=	TARGET_ARCH=

ALL_TARGET=	${PORTNAME}

PLIST_FILES=	bin/ext4fuse

do-install:
	${INSTALL_PROGRAM} ${WRKSRC}/${PORTNAME} ${STAGEDIR}${PREFIX}/bin

# XigmaNAS® Install
	@echo "Start install to ${XIGMANAS_ROOTFS}"
	@${INSTALL_PROGRAM} -v ${STAGEDIR}${PREFIX}/bin/ext4fuse ${XIGMANAS_ROOTFS}/usr/local/bin
# XigmaNAS® End install
	@echo "End install to ${XIGMANAS_ROOTFS}"

.include <bsd.port.mk>
