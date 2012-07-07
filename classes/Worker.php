<?php

class Worker
{
    protected $dir = null;
    /**
     * RegEx to find mp3 files
     *
     * @var [String] RegEx
     */
    protected $file_matcher = "/\.mp3$/";

    /**
     * Ignore directories
     *
     * @var [String] RegEx
     */

    protected $ignore_dirs = "/(\.|\.\.|\.git)/";

    /**
     *
     * @var [Object]
     */
    protected $tagger = null;

    /**
     *
     * @var [Array] Contains all rule objects
     */
    protected $rules = null;

    public function __construct (IdTag $idTag)
    {
        $this->tagger   = $idTag;
    }

    public function loadRules ( $file )
    {
        if (is_dir($file)) {
            foreach( glob($file . "*.rule.php") as $file)  {
                $class_name = str_replace(".rule.php", "",basename($file));
                include $file;
                $this->rules[] = new $class_name( $this );
            }
        } else {
            throw new Exception("Rule Folder dosen't exist.");
        }
    }

    public function scanFolder( $dir )
    {
        if ( substr($dir, -1) != DS) {
            $dir .= DS;
        }
        if (file_exists($dir) and is_dir($dir)) {
            $dir_handle = opendir($dir);

            while (false !== ($entry = readdir($dir_handle))) {
                if (is_dir($dir . $entry) and !preg_match($this->ignore_dirs,$entry)) {
                    $this->scanFolder($dir . $entry . DS);
                } elseif (preg_match($this->file_matcher, $entry)) {
                    $this->current_dir = $dir;
                    $this->current_file = $entry;
                    $this->loadTag($dir . $entry);
                }
            }
        } else {
            /**
             * @todo throw exception
             */
        }
    }

    protected function loadTag ( $file )
    {
        $this->tagger->loadTags($file);
        $all_frames = $this->tagger->getAllFrames();

        foreach ($this->rules as $obj) {
            $obj->trigger($all_frames, $file);
        }

        $this->tagger->saveTag(
            $all_frames,
            $this->current_dir,
            $this->current_file
        );
    }
}