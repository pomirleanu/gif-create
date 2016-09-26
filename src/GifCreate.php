<?php

namespace pomirleanu\GifCreate;

class GifCreate
{

    /**
     * Config
     *
     * @var array $config
     */
    public $config = [
        'loop'     => 0, // infinite loop
        'duration' => 10 // default duration
    ];

    /**
     * Errors
     *
     * @var array $errors
     */
    private $errors = [
        'ERR00' => 'Need at least 2 frames for an animation.',
        'ERR01' => 'Resource is not a GIF image.',
        'ERR02' => 'Only image resource variables, file paths, URLs or binary bitmap data are accepted.',
        'ERR03' => 'Cannot make animation from animated GIF.',
        'ERR04' => 'Loading from URLs is disabled by PHP.',
        'ERR05' => 'Failed to load or invalid image (dir): "%s".',
        'ERR06' => 'At least two image sources must be provided.',
    ];

    /*
     * Check if gif was build or not.
     *
     * @var $builded
     */

    /**
     * The gif source as string
     *
     * @var string $gif
     */
    private $gif;

    /*
     * The images paths.
     *
     * @var array $sources
     */
    private $sources = [];

    /**
     * Gif was build
     *
     * @var bool $builded
     */
    private $builded = false;

    /*
     * Frames dir, can provide a directory for the the gif createion and will get all the images from there and build the gif with them.
     *
     * @var string $frames_dir
     */
    private $frames_dir;

    /**
     * @var integer Gif dis [!!?]
     */
    private $dis;

    /**
     * @var integer Gif transparent color index
     */
    private $transparent_color = -1;


    /**
     * Creates new instance of GifCreator
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->gif = 'GIF89a'; // the GIF header
        $this->configure($config);
    }


    /**
     * Overrides configuration settings
     *
     * @param array $config
     *
     * @return $this
     */
    private function configure(array $config = [])
    {
        $this->config = array_replace($this->config, $config);

        return $this;
    }


    /**
     * Creates a gif from the given array images sources
     *
     * @param      $frames
     * @param int  $durations
     * @param null $loop
     *
     * @return \pomirleanu\GifCreate\GifCreate
     * @throws \Exception
     */
    public function create($frames, $durations = 10, $loop = null)
    {
        if (count($frames) < 2) {
            throw new \Exception(sprintf($this->errors[ 'ERR06' ]));
        }
        $this->setIfSet($durations, $loop);

        // Check if $frames is a dir; get all files in ascending order if yes (else die):
        if (! is_array($frames)) {
            if (is_dir($frames)) {
                $this->frames_dir = $frames;
                if ($frames = scandir($this->frames_dir)) {
                    $frames = array_filter($frames, function ($dir) {
                        return $dir[ 0 ] != ".";
                    });
                    array_walk($frames, function (&$dir) {
                        $dir = $this->frames_dir.DIRECTORY_SEPARATOR."$dir";
                    });
                }
            }
            if (! is_array($frames)) {
                throw new \Exception(sprintf($this->errors[ 'ERR05' ], $this->frames_dir));
            }
        }

        assert(is_array($frames));

        if (sizeof($frames) < 2) {
            throw new \Exception($this->errors[ 'ERR00' ]);
        }

        return $this->buildFrameSources($frames, $durations);
    }


    private function setIfSet($loop)
    {
        if ($loop !== null) {
            $this->config[ 'loop' ] = $loop;
        }
    }


    /**
     * Building the frame sources for the given images
     *
     * @param $frames
     * @param $durations
     *
     * @return $this
     * @throws \Exception
     */
    private function buildFrameSources($frames, $durations)
    {
        $i = 0;
        foreach ($frames as $frame) {
            $resourceImage = $frame;
            if (is_resource($frame)) { // in-memory image resource (hopefully)
                $resourceImg = $frame;
                ob_start();
                imagegif($frame);
                $this->sources[] = ob_get_contents();
                ob_end_clean();
                if (substr($this->sources[ $i ], 0, 6) != 'GIF87a' && substr($this->sources[ $i ], 0, 6) != 'GIF89a') {
                    throw new \Exception($i.' '.$this->errors[ 'ERR01' ]);
                }

            } elseif (is_string($frame)) { // file path, URL or binary data

                if (@is_readable($frame)) { // file path
                    $bin = file_get_contents($frame);
                } else {
                    if (filter_var($frame, FILTER_VALIDATE_URL)) {
                        if (ini_get('allow_url_fopen')) {
                            $bin = @file_get_contents($frame);
                        } else {
                            throw new \Exception($i.' '.$this->errors[ 'ERR04' ]);
                        }
                    } else {
                        $bin = $frame;
                    }
                }

                if (! ($bin && ($resourceImage = imagecreatefromstring($bin)))) {
                    throw new \Exception($i.' '.sprintf($this->errors[ 'ERR05' ], substr($frame, 0, 200)));
                }
                ob_start();
                imagegif($resourceImage);
                $this->sources[] = ob_get_contents();
                ob_end_clean();

            } else { // Fail
                throw new \Exception($this->errors[ 'ERR02' ]);
            }
            if ($i == 0) {
                $this->transparent_color = imagecolortransparent($resourceImage);
            }

            for ($j = (13 + 3 * (2 << (ord($this->sources[ $i ]{10}) & 0x07))), $k = true; $k; $j++) {

                switch ($this->sources[ $i ]{$j}) {

                    case '!':
                        if ((substr($this->sources[ $i ], ($j + 3), 8)) == 'NETSCAPE') {

                            throw new \Exception($this->errors[ 'ERR03' ].' ('.($i + 1).' source).');
                        }

                        break;

                    case ';':
                        $k = false;
                        break;
                }
            }
            unset($resourceImg);
            ++$i;
        }//foreach

        $this->gifAddHeader();
        for ($i = 0; $i < count($this->sources); $i++) {
            // Use the last delay, if none has been specified for the current frame
            if (is_array($durations)) {
                $d                          = (empty($durations[ $i ]) ? $this->config[ 'duration' ] : $durations[ $i ]);
                $this->config[ 'duration' ] = $d;
            } else {
                $d = $durations;
            }
            $this->addFrame($i, $d);
        }
        $this->gif .= ';';

        return $this;
    }


    /**
     * Add the header gif string in its source
     */
    protected function gifAddHeader()
    {
        $cmap = 0;
        if (ord($this->sources[ 0 ]{10}) & 0x80) {

            $cmap = 3 * (2 << (ord($this->sources[ 0 ]{10}) & 0x07));
            $this->gif .= substr($this->sources[ 0 ], 6, 7);
            $this->gif .= substr($this->sources[ 0 ], 13, $cmap);
            $this->gif .= "!\377\13NETSCAPE2.0\3\1".$this->word2bin($this->config[ 'loop' ])."\0";
        }
    }


    /**
     * Convert an integer to 2-byte little-endian binary data
     *
     * @param integer $word Number to encode
     *
     * @return string of 2 bytes representing @word as binary data
     */
    protected function word2bin($word)
    {
        return (chr($word & 0xFF).chr(($word >> 8) & 0xFF));
    }


    private function addFrame($i, $d)
    {
        $Locals_str = 13 + 3 * (2 << (ord($this->sources[ $i ]{10}) & 0x07));

        $Locals_end = strlen($this->sources[ $i ]) - $Locals_str - 1;
        $Locals_tmp = substr($this->sources[ $i ], $Locals_str, $Locals_end);

        $Global_len = 2 << (ord($this->sources[ 0 ]{10}) & 0x07);
        $Locals_len = 2 << (ord($this->sources[ $i ]{10}) & 0x07);

        $Global_rgb = substr($this->sources[ 0 ], 13, 3 * (2 << (ord($this->sources[ 0 ]{10}) & 0x07)));
        $Locals_rgb = substr($this->sources[ $i ], 13, 3 * (2 << (ord($this->sources[ $i ]{10}) & 0x07)));

        $Locals_ext = "!\xF9\x04".chr(($this->dis << 2) + 0).$this->word2bin($d)."\x0\x0";

        if ($this->transparent_color > -1 && ord($this->sources[ $i ]{10}) & 0x80) {

            for ($j = 0; $j < (2 << (ord($this->sources[ $i ]{10}) & 0x07)); $j++) {

                if (ord($Locals_rgb{3 * $j + 0}) == (($this->transparent_color >> 16) & 0xFF) && ord($Locals_rgb{3 * $j + 1}) == (($this->transparent_color >> 8) & 0xFF) && ord($Locals_rgb{3 * $j + 2}) == (($this->transparent_color >> 0) & 0xFF)) {
                    $Locals_ext = "!\xF9\x04".chr(($this->dis << 2) + 1).chr(($d >> 0) & 0xFF).chr(($d >> 8) & 0xFF).chr($j)."\x0";
                    break;
                }
            }
        }

        switch ($Locals_tmp{0}) {

            case '!':

                $Locals_img = substr($Locals_tmp, 8, 10);
                $Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);

                break;

            case ',':

                $Locals_img = substr($Locals_tmp, 0, 10);
                $Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);

                break;
            default:
                $Locals_img = substr($Locals_tmp, 8, 10);
                $Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);
                break;


        }

        if (ord($this->sources[ $i ]{10}) & 0x80 && $this->builded) {

            if ($Global_len == $Locals_len) {

                if ($this->gifBlockCompare($Global_rgb, $Locals_rgb, $Global_len)) {

                    $this->gif .= $Locals_ext.$Locals_img.$Locals_tmp;

                } else {

                    $byte = ord($Locals_img{9});
                    $byte |= 0x80;
                    $byte &= 0xF8;
                    $byte |= (ord($this->sources[ 0 ]{10}) & 0x07);
                    $Locals_img{9} = chr($byte);
                    $this->gif .= $Locals_ext.$Locals_img.$Locals_rgb.$Locals_tmp;
                }

            } else {

                $byte = ord($Locals_img{9});
                $byte |= 0x80;
                $byte &= 0xF8;
                $byte |= (ord($this->sources[ $i ]{10}) & 0x07);
                $Locals_img{9} = chr($byte);
                $this->gif .= $Locals_ext.$Locals_img.$Locals_rgb.$Locals_tmp;
            }

        } else {

            $this->gif .= $Locals_ext.$Locals_img.$Locals_tmp;
        }

        $this->builded = true;
    }


    /**
     * Compare two block and return the version
     *
     * @param string  $globalBlock
     * @param string  $localBlock
     * @param integer $length
     *
     * @return integer
     */
    private function gifBlockCompare($globalBlock, $localBlock, $length)
    {
        for ($i = 0; $i < $length; $i++) {

            if ($globalBlock [ 3 * $i + 0 ] != $localBlock [ 3 * $i + 0 ] || $globalBlock [ 3 * $i + 1 ] != $localBlock [ 3 * $i + 1 ] || $globalBlock [ 3 * $i + 2 ] != $localBlock [ 3 * $i + 2 ]) {

                return 0;
            }
        }

        return 1;
    }


    /**
     * Get the resulting GIF image binary
     *
     * @return string
     */
    public function get()
    {
        return $this->gif;
    }

    /**
     * Save the resulting GIF to a file.
     *
     * @param $filename String Target file path
     *
     * @return that of file_put_contents($filename)
     */
    public function save($filename)
    {
        return file_put_contents($filename, $this->gif);
    }
}
