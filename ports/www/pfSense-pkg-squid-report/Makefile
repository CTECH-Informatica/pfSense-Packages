
PORTNAME=	pfSense-pkg-squid-report
PORTVERSION=	1.0.0
CATEGORIES=	www
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	contato@ctech.net.br
COMMENT=	CTECH package squid-report
WWW=	https://github.com/CTECH-Informatica/pfSense-Packages/blob/main/ports/www/pfSense-pkg-squid-report

LICENSE=	APACHE20

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/www/squid_report
	${MKDIR} ${STAGEDIR}${PREFIX}/www/squid_report/assets
	${MKDIR} ${STAGEDIR}${PREFIX}/www/squid_report/assets/css
	${MKDIR} ${STAGEDIR}${PREFIX}/www/squid_report/assets/css/fonts
	${MKDIR} ${STAGEDIR}${PREFIX}/www/squid_report/assets/js
	${MKDIR} ${STAGEDIR}${PREFIX}/www/squid_report/assets/images
	${MKDIR} ${STAGEDIR}${PREFIX}/etc/squid_report
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/squid-report.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/squid-report.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/squid-report-util.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/squidreport.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/index.php \
		${STAGEDIR}${PREFIX}/www/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/ips.php \
		${STAGEDIR}${PREFIX}/www/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/report.php \
		${STAGEDIR}${PREFIX}/www/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/users.php \
		${STAGEDIR}${PREFIX}/www/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/js/axios.min.js \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/js
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/js/bootstrap.bundle.min.js \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/js
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/js/jquery.min.js \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/js
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/js/report.js \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/js
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/css/bootstrap-icons.min.css \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/css
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/css/bootstrap.min.css \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/css
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/css/report.css \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/css
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/css/fonts/bootstrap-icons.woff \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/css/fonts
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/css/fonts/bootstrap-icons.woff2 \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/css/fonts
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/images/favicon.ico \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/images
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squid_report/assets/images/logo.png \
		${STAGEDIR}${PREFIX}/www/squid_report/assets/images
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/etc/squid_report/parser.php \
		${STAGEDIR}${PREFIX}/etc/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/etc/squid_report/optimize_db.php \
		${STAGEDIR}${PREFIX}/etc/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/etc/squid_report/remove_old.php \
		${STAGEDIR}${PREFIX}/etc/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/etc/squid_report/squid_report.inc.php \
		${STAGEDIR}${PREFIX}/etc/squid_report
	${INSTALL_DATA} -m 0644 ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${FILESDIR}${PREFIX}/pkg/squid-report.xml

.include <bsd.port.mk>
