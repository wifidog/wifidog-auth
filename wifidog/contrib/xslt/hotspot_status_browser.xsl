<?xml version="1.0" encoding="UTF-8"?>
<!-- WifiDog - Hotspot Status XSLT - Browser view - Contribution by François Proulx <francois.proulx@gmail.com>-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output indent="yes" method="html" encoding="UTF-8"/>
    <xsl:template match="/">
        <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
            </head>
            <body>
                <table>
                    <thead>
                        <tr>
                            <th/>
                            <th>Hotspot / Point d'accès</th>
                            <th>Description</th>
                            <th>Location / Emplacement</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="wifidogHotspotsStatus/hotspots"/>
                </table>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="hotspots">
        <xsl:for-each select="hotspot">
            <xsl:sort select="name"/>
            <tr>
                <td>
                    <xsl:variable name="globalStatus" select="globalStatus"/>
                    <xsl:choose>
                        <xsl:when test="$globalStatus = '100'">
                            <img src="http://auth.ilesansfil.org/media/common_images/HotspotStatus/up.gif" alt="Up"/>
                        </xsl:when>
                        <xsl:when test="$globalStatus = '0'">
                            <img src="http://auth.ilesansfil.org/media/common_images/HotspotStatus/down.gif" alt="Down"/>
                        </xsl:when>
                        <xsl:otherwise>
			  <b><xsl:text>?</xsl:text></b>
                        </xsl:otherwise>
                    </xsl:choose>
                </td>
                <td>
                    <xsl:variable name="webSiteUrl" select="webSiteUrl"/>
                    <xsl:choose>
                        <xsl:when test="string-length($webSiteUrl) &gt;= 1">
                            <a href="{$webSiteUrl}"><xsl:value-of select="name"/></a>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="name"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </td>
                <td>
                    <xsl:value-of select="description"/>
                </td>
                <td>
		    <xsl:value-of select="civicNumber"/>
		    <xsl:text> </xsl:text>
                    <xsl:value-of select="streetAddress"/>
		    <br/>
		    <xsl:value-of select="city"/>,
		    <xsl:value-of select="province"/>,
		    <xsl:value-of select="country"/>
                    <xsl:variable name="mapUrl" select="mapUrl"/>
                    <xsl:if test="string-length($mapUrl) &gt;= 1">
                        <xsl:text> - </xsl:text>
                        <a href="{$mapUrl}">Map</a>
                    </xsl:if>
                    <br/>
                    <xsl:value-of select="massTransitInfo"/>
                    <br/>
                    <xsl:value-of select="contactPhoneNumber"/>
                    <br/>
                </td>
            </tr>
        </xsl:for-each>
    </xsl:template>
</xsl:stylesheet>
