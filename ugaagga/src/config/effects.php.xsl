<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="ISO-8859-1"/>
<xsl:strip-space elements="*"/>

<xsl:template match="Config"><xsl:apply-templates select="EffectTypes" /></xsl:template>

<xsl:template match="EffectTypes">&lt;?php
define("MAX_EFFECT", <xsl:value-of select="count(EffectType)"/>);
global $effectTypeList;
$effectTypeList = array();
<xsl:for-each select="EffectType">
<xsl:variable name="id" select="position()-1"/>
/* ***** <xsl:value-of select="Name"/> ***** */
$effectTypeList[<xsl:value-of select="$id"/>]-&gt;effectID    = <xsl:value-of select="$id"/>;
$effectTypeList[<xsl:value-of select="$id"/>]-&gt;name        = "<xsl:value-of select="Name"/>";
$effectTypeList[<xsl:value-of select="$id"/>]-&gt;dbFieldName = "<xsl:value-of select="@id"/>";
$effectTypeList[<xsl:value-of select="$id"/>]-&gt;description = "<xsl:apply-templates select="Description[@lang='de_DE']"/>";
</xsl:for-each>?&gt;
</xsl:template>

<xsl:template match="Description"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>
</xsl:stylesheet>
