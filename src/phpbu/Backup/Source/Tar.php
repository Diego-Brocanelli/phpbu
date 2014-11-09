<?php
namespace phpbu\Backup\Source;

use phpbu\Backup\Runner;
use phpbu\Backup\Source;
use phpbu\Backup\Target;
use phpbu\Util;

class Tar implements Source
{
    /**
     * Runner to execute tar shell command.
     *
     * @var phpbu\Backup\Runner\Cli
     */
    private $runner;

    /**
     * Setup.
     *
     * @see    phpbu\Backup\Source
     * @param  phpbu\Backup\Target $target
     * @param  array               $conf
     * @throws RuntimeException
     */
    public function setup(Target $target, array $conf = array())
    {
        $this->runner = new Runner\Cli();
        $this->runner->setTarget($target);
        $this->runner->setOutputCompression(false);

        $compressOption     = '';
        $compressed         = false;
        $allowedCompressors = array(
            'bzip2' => 'j',
            'gzip'  => 'z',
        );

        if ($target->shouldBeCompressed()) {
            $compressor = $target->getCompressor();
            $name       = $compressor->getCommand(false);
            if (isset($allowedCompressors[$name])) {
                $compressed     = true;
                $compressOption = $allowedCompressors[$name];
            }
        }

        $path = isset($conf['pathToTar']) ? $conf['pathToTar'] : null;
        $tar  = Util\Cli::detectCmdLocation('tar', $path);
        $cmd  = new Runner\Cli\Cmd($tar);
        $cmd->addOption(
            '-' . $compressOption . 'cvf',
            array(
                $target->getPath($compressed),
                $conf['dir'],
            )
        );
        $this->runner->addCommand($cmd);
    }

    /**
     *
     * @return phpbu\Backup\Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }
}
