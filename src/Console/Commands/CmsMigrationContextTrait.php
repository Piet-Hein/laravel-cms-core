<?php
namespace Czim\CmsCore\Console\Commands;

use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;

trait CmsMigrationContextTrait
{

    /**
     * Determines and returns the connection to use, if not set by --database.
     *
     * @return string
     */
    public function determineConnection()
    {
        $connection = $this->input->getOption('database');

        if ($connection) {
            return $connection;
        }

        $cmsConnection = $this->getCore()->config('database.driver');

        // If no separate database driver is configured for the CMS,
        // we can use the normal driver and rely on the prefix alone.
        if ( ! $cmsConnection) {
            return $connection;
        }

        // If we're running tests, make sure we use the correct override
        // for the CMS database connection/driver.
        // N.B. This is ignored on purpose if the default connection is not overridden normally.
        if (app()->runningUnitTests()) {
            $cmsConnection = $this->getCore()->config('database.testing.driver') ?: $cmsConnection;
        }

        return $cmsConnection;
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return app()->databasePath() . DIRECTORY_SEPARATOR
             . $this->getCore()->config('database.migrations.path');
    }

    /**
     * Overridden to block internal migrator paths.
     *
     * @inheritdoc
     */
    protected function getMigrationPaths()
    {
        if ($this->input->hasOption('path') && $this->option('path')) {
            return [app()->basePath() . '/' . $this->option('path')];
        }

        // Do not use the migrator paths.
        return [ $this->getMigrationPath() ];
    }

    /**
     * @return CoreInterface
     */
    protected function getCore()
    {
        return app(Component::CORE);
    }

}
