<?php

/**
 *
 * @author Mesa <mesa@xebro.de>
 */
class IdTag
{

    /**
     * Contains all existing ID tags
     * @var [Array] ID tags
     */
    protected $frames           = array();
    protected $file_path        = null;
    protected $tag_version      = null;
    protected $tmp_file_name    = "tmp_";
    protected $tag_size         = null;

    /**
     * File extensions where a ID2-tag can be found.
     *
     * @var [Array]
     */
    protected $allowed_file_types = array("mp3", "wma");

    /**
     * Filehandle of the MP3 File
     *
     */
    protected $file_handle      = null;
    protected $file_extension   = null;
    protected $file_name        = null;
    protected $dir_name         = null;
    protected $path_to_file     = null;
    protected $file_size        = null;

    /**
     * change IdTag encoding to utf8?
     *
     * @var [Boolean]
     */
    protected $change_encoding = true;

    /**
     * Size of padding behind tags
     *
     * @var [Integer]
     */
    protected $tag_padding      = 0;

    /**
     * When file has not enough padding, copy file an add this amount of zeros
     * behind all frames
     *
     * @var [Integer]
     */
    protected $new_padding      = 1000;

    const UTF8ENCODING          = 3;
    const ISOENCODING           = 0;
    const UTF16ENCODING1        = 1;
    const UTF16ENCODING2        = 2;

    protected $header_flag_usync = false;
    protected $header_flag_ext  = false;
    protected $header_flag_exp  = false;
    protected $header_flag_footer = false;

    const HEADER_FLAG_USYNC     = 128;
    const HEADER_FLAG_EXT       = 64;
    const HEADER_FLAG_EXP       = 32;
    const HEADER_FLAG_FOOTER    = 16;

    public function getAllFrames ()
    {
        return $this->frames;
    }

    public function saveTag ( $array )
    {
        if (is_array($array)) {

            $header = "ID3";
            $header .= pack('hhC', 0x04, 0x00, decbin(0));
            $data = "";
            foreach ($array as $frame) {
                $tmp = $this->writeFrame($frame);
                if ($tmp !== false) {
                    $data .= $tmp;
                }
                $tmp = "";
            }
            $old_tag_size = $this->tag_size;

//            $header .= $this->dec2syncbin(strlen($data) + 10);
//            $new_tag = $header . $data;
//            $new_tag_length = strlen($new_tag);
            $new_tag_length = strlen($data) + 10;

            if ($new_tag_length <= $old_tag_size) {
                /**
                 * There is enough space for the new id tag
                 * reset filepointer and write our new tag.
                 *
                 */
                $spacing = $old_tag_size - $new_tag_length;

                $header .= $this->dec2syncbin($old_tag_size);
                $new_tag = $header . $data;

                fseek($this->file_handle, 0);
                fwrite($this->file_handle, $new_tag . $this->createPadding($spacing), $new_tag_length);
                fclose($this->file_handle);
                $this->file_handle = null;
            } elseif ($new_tag_length > $old_tag_size) {
                /**
                 * Not enough space, create new file with more padding
                 */

                $header .= $this->dec2syncbin(strlen($data) + 10 + $this->new_padding);
                $new_tag = $header . $data;

                fseek($this->file_handle, $old_tag_size);
                $musik_bin = fread($this->file_handle, filesize($this->file_name));
                fseek($this->file_handle,0);
                fwrite($this->file_handle,$new_tag . $this->createPadding($this->new_padding) . $musik_bin);
                fclose($this->file_handle);
                $this->file_handle = null;
            }

            return $new_tag_length;
        } else {

        }
    }

    protected function writeFrame ( $array )
    {
        if (
            isset($array["tag_name"])
            and isset($array["tag_body"])
            and strlen($array["tag_name"]) == 4
            and strlen($array["tag_body"]) > 0
        ) {
            $frame_body = "";

            if (substr(trim($array["tag_name"]), 0, 1) == "T") {
                if ( !isset($array["tag_enc"]) ) {

                    $encoding = mb_detect_encoding($array["tag_body"]);

                    switch ($encoding)
                    {
                    case "UTF-8":
                        $encoding = IdTag::UTF8ENCODING;
                        break;

                    case "UTF-16":
                        $encoding = IdTag::UTF16ENCODING1;
                        break;

                    default:
                        $encoding = IdTag::ISOENCODING;
                        break;
                    }

                    $array["tag_body"] = $this->convertEncoding(
                        $array["tag_body"],
                        IdTag::UTF8ENCODING
                    );
                    $array["tag_enc"] = IdTag::UTF8ENCODING;
                }
                $frame_body .= pack('C', dechex($array["tag_enc"]));
            }

            $frame_body .= $array["tag_body"];

            $frame_length = strlen($frame_body);

            $frame_header = strtoupper($array["tag_name"]);
            $frame_header .= $this->dec2syncbin($frame_length);
            if (!isset($array["tag_flag_1"])) {
                $array["tag_flag_1"] = 0;
            }
            if (!isset($array["tag_flag_2"])) {
                $array["tag_flag_2"] = 0;
            }

            $frame_header
                .= pack(
                    'HH',
                    dechex($array["tag_flag_1"]),
                    dechex($array["tag_flag_2"])
                );

            $data = $frame_header . $frame_body;
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Reset all data for new File.
     */
    protected function resetData ()
    {
        $this->header_flag_exp      = false;
        $this->header_flag_ext      = false;
        $this->header_flag_footer   = false;
        $this->header_flag_usync    = false;
        $this->tag_padding          = 0;
        $this->frames               = array();
        $this->file_path            = null;
        /**
         * @todo write frame changes to file
         */
        if ($this->file_handle != null) {
            fclose($this->file_handle);
            $this->file_handle = null;
        }
    }

    public function loadTags ( $file_path )
    {
        if (file_exists($file_path)) {
            if (is_writable($file_path)) {
                $this->resetData();
                $this->file_path = $file_path;
                $this->file_handle = fopen($file_path, "r+");
                $this->loadHeader();
                while ($this->readFrame()) {

                }
                $this->getPadding();
            } else {
                throw new IdTagException("File is not writeable " . $file_path);
            }
        } else {
            throw new IdTagException("File not found " . $file_path);
        }
    }

    protected function createPadding ( $size )
    {
        $data = "";

        for ($i=0; $i<=$size; $i++) {
            $data .= pack("H", 0x00);
        }
        return $data;
    }
    protected function getPadding ()
    {
        while ($this->readFromFile(1, "hex") == 0) {
            $this->tag_padding++;
        }
    }

    /**
     * Read the first 10 bytes from file
     */
    protected function loadHeader ()
    {
        $version = $this->readFromFile(3, "string");
        $version2 = (int) $this->readFromFile(1, "hex");

        if ($version == "ID3" and $version2 > 0) {
            $this->tag_version
                = "ID3.2" . $version2 . $this->readFromFile(1, "hex");
        }
        $flags = $this->readFromFile(1);

        if ($flags & IdTag::HEADER_FLAG_USYNC == IdTag::HEADER_FLAG_USYNC) {
            $this->header_flag_usync = true;
        }

        if ($flags & IdTag::HEADER_FLAG_EXT == IdTag::HEADER_FLAG_EXT) {
            $this->header_flag_ext = true;
            $this->readExtHeader();
        }

        if ($flags & IdTag::HEADER_FLAG_EXP == IdTag::HEADER_FLAG_EXP) {
            $this->header_flag_exp = true;
        }

        if ($flags & IdTag::HEADER_FLAG_FOOTER == IdTag::HEADER_FLAG_FOOTER) {
            $this->header_flag_footer = true;
        }

        $this->tag_size = $this->readFromFile(4, "sync");
    }

    protected function readFrame ()
    {
        $data = array();

        $data["tag_name"] = $this->readFromFile(4);
        $tag_name = $data["tag_name"];
        if (preg_match('/^[A-Z][A-Z0-9]{3}$/', $tag_name)) {

            $data["tag_size"] = $this->readFromFile(4, "sync");
            $data["tag_flag_1"] = $this->readFromFile(1, "hex");
            $data["tag_flag_2"] = $this->readFromFile(1, "hex");

            if (substr($tag_name, 0, 1) == "T") {
                $tmp = $this->readTextFrame($this->readFromFile($data["tag_size"]));
                $data = array_merge($data, $tmp);
            } else {
                $data["tag_body"] = $this->readFromFile($data["tag_size"]);
            }
            /**
             * Frames that allow different types of text encoding contains a
             * text encoding description byte.
             */
            $this->frames[$tag_name] = $data;
            return true;
        } else {
            return false;
        }
    }

    protected function readTextFrame ( $data )
    {
        $tag["tag_enc"] = $this->hex2int(substr($data, 0, 1));
        $tag_body = substr($data, 1, strlen($data));

        switch ($tag["tag_enc"]) {
        case IdTag::ISOENCODING:
            /**
                * Frame is ISO-8859-1 encoded
                */
            if ($this->change_encoding) {
                $tag["tag_body"]
                    = $this->convertEncoding(
                        substr($tag_body, 0, strlen($tag_body) - 1), IdTag::UTF8ENCODING, "ISO-8859-1"
                    );
                $tag["tag_enc"] = IdTag::UTF8ENCODING;
            } else {
                $tag["tag_body"] = $tag_body;
            }
            break;

        case IdTag::UTF8ENCODING:
            /**
                * Frame is UTF-8 encoded
                */
            $tag["tag_body"] = $tag_body;
            break;

        case IdTag::UTF16ENCODING1:
        case IdTag::UTF16ENCODING2:
            /**
                * Frame is UTF-16 encoded
                */
            $tag["tag_body"] = $tag_body;
            break;
        }
        return $tag;
    }

    protected function convertEncoding ( $frame_data, $to_encoding, $from_encoding = "auto" )
    {
        $string = array();

        switch ($to_encoding) {
        case IdTag::ISOENCODING:
            $target_encoding = "ISO-8859-1";
            break;

        case IdTag::UTF16ENCODING1:
        case IdTag::UTF16ENCODING1:
            $target_encoding = "UTF-16";
            break;

        case IdTag::UTF8ENCODING:
        default:
            $target_encoding = "UTF-8";
        }

        return mb_convert_encoding($frame_data, $target_encoding, $from_encoding);
    }

    protected function readExtHeader ()
    {
        /**
         * @todo implement extendet header handling
         */
    }

    protected function readFromFile ( $length, $type = null )
    {
        $data = fread($this->file_handle, (int) $length);
        switch ($type) {
        case "bin":
            return $this->bin2int($data);
            break;
        case "hex":
            return $this->hex2int($data);
            break;

        case "sync":
            return $this->syncbin2dec($data);
            break;
        default :
            return $data;
        }
    }

    /**
     * Entfernt jede 8. Stelle aus dem übergeben 4 Byte langen Binärcode
     *
     * @param [BINÄR] $syncbin (vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge Big Endian)
     *
     * @return [INTEGER]
     */
    protected function syncbin2dec ( $syncbin )
    {
        $true_value = "";
        $enc_syncbin = unpack('N', $syncbin);

//        foreach ($enc_syncbin as $bin_value) {
//            $true_value .= decbin($bin_value);
//        }

        $true_value .= decbin($enc_syncbin[1]);

        $bin_str = "";
        for ($i = 0; $i < strlen($true_value); $i++) {
            $dump = substr($true_value, -1 - $i, 1);


            if (is_int($i / 7) and $i > 0) {
                /**
                 * Sollte doch eine Eins vorkommen und der Binärwert dadurch nicht Syncsafe sein
                 * den unveränderten String ausgeben
                 */
                if ($dump == "1") {
                    $bin_str = $true_value;
                    break;
                }
            } else {
                $bin_str = $dump . $bin_str;
            }
        }

        return bindec($bin_str);
    }

    /**
     * Binären String in Synchsafe integer umwandeln
     * (Besonderheit ist das die 8. Stelle IMMER "0" ist)
     *
     * @param [Integer] $bin Dezimalwert der in einen Syncsafe Binärwert umgewandelt werden soll (vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge Big Endian) )
     */
    protected function dec2syncbin ( $bin )
    {
        $bin_data = decbin($bin);
        $syncsafe_bin = null;

        for ($i = 0; $i < strlen($bin_data); $i++) {
            $dump = (int) substr($bin_data, -1 - $i, 1);

            if (is_int(strlen($syncsafe_bin) / 7) and $i > 0) {
                $syncsafe_bin = $dump . "0" . $syncsafe_bin;
            } else {
                $syncsafe_bin = $dump . $syncsafe_bin;
            }
        }

        /**
         *  Convert string with binary layout (100110) to dec and then to binary
         */
        return pack('N', base_convert($syncsafe_bin, 2, 10));
    }

    protected function bin2Int ( $bin )
    {
        return (int) bindec($bin);
    }

    protected function hex2int ( $hex )
    {
        $data = unpack('h', $hex);
        return (int) $data[1];
    }

    public function __destruct ()
    {
//        fclose($this->file_handle);
    }

}

?>
