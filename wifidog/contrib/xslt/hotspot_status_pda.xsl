<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--  WifiDog - Hotspot Status XSLT - Browser view - Contribution by Jean-Pierre Lessard <jplprog@videotron.ca> -->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output indent="yes" method="html" encoding="ISO-8859-1"/>
    <xsl:template match="/">
        <xsl:variable name="urlSite" select="www.jplprog.com"/>
        <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1"/>
            </head>
            <body>
   	        <hr/>
            	<td>
              	<img style="width: 600px; height: 100px;" src="http://www.jplprog.com/isf/logo.gif" border="0"/>
            	</td>
            	<hr/>
	    	<b>Version 1.0 du 24 septembre 2005 par <a href="http://www.jplprog.com/dokuwiki/doku.php?id=hotspotsisf">Jean-Pierre Lessard</a></b><br />
                <table>
                    <thead>
			<tr>
			    Operationnel <img src="http://www.jplprog.com/isf/hotspots_status_map_up.png" alt="Up"/>
			    Hors service <img src="http://www.jplprog.com/isf/hotspots_status_map_down.png" alt="Down"/>
                            Non surveille <img src="http://www.jplprog.com/isf/hotspots_status_map_unknown.png" alt="Unknown"/>
                        </tr>
			<hr/>
                    </thead>
                    <xsl:apply-templates select="wifidogHotspotsStatus/hotspots"/>
                </table>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="hotspots">
        <xsl:variable name="numero">1</xsl:variable>
        <xsl:for-each select="hotspot">
            <xsl:sort select="name"/>
            <tr>
                <td>
                    <xsl:variable name="globalStatus" select="globalStatus"/>
                    <xsl:choose>
                        <xsl:when test="$globalStatus = '100'">
                            <img src="http://www.jplprog.com/isf/hotspots_status_map_up.png" alt="Up"/>
                        </xsl:when>
                        <xsl:when test="$globalStatus = '0'">
                            <img src="http://www.jplprog.com/isf/hotspots_status_map_down.png" alt="Down"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <img src="http://www.jplprog.com/isf/hotspots_status_map_unknown.png" alt="Unknown"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </td>
                <td>
                    <xsl:variable name="webSiteUrl" select="webSiteUrl"/>
                    <xsl:choose>
                        <xsl:when test="string-length($webSiteUrl) &gt;= 1">
                            <xsl:value-of select="name"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="name"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </td>
                <td>
                    <xsl:value-of select="streetAddress"/>
                    <br/>
                    <xsl:value-of select="massTransitInfo"/>
                    <br/>
                    <xsl:value-of select="contactPhoneNumber"/>
		    <br/>
                    <xsl:text> No : </xsl:text>
           	    <xsl:value-of select="$numero"/>
                    <br/>
                    <xsl:text> Id : </xsl:text>
		    <xsl:value-of select="hotspotId"/>
                    <br/>
                    <xsl:variable name="hotspotId" select="hotspotId"/>
                    <a href="http://www.jplprog.com/isf/info.php?id={$hotspotId}">Informations</a>
                    <br/>
                    <hr/>
                </td>
            </tr>
         </xsl:for-each>
    </xsl:template>
</xsl:stylesheet>
