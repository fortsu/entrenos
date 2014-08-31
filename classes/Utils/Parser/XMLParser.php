<?php
namespace Entrenos\Utils\Parser;
/**
 * XMLParser Class File
 *
 * This class loads an XML document into a SimpleXMLElement that can
 * be processed by the calling application.  This accepts xml strings,
 * files, and DOM objects.  It can also perform the reverse, converting
 * an SimpleXMLElement back into a string, file, or DOM object.
 */
class XMLParser {
    /**
     * While parsing, parse the supplied XML document.
     *
     * Sets up a SimpleXMLElement object based on success of parsing
     * the XML document file.
     *
     * @param string $doc the xml document location path
     * @return object
     */
    public static function loadFile($doc) {
        if (file_exists($doc)) {
            return simplexml_load_file($doc);
        } else {
            throw new Exception ("Unable to load the xml file " .
                                 "using: \"$doc\"", E_USER_ERROR);
        }
    }
    /**
     * While parsing, parse the supplied XML string.
     *
     * Sets up a SimpleXMLElement object based on success of parsing
     * the XML string.
     *
     * @param string $string the xml document string
     * @return object
     */
    public static function loadString($string) {
        if (isset($string)) {
            return simplexml_load_string($string);
        } else {
            throw new Exception ("Unable to load the xml string " .
                                 "using: \"$string\"", E_USER_ERROR);
        }
    }
    /**
     * While parsing, parse the supplied XML DOM node.
     *
     * Sets up a SimpleXMLElement object based on success of parsing
     * the XML DOM node.
     *
     * @param object $dom the xml DOM node
     * @return object
     */
    public static function loadDOM($dom) {
        if (isset($dom)) {
            return simplexml_import_dom($dom);
        } else {
            throw new Exception ("Unable to load the xml DOM node " .
                                 "using: \"$dom\"", E_USER_ERROR);
        }
    }
    /**
     * While parsing, parse the SimpleXMLElement.
     *
     * Sets up a XML file, string, or DOM object based on success of
     * parsing the XML DOM node.
     *
     * @param object $path the xml document location path
     * @param string $type the return type (string, file, dom)
     * @param object $simplexml the simple xml element
     * @return mixed
     */
    public static function loadSXML($simplexml, $type, $path) {
        if (isset($simplexml) && isset($type)) {
        switch ($type) {
            case 'string':
                return $simplexml->asXML();
            break;
            case 'file':
                if (isset($path)) {
                    return $simplexml->asXML($path);
                } else {
                    throw new Exception ("Unable to create the XML file. Path is missing or" .
                                         "is invalid: \"$path\"", E_USER_ERROR);
                }
            break;
            case 'dom':
                return dom_import_simplexml($simplexml);
            break;
        }
        } else {
            throw new Exception ("Unable to load the simple XML element " .
                                 "using: \"$simplexml\"", E_USER_ERROR);
        }
    }
}
?>
