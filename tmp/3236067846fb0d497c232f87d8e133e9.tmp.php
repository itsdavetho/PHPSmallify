<?php
namespace Orpheus;

class MCStatus {
    protected $status = array('online'     => false,
                              'version'    => null,
                              'motd'       => null,
                              'players'    => -1,
                              'maxPlayers' => -1);

    /**
     *
     * Constructor.
     *
     * @param string $ip   The IP of the server.
     * @param int    $port The port of the server.
     *
     */
    public function __construct($ip = null, $port = 25565) {
        if($ip !== null) {
            $this->setServer($ip, $port);
        }
    }

    /**
     *
     * Set the IP and/or port of the server.
     *
     * @param string $ip   The IP of the server.
     * @param int    $port The port of the server.
     *
     */
    public function setServer($ip, $port = 25565) {
        if($port === null) {
            $port = 25565;
        }

        $this->ip = $this->status['ip'] = $ip;
        $this->port = $this->status['port'] = (int) $port;
        if($this->port < 0 || $this->port > 65535) {
            throw new \Exception(__METHOD__ . ': Port range: 1-65535');
        }
    }

    /**
     *
     * Get the status of the server.
     *
     * @param bool $format Should we format the string?
     *
     * @return mixed
     *
     */
    public function getStatus($format = true) {
        $f = @fsockopen($this->ip, $this->port, $errno, $errstr, 5);
        if($f === false) {
            $this->status['online'] = false;
            return $this->status;
        }

        fwrite($f, "\xFE\x01");
        $result = fread($f, 256);

        if(substr($result, 0, 1) != "\xff") {
            $this->status['online'] = false;
            return $this->status;
        } else {
            if(substr($result, 3, 5) == "\x00\xa7\x00\x31\x00"){
                $result = mb_convert_encoding(substr($result, 15), 'UTF-8', 'UCS-2');
                $result = explode("\x00", $result);
            }

            $motd = $format == true ? $this->formatString($result[count($result) - 3]) : preg_replace('/((\d))/', '', $result[count($result) - 3]);
            $this->status = array(
                'ip'         => $this->ip,
                'port'       => $this->port,
                'online'     => true,
                'version'    => $result[0],
                'motd'       => $motd,
                'players'    => (int) $result[count($result) - 2],
                'maxPlayers' => (int) $result[count($result) - 1]
            );
            return $this->status;
        }
    }

    /**
     *
     * Colorizes the string.
     *
     * @param string $string The string to be formatted.
     *
     * @return string
     */
    protected function formatString($string) {
        preg_match_all('/(([\d\w]))/', $string, $formats);

        $replacements = json_decode(file_get_contents(__DIR__ . '/formats.json'), true);
            
        $tags = 0;
        foreach($formats[1] as $key => $format) {
            $string = preg_replace('/' . $format . '/', '<span style="' . $replacements[$formats[2][$key]] . '">', $string);
            $tags++;
        }

        for($i = 0; $i < $tags; $i++) {
            $string .= '</span>';
        }

        return $string;
    }
}
?>