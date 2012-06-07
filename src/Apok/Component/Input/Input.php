<?php
namespace Apok\Component\Input;

class Input
{
    /**
     * Get $_GET variable shortened (without notices)
     *
     * @param   string $var Variable to find from $_GET
     * @param   mixed $emptyVar Force empty string to be returned as given $emptyVar, Default=null (OPTIONAL)
     * @return  mixed Secured input value
     */
    public static function get($var, $emptyVar=null)
    {
        $getVariable = null;

        if (isset($_GET[$var]) && $_GET[$var]!=='') {
            $getVariable = self::secureInput($_GET[$var]);
        }
        else if ($emptyVar || (isset($_GET[$var]) && $_GET[$var]==='')) {
            $getVariable = $emptyVar;
        }

        return $getVariable;
    }

    /**
     * Get $_POST variable shortened (without notices)
     *
     * @param   string $var Variable to find from $_POST
     * @param   mixed $emptyVar Force empty string to be returned as given $emptyVar, Default=null (OPTIONAL)
     * @return  mixed Secured input value
     */
    public static function post($var, $emptyVar=null)
    {
        $postVariable = null;

        if (isset($_POST[$var]) && $_POST[$var]!=='') {
            $postVariable = self::secureInput($_POST[$var]);
        }
        else if ($emptyVar || (isset($_POST[$var]) && $_POST[$var]==='')) {
            $postVariable = $emptyVar;
        }

        return $postVariable;
    }

    /**
     * Function for translating the payload (JSON)
     *
     * @return  string Content of payload
     */
    public static function getPayload()
    {
        // define the holder for our data
        $payload = null;

        // make sure there is payload data
        if (isset($_SERVER['CONTENT_LENGTH']) &&
           $_SERVER['CONTENT_LENGTH'] > 0)
        {
            $payload = '';
            $httpContent = fopen('php://input', 'r');

            while ($data = fread($httpContent, 8024)) {
                $payload .= $data;
            }

            fclose($httpContent);
        }

        return $payload;
    }

    /**
     * Get JSON from payload
     *
     * @return  array JSON content
     */
    public static function getJsonPayload()
    {
        $json    = null;
        $content = self::getPayload();
        $json    = json_decode($content, true);

        if ($error = json_last_error()) {
            throw new Exception('Malformed JSON passed ['.$error.']');
        }

        return $json;
    }

    /**
     * JSON encode array (workaround for json_encode($value, JSON_UNESCAPED_UNICODE))
     *
     * @param   array $array Data to convert to JSON
     * @return  string JSON formatted string
     */
    public static function jsonEncode($array)
    {
        array_walk_recursive($array, function(&$item, $key) {
            if (is_string($item)) {
                $item = htmlentities($item, null, 'utf-8');
            }
        });

        $json = json_encode($array);

        // Decode the html entities and end up with unicode again.
        $json = html_entity_decode($json, null, 'utf-8');

        return $json;
    }

    /**
     * Make user input secure against injections
     *
     * @param   string $input Value to secure (pointer)
     * @param   bool $html Make HTML special characters to entities
     * @param   bool $tags Transform < and > to entities if true
     * @return  mixed Secured value
     */
    public static function secureInput(&$input, $html=true, $tags=true)
    {
        $replace = array("\x00"   => '\x00'
                         , "\n"   => '\n'
                         , "\r"   => '\r'
                         , '\\'   => '\\\\'
                         , "'"    => "\'"
                         , '"'    => '\"'
                         , "\x1a" => '\x1a');

        if ($tags) {
            $replace['<'] = '&lt;';
            $replace['>'] = '&gt;';
        }

        $input = strtr($input, $replace);

        if ($html) {
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8', false);
        }

        return $input;
    }
}