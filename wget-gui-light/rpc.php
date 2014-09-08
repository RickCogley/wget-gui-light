<?php

## @author    Samoylov Nikolay
## @project   Wget GUI Light
## @copyright 2014 <samoylovnn@gmail.com>
## @license   MIT <http://opensource.org/licenses/MIT>
## @github    https://github.com/tarampampam/wget-gui-light
## @version   Look in 'settings.php'
##
## @depends   *nix, php5 (PECL json >= 1.2.0), wget, bash, ps, rm


# *****************************************************************************
# ***                               Config                                   **
# *****************************************************************************


## Settings paths
##   Path to directory, where this script located
define('BASEPATH', realpath(dirname(__FILE__)));

## Path to settings file
define('SETTINGS_FILE', BASEPATH.'/settings.php');

## Load settings
if(file_exists(SETTINGS_FILE))
    require(SETTINGS_FILE);
else
    die('{"status":0,"msg":"Cannot load settings file"}');

# *****************************************************************************
# ***                            END Config                                  **
# *****************************************************************************

header('Content-Type: application/json; charset=UTF-8'); // Default header
header('Access-Control-Allow-Origin: *'); // For request from anywhere

if(defined('DebugMode') && DebugMode) {
    error_reporting(-1); ini_set('display_errors', 'On');
} else error_reporting(0);

if(!defined('tmp_path')) define('tmp_path', '/tmp');
if(!defined('wget')) define('wget', 'wget');
if(!defined('ps'))   define('ps', 'ps');
if(!defined('rm'))   define('rm', 'rm');

/**
 * Simple class for writing messages to a log file.
 *   Example call is: "log::debug('Something happens');"
 */
class log {
    const ErrorsLog  = 'wgetgui.log';
    const WarningLog = 'wgetgui.log';
    const NoticesLog = 'wgetgui.log';
    const DebugLog   = 'wgetgui.log';
    ## --- Or you can set ---
    #const NoticesLog = 'wgetgui-notices.log';
    #const WarningLog = 'wgetgui-warnings.log';
    #const ErrorsLog  = 'wgetgui-errors.log';
    #const DebugLog   = 'wgetgui-debug.log';
    
    private static function getTimeStamp() {
        list($usec, $sec) = explode(' ', microtime());
        return '['.date('Y/m/d H:i:s.', $sec) . substr($usec, 2, 3).']';
    }
    
    private static function checkPermissions() {
        return (file_exists(log_path) &&
                is_dir(log_path) &&
                is_writable(log_path)) ? true : false;
    }
    
    private static function writeLog($logName, $msg = '') {

        if(!defined('log_path')) return false;
        
        $msg = str_replace(array("  ", "\n", "\r", "\t"), "", $msg);
        $msg = str_replace(array(",)", ", )"), ")", $msg);
        $msg = str_replace("array (", "array(", $msg);
        
        if(!self::checkPermissions()) {
            mkdir(log_path, 0777, true); chmod(log_path, 0777);
            if(!self::checkPermissions())
                return false;
        }
        
        $LogFilePath = log_path.'/'.$logName;
        
        $logFile = fopen($LogFilePath, 'a');
        if($logFile)
            if(fwrite($logFile, $msg."\n"))
                fclose($logFile);
        
        return true;
    }

    public static function error($msg) {
        return self::writeLog(self::ErrorsLog,
                              self::getTimeStamp().' (ERROR) '.$msg);
    }
    
    public static function warning($msg) {
        return self::writeLog(self::WarningLog,
                              self::getTimeStamp().' (warning) '.$msg);
    }
    
    public static function notice($msg) {
        return self::writeLog(self::NoticesLog,
                              self::getTimeStamp().' (notice) '.$msg);
    }
    
    public static function debug($msg) {
        if(defined('DebugMode') && DebugMode)
            return self::writeLog(self::DebugLog,
                                  self::getTimeStamp().' (debug) '.$msg);
        else return false;
    }
    
    public static function emptyLine() {
        return self::writeLog(self::DebugLog, '');
    }
}

/**
 * Converts all accent characters to ASCII characters (short version)
 *
 * Full (original) version here: http://goo.gl/ndT7gT
 *
 * @param  (string) (string) Text that might have accent characters
 * @return (string) Filtered string with replaced "nice" characters
 */
// http://stackoverflow.com/a/10790734/2252921
function remove_accents($string) {
    if ( !preg_match('/[\x80-\xff]/', $string) )
        return $string;
    $chars = array(
    // Decompositions for Latin-1 Supplement
    chr(195).chr(128)=>'A', chr(195).chr(129)=>'A', chr(195).chr(130)=>'A',
    chr(195).chr(131)=>'A', chr(195).chr(132)=>'A', chr(195).chr(133)=>'A',
    chr(195).chr(135)=>'C', chr(195).chr(136)=>'E', chr(195).chr(137)=>'E',
    chr(195).chr(138)=>'E', chr(195).chr(139)=>'E', chr(195).chr(140)=>'I',
    chr(195).chr(141)=>'I', chr(195).chr(142)=>'I', chr(195).chr(143)=>'I',
    chr(195).chr(145)=>'N', chr(195).chr(146)=>'O', chr(195).chr(147)=>'O',
    chr(195).chr(148)=>'O', chr(195).chr(149)=>'O', chr(195).chr(150)=>'O',
    chr(195).chr(153)=>'U', chr(195).chr(154)=>'U', chr(195).chr(155)=>'U',
    chr(195).chr(156)=>'U', chr(195).chr(157)=>'Y', chr(195).chr(159)=>'s',
    chr(195).chr(160)=>'a', chr(195).chr(161)=>'a', chr(195).chr(162)=>'a',
    chr(195).chr(163)=>'a', chr(195).chr(164)=>'a', chr(195).chr(165)=>'a',
    chr(195).chr(167)=>'c', chr(195).chr(168)=>'e', chr(195).chr(169)=>'e',
    chr(195).chr(170)=>'e', chr(195).chr(171)=>'e', chr(195).chr(172)=>'i',
    chr(195).chr(173)=>'i', chr(195).chr(174)=>'i', chr(195).chr(175)=>'i',
    chr(195).chr(177)=>'n', chr(195).chr(178)=>'o', chr(195).chr(179)=>'o',
    chr(195).chr(180)=>'o', chr(195).chr(181)=>'o', chr(195).chr(182)=>'o',
    chr(195).chr(182)=>'o', chr(195).chr(185)=>'u', chr(195).chr(186)=>'u',
    chr(195).chr(187)=>'u', chr(195).chr(188)=>'u', chr(195).chr(189)=>'y',
    chr(195).chr(191)=>'y',
    // Decompositions for Latin Extended-A
    chr(196).chr(128)=>'A', chr(196).chr(129)=>'a', chr(196).chr(130)=>'A',
    chr(196).chr(131)=>'a', chr(196).chr(132)=>'A', chr(196).chr(133)=>'a',
    chr(196).chr(134)=>'C', chr(196).chr(135)=>'c', chr(196).chr(136)=>'C',
    chr(196).chr(137)=>'c', chr(196).chr(138)=>'C', chr(196).chr(139)=>'c',
    chr(196).chr(140)=>'C', chr(196).chr(141)=>'c', chr(196).chr(142)=>'D',
    chr(196).chr(143)=>'d', chr(196).chr(144)=>'D', chr(196).chr(145)=>'d',
    chr(196).chr(146)=>'E', chr(196).chr(147)=>'e', chr(196).chr(148)=>'E',
    chr(196).chr(149)=>'e', chr(196).chr(150)=>'E', chr(196).chr(151)=>'e',
    chr(196).chr(152)=>'E', chr(196).chr(153)=>'e', chr(196).chr(154)=>'E',
    chr(196).chr(155)=>'e', chr(196).chr(156)=>'G', chr(196).chr(157)=>'g',
    chr(196).chr(158)=>'G', chr(196).chr(159)=>'g', chr(196).chr(160)=>'G',
    chr(196).chr(161)=>'g', chr(196).chr(162)=>'G', chr(196).chr(163)=>'g',
    chr(196).chr(164)=>'H', chr(196).chr(165)=>'h', chr(196).chr(166)=>'H',
    chr(196).chr(167)=>'h', chr(196).chr(168)=>'I', chr(196).chr(169)=>'i',
    chr(196).chr(170)=>'I', chr(196).chr(171)=>'i', chr(196).chr(172)=>'I',
    chr(196).chr(173)=>'i', chr(196).chr(174)=>'I', chr(196).chr(175)=>'i',
    chr(196).chr(176)=>'I', chr(196).chr(177)=>'i', chr(196).chr(178)=>'IJ',
    chr(196).chr(179)=>'ij',chr(196).chr(180)=>'J', chr(196).chr(181)=>'j',
    chr(196).chr(182)=>'K', chr(196).chr(183)=>'k', chr(196).chr(184)=>'k',
    chr(196).chr(185)=>'L', chr(196).chr(186)=>'l', chr(196).chr(187)=>'L',
    chr(196).chr(188)=>'l', chr(196).chr(189)=>'L', chr(196).chr(190)=>'l',
    chr(196).chr(191)=>'L', chr(197).chr(128)=>'l', chr(197).chr(129)=>'L',
    chr(197).chr(130)=>'l', chr(197).chr(131)=>'N', chr(197).chr(132)=>'n',
    chr(197).chr(133)=>'N', chr(197).chr(134)=>'n', chr(197).chr(135)=>'N',
    chr(197).chr(136)=>'n', chr(197).chr(137)=>'N', chr(197).chr(138)=>'n',
    chr(197).chr(139)=>'N', chr(197).chr(140)=>'O', chr(197).chr(141)=>'o',
    chr(197).chr(142)=>'O', chr(197).chr(143)=>'o', chr(197).chr(144)=>'O',
    chr(197).chr(145)=>'o', chr(197).chr(146)=>'OE',chr(197).chr(147)=>'oe',
    chr(197).chr(148)=>'R', chr(197).chr(149)=>'r', chr(197).chr(150)=>'R',
    chr(197).chr(151)=>'r', chr(197).chr(152)=>'R', chr(197).chr(153)=>'r',
    chr(197).chr(154)=>'S', chr(197).chr(155)=>'s', chr(197).chr(156)=>'S',
    chr(197).chr(157)=>'s', chr(197).chr(158)=>'S', chr(197).chr(159)=>'s',
    chr(197).chr(160)=>'S', chr(197).chr(161)=>'s', chr(197).chr(162)=>'T',
    chr(197).chr(163)=>'t', chr(197).chr(164)=>'T', chr(197).chr(165)=>'t',
    chr(197).chr(166)=>'T', chr(197).chr(167)=>'t', chr(197).chr(168)=>'U',
    chr(197).chr(169)=>'u', chr(197).chr(170)=>'U', chr(197).chr(171)=>'u',
    chr(197).chr(172)=>'U', chr(197).chr(173)=>'u', chr(197).chr(174)=>'U',
    chr(197).chr(175)=>'u', chr(197).chr(176)=>'U', chr(197).chr(177)=>'u',
    chr(197).chr(178)=>'U', chr(197).chr(179)=>'u', chr(197).chr(180)=>'W',
    chr(197).chr(181)=>'w', chr(197).chr(182)=>'Y', chr(197).chr(183)=>'y',
    chr(197).chr(184)=>'Y', chr(197).chr(185)=>'Z', chr(197).chr(186)=>'z',
    chr(197).chr(187)=>'Z', chr(197).chr(188)=>'z', chr(197).chr(189)=>'Z',
    chr(197).chr(190)=>'z', chr(197).chr(191)=>'s');
    $string = strtr($string, $chars);
    return $string;
}

/**
 * Transliterate string (iconv works not correct)
 *
 * @param  (string) (string) Input string
 * @return (string) transliterated string
 */
function transliterate($string) {
    // http://goo.gl/ZOiMGL - ISO/R 9 (1968), GOST 16876-71, OON (1987)
    $q = chr(226).chr(128).chr(179); $s = chr(226).chr(128).chr(178); 
    $chars = array(
    chr(208).chr(144)=>'A', chr(208).chr(176)=>'a', chr(208).chr(145)=>'B',
    chr(208).chr(177)=>'b', chr(208).chr(146)=>'V', chr(208).chr(178)=>'v',
    chr(208).chr(147)=>'G', chr(208).chr(179)=>'g', chr(208).chr(148)=>'D',
    chr(208).chr(180)=>'d', chr(208).chr(149)=>'E', chr(208).chr(181)=>'e',
    chr(208).chr(129)=>'Jo',chr(209).chr(145)=>'jo',chr(208).chr(150)=>'Zh',
    chr(208).chr(182)=>'zh',chr(208).chr(151)=>'Z', chr(208).chr(183)=>'z',
    chr(208).chr(152)=>'I', chr(208).chr(184)=>'i', chr(208).chr(153)=>'Jj',
    chr(208).chr(185)=>'jj',chr(208).chr(154)=>'K', chr(208).chr(186)=>'k',
    chr(208).chr(155)=>'L', chr(208).chr(187)=>'l', chr(208).chr(156)=>'M',
    chr(208).chr(188)=>'m', chr(208).chr(157)=>'N', chr(208).chr(189)=>'n',
    chr(208).chr(158)=>'O', chr(208).chr(190)=>'o', chr(208).chr(159)=>'P',
    chr(208).chr(191)=>'p', chr(208).chr(160)=>'R', chr(209).chr(128)=>'r',
    chr(208).chr(161)=>'S', chr(209).chr(129)=>'s', chr(208).chr(162)=>'T',
    chr(209).chr(130)=>'t', chr(208).chr(163)=>'U', chr(209).chr(131)=>'u',
    chr(208).chr(164)=>'F', chr(209).chr(132)=>'f', chr(208).chr(165)=>'Kh',
    chr(209).chr(133)=>'kh',chr(208).chr(166)=>'C', chr(209).chr(134)=>'c',
    chr(208).chr(167)=>'Ch',chr(209).chr(135)=>'ch',chr(208).chr(168)=>'Sh',
    chr(209).chr(136)=>'sh',chr(208).chr(169)=>'Shh',chr(209).chr(137)=>'shh',
    chr(208).chr(170)=>$q,  chr(209).chr(138)=>$q,  chr(208).chr(171)=>'Y',
    chr(209).chr(139)=>'y', chr(208).chr(172)=>$s,  chr(209).chr(140)=>$s,
    chr(208).chr(173)=>'Eh',chr(209).chr(141)=>'eh',chr(208).chr(174)=>'Ju',
    chr(209).chr(142)=>'ju',chr(208).chr(175)=>'Ya',chr(209).chr(143)=>'ja');
    $string = strtr($string, $chars);
    return $string;
}

/**
 * Make system command call, return result as string or array
 *
 * @param  (string) (cmd) Command for execute
 * @param  (string) (result_type) Type for result ('string' or 'array')
 * @return (string) Exec output
 */
function bash($cmd, $result_type = '') {
    $out = ''; $result = '';
    
    if(empty($cmd))
        return false;
        
    // Switch output language to English
    exec('export LC_ALL=C; '.$cmd, $out);
    
    $result_type = empty($result_type) ? 'string' : $result_type;
    
    switch ($result_type) {
        case 'string':
            foreach($out as $line)
                $result .= $line."\n";
            break;
        case 'array':
                $result = $out;
            break;
    }
    return $result;
}

/**
 * Check PID value
 *
 * @param  (numeric) (pid) Pid number
 * @return (bool)
 */
function validatePid($pid) {
    // 32768 is maximum pid by default
    return (is_numeric($pid) &&
           ($pid > 0) &&
           ($pid <= 32768)) ? true : false;
}

/**
 * Make string clear, for file system file name
 *
 * @param  (string) (str) Input string
 * @return (string)
 */
function makeStringSafe($str) {
    return trim(
        str_replace(array('  '), array(' '), 
            preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', 
                remove_accents(
                    transliterate($str)
                )
            )
        )
    );
}

/**
 * Make url clear, without 'unsecure' chars
 *
 * @param  (string) (str) Input string
 * @return (string)
 */
function makeUrlSafe($str) {
    return str_replace( // http://tools.ietf.org/html/rfc1738
        // encode url string, and make "back-encode" for important wget chars
        array('%2F', '%3A', '%40', '%3F', '%3D', '%26'),
        array('/',   ':',   '@',   '?',   '=',   '&'),
        rawurlencode($str)
    );
}

/**
 * Get wget tasks from `ps` 'as is'; or only with string, if $string is not
 *  empty (passed in $string); or with PID = $string, if $string is 
 *  valid PID
 *
 * @param  (string|numeric) (string) String or PID for search
 * @return (array)
 */
function getWgetTasksList($string = '') {
    log::debug('(call) getWgetTasksList() called, $string='.var_export($string, true));
    $result = array();
    // For BSD 'ps axwwo pid,args'
    // Issue <https://github.com/tarampampam/wget-gui-light/issues/8>
    // Thx to @ghospich <https://github.com/ghospich>
    $tasks = bash(ps.' -ewwo pid,args', 'array');
    $string = empty($string) ? ' ' : (string) $string;
    
    //var_dump($tasks);
    
    foreach($tasks as $task) {
        // make FAST search:
        // find string with 'wget' and without '2>&1'
        if((strpos($task, 'wget') !== false) && (strpos($task, $string) !== false)) {
            preg_match("/(\d{1,5})\swget.*--output-file=(\/.*\d{3,6}\.log\.tmp)(.*)".wget_secret_flag."\s(.*)/i", $task, $founded);
            //var_dump($task); var_dump($founded);
            $pid = @$founded[1]; $logfile = @$founded[2]; $etcParams = @$founded[3]; $url = @$founded[4];
            if(validatePid($pid)) {
                preg_match('/--output-document=.*\/(.*?)\s$/', $etcParams, $etcParts);
                //var_dump($etcParts);
                array_push($result, array(
                    'raw'     => (string) @$founded[0],
                    'saveAs'  => (string) @$etcParts[1],
                    'pid'     => (int) $pid,
                    'logfile' => (string) $logfile,
                    'url'     => (string) $url
                ));
            }
        }
    }
    
    // If in '$string' passed PID
    if(validatePid($string)) {
        foreach($result as $task)
            if($task['pid'] == $string) {
                log::debug('getWgetTasksList() return '.var_export($task, true));
                return $task;
            }
        return array();
    }
    
    log::debug('getWgetTasksList() return '.var_export($result, true));
    return $result;
}

/**
 * Get list of active tasks
 * ************************
 *
 * @return (array)
 */
function getWgetTasks() {
    log::debug('(call) getWgetTasks() called');
    $result = array();

    foreach(getWgetTasksList('') as $task) {
        $preogress = 0;
        if(validatePid($task['pid']) && is_string($task['url']) && !empty($task['url'])) {
            if (!empty($task['logfile']) && file_exists($task['logfile'])) {
                // Read last line <http://stackoverflow.com/a/1510248>
                $lastline = (string) ''; $f = fopen($task['logfile'], 'r'); $cursor = -1;
                fseek($f, $cursor, SEEK_END); $char = fgetc($f);
                
                while ($char === "\n" || $char === "\r") {
                    fseek($f, $cursor--, SEEK_END);
                    $char = fgetc($f);
                }
                
                while ($char !== false && $char !== "\n" && $char !== "\r") {
                    $lastline = $char . $lastline;
                    fseek($f, $cursor--, SEEK_END);
                    $char = fgetc($f);
                }
                
                preg_match("/(\d{1,2}).*\[.*].*/i", $lastline, $founded);
                $preogress = !is_null(@$founded[1]) ? @$founded[1] : -1;
                //print_r($founded);
                
                log::debug('Last line is '.var_export($lastline, true).', progress value is '.var_export($preogress, true));
            }
            
            array_push($result, array(
                'pid'      => (int) $task['pid'],
                'logfile'  => (string) $task['logfile'],
                'saveAs'   => (string) $task['saveAs'],
                'progress' => (int) $preogress,
                'url'      => (string) $task['url']
            ));
        }
    }
    return $result;
}

/**
 * Remove download task. Kill process & remove temp log file
 * *********************************************************
 *
 * @param  (numeric) (pid) PID value for process
 * @return (array)
 */
function removeWgetTask($pid) {
    log::debug('(call) removeWgetTask() called, $pid='.var_export($pid, true));
    if(!validatePid($pid))
        return array(
            'result' => false,
            'msg' => 'ID is invalid'
        );

    $taskData = getWgetTasksList($pid);
    //var_dump($taskData);
    
    if(empty($taskData)) {
        log::debug('Task with ID '.$pid.' not exists');
        return array(
            'result' => false,
            'msg' => 'Task not exists'
        );
    }
    
    $kill = @posix_kill((int) $taskData['pid'], 15); // http://php.net/manual/ru/function.posix-kill.php
    if(!$kill) log::error('Task with PID '.$taskData['pid'].' NOT killed');
    
    usleep(200000); // at first - subprocess must del file. if not - when del we // 1/5 sec
    if (!empty($taskData['logfile']) && file_exists($taskData['logfile'])) {
        log::notice('removeWgetTask() remove file '.var_export($taskData['logfile'], true));
        $del = @unlink($taskData['logfile']); // http://php.net//manual/ru/function.unlink.php
        if(!$del) log::error('File '.var_export($taskData['logfile'], true).' NOT deleted');
    }
    
    if(!is_null($taskData['pid']) && $kill) {
        log::notice('Task with ID '.var_export($taskData['pid'], true).' killed');
        return array(
            'result' => true,
            'msg' => 'Task removed success'
        );
    }
        
    return array(
        'result' => false,
        'msg' => 'No remove data'
    );
}
//var_dump(removeWgetTask(1276)); // Debug call

/**
 * Add task for a work
 * *******************
 *
 * @param  (string) (url) Url for task
 * @param  (string) (saveAs) Save to this file name
 * @return (array)
 */
function addWgetTask($url, $saveAs) {
    function checkPermissions($path) {
        return (file_exists($path) && is_dir($path) && is_writable($path)) ? true : false;
    }
    function checkDirectory($dir) {
        if(!checkPermissions($dir)) {
            mkdir($dir, 0777, true); chmod($dir, 0777);
            return checkPermissions($dir) ? true : false;
        } return true;
    }
    log::debug('(call) addWgetTask() called, $url='.var_export($url, true).', $saveAs='.var_export($saveAs, true));
    
    define('OriginalTaskUrl', $url); // Save in constant original task url
    
    if(empty($url)) return array('result' => false, 'msg' => 'No URL');
    
    if(defined('WgetOnetimeLimit') && (count(getWgetTasks())+1 > WgetOnetimeLimit)) {
        log::notice('Task not added, because one time tasks limit is reached');
        return array('result' => false, 'msg' => 'One time tasks limit is reached');
    }

    if(!defined('download_path')) {
        log::error('"download_path" not defined');
        return array('result' => false, 'msg' => '"download_path" not defined');
    }
    
    if(!checkDirectory(download_path)) {
        log::error('Directory '.var_export(download_path, true).' cannot be created');
        return array('result' => false, 'msg' => 'Cannot create directory for downloads');
    }
    
    // DOWNLOAD YOUTUBE VIDEO
    // Detect - if url is link to youtube video
    if((strpos($url, 'youtube.com/') !== false) || (strpos($url, 'youtu.be/') !== false)) {
        $youtubeVideos = array();
        // http://stackoverflow.com/a/10315969/2252921
        preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/', $url, $founded);
        define('YoutubeVideoID', @$founded[1]); // Set as constant YouTube video ID
        if(strlen(YoutubeVideoID) == 11) {
            $rawVideoInfo = file_get_contents('http://youtube.com/get_video_info?video_id='.YoutubeVideoID.'&ps=default&eurl=&gl=US&hl=en');
            if(($rawVideoInfo !== false)) {
                parse_str($rawVideoInfo, $videoInfo);
                //var_dump($videoInfo);
                if(isset($videoInfo['url_encoded_fmt_stream_map'])) {
                    $my_formats_array = explode(',', $videoInfo['url_encoded_fmt_stream_map']);
                    foreach($my_formats_array as $videoItem) {
                        parse_str($videoItem, $videoItemData);
                        if(isset($videoItemData['url'])) {
                            //var_dump($videoItemData);
                            switch (@$videoItemData['quality']) {
                                case 'small':  $videoItemData['quality'] = '240p'; break;
                                case 'medium': $videoItemData['quality'] = '360p'; break;
                                case 'large':  $videoItemData['quality'] = '480p'; break;
                                case 'hd720':  $videoItemData['quality'] = '720p'; break;
                                case 'hd1080': $videoItemData['quality'] = '1080p'; break;
                            }
                            array_push($youtubeVideos, array(
                                'title'     => trim(@$videoInfo['title']),
                                'thumbnail' => @$videoInfo['thumbnail_url'], // or 'iurlsd'
                                'url'       => urldecode($videoItemData['url']),
                                'type'      => @$videoItemData['type'],
                                'quality'   => @$videoItemData['quality']
                            ));
                        } else {
                            log::error('Link to youtube source video file not exists '.var_export($videoItemData, true)); 
                            return array('result' => false, 'msg' => 'Link to youtube source video file not exists');
                        }
                    }
                } else {
                    $errorDescription = 'Youtube answer not contains data about video files';
                    if(isset($videoInfo['reason']) && !empty($videoInfo['reason']))
                        $errorDescription = trim(strip_tags($videoInfo['reason'], '<a><br/>'));
                    log::error($errorDescription.', raw='.var_export($rawVideoInfo, true));
                    return array('result' => false, 'msg' => $errorDescription);
                }
                
            } else {
                log::error('Cannot call "file_get_contents()" for $url='.var_export($url, true));
                return array('result' => false, 'msg' => 'Cannot get remote content');
            }
        }
        //var_dump($youtubeVideos);
        
        // If we found video links
        if(count($youtubeVideos) > 0) {
            // Get first 'mp4' video
            foreach($youtubeVideos as $video)
                if(isset($video['type']) && !empty($video['type'])) {
                    preg_match('~\/(.*?)\;~', $video['type'], $videoType);
                    if(@$videoType[1] == 'mp4') {
                        $videoToDownload = $video;
                        break;
                    }
                }
            
            // Or take first video (by default - with highest quality)
            if(!isset($videoToDownload))
                $videoToDownload = $youtubeVideos[0];
            
            preg_match('~\/(.*?)\;~', $videoToDownload['type'], $extension);
            switch (@$extension[1]) {
                case 'mp4':   $fileExtension = 'mp4';  break;
                case 'webm':  $fileExtension = 'webm'; break;
                case 'x-flv': $fileExtension = 'flv';  break;
                case '3gpp':  $fileExtension = '3gp';  break;
                default: $fileExtension = 'video';
            }
            
            $clearTitle = makeStringSafe(@$videoToDownload['title']);
            
            // Tadaaam :)
            $url = $videoToDownload['url'];
            if(empty($saveAs))
                if(empty($clearTitle))
                    $saveAs = 'youtube_video_id'.YoutubeVideoID.' ('.$videoToDownload['quality'].').'.$fileExtension;
                else
                    $saveAs = $videoToDownload['title'].' ('.$videoToDownload['quality'].').'.$fileExtension;
        }
    }
    
    // DOWNLOAD VK.COM VIDEO
    // Detect - if url is link to vk.com video
    if(strpos($url, 'vk.com/video_ext.php') !== false) {
        // For test code/decode url - http://meyerweb.com/eric/tools/dencoder/
        // Get url query and parse it to $q
        $urlParts = parse_url(urldecode($url)); parse_str($urlParts['query'], $q);
        define('VkVideoID', @$q['id']); // Set as constant VK video ID
        if(isset($q['oid'])  && !empty($q['oid']) && is_numeric($q['oid']) &&
           is_numeric(VkVideoID) &&
           isset($q['hash']) && !empty($q['hash'])) {
            // Build request url
            $queryUrl = 'https://vk.com/video_ext.php?oid='.$q['oid'].'&id='.$q['id'].'&hash='.$q['hash'];
            
            // Get page content
            $rawVideoInfo = file_get_contents($queryUrl);
            if(($rawVideoInfo !== false)) {
                if(preg_match('/.*\<div.*id\=\"video_player\".*/im', $rawVideoInfo) !== 0) {
                    $videoData = array();
                    
                    preg_match('/\&amp\;url240\=(.*?)\&amp\;/i', $rawVideoInfo, $f);
                    $videoData['240'] = urldecode(@$f[1]);
                    
                    preg_match('/\&amp\;url360\=(.*?)\&amp\;/i', $rawVideoInfo, $f);
                    $videoData['360'] = urldecode(@$f[1]);
                    
                    preg_match('/\&amp\;url480\=(.*?)\&amp\;/i', $rawVideoInfo, $f);
                    $videoData['480'] = urldecode(@$f[1]);
                    
                    preg_match('/\&amp\;url720\=(.*?)\&amp\;/i', $rawVideoInfo, $f);
                    $videoData['720'] = urldecode(@$f[1]);
                    
                    preg_match('/\&amp\;thumb\=(.*?)\&amp\;/i', $rawVideoInfo, $f);
                    $videoData['thumbnail'] = trim(urldecode(@$f[1]));
                    
                    preg_match('/\&amp\;md_title\=(.*?)\&amp\;/i', $rawVideoInfo, $f);
                    $videoData['title'] = trim(urldecode(@$f[1]));
                    
                    // video in low quality always must exists, if parse complete without errors
                    if(isset($videoData['240']) && !empty($videoData['240'])) {
                        $videoQualityStr = '';
                        if(isset($videoData['240']) && !empty($videoData['240'])) {
                            $url = $videoData['240']; $videoQualityStr = '240p';
                        }
                        if(isset($videoData['360']) && !empty($videoData['360'])) {
                            $url = $videoData['360']; $videoQualityStr = '360p';
                        }
                        if(isset($videoData['480']) && !empty($videoData['480'])) {
                            $url = $videoData['480']; $videoQualityStr = '480p';
                        }
                        if(isset($videoData['720']) && !empty($videoData['720'])) {
                            $url = $videoData['720']; $videoQualityStr = '720p';
                        }

                        $clearTitle = makeStringSafe(@$videoData['title']);

                        if(empty($saveAs))
                            if(empty($clearTitle))
                                $saveAs = 'vk_video_id'.VkVideoID.' ('.$videoQualityStr.').mp4';
                            else
                                $saveAs = $clearTitle.' ('.$videoQualityStr.').mp4';
                    } else {
                        log::error('Link to video file not found');
                        return array('result' => false, 'msg' => 'Link to video file not found');
                    }
                } else {
                    log::error('Video container not found $queryUrl='.var_export($queryUrl, true));
                    return array('result' => false, 'msg' => 'Video container not found');
                }
            } else {
                log::error('Cannot call "file_get_contents()" for $url='.var_export($url, true));
                return array('result' => false, 'msg' => 'Cannot get remote content');
            }
        } else {
            log::error('Request error - some important query part not exists, $url='.var_export($url, true));
            return array('result' => false, 'msg' => 'Request error - some important query part(s) not exists');
        }
    }
    
    //var_dump($videoToDownload);
    
    $historyAction = ''; $saveAs = makeStringSafe($saveAs);
    if(defined('history'))
        if(checkDirectory(dirname(history))) {
            $savedAsCmdString = (!empty($saveAs)) ? ' ## SavedAs: \"'.$saveAs.'\"' : '';
            // If string passed in '$url' and saved 'OriginalTaskUrl' not equal each other,
            //   we understand - URL was PARSED and changed. And now, for a history 
            //   (tadatadaaaam =)) we must write ORIGINAL url (not parsed)
            $urlForHistory = ($url !== OriginalTaskUrl) ? OriginalTaskUrl : $url;
            $urlForHistoryCmd = ($url !== OriginalTaskUrl) ? ' && URL="'.$urlForHistory.'"' : '';
            $historyAction = ' && HISTORY="'.history.'"'.$urlForHistoryCmd.' && if [ "$?" = "0" ]; then '.
                                   'echo "Success: \"$URL\"'.$savedAsCmdString.'" >> "$HISTORY"; '.
                               'else '.
                                   'echo "Failed: \"$URL\"" >> "$HISTORY"; '.
                               'fi';
        } else
            log::error('Directory '.var_export(dirname(history), true).' cannot be created');
            
    $speedLimit = (defined('wget_download_limit')) ? '--limit-rate='.wget_download_limit.'k ' : ' ';
    $saveAsFile  = (!empty($saveAs)) ? '--output-document="'.download_path.'/'.$saveAs.'" ' : ' ';
    $tmpFileName = tmp_path.'/wget'.rand(1, 32768).'.log.tmp';
    
    $cmd = '(URL="'.$url.'"; TMPFILE="'.$tmpFileName.'"; echo > "$TMPFILE"; '.wget.' '.
        '--progress=bar:force --output-file="$TMPFILE" '. // forever stand at beginning
        '--tries=0 '.
        '--no-cache '.
        '--user-agent="Mozilla/5.0 (X11; Linux amd64; rv:21.0) Gecko/20100101 Firefox/21.0" '.
        '--directory-prefix="'.download_path.'" '.
        '--content-disposition '. // by <https://github.com/CodingFu>
        '--restrict-file-names=nocontrol '. // For Cyrillic correct filenames
        $speedLimit.
        $saveAsFile.
        wget_secret_flag.' '. // forever LAST param
        '"$URL"'.$historyAction.'; '.rm.' -f "$TMPFILE") > /dev/null 2>&1 & echo $!';
    
    log::debug('Command to exec: '.var_export($cmd, true));
    
    $task = bash($cmd, 'string');
    if(empty($task)) {
        log::error('Exec task '.var_export($cmd, true).' error');
        return array(
            'result' => false,
            'msg' => 'Exec task error'
        );
    }
    
    usleep(100000); // 1/10 sec
    
    preg_match("/(\d{2,5})/i", $task, $founded);
    $parentPid = @$founded[1];
    if(!validatePid($parentPid)) {
        log::error('Parent PID '.var_export($parentPid, true).' for task '.var_export($url, true).' not valid');
        return array(
            'result' => false,
            'msg' => 'Parent PID not valid'
        );
    }

    //var_dump($cmd); var_dump($task); var_dump($parentPid); 

    // Wait ~1 sec until child pipe not running, check every second
    for ($i = 1; $i <= 4; $i++) {
        // Get pipe with out wget task (search by $tmpFileName)
        $taskData = getWgetTasksList($tmpFileName);
        // Get last job with current URL
        $taskPid = @$taskData[0]['pid'];
        
        if(validatePid($taskPid)) 
            break;
        else
            usleep(250000); // 1/4 sec
    }
    
    if(!file_exists($tmpFileName)) {
        log::notice('Task '.var_export($url, true).' already complete (probably with error)');
        return array(
            'result' => true,
            'msg' => 'Task completed too fast (probably with error)'
        );
    }
    
    if(!validatePid($taskPid)) {
        log::error('Task PID '.var_export($taskPid, true).' for '.var_export($url, true).' not valid');
        return array(
            'result' => false,
            'msg' => 'Task PID not valid'
        );
    }
    
    log::notice('Task '.var_export($url, true).' added successful (pid '.var_export($taskPid, true).')');
    return array(
        'result' => true,
        'pid' => (int) $taskPid,
        'msg' => 'Task added successful'
    );
}

/**
 * Get history
 *
 * @param  (numeric) Entries count
 * @return (array)
 */
function getTasksHistory($count = 5) {
    if(!defined('history'))
        return array(
            'result' => false,
            'msg' => 'Feature disabled'
        );

    if(!file_exists(history))
        return array(
            'result' => false,
            'msg' => 'History file not exists'
        );

    $hist = file(history);
    if(is_bool($hist) && !$hist)
        return array(
            'result' => false,
            'msg' => 'Cannot open history file'
        );

    $result = array();
    $offset = ($count < count($hist)) ? count($hist)-$count : 0;
    for ($i = $offset; $i < count($hist); $i++) {
        $subResult = array();
        $currentLine = $hist[$i];
        preg_match("/([A-Za-z]+)\:\s\"(.*?)\"/i", $currentLine, $founded);
        //var_dump($founded);
        if(isset($founded[1]) && !empty($founded[1]) && isset($founded[2]) && !empty($founded[2])) {
            $subResult['success'] = (string) (strpos(strtolower($founded[1]), 'success') !== false) ? true : false;
            $subResult['url']     = (string) $founded[2];
            
            preg_match("/\s\#\#\sSavedAs\:\s\"(.*?)\"/i", $currentLine, $saveAsRaw);
            if(isset($saveAsRaw[1]) && !empty($saveAsRaw[1]))
                $subResult['savedAs'] = (string) $saveAsRaw[1];

            array_push($result, $subResult);
        }
    }
    return $result;
}

/**
 * Print work result
 *
 * @param  (string) (data) Data for a printing
 * @param  (string) (type) Type of result ('json' or 'text')
 * @return (bool) true
 */
function echoResult($data, $type) {
    log::debug('Returned data: '.var_export($data, true));
    $type = (empty($type)) ? 'json' : (string) $type;

    switch ($type) {
        case 'json':
            echo(json_encode($data));
            break;
        case 'text':
            var_dump($data);
            break;
    }
    if(defined('DebugMode') && DebugMode) log::emptyLine();
    return true;
}

// Result array {status: 0, msg: 'message'}
//   -1 - script started without errors
//    0 - error
//    1 - script finished without errors
$result = array('status' => -1, 'msg' => 'No input data');

// Command line support
if(isset($argv)) {
    log::debug('(ext) Command line run detected, $argv='.var_export($argv, true));
    if(isset($argv[1]) && !empty($argv[1])) {
        @$_GET['action'] = $argv[1];
        if(isset($argv[2]) && !empty($argv[2]))
            if(validatePid($argv[2]))
                @$_GET['id'] = $argv[2];
        else
            @$_GET['url'] = $argv[2];
        if(isset($argv[3]) && !empty($argv[3]))
            @$_GET['saveAs'] = $argv[3];
    }
}

// AJAX send data in GET, html in POST
if((count($_POST) > 0) and (count($_GET) === 0)) 
    $_GET = $_POST;
else 
    $_POST = $_GET;

if(!empty($_GET['action'])) {
    log::debug('Input data: '.var_export($_GET, true));
    // Set value from GET array to $formData
    $formData = array(
        'action' => preg_replace("/[^a-z_]/", "", strtolower(@$_GET['action'])),
        'url'    => makeUrlSafe(@$_GET['url']),
        'saveAs' => makeStringSafe(@$_GET['saveAs']),
        'id'     => preg_replace("/[^0-9]/", "", @$_GET['id'])
    );
    
    log::debug('Prepared data: '.var_export($formData, true));
    
    switch ($formData['action']) {
        ## Action - Get Tasks List
        ##########################
        case 'get_list':
            $result['tasks'] = array();
            
            foreach (getWgetTasks() as $task) {
                
                array_push($result['tasks'], array(
                    'url'      => (string) $task['url'],
                    'saveAs'   => (string) $task['saveAs'],
                    'progress' => (int) $task['progress'],
                    'id'       => (int) $task['pid']
                ));
            }

            if(!empty($result['tasks']))
                $result['msg'] = 'Active tasks list';
            else
                $result['msg'] = 'No active tasks';

            $result['status'] = 1;
            break;
            
        ## Action - Add Task
        ####################
        case 'add_task':
            $url    = $formData['url'];
            $saveAs = (!empty($formData['saveAs'])) ? $formData['saveAs'] : '';
            
            $addTaskResult = addWgetTask($url, $saveAs);
            
            if($addTaskResult['result'] === true) {
                $result['msg']    = !empty($addTaskResult['msg']) ? $addTaskResult['msg'] : 'Task added success';
                $result['id']     = (isset($addTaskResult['pid']) && validatePid($addTaskResult['pid'])) ? (int) $addTaskResult['pid'] : -1;
                $result['status'] = 1;
            } else {
                $result['msg']    = !empty($addTaskResult['msg']) ? $addTaskResult['msg'] : 'Error while adding task';
                $result['status'] = 0;
            }
            break;
            
        ## Action - Cancel (remove) Task
        ################################
        case 'remove_task':
            $id = $formData['id'];
            
            $removeResult = removeWgetTask($id);
            if($removeResult['result'] === true) {
                $result['msg']    = $removeResult['msg'];
                $result['status'] = 1;
            } else {
                $result['msg']    = $removeResult['msg'];
                $result['status'] = 0;
            }
            break;
            
        ## Action - Get history
        #######################
        case 'get_history':
            $itemsCount = 6;
            $historyItems = getTasksHistory($itemsCount);
            
            if(count($historyItems) > 0) {
                $result['history'] = $historyItems;
                $result['msg']     = 'Recent '.$itemsCount.' history entries';
                $result['status']  = 1;
            } else {
                $result['msg']    = 'History is empty';
                $result['status'] = 0;
            }

            break;
            
        ## Action - Cancel (remove) Task
        ################################
        case 'test':
            // Added after comment in issue <http://goo.gl/I8gYoK>
            if(is_dir(tmp_path) || is_writable(tmp_path)) {
                if(is_dir(download_path) || is_writable(download_path)) {
                    if(function_exists('ini_get') && !ini_get('safe_mode')) {
                        $testVal = (string) 'test'.rand(1024, 32768);
                        $bash = bash('echo "'.$testVal.'"', 'string');
                        if(strpos($bash, $testVal) !== false) {
                            $bash = bash(wget.' -V', 'array');
                            if(strpos(strtolower($bash[0]), 'gnu wget') !== false) {
                                preg_match("/(\d{1,2}\.\d{1,3}\.\d{1,3})/i", $bash[0], $founded);
                                $wgetVersion = @$founded[1];
                                if(!empty($wgetVersion)) {
                                    $result['msg'] = 'Patches is right, PHP \'safe_mode\' = Off, \'exec()\' enabled, \'wget\' version = \''.$wgetVersion.'\'. All looks right, cap!';
                                    $result['status'] = 1;
                                    break;
                                } else {
                                    $result['msg'] = 'Getting \'wget\' version error';
                                    $result['status'] = 0;
                                    break;
                                }
                            } else {
                                $result['msg'] = '\'wget\' not installed (http://www.gnu.org/software/wget/)';
                                $result['status'] = 0;
                                break;
                            }
                        } else {
                            $result['msg'] = 'Enable \'exec()\' in PHP (http://php.net/manual/en/function.exec.php)';
                            $result['status'] = 0;
                            break;
                        }
                    } else {
                        $result['msg'] = 'Disable PHP \'safe_mode\' (http://php.net/manual/en/features.safe-mode.php)';
                        $result['status'] = 0;
                        break;
                    }
                } else {
                    $result['msg'] = 'Directory, defined in \'download_path\' not exists or not writable';
                    $result['status'] = 0;
                    break;
                }
            } else {
                $result['msg'] = 'Directory, defined in \'tmp_path\' not exists or not writable';
                $result['status'] = 0;
                break;
            }
            break;
            
        default:
            $result['msg']    = 'No action';
            $result['status'] = 0;
    }

}

echoResult($result, 'json');
