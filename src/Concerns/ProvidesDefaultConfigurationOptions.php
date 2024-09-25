<?php

namespace Twid\Octane\Concerns;

trait ProvidesDefaultConfigurationOptions
{
    /**
     * Get the listeners that will prepare the Laravel application for a new request.
     *
     * @return array
     */
    public static function prepareApplicationForNextRequest(): array
    {
        return [
            \Twid\Octane\Listeners\FlushLocaleState::class,
            \Twid\Octane\Listeners\FlushQueuedCookies::class,
            \Twid\Octane\Listeners\FlushSessionState::class,
            \Twid\Octane\Listeners\FlushAuthenticationState::class,
            \Twid\Octane\Listeners\EnforceRequestScheme::class,
            \Twid\Octane\Listeners\EnsureRequestServerPortMatchesScheme::class,
            \Twid\Octane\Listeners\GiveNewRequestInstanceToApplication::class,
            \Twid\Octane\Listeners\GiveNewRequestInstanceToPaginator::class,
        ];
    }

    /**
     * Get the listeners that will prepare the Laravel application for a new operation.
     *
     * @return array
     */
    public static function prepareApplicationForNextOperation(): array
    {
        return [
            \Twid\Octane\Listeners\CreateConfigurationSandbox::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToAuthorizationGate::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToBroadcastManager::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToDatabaseManager::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToDatabaseSessionHandler::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToFilesystemManager::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToHttpKernel::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToMailManager::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToNotificationChannelManager::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToPipelineHub::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToQueueManager::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToRouter::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToValidationFactory::class,
            \Twid\Octane\Listeners\GiveNewApplicationInstanceToViewFactory::class,
            \Twid\Octane\Listeners\FlushDatabaseRecordModificationState::class,
            \Twid\Octane\Listeners\FlushDatabaseQueryLog::class,
            \Twid\Octane\Listeners\RefreshQueryDurationHandling::class,
            \Twid\Octane\Listeners\FlushLogContext::class,
            \Twid\Octane\Listeners\FlushArrayCache::class,
            \Twid\Octane\Listeners\FlushMonologState::class,
            \Twid\Octane\Listeners\FlushStrCache::class,
            \Twid\Octane\Listeners\FlushTranslatorCache::class,

            // First-Party Packages...
            \Twid\Octane\Listeners\PrepareInertiaForNextOperation::class,
            \Twid\Octane\Listeners\PrepareLivewireForNextOperation::class,
            \Twid\Octane\Listeners\PrepareScoutForNextOperation::class,
            \Twid\Octane\Listeners\PrepareSocialiteForNextOperation::class,
        ];
    }

    /**
     * Get the container bindings / services that should be pre-resolved by default.
     *
     * @return array
     */
    public static function defaultServicesToWarm(): array
    {
        return [
            'auth',
            'cache',
            'cache.store',
            'config',
            'cookie',
            'db',
            'db.factory',
            'db.transactions',
            'encrypter',
            'files',
            'hash',
            'log',
            'router',
            'routes',
            'session',
            'session.store',
            'translator',
            'url',
            'view',
        ];
    }
}
