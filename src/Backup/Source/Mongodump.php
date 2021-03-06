<?php
namespace phpbu\App\Backup\Source;

use phpbu\App\Backup\Cli;
use phpbu\App\Backup\Source;
use phpbu\App\Backup\Target;
use phpbu\App\Cli\Executable;
use phpbu\App\Exception;
use phpbu\App\Result;
use phpbu\App\Util;

/**
 * Mongodump source class.
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://phpbu.de/
 * @since      Class available since Release 1.1.6
 */
class Mongodump extends Cli implements Source
{
    /**
     * Path to mongodump command.
     *
     * @var string
     */
    private $pathToMongodump;

    /**
     * Show stdErr
     *
     * @var boolean
     */
    private $showStdErr;

    /**
     * Use IPv6
     * --ipv6
     *
     * @var boolean
     */
    private $useIPv6;

    /**
     * Host to connect to
     * --host <hostname:port>
     *
     * @var string
     */
    private $host;

    /**
     * User to connect with
     * --user <username>
     *
     * @var string
     */
    private $user;

    /**
     * Password to authenticate with
     * --password <password>
     *
     * @var string
     */
    private $password;

    /**
     * Database to use for authentication
     * --authenticationDatabase <dbname>
     *
     * @var string
     */
    private $authenticationDatabase;

    /**
     * List of databases to backup
     * --db <database>
     *
     * @var array
     */
    private $databases;

    /**
     * List of collections to backup
     * --collection <collection>
     *
     * @var array
     */
    private $collections;

    /**
     * List of collections to ignore
     * --excludeCollections array of strings
     *
     * @var array
     */
    private $excludeCollections;

    /**
     * List of prefixes to exclude collections
     * --excludeCollectionWithPrefix array of strings
     *
     * @var array
     */
    private $excludeCollectionsWithPrefix;

    /**
     * (No PHPDoc)
     *
     * @see    \phpbu\App\Backup\Source
     * @param  array $conf
     * @throws \phpbu\App\Exception
     */
    public function setup(array $conf = array())
    {
        $this->setupSourceData($conf);
        $this->setupCredentials($conf);

        // environment settings, config & validation
        $this->pathToMongodump = Util\Arr::getValue($conf, 'pathToMongodump');
        $this->useIPv6         = Util\Str::toBoolean(Util\Arr::getValue($conf, 'ipv6', ''), false);
        $this->showStdErr      = Util\Str::toBoolean(Util\Arr::getValue($conf, 'showStdErr', ''), false);
    }

    /**
     * Fetch databases and collections to backup.
     *
     * @param array $conf
     */
    protected function setupSourceData(array $conf)
    {
        $this->databases                    = Util\Str::toList(Util\Arr::getValue($conf, 'databases'));
        $this->collections                  = Util\Str::toList(Util\Arr::getValue($conf, 'collections'));
        $this->excludeCollections           = Util\Str::toList(Util\Arr::getValue($conf, 'excludeCollections'));
        $this->excludeCollectionsWithPrefix = Util\Str::toList(Util\Arr::getValue($conf, 'excludeCollectionsWithPrefix'));
    }

    /**
     * Fetch credential settings.
     *
     * @param array $conf
     */
    protected function setupCredentials(array $conf)
    {
        $this->host                   = Util\Arr::getValue($conf, 'host');
        $this->user                   = Util\Arr::getValue($conf, 'user');
        $this->password               = Util\Arr::getValue($conf, 'password');
        $this->authenticationDatabase = Util\Arr::getValue($conf, 'authenticationDatabase');
    }

    /**
     * (non-PHPDoc)
     *
     * @see    \phpbu\App\Backup\Source
     * @param  \phpbu\App\Backup\Target $target
     * @param  \phpbu\App\Result        $result
     * @return \phpbu\App\Backup\Source\Status
     * @throws \phpbu\App\Exception
     */
    public function backup(Target $target, Result $result)
    {
        // setup dump location and execute the dump
        $mongodump = $this->execute($target);

        $result->debug($mongodump->getCmd());

        if (!$mongodump->wasSuccessful()) {
            throw new Exception('Mongodump failed');
        }

        return Status::create()->uncompressed()->dataPath($this->getDumpDir($target));
    }

    /**
     * Create the Executable to run the Mongodump command.
     *
     * @param  \phpbu\App\Backup\Target $target
     * @return \phpbu\App\Cli\Executable
     */
    public function getExecutable(Target $target)
    {
        if (null == $this->executable) {
            $this->executable = new Executable\Mongodump($this->pathToMongodump);
            $this->executable->dumpToDirectory($this->getDumpDir($target))
                             ->useIpv6($this->useIPv6)
                             ->useHost($this->host)
                             ->credentials($this->user, $this->password, $this->authenticationDatabase)
                             ->dumpDatabases($this->databases)
                             ->dumpCollections($this->collections)
                             ->excludeCollections($this->excludeCollections)
                             ->excludeCollectionsWithPrefix($this->excludeCollectionsWithPrefix)
                             ->showStdErr($this->showStdErr);
        }

        return $this->executable;
    }

    /**
     * Get the MongoDB dump directory.
     *
     * @param  \phpbu\App\Backup\Target $target
     * @return string
     */
    public function getDumpDir(Target $target)
    {
        return $target->getPath() . '/dump';
    }
}
