<?php namespace Ghobaty\FormThrottler;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Events\Dispatcher;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FormThrottler
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Illuminate\Events\Dispatcher
     */
    private $events;

    /**
     * @var \Illuminate\Translation\Translator
     */
    private $translator;

    /**
     * @var \Illuminate\Cache\RateLimiter
     */
    private $limiter;

    /**
     * The maximum number of attempts to allow.
     *
     * @var int
     */
    private $maxAttempts = 5;

    /**
     * The number of minutes to throttle for.
     *
     * @var int
     */
    private $decayMinutes = 1;

    /**
     * The name of the input field associated with the lock out response.
     *
     * @var string
     */
    private $validationField = 'throttle';

    /**
     * @param array                              $config
     * @param \Illuminate\Http\Request           $request
     * @param \Illuminate\Cache\RateLimiter      $limiter
     * @param \Illuminate\Events\Dispatcher      $events
     * @param \Illuminate\Translation\Translator $translator
     */
    public function __construct(
        array $config, Request $request, RateLimiter $limiter,
        Dispatcher $events, Translator $translator
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->limiter = $limiter;
        $this->events = $events;
        $this->translator = $translator;
    }

    /**
     * Set the maximum number of attempts to allow.
     *
     * @param int $value
     * @return $this
     */
    public function maxAttempts(int $value)
    {
        $this->maxAttempts = $value;

        return $this;
    }

    /**
     * Set the number of minutes to throttle for.
     *
     * @param int $value
     * @return $this
     */
    public function decayMinutes(int $value)
    {
        $this->decayMinutes = $value;

        return $this;
    }

    /**
     * Set the validation field with which the error message would be associated.
     *
     * @param string $value
     * @return $this
     */
    public function validationField(string $value)
    {
        $this->validationField = $value;

        return $this;
    }

    /**
     * @param callable $callback
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function throttle(callable $callback)
    {
        if ($this->hasTooManyAttempts()) {
            $this->fireLockoutEvent();

            return $this->sendLockoutResponse();
        }

        try {
            $ret = $callback();
        } catch (Exception $e) {
            if ($this->isThrottledException($e)) {
                $this->incrementAttempts();
            }
            throw $e;
        }

        $this->clearAttempts();

        return $ret;
    }

    /**
     * Get the number of attempts made so far.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->limiter()->attempts(
            $this->key()
        );
    }

    /**
     * @return bool
     */
    protected function hasTooManyAttempts(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return $this->limiter()->tooManyAttempts(
            $this->key(), $this->maxAttempts
        );
    }

    /**
     * Increment the attempts for the user.
     *
     * @return void
     */
    protected function incrementAttempts()
    {
        if ($this->enabled()) {
            $this->limiter()->hit(
                $this->key(), $this->decayMinutes * 60
            );
        }
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendLockoutResponse()
    {
        $seconds = $this->limiter()->availableIn(
            $this->key()
        );

        throw ValidationException::withMessages([
            $this->validationField => [
                sprintf("Too many attempts. Please try again in %d seconds.", $seconds),
            ],
        ])->status($this->responseStatus());
    }

    /**
     * Fire an event when a lockout occurs.
     *
     * @return void
     */
    protected function fireLockoutEvent()
    {
        $this->events->dispatch(
            new Lockout($this->request())
        );
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @return void
     */
    protected function clearAttempts()
    {
        $this->limiter()->clear($this->key());
    }

    /**
     * Get the throttle key for the given request.
     *
     * @return string
     */
    protected function key(): string
    {
        $parts = [
            Str::upper($this->request()->method()),
            $this->request()->getPathInfo(),
            $this->request()->ip(),
        ];

        return implode('|', $parts);
    }

    /**
     * Whether or not the throttling functionality is enabled.
     *
     * @return bool
     */
    protected function enabled(): bool
    {
        return true;
    }

    /**
     * Get the request instance.
     *
     * @return \Illuminate\Http\Request
     */
    protected function request(): Request
    {
        return $this->request;
    }

    /**
     * Get the rate limiter instance.
     *
     * @return \Illuminate\Cache\RateLimiter
     */
    protected function limiter(): RateLimiter
    {
        return $this->limiter;
    }

    /**
     * @param \Exception $e
     * @return bool
     */
    protected function isThrottledException(Exception $e): bool
    {
        foreach ($this->throttledExceptions() as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    protected function throttledExceptions(): array
    {
        return $this->config['exceptions'] ?? [];
    }

    /**
     * @return int
     */
    protected function responseStatus(): int
    {
        return $this->config['response-status'] ?? Response::HTTP_LOCKED;
    }
}
