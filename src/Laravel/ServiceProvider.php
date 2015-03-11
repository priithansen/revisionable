<?php namespace Sofa\Revisionable\Laravel;

use Illuminate\Support\ServiceProvider as BaseProvider;
use ReflectionClass;

/**
 * @method void publishes(array $paths, $group = null)
 */
class ServiceProvider extends BaseProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->guessPackagePath() . '/config/config.php' => config_path('sofa_revisionable.php'),
            $this->guessPackagePath() . '/migrations/' => base_path('/database/migrations'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindLogger();

        $this->bindUserProvider();

        $this->bindListener();

        $this->bootModel();
    }

    /**
     * Bind Revisionable logger implementation to the IoC.
     *
     * @return void
     */
    protected function bindLogger()
    {
        $this->app->bindShared('revisionable.logger', function ($app) {
            return new \Sofa\Revisionable\Laravel\DbLogger($app['db']->connection());
        });
    }

    /**
     * Bind user provider implementation to the IoC.
     *
     * @return void
     */
    protected function bindUserProvider()
    {
        $userProvider = $this->app['config']->get('sofa_revisionable.userprovider');

        switch ($userProvider) {
            case 'sentry':
                $this->bindSentryProvider();
                break;

            case 'tymon.jwt.auth':
                $this->bindJWTAuthProvider();
                break;

            default:
                $this->bindGuardProvider();
        }
    }

    /**
     * Bind adapter for Sentry to the IoC.
     *
     * @return void
     */
    protected function bindSentryProvider()
    {
        $this->app->bindShared('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\Sentry($app['sentry'], $field);
        });
    }

    /**
     * Bind adapter for Illuminate Guard to the IoC.
     *
     * @return void
     */
    protected function bindGuardProvider()
    {
        $this->app->bindShared('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\Guard($app['auth']->driver(), $field);
        });
    }

    /**
     * Bind adapter for JWTAuth for the IoC.
     *
     * @return void
     */
    protected function bindJWTAuthProvider()
    {
        $this->app->bindShared('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('sofa_revisionable.userfield');

            return new \Sofa\Revisionable\Adapters\JWTAuth($app['tymon.jwt.auth'], $field);
        });
    }

    /**
     * Bind listener implementation to the Ioc.
     *
     * @return void
     */
    protected function bindListener()
    {
        $this->app->bind('Sofa\Revisionable\Listener', function ($app) {
            return new \Sofa\Revisionable\Laravel\Listener($app['revisionable.userprovider']);
        });
    }

    /**
     * Boot the Revision model.
     *
     * @return void
     */
    protected function bootModel()
    {
        $table = $this->app['config']->get('sofa_revisionable.table', 'revisions');

        forward_static_call_array(['\Sofa\Revisionable\Laravel\Revision', 'setCustomTable'], [$table]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'revisionable.logger',
            'revisionable.userprovider',
        ];
    }

    /**
     * Guess real path of the package.
     *
     * @return string
     */
    public function guessPackagePath()
    {
        $path = (new ReflectionClass($this))->getFileName();

        return realpath(dirname($path).'/../');
    }
}
