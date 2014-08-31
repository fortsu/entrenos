<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:strip-space elements="*"/>
<xsl:template match="Activities">
<gpx xmlns="http://www.topografix.com/GPX/1/1" creator="FortSu http://www.fortsu.com" version="1.1" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd"
    xmlns:gpxdata="http://www.cluetrust.com/XML/GPXDATA/1/0">
    <metadata>
        <name><xsl:value-of select="Activity/@Sport"/></name>
        <time><xsl:value-of select="Activity/Id"/></time>
        <extensions>
            <gpxdata:sport><xsl:value-of select="Activity/@Sport"/></gpxdata:sport>
        </extensions>
    </metadata>
    <trk>
        <trkseg>
            <xsl:for-each select="Activity/Lap">
                <xsl:for-each select="Track/Trackpoint">
                <!-- I am aware can be done in a more elegant way when no position is available -->
                    <xsl:choose>
                        <xsl:when test="Position">
                            <trkpt lat="{Position/LatitudeDegrees}" lon="{Position/LongitudeDegrees}">
                                <ele><xsl:value-of select="AltitudeMeters"/></ele>
                                <time><xsl:value-of select="Time"/></time>
			        	        <xsl:if test="HeartRateBpm">
	                                <extensions>
        	                        <gpxdata:hr><xsl:value-of select="HeartRateBpm/Value"/></gpxdata:hr>
        	                        </extensions>
			        	        </xsl:if>
			        	        <xsl:if test="Cadence">
	                                <extensions>
        	                            <gpxdata:cadence><xsl:value-of select="."/></gpxdata:cadence>
        	                        </extensions>
			        	        </xsl:if>
                            </trkpt>
                        </xsl:when>
                        <xsl:otherwise>
                        <!-- Allowing GPX files (yep, no valid) with no position data: exercise bikes, etc. -->
                            <trkpt lat="" lon="">
                                <ele><xsl:value-of select="AltitudeMeters"/></ele>
                                <time><xsl:value-of select="Time"/></time>
			        	        <xsl:if test="HeartRateBpm">
	                                <extensions>
        	                        <gpxdata:hr><xsl:value-of select="HeartRateBpm/Value"/></gpxdata:hr>
        	                        </extensions>
			        	        </xsl:if>
			        	        <xsl:if test="Cadence">
	                                <extensions>
        	                            <gpxdata:cadence><xsl:value-of select="."/></gpxdata:cadence>
        	                        </extensions>
			        	        </xsl:if>
                            </trkpt>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:for-each>
            </xsl:for-each>
        </trkseg>
    </trk>
	<extensions>
    <xsl:for-each select="Activity/Lap">
        <xsl:variable name="vIndex">
            <xsl:number count="Lap"/>
        </xsl:variable>
		<gpxdata:lap>
			<gpxdata:index><xsl:value-of select="$vIndex"/></gpxdata:index>
            <gpxdata:startPoint lat="{Track/Trackpoint[1]/Position/LatitudeDegrees}" lon="{Track/Trackpoint[1]/Position/LongitudeDegrees}"/>
			<xsl:variable name="cnt"><xsl:value-of select="count(Track/Trackpoint/Position)-1"/></xsl:variable>
      		<gpxdata:endPoint lat="{Track/Trackpoint[number($cnt)]/Position/LatitudeDegrees}" lon="{Track/Trackpoint[number($cnt)]/Position/LongitudeDegrees}"/>
			<gpxdata:startTime><xsl:value-of select="@StartTime"/></gpxdata:startTime>
			<gpxdata:elapsedTime><xsl:value-of select="TotalTimeSeconds"/></gpxdata:elapsedTime>
			<gpxdata:calories><xsl:value-of select="Calories"/></gpxdata:calories>
			<gpxdata:distance><xsl:value-of select="DistanceMeters"/></gpxdata:distance>
			<gpxdata:summary name="MaximumSpeed" kind="max"><xsl:value-of select="MaximumSpeed"/></gpxdata:summary>
			<gpxdata:summary name="AverageHeartRateBpm" kind="avg"><xsl:value-of select="AverageHeartRateBpm/Value"/></gpxdata:summary>
			<gpxdata:summary name="MaximumHeartRateBpm" kind="max"><xsl:value-of select="MaximumHeartRateBpm/Value"/></gpxdata:summary>
			<gpxdata:trigger kind="{translate(TriggerMethod, $uppercase, $smallcase)}"/>
			<gpxdata:intensity><xsl:value-of select="translate(Intensity, $uppercase, $smallcase)"/></gpxdata:intensity>
		</gpxdata:lap>
    </xsl:for-each>
    </extensions>
</gpx>
</xsl:template>
<xsl:variable name="smallcase" select="'abcdefghijklmnopqrstuvwxyz'" />
<xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />
<xsl:template match="Author">
</xsl:template>
</xsl:stylesheet>
