PORTNAME=		pam_mkhomedir
PORTVERSION=	1.0
PORTREVISION=	1
CATEGORIES=		security
COMMENT=		Create HOME with a PAM module on demand
MAINTAINER=		zoon01@xigmanas.com

# No files are needed to be fetched.
DISTFILES?=
# No build is required for this port.
NO_BUILD?=		 yes
# Don't register a port installation as a package.
NO_PKG_REGISTER?=	 yes
# Set default target to execute when a port is calling a dependency.
#ALWAYS_BUILD_DEPENDS= yes
DEPENDS_TARGET=	 clean build

BUILD_DEPENDS=	${NONEXISTENT}:security/pam_mkhomedir

do-install:
# XigmaNAS® install
	@echo "Start install to ${XIGMANAS_ROOTFS}"
	@${INSTALL_PROGRAM} -v ${PORTSDIR}/security/pam_mkhomedir/work/pam_mkhomedir-*/pam_mkhomedir.so ${XIGMANAS_ROOTFS}/usr/local/lib
# XigmaNAS® end install
	@echo "End install to ${XIGMANAS_ROOTFS}"

.include <bsd.port.mk>
