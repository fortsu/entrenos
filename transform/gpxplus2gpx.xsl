<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" encoding="UTF-8"/>
<xsl:strip-space elements="*"/>
    <!-- default: copy everything using the identity transform -->
    <xsl:template match="node()|@*">
        <xsl:copy>
            <xsl:apply-templates select="node()|@*"/>
        </xsl:copy>
    </xsl:template>
    <!-- ignore track/waypoints with invalid latitude and longitude values -->
    <xsl:template match="*[starts-with(name(), 'trkpt')][@*[(. = '' or . = 0)]]"/>
    <!-- override: ignore "extensions" nodes -->
    <xsl:template match="*[starts-with(name(), 'extensions')]"/>
</xsl:stylesheet>
