<?php

/**
 * @version     $Id: symlinker-win.php 11 2010-04-27 16:22:40Z torkiljohnsen $
 * @package     NookuSymlinker
 * @copyright   Copyright (C) 2007 - 2010 Johan Janssens and Mathias Verraes. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.koowa.org
 *
 * Windows port by Ercan Özkaya
 */
include_once 'Console/CommandLine.php';
if (!class_exists('Console_CommandLine')) {
        die(PHP_EOL . "You need PEAR Console_CommandLine to use this script. Install using the following command: " . PHP_EOL . "pear install --alldeps Console_CommandLine\n\n");
}

// General
$parser = new Console_CommandLine();
$parser->description = "Make symlinks for Joomla extensions on Windows.";
$parser->version = '1.0';

// Arguments
$parser->addArgument('source', array(
    'description' => 'The source dir',
    'help_name' => 'SOURCE'
));

$parser->addArgument('target', array(
    'description' => "The target Joomla! installation",
    'help_name' => 'TARGET'
));

$parser->addArgument('gitorsvn', array(
    'description' => "Git or SVN",
    'help_name' => 'GITORSVN'
));

// Ex.
// jplinker jbetolo j16trunk git

$targetbase = 'I:\\www\\';

// Parse input
try {
        $result = $parser->parse();
        $ext = $result->args['source'];
        $src = $result->args['gitorsvn'];
        $source = 'I:\\'.$src.'\\' . $ext . ($src == 'svn' ? '\\trunk' : '') . '\\';
        echo $source."\n";
        $target = realpath('I:\\www\\' . $result->args['target']) . '\\';
        echo $target."\n";
        $jversion = '+15';
} catch (Exception $e) {
        $parser->displayError($e->getMessage());
        die;
}

// Make symlinks
if (file_exists($source)) {
        $it = new KSymlinker($source, $target, $jversion, $ext);
        
        while ($it->valid()) {
                $it->next();
        }

        $adhocs = array(
                array('src'=>'I:\\git\\jproven\\build.xml', 'tgt' => $target . 'build.ant'),
                array('src'=>'I:\\git\\jproven\\build_js.xml', 'tgt' => $target . 'build_js.ant')
        );
        
        foreach ($adhocs as $adhoc) {
                $cmd = "mklink ".$adhoc['tgt']." ".$adhoc['src'];
                echo $cmd."\n";
                exec($cmd);                
        }
}

class KSymlinker extends RecursiveIteratorIterator {
        protected $_srcdir;
        protected $_tgtdir;
        protected $_jversion;
        protected $_ext;
        protected $_exceptions = array('README');

        public function __construct($srcdir, $tgtdir, $jversion, $ext) {
                $this->_srcdir = $srcdir;
                $this->_tgtdir = $tgtdir;
                $this->_jversion = $jversion;
                $this->_ext = $ext;
                
                foreach ($this->_exceptions as &$e) $e = $srcdir . $e;
                
                parent::__construct(new RecursiveDirectoryIterator($this->_srcdir));
        }

        public function callHasChildren() {
                if (in_array($this->getPathName(), $this->_exceptions)) return false;
                
                $filename = $this->getFilename();
                
                if ($filename[0] == '.') return false;

                $src = $this->key();
                $tgt = str_replace($this->_srcdir, '', $src);
                $tgt = $this->_tgtdir . $tgt;

                if (is_link($tgt)) unlink($tgt);

                if (!is_dir($tgt)) {
                        $this->createLink($src, $tgt);
                        return false;
                }

                return parent::callHasChildren();
        }

        public function createLink($src, $tgt) {
                if (!file_exists($tgt)) {
                        $opts = '';
                        if (is_dir($src)) {
                                $opts = '/D';
                        }

                        $cmd = "mklink $opts $tgt $src";
                        exec($cmd);
                        echo $src . "\n\t--> $tgt\n";
                }
        }

}