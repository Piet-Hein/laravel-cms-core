<?php
namespace Czim\CmsCore\Exceptions;

use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class Handler
 *
 * This CMS handler extends the base laravel handler, so it does not
 * use the app's logic.
 */
class Handler extends ExceptionHandler
{

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Create a new exception handler instance.
     *
     * @param LoggerInterface $log
     */
    public function __construct(LoggerInterface $log)
    {
        parent::__construct($log);

        $this->mergeConfiguredDontReportExceptions();
    }

    /**
     * Merges CMS-configured exception classes not to report
     */
    protected function mergeConfiguredDontReportExceptions()
    {
        $this->dontReport = array_merge(
            $this->dontReport,
            config('cms-core.exceptions.dont-report', [])
        );
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldReport($e)) {
            cms()->log($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($core = $this->getCoreIfBound()) {

            if ($core->bootChecker()->isCmsWebRequest()) {
                return $this->renderCmsWebException($request, $e);
            } elseif ($core->bootChecker()->isCmsApiRequest()) {
                return $this->renderCmsApiException($request, $e);
            }
        }

        return parent::render($request, $e);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Exception                $e
     * @return mixed
     */
    protected function renderCmsWebException($request, Exception $e)
    {
        return parent::render($request, $e);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Exception                $e
     * @return mixed
     */
    protected function renderCmsApiException($request, Exception $e)
    {
        $core = $this->getCoreIfBound();

        return $core->api()->error($e);
    }

    /**
     * @return CoreInterface|null
     */
    protected function getCoreIfBound()
    {
        if ( ! app()->bound(Component::CORE)) {
            return null;
        }

        return app(Component::CORE);
    }
}
